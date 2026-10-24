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

// Servis durumlarını tek sorguda sayıyoruz.
$sql = "SELECT
            COUNT(*) AS toplam,
            COALESCE(SUM(durum = 'Bekliyor'), 0) AS bekleyen,
            COALESCE(SUM(durum IN (
                'İnceleniyor', 'İşlemde', 'Parça Bekliyor'
            )), 0) AS devam_eden,
            COALESCE(SUM(durum IN (
                'Tamamlandı', 'Teslim Edildi'
            )), 0) AS tamamlanan
        FROM servisler";

$sonuc = mysqli_query($baglanti, $sql);
$sayilar = mysqli_fetch_assoc($sonuc);
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
               aria-current="page">
                Panelim
            </a>

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
    <h1>Müdürlük Paneli</h1>

    <p class="aciklama">
        Servislerin genel durumunu görüntüleyebilir
        ve servis yönetimi ekranına ulaşabilirsiniz.
    </p>

    <h2>Servis Durumları</h2>

    <div class="panel-kartlari">
        <section class="panel-kart">
            <h3>Toplam Servis</h3>
            <p class="sayac"><?= (int) $sayilar["toplam"] ?></p>
            <p class="aciklama">Sistemdeki bütün servis kayıtları.</p>
        </section>

        <section class="panel-kart">
            <h3>Bekleyen Servis</h3>
            <p class="sayac"><?= (int) $sayilar["bekleyen"] ?></p>
            <p class="aciklama">Durumu “Bekliyor” olan servisler.</p>
        </section>

        <section class="panel-kart">
            <h3>Devam Eden Servis</h3>
            <p class="sayac"><?= (int) $sayilar["devam_eden"] ?></p>
            <p class="aciklama">
                İncelenen, işlemde olan veya parça bekleyen servisler.
            </p>
        </section>

        <section class="panel-kart">
            <h3>Tamamlanan Servis</h3>
            <p class="sayac"><?= (int) $sayilar["tamamlanan"] ?></p>
            <p class="aciklama">
                Tamamlanan veya teslim edilen servisler.
            </p>
        </section>
    </div>

    <section class="kart alt-cagri">
        <h2>Servis Yönetimi</h2>
        <p class="aciklama">
            Servis kayıtlarını görüntülemek ve teknisyen
            atama işlemlerine ulaşmak için devam edin.
        </p>

        <a href="/teknik-servis/mudurluk/servis-yonetimi.php"
           class="buton">
            Servisleri Yönet
        </a>
    </section>
</main>

<footer>
    Bilgisayar Teknik Servis Takip ve Yönetim Sistemi
</footer>

</body>
</html>