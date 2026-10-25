<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav aria-label="Ana menü">
            <a href="index.php">Ana Sayfa</a>
            <a href="giris.php">Giriş Yap</a>
            <a href="kayit.php" aria-current="page">Kayıt Ol</a>
        </nav>
    </div>
</header>

<main>
    <section class="form-alani">
        <h1>Kayıt Ol</h1>

        <p class="aciklama">
            Servis talebi oluşturmak ve cihazınızı takip etmek için kayıt olun.
        </p>

        <form action="kayit-islem.php" method="POST">
            <label for="adsoyad">Ad Soyad</label>
            <input
                type="text"
                id="adsoyad"
                name="adsoyad"
                autocomplete="name"
                maxlength="100"
                required
            >

            <label for="email">E-posta</label>
            <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                maxlength="100"
                required
            >

            <label for="telefon">Telefon</label>
            <input
                type="tel"
                id="telefon"
                name="telefon"
                autocomplete="tel"
                placeholder="05XXXXXXXXX"
                pattern="05[0-9]{9}"
                maxlength="11"
                title="05 ile başlayan 11 rakam girin."
                required
            >

            <label for="sifre">Şifre</label>
            <input
                type="password"
                id="sifre"
                name="sifre"
                autocomplete="new-password"
                minlength="8"
                placeholder="En az 8 karakter"
                required
            >

            <button type="submit">Kayıt Ol</button>
        </form>

        <p class="form-alt">
            Zaten hesabınız var mı?
            <a href="giris.php">Giriş Yap</a>
        </p>
    </section>
</main>

</body>
</html>