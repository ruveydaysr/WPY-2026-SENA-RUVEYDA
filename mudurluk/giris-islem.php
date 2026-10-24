<?php
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: giris.php");
    exit();
}

$email = trim((string) ($_POST["email"] ?? ""));
$sifre = (string) ($_POST["sifre"] ?? "");
$mesaj = "E-posta veya şifre hatalı.";

if ($email === "" || $sifre === "") {
    $mesaj = "E-posta ve şifre alanlarını doldurun.";
} else {
    try {
        require_once(__DIR__ . "/baglanti.php");
        mysqli_set_charset($baglanti, "utf8mb4");

        $sql = "SELECT id, sifre, rol, durum
                FROM kullanicilar WHERE email = ? LIMIT 1";

        $sorgu = mysqli_prepare($baglanti, $sql);
        mysqli_stmt_bind_param($sorgu, "s", $email);
        mysqli_stmt_execute($sorgu);

        mysqli_stmt_bind_result(
            $sorgu, $id, $kayitli_sifre, $rol, $durum
        );

        $bulundu = mysqli_stmt_fetch($sorgu);
        mysqli_stmt_close($sorgu);

        if ($bulundu && password_verify($sifre, $kayitli_sifre)) {
            $sayfalar = [
                "musteri"   => "musteri/musteri-panel.php",
                "root"     => "root/root-panel.php",
                "mudur"    => "mudurluk/mudur-panel.php",
                "teknisyen" => "teknisyen/teknisyen-panel.php",
                "teknik"   => "teknisyen/teknisyen-panel.php"
            ];

            if ($durum !== "aktif") {
                $mesaj = "Hesabınız aktif değil.";
            } elseif (!isset($sayfalar[$rol])) {
                $mesaj = "Hesabınız için geçerli bir rol bulunamadı.";
            } else {
                session_regenerate_id(true);

                $_SESSION = [
                    "kullanici_id" => $id,
                    "rol" => $rol
                ];

                header("Location: " . $sayfalar[$rol]);
                exit();
            }
        }
    } catch (mysqli_sql_exception $hata) {
        error_log($hata->getMessage());
        $mesaj = "Giriş yapılamadı. Lütfen tekrar deneyin.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Sonucu | TeknoServis</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="index.php" class="logo">Tekno<span>Servis</span></a>

        <nav aria-label="Ana menü">
            <a href="index.php">Ana Sayfa</a>
            <a href="kayit.php">Kayıt Ol</a>
        </nav>
    </div>
</header>

<main>
    <section class="form-alani">
        <h1>Giriş Yapılamadı</h1>

        <p role="alert">
            <?= htmlspecialchars($mesaj, ENT_QUOTES, "UTF-8") ?>
        </p>

        <a href="giris.php" class="buton">Tekrar Dene</a>
    </section>
</main>

</body>
</html>