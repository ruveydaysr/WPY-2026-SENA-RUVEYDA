<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TeknoServis | Ana Sayfa</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=3">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="index.php" class="logo">Tekno<span>Servis</span></a>
        <nav aria-label="Ana menü">
            <a href="index.php" aria-current="page">Ana Sayfa</a>
            <a href="musteri/giris.php">Giriş Yap</a>
            <a href="musteri/kayit.php">Kayıt Ol</a>
        </nav>
    </div>
</header>

<main>
    <section class="tanitim">
        <div>
            <h1>Cihazınızın servis süreci <span>kontrolünüz altında.</span></h1>
            <p class="aciklama">
                Servis talebinizi oluşturun, cihazınızın durumunu
                takip edin ve kayıtlarınıza tek bir panelden ulaşın.
            </p>
            <div class="butonlar">
                <a href="giris.php" class="buton">Servis Talebi Oluştur</a>
                <a href="giris.php" class="buton ikincil">Servisimi Takip Et</a>
            </div>
        </div>

        <div class="kart">
            <h2>Başvurudan teslimata</h2>
            <p class="aciklama">Servis sürecini hesabınızdan izleyin.</p>
            <ol class="adimlar">
                <li>Servis talebinizi oluşturun.</li>
                <li>Teknisyen incelemesi başlasın.</li>
                <li>Servis durumunu takip edin.</li>
                <li>İşlemi tamamlanan cihazınızı teslim alın.</li>
            </ol>
        </div>
    </section>
    <section>
    <h2>Servis işlemleriniz için tek adres</h2>
    <p class="aciklama">
        Hesabınızı oluşturun ve servis sürecinizi takip edin.
    </p>

    <div class="panel-kartlari">
        <article class="panel-kart">
            <h3>Talebinizi oluşturun</h3>
            <p>Cihaz bilgilerini girerek yaşadığınız arızayı açıklayın.</p>
        </article>

        <article class="panel-kart">
            <h3>Durumunu takip edin</h3>
            <p>Servis kaydınızın güncel durumunu panelinizden görüntüleyin.</p>
        </article>

        <article class="panel-kart">
            <h3>Kayıtlarınıza ulaşın</h3>
            <p>Önceki servis taleplerinizi bir arada görüntüleyin.</p>
        </article>
    </div>
</section>

<section class="kart alt-cagri">
    <h2>İlk servis talebinizi oluşturun</h2>
    <p class="aciklama">
        Henüz hesabınız yoksa kayıt olarak başlayabilirsiniz.
    </p>
    <a href="kayit.php" class="buton">Hesap Oluştur</a>
</section>
</main>

<footer>Bilgisayar Teknik Servis Takip ve Yönetim Sistemi</footer>

</body>
</html>