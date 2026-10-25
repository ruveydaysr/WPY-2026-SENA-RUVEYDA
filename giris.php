<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>

<body>
<header>
    <div class="ust-menu">
        <a href="index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav>
            <a href="index.php">Ana Sayfa</a>
            <a href="kayit.php">Kayıt Ol</a>
            <a href="giris.php">Giriş Yap</a>
        </nav>
    </div>
</header>

<main>

    <section class="form-alani">

        <h2>Müşteri Girişi</h2>

        <form action="giris-islem.php" method="POST">

            <label for="email">E-posta:</label>
            <input type="email" id="email" name="email" required>

            <label for="sifre">Şifre:</label>
            <input type="password" id="sifre" name="sifre" required>

            <button type="submit">Giriş Yap</button>

        </form>

        <p>
            Hesabınız yok mu?
            <a href="kayit.php">Kayıt Ol</a>
        </p>

    </section>

</main>

</body>
</html>