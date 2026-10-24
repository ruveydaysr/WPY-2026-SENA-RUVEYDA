<?php
session_start();

if (!isset($_SESSION["kullanici_id"])) {
    header("Location: /teknik-servis/musteri/giris.php");
    exit();
}

if (($_SESSION["rol"] ?? "") !== "musteri") {
    http_response_code(403);
    exit("Bu sayfaya erişiminiz yok.");
}

require_once(__DIR__ . "/../baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");

$id = (int) $_SESSION["kullanici_id"];

// Müşteri bilgileri
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

// Müşterinin servis sayıları
$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT COUNT(*),
            COALESCE(SUM(durum = 'Bekliyor'), 0)
     FROM servisler
     WHERE kullanici_id = ?"
);
mysqli_stmt_bind_param($sorgu, "i", $id);
mysqli_stmt_execute($sorgu);
mysqli_stmt_bind_result($sorgu, $toplam, $bekleyen);
mysqli_stmt_fetch($sorgu);
mysqli_stmt_close($sorgu);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Paneli | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="/teknik-servis/index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav aria-label="Müşteri menüsü">
            <a href="/teknik-servis/musteri/musteri-panel.php"
               aria-current="page">
                Panelim
            </a>

            <a href="/teknik-servis/musteri/servis-olustur.php">
                Servis Oluştur
            </a>

            <a href="/teknik-servis/musteri/servis-takip.php">
                Servis Takip
            </a>

            <a href="/teknik-servis/musteri/profil.php">
                Profilim
            </a>

            <a href="/teknik-servis/musteri/cikis.php"
               onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                Çıkış Yap
            </a>
        </nav>
    </div>
</header>

<main>
    <h1>
        Hoş geldiniz,
        <?= htmlspecialchars($adsoyad, ENT_QUOTES, "UTF-8") ?>
    </h1>

    <p class="aciklama">
        Servis taleplerinizi ve hesap bilgilerinizi buradan yönetebilirsiniz.
    </p>

    <div class="panel-kartlari">
        <section class="panel-kart">
            <h2>Toplam Servis</h2>
            <p class="sayac"><?= (int) $toplam ?></p>
            <p class="aciklama">
                Oluşturduğunuz tüm servis kayıtları.
            </p>
        </section>

        <section class="panel-kart">
            <h2>Bekleyen Servis</h2>
            <p class="sayac"><?= (int) $bekleyen ?></p>
            <p class="aciklama">
                Durumu “Bekliyor” olan kayıtlarınız.
            </p>
        </section>
    </div>

    <h2>İşlemlerim</h2>

    <div class="panel-kartlari">
        <section class="panel-kart">
            <h3>Yeni Servis Talebi</h3>
            <p>
                Cihaz bilgilerinizi ve yaşadığınız arızayı bildirin.
            </p>

            <a href="/teknik-servis/musteri/servis-olustur.php"
               class="buton">
                Servis Oluştur
            </a>
        </section>

        <section class="panel-kart">
            <h3>Servis Kayıtlarım</h3>
            <p>
                Kayıtlarınızı ve güncel servis durumlarını görüntüleyin.
            </p>

            <a href="/teknik-servis/musteri/servis-takip.php"
               class="buton">
                Servisleri Gör
            </a>
        </section>

        <section class="panel-kart">
            <h3>Profil Bilgilerim</h3>
            <p>
                Kişisel bilgilerinizi ve şifrenizi güncelleyin.
            </p>

            <a href="/teknik-servis/musteri/profil.php"
               class="buton">
                Profilime Git
            </a>
        </section>
    </div>
</main>

<footer>
    Bilgisayar Teknik Servis Takip ve Yönetim Sistemi
</footer>

</body>
</html>