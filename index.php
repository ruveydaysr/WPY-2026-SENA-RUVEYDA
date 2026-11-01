
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TeknoServis | Ana Sayfa</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="index.php" class="logo">Tekno<span>Servis</span></a>
        
<nav aria-label="Ana menü">
    <a href="index.php" aria-current="page">Ana Sayfa</a>
    
<div class="acilir-menu">
    <a href="#hakkimizda">Kurumsal ▾</a>

    <div class="alt-menu">
        <a href="#hakkimizda">Hakkımızda</a>
        <a href="#bolgelerimiz">Bölgelerimiz</a>
        <a href="#iletisim">İletişim</a>
    </div>
</div>

    
<div class="acilir-menu">
    <a href="#hizmetlerimiz">Hizmetlerimiz ▾</a>

    <div class="alt-menu">
        <a href="#laptop-tamiri">Laptop Tamiri</a>
        <a href="#masaustu-tamiri">Masaüstü Bilgisayar Tamiri</a>
        <a href="#donanim-degisimi">Donanım Değişimi</a>
        <a href="#bakim-temizlik">Bakım ve Temizlik</a>
        <a href="#virus-temizleme">Virüs Temizleme</a>
        <a href="#yazilim-kurulumu">Yazılım Kurulumu</a>
    </div>
</div>

    <a href="giris.php">Giriş Yap</a>
    <a href="kayit.php">Kayıt Ol</a>
</nav>

    </div>
</header>

<main>
    <section class="tanitim">
        <div>
            <h1>Bilgisayarınız için <span>güvenilir teknik servis.</span></h1>
            <p class="aciklama">
    Bilgisayarınızın bakım, onarım ve arıza tespit
    işlemleri için TeknoServis yanınızda.
    Cihazınızı teknik servise ulaştırın,
    servis talebinizi oluşturun ve onarım sürecini
    online olarak takip edin.
</p>
            <div class="butonlar">
                <a href="giris.php" class="buton">Servis Talebi Oluştur</a>
                <a href="giris.php" class="buton ikincil">Servisimi Takip Et</a>
            </div>
        </div>

<div class="tanitim-gorsel">
    <div class="tanitim-gorsel-ikon">💻</div>
    <h2>TeknoServis ile kolay teknik servis</h2>
    <p>
        Cihazınızın bakım ve onarım sürecini
        tek bir yerden yönetin.
    </p>

    <div class="tanitim-avantajlar">
        <span>✓ Online Servis Talebi</span>
        <span>✓ Anlık Durum Takibi</span>
        <span>✓ Güvenli Ücret Onayı</span>
    </div>
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

<!-- Hakkımızda Bölümü -->
<section id="hakkimizda" class="hakkimizda-bolumu">

    <div class="hakkimizda-icerik">
        <span class="bolum-etiketi">BİZ KİMİZ?</span>

        <h2>Teknolojiniz için güvenilir çözüm ortağınız</h2>

        <p>
            TeknoServis, bilgisayar ve teknolojik cihazların
            bakım, onarım ve teknik destek süreçlerini
            kolaylaştırmak amacıyla oluşturulmuş bir
            teknik servis takip ve yönetim sistemidir.
        </p>

        <p>
            Müşterilerimiz servis taleplerini çevrim içi
            oluşturabilir, cihazlarının güncel durumunu
            takip edebilir ve yapılan işlemleri
            hesaplarından görüntüleyebilir.
        </p>

        <div class="hakkimizda-ozellikler">
            <div>✓ Kolay servis başvurusu</div>
            <div>✓ Şeffaf işlem takibi</div>
            <div>✓ Düzenli servis kayıtları</div>
            <div>✓ Müşteri odaklı hizmet</div>
        </div>
    </div>

    <div class="hakkimizda-gorsel">
        <div class="gorsel-simge">💻</div>
        <h3>TeknoServis</h3>
        <p>Teknik Servis Takip ve Yönetim Sistemi</p>
    </div>

</section>

<!-- Hizmetlerimiz -->
<section id="hizmetlerimiz" class="hizmetler-bolumu">
    <div class="bolum-baslik">
        <span class="bolum-etiketi">NELER YAPIYORUZ?</span>
        <h2>Hizmetlerimiz</h2>
        <p>Bilgisayarlarınız için teknik destek ve bakım çözümleri.</p>
    </div>

    <div class="hizmet-kartlari">
        <article id="laptop-tamiri" class="hizmet-kart">
            <div class="hizmet-ikon">💻</div>
            <h3>Laptop Tamiri</h3>
            <p>Laptop arızalarının tespiti ve onarım işlemleri.</p>
        </article>

    	<article id="masaustu-tamiri" class="hizmet-kart">
            <div class="hizmet-ikon">🖥️</div>
            <h3>Masaüstü Bilgisayar Tamiri</h3>
            <p>Masaüstü bilgisayarların donanım ve sistem sorunları.</p>
        </article>

        <article id="donanim-degisimi" class="hizmet-kart">
            <div class="hizmet-ikon">⚙️</div>
            <h3>Donanım Değişimi</h3>
            <p>RAM, SSD ve diğer bilgisayar parçalarının değişimi.</p>
        </article>

        <article id="bakim-temizlik" class="hizmet-kart">
            <div class="hizmet-ikon">🛠️</div>
            <h3>Bakım ve Temizlik</h3>
            <p>Fan temizliği, termal macun ve genel bakım işlemleri.</p>
        </article>

        <article id="virus-temizleme" class="hizmet-kart">
            <div class="hizmet-ikon">🛡️</div>
            <h3>Virüs Temizleme</h3>
            <p>Zararlı yazılımların temizlenmesi ve sistem kontrolü.</p>
        </article>

        <article id="yazilim-kurulumu" class="hizmet-kart">
            <div class="hizmet-ikon">📦</div>
            <h3>Yazılım Kurulumu</h3>
            <p>İşletim sistemi, sürücü ve gerekli programların kurulumu.</p>
        </article>
    </div>
</section>

<!-- Bölgelerimiz -->
<section id="bolgelerimiz" class="bolgeler-bolumu">
    <div class="bolum-baslik">
        <span class="bolum-etiketi">HİZMET AĞIMIZ</span>
        <h2>Türkiye Genelinde Teknik Servis</h2>
        <p>
            TeknoServis ile bulunduğunuz ili seçerek
            teknik servis talebinizi oluşturabilirsiniz.
            Servis talepleri, ilgili ilde görev yapan
            uygun teknisyenlere yönlendirilir.
        </p>
    </div>

    <div class="panel-kartlari">
        <article class="panel-kart">
            <h3>📍 İl Bazlı Hizmet</h3>
            <p>Servis talebinizi bulunduğunuz ile göre oluşturun.</p>
        </article>

        <article class="panel-kart">
            <h3>🔧 Teknik Destek</h3>
            <p>Uygun teknisyenler tarafından arıza tespiti ve onarım işlemleri gerçekleştirilir.</p>
        </article>

        <article class="panel-kart">
            <h3>💻 Online Takip</h3>
            <p>Cihazınızı servise teslim ettikten sonra işlem sürecini hesabınızdan takip edin.</p>
        </article>
    </div>
</section>

<!-- İletişim Bölümü -->

<!-- İletişim Bölümü -->
<section id="iletisim" class="iletisim-bolumu">

    <div class="bolum-baslik">
        <span class="bolum-etiketi">BİZE ULAŞIN</span>
        <h2>İletişim ve Destek</h2>
        <p>
            Sorularınız, önerileriniz ve teknik servis
            talepleriniz hakkında bizimle iletişime geçin.
        </p>
    </div>

    <div class="iletisim-yeni">

        <div class="iletisim-bilgi">
            <h3>TeknoServis Destek</h3>
            <p>
                Bilgisayar bakım ve onarım hizmetlerimiz
                hakkında bilgi almak için bize mesaj gönderebilirsiniz.
            </p>

            <h4>📍 Hizmet Bölgelerimiz</h4>
            <p>İl bazlı teknik servis hizmeti</p>

            <h4>🔧 Teknik Servis</h4>
            <p>Bakım, onarım ve arıza tespiti</p>

            <h4>💻 Online İşlemler</h4>
            <p>Servis başvurusu ve durum takibi</p>
        </div>

        <div class="iletisim-form-kutu">
            <h3>Bize Mesaj Gönderin</h3>

<?php if (isset($_SESSION["iletisim_basarili"])): ?>
    <p style="color: green; font-weight: bold;">
        <?= htmlspecialchars($_SESSION["iletisim_basarili"]) ?>
    </p>
    <?php unset($_SESSION["iletisim_basarili"]); ?>
<?php endif; ?>

<?php if (isset($_SESSION["iletisim_hata"])): ?>
    <p style="color: red; font-weight: bold;">
        <?= htmlspecialchars($_SESSION["iletisim_hata"]) ?>
    </p>
    <?php unset($_SESSION["iletisim_hata"]); ?>
<?php endif; ?>

            <form action="iletisim-kaydet.php" method="POST">

                <label for="iletisim-ad">Ad Soyad</label>
                <input type="text" id="iletisim-ad"
                       name="adsoyad" maxlength="100" required>

                <label for="iletisim-email">E-posta</label>
                <input type="email" id="iletisim-email"
                       name="email" maxlength="150" required>

                <label for="iletisim-konu">Konu</label>
                <input type="text" id="iletisim-konu"
                       name="konu" maxlength="200" required>

                <label for="iletisim-mesaj">Mesajınız</label>
                <textarea id="iletisim-mesaj" name="mesaj"
                          rows="5" maxlength="5000" required></textarea>

                <button type="submit">Mesaj Gönder</button>

            </form>
        </div>

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


<footer class="site-footer">
    <div class="footer-icerik">

        <div class="footer-kolon">
            <h3>Tekno<span>Servis</span></h3>
            <p>
                Bilgisayar bakım, onarım ve teknik servis
                süreçlerini kolaylaştıran yönetim sistemi.
            </p>
        </div>

        <div class="footer-kolon">
            <h3>Kurumsal</h3>
            <a href="index.php">Ana Sayfa</a>
            
            <a href="#hizmetlerimiz">Hizmetlerimiz</a>
            <a href="#bolgelerimiz">Bölgelerimiz</a>
            <a href="#iletisim">İletişim</a>
        </div>

        <div class="footer-kolon">
            <h3>Hizmetlerimiz</h3>
            <a href="#laptop-tamiri">Laptop Tamiri</a>
            <a href="#masaustu-tamiri">Masaüstü Bilgisayar Tamiri</a>
            <a href="#donanim-degisimi">Donanım Değişimi</a>
            <a href="#bakim-temizlik">Bakım ve Temizlik</a>
            <a href="#virus-temizleme">Virüs Temizleme</a>
            <a href="#yazilim-kurulumu">Yazılım Kurulumu</a>
        </div>

        <div class="footer-kolon">
            <h3>Hızlı İşlemler</h3>
            <a href="kayit.php">Kayıt Ol</a>
            <a href="giris.php">Giriş Yap</a>
            <a href="giris.php">Servis Talebi Oluştur</a>
            <a href="giris.php">Servisimi Takip Et</a>
        </div>

    </div>

    <div class="footer-alt">
        <p>© 2026 TeknoServis | Bilgisayar Teknik Servis Takip ve Yönetim Sistemi</p>
    </div>
</footer>

</body>
</html>