<?php
$basliklar = [
    "musteri"   => "Müşteri Girişi",
    "teknisyen" => "Teknisyen Girişi",
    "yonetici"  => "Yönetici Girişi",
    "root"     => "Admin (Root) Girişi"
];

$tur = $_GET["tur"] ?? "";

if (!is_string($tur) || !isset($basliklar[$tur])) {
    $tur = "";
}

$baslik = $basliklar[$tur] ?? "Giriş Yap";
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $baslik ?></title>
    <link rel="stylesheet" href="style.css">
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
    <section class="form-alani">
        <h2><?= $baslik ?></h2>

        <form action="giris-islem.php" method="POST">
            <label for="email">E-posta:</label>
            <input
                type="email"
                id="email"
                name="email"
                autocomplete="username"
                required
            >

            <label for="sifre">Şifre:</label>
            <input
                type="password"
                id="sifre"
                name="sifre"
                autocomplete="current-password"
                required
            >

            <button type="submit">Giriş Yap</button>
        </form>

        <?php if ($tur === "musteri") { ?>
            <p>
                Hesabınız yok mu?
                <a href="kayit.php">Kayıt Ol</a>
            </p>
        <?php } ?>

        <p><a href="index.php">Giriş seçeneklerine dön</a></p>
    </section>
</main>

</body>
</html>