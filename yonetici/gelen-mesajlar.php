
<?php
session_start();

if (
    !isset($_SESSION["kullanici_id"]) ||
    ($_SESSION["rol"] ?? "") !== "yonetici"
) {
    header("Location: ../giris.php");
    exit();
}

require_once("../baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");

function guvenli($deger) {
    return htmlspecialchars(
        (string) $deger,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Güvenli form işlemleri için token oluştur
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Mesaj durumunu güncelle
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    $mesaj_id = filter_input(
        INPUT_POST,
        "mesaj_id",
        FILTER_VALIDATE_INT
    );

    $yeni_durum = $_POST["durum"] ?? "";

    $izinli_durumlar = [
        "Yeni",
        "Okundu",
        "Yanıtlandı"
    ];

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("İşlem doğrulanamadı.");
    }

    if (
        !$mesaj_id ||
        $mesaj_id < 1 ||
        !is_string($yeni_durum) ||
        !in_array($yeni_durum, $izinli_durumlar, true)
    ) {
        http_response_code(400);
        exit("Geçersiz mesaj veya durum bilgisi.");
    }

    $stmt = mysqli_prepare(
        $baglanti,
        "UPDATE iletisim_mesajlari
         SET durum = ?
         WHERE id = ?"
    );

    if (!$stmt) {
        http_response_code(500);
        exit("Güncelleme hazırlanamadı.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "si",
        $yeni_durum,
        $mesaj_id
    );

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        exit("Mesaj durumu güncellenemedi.");
    }

    mysqli_stmt_close($stmt);

    header("Location: gelen-mesajlar.php");
    exit();
}

$sql = "SELECT id, adsoyad, email, konu, mesaj, durum, tarih
        FROM iletisim_mesajlari
        ORDER BY tarih DESC, id DESC";

$sonuc = mysqli_query($baglanti, $sql);

if (!$sonuc) {
    http_response_code(500);
    exit("Mesajlar yüklenirken bir hata oluştu.");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gelen Mesajlar | TeknoServis</title>
        
<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #f4f7ff;
        color: #17233d;
    }

    .ust-alan {
        background: #ffffff;
        border-bottom: 1px solid #e0e6f0;
        padding: 24px 8%;
    }

    .ust-alan h1 {
        margin: 0;
        font-size: 26px;
        color: #17233d;
    }

    .ust-alan p {
        color: #64748b;
        margin-bottom: 0;
    }

    .icerik {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .geri-link {
        display: inline-block;
        margin-bottom: 25px;
        color: #365de5;
        text-decoration: none;
        font-weight: bold;
    }

    .geri-link:hover {
        text-decoration: underline;
    }

    .mesaj-kart {
        background: #ffffff;
        padding: 28px;
        margin-bottom: 20px;
        border-radius: 14px;
        border: 1px solid #dce4f5;
        box-shadow: 0 4px 15px rgba(20, 40, 80, 0.04);
    }

    .mesaj-ust {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .mesaj-kart h3 {
        margin: 0 0 12px;
        font-size: 20px;
    }

    .mesaj-bilgi {
        color: #64748b;
        font-size: 14px;
    }

    .mesaj-metni {
        background: #f5f7ff;
        border-radius: 10px;
        padding: 18px;
        line-height: 1.7;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        margin-top: 20px;
    }

    .durum {
        background: #e8efff;
        color: #365de5;
        padding: 8px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: bold;
    }

    .bos-mesaj {
        background: white;
        padding: 30px;
        border-radius: 12px;
        text-align: center;
    }
    
/* Mesaj durum butonları */
.mesaj-durum-formu {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.durum-buton {
    padding: 11px 18px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
}

.okundu-buton {
    background: #e8efff;
    color: #365de5;
}

.okundu-buton:hover {
    background: #d6e2ff;
}

.yanitlandi-buton {
    background: #dcfce7;
    color: #166534;
}

.yanitlandi-buton:hover {
    background: #bbf7d0;
}

</style>

</head>

<body>

    <header class="ust-alan">
        <h1>Gelen Mesajlar</h1>
        <p>TeknoServis İletişim ve Destek</p>
    </header>

    <main class="icerik">

        <a href="yonetici-panel.php" class="geri-link">
            ← Yönetici Paneline Dön
        </a>

        <?php if (mysqli_num_rows($sonuc) === 0): ?>

            <div class="bos-mesaj">
                Henüz gönderilmiş bir mesaj bulunmuyor.
            </div>

        <?php else: ?>

            <?php while ($mesaj = mysqli_fetch_assoc($sonuc)): ?>

                <div class="mesaj-kart">

                    <div class="mesaj-ust">
                        <h3><?= guvenli($mesaj["konu"]) ?></h3>

                        <span class="durum">
                            <?= guvenli($mesaj["durum"]) ?>
                        </span>
                    </div>

                    <p class="mesaj-bilgi">
                        <strong>Gönderen:</strong>
                        <?= guvenli($mesaj["adsoyad"]) ?>
                    </p>

                    <p class="mesaj-bilgi">
                        <strong>E-posta:</strong>
                        <?= guvenli($mesaj["email"]) ?>
                    </p>

                    <p class="mesaj-bilgi">
                        <strong>Tarih:</strong>
                        <?= guvenli($mesaj["tarih"]) ?>
                    </p>

                    <div class="mesaj-metni"><?= guvenli($mesaj["mesaj"]) ?></div>

<!-- Mesaj Durumu İşlemleri -->
<form method="POST" class="mesaj-durum-formu">

    <input type="hidden"
           name="csrf_token"
           value="<?= guvenli($_SESSION["csrf_token"]) ?>">

    <input type="hidden"
           name="mesaj_id"
           value="<?= (int)$mesaj["id"] ?>">

    <button type="submit"
            name="durum"
            value="Okundu"
            class="durum-buton okundu-buton">
        ✓ Okundu
    </button>

    <button type="submit"
            name="durum"
            value="Yanıtlandı"
            class="durum-buton yanitlandi-buton">
        ✓ Yanıtlandı
    </button>

</form>

                </div>

            <?php endwhile; ?>

        <?php endif; ?>

    </main>

</body>
</html>
