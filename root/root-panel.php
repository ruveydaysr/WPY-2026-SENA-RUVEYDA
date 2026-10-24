<?php
require_once(__DIR__ . "/root-kontrol.php");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Root Yönetim Paneli | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="/teknik-servis/index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav aria-label="Root menüsü">
            <a href="/teknik-servis/root/root-panel.php"
               aria-current="page">Panelim</a>

            <a href="/teknik-servis/root/kullanicilar.php">
                Kullanıcı Yönetimi
            </a>

            <a href="/teknik-servis/root/islem-gecmisi.php">
                İşlem Geçmişi
            </a>

            <a href="/teknik-servis/musteri/cikis.php"
               onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                Çıkış Yap
            </a>
        </nav>
    </div>
</header>

<main>
    <h1>Root Yönetim Paneli</h1>

    <p class="aciklama">
        Kullanıcı hesaplarını yönetebilir ve yapılan
        kullanıcı işlemlerinin geçmişini görüntüleyebilirsiniz.
    </p>

    <div class="panel-kartlari">
        <section class="panel-kart">
            <h2>Kullanıcı Yönetimi</h2>
            <p>
                Sistemde kayıtlı kullanıcıları görüntüleyin
                ve hesap yönetimi işlemlerine ulaşın.
            </p>

            <a href="/teknik-servis/root/kullanicilar.php"
               class="buton">
                Kullanıcıları Gör
            </a>
        </section>

        <section class="panel-kart">
            <h2>İşlem Geçmişi</h2>
            <p>
                Root tarafından gerçekleştirilen kullanıcı
                işlemlerinin kayıtlarını görüntüleyin.
            </p>

            <a href="/teknik-servis/root/islem-gecmisi.php"
               class="buton">
                İşlem Geçmişini Gör
            </a>
        </section>
    </div>
</main>

<footer>
    Bilgisayar Teknik Servis Takip ve Yönetim Sistemi
</footer>

</body>
</html>