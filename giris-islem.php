
<?php
session_start();

require_once("baglanti.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: giris.php");
    exit();
}

$email = trim($_POST["email"] ?? "");
$sifre = $_POST["sifre"] ?? "";

if ($email === "" || $sifre === "") {
    die("E-posta ve şifre alanlarını doldurunuz.");
}

$sorgu = "SELECT id, sifre, rol, durum
          FROM kullanicilar
          WHERE email = ?
          LIMIT 1";

$stmt = mysqli_prepare($baglanti, $sorgu);

if (!$stmt) {
    die("Giriş işlemi sırasında bir hata oluştu.");
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

mysqli_stmt_bind_result(
    $stmt,
    $kullanici_id,
    $sifreli_sifre,
    $rol,
    $durum
);

if (
    mysqli_stmt_fetch($stmt) &&
    password_verify($sifre, $sifreli_sifre)
) {
    mysqli_stmt_close($stmt);

    if ($durum !== "aktif") {
        die("Hesabınız aktif değil.");
    }

    session_regenerate_id(true);

    $_SESSION["kullanici_id"] = $kullanici_id;
    $_SESSION["rol"] = $rol;

    // ROOT yönlendirmesi
    if ($rol === "root") {
        header("Location: root/root-panel.php");
        exit();
    }

    // Müşteri yönlendirmesi
    if ($rol === "musteri") {
        header("Location: musteri-panel.html");
        exit();
    }

    // Diğer roller daha sonra eklenecek
    die("Bu kullanıcı rolü için giriş yönlendirmesi henüz hazırlanmadı.");

} else {
    mysqli_stmt_close($stmt);
    echo "E-posta veya şifre hatalı.";
}
?>
