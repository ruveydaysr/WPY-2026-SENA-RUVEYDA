<?php
session_start();

if (!isset($_SESSION["kullanici_id"])) {
    header("Location: /teknik-servis/musteri/giris.php");
    exit();
}

if (($_SESSION["rol"] ?? "") !== "mudur") {
    http_response_code(403);
    exit("Bu sayfaya erişim yetkiniz yok.");
}

require_once(__DIR__ . "/../baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit("Geçersiz servis numarası.");
}

$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT s.*, k.adsoyad, k.email, k.telefon,
            t.adsoyad AS teknisyen_adi
     FROM servisler s
     INNER JOIN kullanicilar k ON s.kullanici_id = k.id
     LEFT JOIN kullanicilar t ON s.teknisyen_id = t.id
     WHERE s.id = ?"
);
mysqli_stmt_bind_param($sorgu, "i", $id);
mysqli_stmt_execute($sorgu);
$sonuc = mysqli_stmt_get_result($sorgu);
$servis = mysqli_fetch_assoc($sonuc);
mysqli_stmt_close($sorgu);

if (!$servis) {
    http_response_code(404);
    exit("Servis kaydı bulunamadı.");
}

$teknisyenler = mysqli_query(
    $baglanti,
    "SELECT id, adsoyad FROM kullanicilar
     WHERE rol IN ('teknisyen', 'teknik')
       AND durum = 'aktif'
     ORDER BY adsoyad"
);

if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

function temizle($deger) {
    return htmlspecialchars((string) ($deger ?? ""), ENT_QUOTES, "UTF-8");
}

function bilgi($deger) {
    return trim((string) ($deger ?? "")) === ""
        ? "Henüz girilmedi"
        : temizle($deger);
}

$parca = (float) ($servis["parca_ucreti"] ?? 0);
$iscilik = (float) ($servis["iscilik_ucreti"] ?? 0);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Detayı | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="/teknik-servis/index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav aria-label="Müdürlük menüsü">
            <a href="/teknik-servis/mudurluk/mudur-panel.php">Panelim</a>
            <a href="/teknik-servis/mudurluk/servis-yonetimi.php">
                Servis Yönetimi
            </a>
            <a href="/teknik-servis/musteri/cikis.php"
               onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                Çıkış Yap
            </a>
        </nav>
    </div>
</header>

<main>
    <h1>Servis Detayı #<?= (int) $servis["id"] ?></h1>
    <p class="aciklama">
        Müşteri bilgilerini, servis işlemlerini ve teknisyen atamasını görüntüleyin.
    </p>

    <div class="panel-kartlari">
        <section class="panel-kart">
            <h2>Müşteri Bilgileri</h2>
            <p><strong>Ad Soyad:</strong> <?= temizle($servis["adsoyad"]) ?></p>
            <p><strong>E-posta:</strong> <?= temizle($servis["email"]) ?></p>
            <p><strong>Telefon:</strong> <?= temizle($servis["telefon"]) ?></p>
        </section>

        <section class="panel-kart">
            <h2>Cihaz ve Servis</h2>
            <p><strong>Cihaz:</strong> <?= temizle($servis["cihaz"]) ?></p>
            <p><strong>Marka:</strong> <?= temizle($servis["marka"]) ?></p>
            <p><strong>Model:</strong> <?= temizle($servis["model"]) ?></p>
            <p><strong>Arıza:</strong> <?= temizle($servis["ariza"]) ?></p>
            <p><strong>Durum:</strong> <?= temizle($servis["durum"]) ?></p>
            <p><strong>Tarih:</strong> <?= temizle($servis["tarih"]) ?></p>
        </section>
    </div>

    <section class="kart alt-cagri">
        <h2>Teknisyen İşlemleri</h2>

        <p>
            <strong>Atanan Teknisyen:</strong>
            <?= temizle($servis["teknisyen_adi"] ?? "Henüz atanmadı") ?>
        </p>

        <p>
            <strong>Arıza Tespiti:</strong>
            <?= bilgi($servis["ariza_tespiti"] ?? null) ?>
        </p>

        <p>
            <strong>Yapılan İşlem:</strong>
            <?= bilgi($servis["yapilan_islem"] ?? null) ?>
        </p>

        <p>
            <strong>Kullanılan Parça:</strong>
            <?= bilgi($servis["kullanilan_parca"] ?? null) ?>
        </p>

        <p>
            <strong>Parça Ücreti:</strong>
            <?= number_format($parca, 2, ",", ".") ?> TL
        </p>

        <p>
            <strong>İşçilik Ücreti:</strong>
            <?= number_format($iscilik, 2, ",", ".") ?> TL
        </p>

        <p>
            <strong>Toplam Ücret:</strong>
            <?= number_format($parca + $iscilik, 2, ",", ".") ?> TL
        </p>
    </section>

    <section class="kart alt-cagri">
        <h2>Teknisyen Atama</h2>

        <?php if (mysqli_num_rows($teknisyenler) === 0): ?>
            <p>Atama yapılabilecek aktif teknisyen bulunmuyor.</p>
        <?php else: ?>
            <form action="/teknik-servis/mudurluk/teknisyen-ata.php"
                  method="POST"
                  onsubmit="return confirm('Teknisyen atamasını kaydetmek istiyor musunuz?')">

                <input type="hidden" name="csrf"
                       value="<?= temizle($_SESSION["csrf"]) ?>">

                <input type="hidden" name="servis_id"
                       value="<?= (int) $servis["id"] ?>">

                <label for="teknisyen_id">Teknisyen Seç</label>
                <select name="teknisyen_id" id="teknisyen_id" required>
                    <option value="">Teknisyen seçiniz</option>

                    <?php while ($teknisyen = mysqli_fetch_assoc($teknisyenler)): ?>
                        <option value="<?= (int) $teknisyen["id"] ?>"
                            <?= (int) ($servis["teknisyen_id"] ?? 0) ===
                                (int) $teknisyen["id"] ? "selected" : "" ?>>
                            <?= temizle($teknisyen["adsoyad"]) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <div class="butonlar">
                    <button type="submit">Atamayı Kaydet</button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <div class="butonlar">
        <a href="/teknik-servis/mudurluk/servis-yonetimi.php"
           class="buton ikincil">
            Servis Yönetimine Dön
        </a>
    </div>
</main>

</body>
</html>