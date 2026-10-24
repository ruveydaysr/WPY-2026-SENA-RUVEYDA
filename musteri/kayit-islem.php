<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mesaj = "";
$basarili = false;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: kayit.php");
    exit();
}

$adsoyad = trim((string) ($_POST["adsoyad"] ?? ""));
$email = trim((string) ($_POST["email"] ?? ""));
$telefon = trim((string) ($_POST["telefon"] ?? ""));
$sifre = (string) ($_POST["sifre"] ?? "");

if ($adsoyad === "" || $email === "" || $telefon === "" || $sifre === "") {
    $mesaj = "Lütfen bütün alanları doldurun.";
} elseif (mb_strlen($adsoyad) > 100 || strlen($email) > 100) {
    $mesaj = "Ad soyad veya e-posta bilgisi çok uzun.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mesaj = "Geçerli bir e-posta adresi girin.";
} elseif (!preg_match('/^05[0-9]{9}$/', $telefon)) {
    $mesaj = "Telefon numarası 05 ile başlayan 11 rakam olmalıdır.";
} elseif (mb_strlen($sifre) < 8 || strlen($sifre) > 72) {
    $mesaj = "Şifre en az 8 karakter, en fazla 72 bayt olmalıdır.";
} else {
    try {
        require_once(__DIR__ . "/../baglanti.php");
        mysqli_set_charset($baglanti, "utf8mb4");

        $sifreli = password_hash($sifre, PASSWORD_DEFAULT);

        $sql = "INSERT INTO kullanicilar
                (adsoyad, email, telefon, sifre, rol, durum)
                VALUES (?, ?, ?, ?, 'musteri', 'aktif')";

        $sorgu = mysqli_prepare($baglanti, $sql);
        mysqli_stmt_bind_param(
            $sorgu, "ssss", $adsoyad, $email, $telefon, $sifreli
        );

        mysqli_stmt_execute($sorgu);
        mysqli_stmt_close($sorgu);

        $basarili = true;
        $mesaj = "Kaydınız oluşturuldu. Hesabınıza giriş yapabilirsiniz.";
    } catch (mysqli_sql_exception $hata) {
        if ($hata->getCode() === 1062) {
            $mesaj = "Bu e-posta adresi zaten kayıtlı.";
        } else {
            error_log($hata->getMessage());
            $mesaj = "Kayıt oluşturulamadı. Lütfen tekrar deneyin.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Sonucu | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=3">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="index.php" class="logo">Tekno<span>Servis</span></a>
        <nav aria-label="Ana menü">
            <a href="index.php">Ana Sayfa</a>
            <a href="giris.php">Giriş Yap</a>
        </nav>
    </div>
</header>

<main>
    <section class="form-alani">
        <h1><?= $basarili ? "Kayıt Başarılı" : "Kayıt Oluşturulamadı" ?></h1>

        <p role="status">
            <?= htmlspecialchars($mesaj, ENT_QUOTES, "UTF-8") ?>
        </p>

        <?php if ($basarili): ?>
            <a href="giris.php" class="buton">Giriş Yap</a>
        <?php else: ?>
            <a href="kayit.php" class="buton">Kayıt Formuna Dön</a>
        <?php endif; ?>
    </section>
</main>

</body>
</html>