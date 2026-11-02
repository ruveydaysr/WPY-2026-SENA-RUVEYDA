
<?php
require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["yonetici"]);

mysqli_set_charset($baglanti, "utf8mb4");

// Hesap durumunu değiştirme
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "musteri_id", FILTER_VALIDATE_INT);
    $yeni_durum = $_POST["yeni_durum"] ?? "";
    $token = $_POST["csrf_token"] ?? "";

    if (
        !hash_equals($_SESSION["musteri_csrf"] ?? "", $token) ||
        !$id ||
        !in_array($yeni_durum, ["aktif", "pasif"], true)
    ) {
        http_response_code(400);
        exit("Geçersiz işlem.");
    }

    $sorgu = mysqli_prepare(
        $baglanti,
        "UPDATE kullanicilar
         SET durum = ?
         WHERE id = ? AND rol = 'musteri'"
    );

    mysqli_stmt_bind_param($sorgu, "si", $yeni_durum, $id);
    mysqli_stmt_execute($sorgu);
    $etkilenen = mysqli_stmt_affected_rows($sorgu);
    mysqli_stmt_close($sorgu);

    $_SESSION["musteri_mesaj"] = $etkilenen > 0
        ? "Müşteri hesabının durumu güncellendi."
        : "Hesap durumu değişmedi.";

    header("Location: musteri-yonetimi.php");
    exit();
}

// CSRF güvenlik anahtarı
if (empty($_SESSION["musteri_csrf"])) {
    $_SESSION["musteri_csrf"] = bin2hex(random_bytes(32));
}

// Bilgilendirme mesajı
$mesaj = $_SESSION["musteri_mesaj"] ?? "";
unset($_SESSION["musteri_mesaj"]);

// Müşterileri listeleme
$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT id, adsoyad, email, durum
     FROM kullanicilar
     WHERE rol = 'musteri'
     ORDER BY id DESC"
);

mysqli_stmt_execute($sorgu);
$musteriler = mysqli_stmt_get_result($sorgu);
mysqli_stmt_close($sorgu);

function guvenli($deger) {
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Yönetimi | TeknoServis</title>
    <link rel="stylesheet" href="../style.css?v=2">

    <style>
        .musteri-yonetim {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        .musteri-yonetim h1 {
            margin-bottom: 10px;
        }

        .musteri-tablo {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            background: white;
        }

        .musteri-tablo th,
        .musteri-tablo td {
            padding: 14px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .musteri-tablo th {
            background: #163b70;
            color: white;
        }

        .musteri-tablo form {
            margin: 0;
        }

        .durum-aktif {
            color: green;
            font-weight: bold;
        }

        .durum-pasif {
            color: #c62828;
            font-weight: bold;
        }

        .islem-buton {
            padding: 9px 15px;
            background: #163b70;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .islem-buton:hover {
            background: #285ca0;
        }

        .geri-link {
            display: inline-block;
            margin-top: 20px;
            color: #163b70;
            text-decoration: none;
        }

        .bilgi-mesaji {
            padding: 12px;
            background: #e8f4e8;
            border: 1px solid #b6d9b6;
            margin-top: 15px;
        }

        .tablo-kapsayici {
            overflow-x: auto;
        }
    </style>
</head>

<body>

<main class="musteri-yonetim">

    <h1>👥 Müşteri Yönetimi</h1>
    <p>Kayıtlı müşterilerin hesap durumlarını yönetebilirsiniz.</p>

    <?php if ($mesaj !== ""): ?>
        <div class="bilgi-mesaji">
            <?= guvenli($mesaj) ?>
        </div>
    <?php endif; ?>

    <div class="tablo-kapsayici">
        <table class="musteri-tablo">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Hesap Durumu</th>
                    <th>İşlem</th>
                </tr>
            </thead>

            <tbody>
                <?php if (mysqli_num_rows($musteriler) === 0): ?>
                    <tr>
                        <td colspan="5">Kayıtlı müşteri bulunamadı.</td>
                    </tr>
                <?php endif; ?>

                <?php while ($musteri = mysqli_fetch_assoc($musteriler)): ?>
                    <?php
                        $aktif = $musteri["durum"] === "aktif";
                        $yeni_durum = $aktif ? "pasif" : "aktif";
                    ?>

                    <tr>
                        <td><?= (int)$musteri["id"] ?></td>

                        <td><?= guvenli($musteri["adsoyad"]) ?></td>

                        <td><?= guvenli($musteri["email"]) ?></td>

                        <td>
                            <span class="<?= $aktif ? 'durum-aktif' : 'durum-pasif' ?>">
                                <?= $aktif ? "Aktif" : "Pasif" ?>
                            </span>
                        </td>

                        <td>
                            <form method="POST"
                                  action="musteri-yonetimi.php"
                                  onsubmit="return confirm('Bu müşterinin hesap durumunu değiştirmek istiyor musunuz?');">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= guvenli($_SESSION["musteri_csrf"]) ?>">

                                <input type="hidden"
                                       name="musteri_id"
                                       value="<?= (int)$musteri["id"] ?>">

                                <input type="hidden"
                                       name="yeni_durum"
                                       value="<?= $yeni_durum ?>">

                                <button type="submit" class="islem-buton">
                                    <?= $aktif ? "Pasif Yap" : "Aktif Yap" ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <a href="yonetici-panel.php" class="geri-link">
        ← Yönetici Paneline Dön
    </a>

</main>

</body>
</html>
