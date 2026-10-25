<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teknisyen Girişi | Bilgisayar Teknik Servis</title>

    <link rel="stylesheet" href="teknisyen-style.css">
</head>
<body>

    <div class="giris-kutusu">

        <h1>Bilgisayar Teknik Servis</h1>
        <h2>Teknisyen Girişi</h2>
        <form action="../giris-islem.php" method="POST">
        <label for="email">E-posta</label>
<input
    type="email"
    id="email"
    name="email"
    placeholder="E-posta adresinizi giriniz"
    required
>

<label for="sifre">Şifre</label>
<input
    type="password"
    id="sifre"
    name="sifre"
    placeholder="Şifrenizi giriniz"
    required
>

<button type="submit">Giriş Yap</button>
        </form>
<a href="../index.php" class="ana-sayfa-link">
    ← Ana Sayfaya Dön
</a>
    </div>

</body>
</html>