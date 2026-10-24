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

// Giriş yapan müdürün adı
$id = (int) $_SESSION["kullanici_id"];

$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT adsoyad FROM kullanicilar WHERE id = ?"
);
mysqli_stmt_bind_param($sorgu, "i", $id);
mysqli_stmt_execute($sorgu);
mysqli_stmt_bind_result($sorgu, $adsoyad);
$bulundu = mysqli_stmt_fetch($sorgu);
mysqli_stmt_close($sorgu);

if (!$bulundu) {
    session_unset();
    session_destroy();
    header("Location: /teknik-servis/musteri/giris.php");
    exit();
}

// Servis istatistikleri
$sonuc = mysqli_query(
    $baglanti,
    "SELECT COUNT(*) AS toplam,
        COALESCE(SUM(durum = 'Bekliyor'), 0) AS bekleyen,
        COALESCE(SUM(durum IN (
            'İnceleniyor', 'İşlemde', 'Parça Bekliyor'
        )), 0) AS devam,
        COALESCE(SUM(durum IN (
            'Tamamlandı', 'Teslim Edildi'
        )), 0) AS tamamlanan
     FROM servisler"
);
$sayilar = mysqli_fetch_assoc($sonuc);

$kartlar = [
    "toplam" => "Toplam Servis",
    "bekleyen" => "Bekleyen Servis",
    "devam" => "Devam Eden Servis",
    "tamamlanan" => "Tamamlanan Servis"
];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müdürlük Paneli | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="/teknik-servis/index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav aria-label="Müdürlük menüsü">
            <a href="/teknik-servis/mudurluk/mudur-panel.php"
               aria-current="page">Panelim</a>

            <a href="/teknik-servis/mudurluk/servis-yonetimi.php">
                Servis Yönetimi
            </a>
<a href="/teknik-servis/mudurluk/kullanici-yonetimi.php">
    Kullanıcı Yönetimi
</a>
            <a href="/teknik-servis/musteri/cikis.php"
               onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                Çıkış Yap
            </a>
        </nav>
    </div>
</header>

<main>
    <h1>Müdürlük Paneli</h1>

    <p class="aciklama">
        Hoş geldiniz,
        <?= htmlspecialchars($adsoyad, ENT_QUOTES, "UTF-8") ?>.
        Servisleri buradan yönetebilirsiniz.
    </p>

    <div class="panel-kartlari">
        <?php foreach ($kartlar as $alan => $baslik): ?>
            <section class="panel-kart">
                <h3><?= $baslik ?></h3>
                <p class="sayac"><?= (int) $sayilar[$alan] ?></p>
            </section>
        <?php endforeach; ?>
    </div>

    <h2>Yönetim İşlemleri</h2>

    <div class="panel-kartlari">
        <section class="panel-kart">
            <h3>Tüm Servisler</h3>
            <p>
                Servis kayıtlarını görüntüleyin,
                müşteri adına veya servis numarasına göre arayın.
            </p>
            <a href="/teknik-servis/mudurluk/servis-yonetimi.php"
               class="buton">
                Servisleri Gör
            </a>
        </section>

        <section class="panel-kart">
            <h3>Bekleyen Servisler</h3>
            <p>
                Bekleyen kayıtları açın, detaylarını inceleyin
                ve uygun teknisyeni atayın.
            </p>
            <a href="/teknik-servis/mudurluk/servis-yonetimi.php?durum=Bekliyor"
               class="buton">
                Bekleyenleri Gör
            </a>
        </section>

        <section class="panel-kart">
            <h3>Tamamlanan Servisler</h3>
            <p>
                İşlemi tamamlanan veya müşteriye
                teslim edilen servisleri görüntüleyin.
            </p>
            <a href="/teknik-servis/mudurluk/servis-yonetimi.php?durum=tamam"
               class="buton">
                Tamamlananları Gör
            </a>
        </section>
    </div>
</main>

<footer>
    Bilgisayar Teknik Servis Takip ve Yönetim Sistemi
</footer>

</body>
</html>