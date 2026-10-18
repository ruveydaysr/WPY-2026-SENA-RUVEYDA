<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teknik Servis Takip Sistemi</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .giris-kartlari {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 20px;
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .giris-karti {
            background: white;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .giris-karti h3 {
            margin-top: 0;
        }

        .giris-karti p {
            color: #555;
            line-height: 1.6;
        }

        .giris-karti a {
            display: inline-block;
            padding: 12px 20px;
            margin-top: 10px;
            background: #2457a7;
            color: white;
            text-decoration: none;
            border-radius: 7px;
        }

        .giris-karti a:hover {
            background: #183e79;
        }
    </style>
</head>
<body>

<header>
    <h1>Bilgisayar Teknik Servis</h1>

    <nav>
        <a href="index.php">Ana Sayfa</a>
        <a href="kayit.php">Müşteri Kayıt</a>
    </nav>
</header>

<main>
    <section class="ana-alan">
        <h2>Teknik Servis Takip Sistemine Hoş Geldiniz</h2>
        <p>Hesabınıza uygun giriş bölümünü seçiniz.</p>
    </section>

    <section class="giris-kartlari" aria-label="Giriş seçenekleri">
        <article class="giris-karti">
            <h3>Müşteri</h3>
            <p>Servis talebi oluşturun ve cihazınızın durumunu takip edin.</p>
            <a href="giris.php?tur=musteri">Müşteri Girişi</a>
        </article>

        <article class="giris-karti">
            <h3>Teknisyen</h3>
            <p>Size atanan servisleri görüntüleyin ve işlemleri güncelleyin.</p>
            <a href="giris.php?tur=teknisyen">Teknisyen Girişi</a>
        </article>

        <article class="giris-karti">
            <h3>Yönetici</h3>
            <p>Müşteri taleplerini görüntüleyin ve teknisyen atayın.</p>
            <a href="giris.php?tur=yonetici">Yönetici Girişi</a>
        </article>

        <article class="giris-karti">
            <h3>Admin (Root)</h3>
            <p>Sistemin genel yönetim paneline erişin.</p>
            <a href="giris.php?tur=root">Admin Girişi</a>
        </article>
    </section>
</main>

<footer>
    <p>Bilgisayar Teknik Servis Takip ve Yönetim Sistemi</p>
</footer>

</body>
</html>