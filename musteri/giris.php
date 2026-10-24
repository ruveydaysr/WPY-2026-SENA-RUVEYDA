<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="/teknik-servis/index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav aria-label="Ana menü">
            <a href="/teknik-servis/index.php">Ana Sayfa</a>
            <a href="/teknik-servis/musteri/giris.php"
               aria-current="page">Giriş Yap</a>
            <a href="kayit.php">Kayıt Ol</a>
        </nav>
    </div>
</header>

<main>
    <section class="form-alani">
        <h1>Giriş Yap</h1>

        <p class="aciklama">
            Servis işlemlerinize ulaşmak için hesabınıza giriş yapın.
        </p>

        <form action="/teknik-servis/giris-islem.php" method="POST">
            <label for="email">E-posta</label>
            <input
                type="email"
                id="email"
                name="email"
                autocomplete="username"
                maxlength="100"
                required
            >

            <label for="sifre">Şifre</label>
            <input
                type="password"
                id="sifre"
                name="sifre"
                autocomplete="current-password"
                required
            >

            <button type="submit">Giriş Yap</button>
        </form>

        <p class="form-alt">
            Hesabınız yok mu?
            <a href="kayit.php">Kayıt Ol</a>
        </p>
    </section>
</main>

</body>
</html>