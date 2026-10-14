
<?php
// ROOT sayfaları için ortak güvenlik kontrolü

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giriş yapılmamışsa giriş sayfasına yönlendir
if (
    !isset($_SESSION["kullanici_id"]) ||
    !isset($_SESSION["rol"]) ||
    $_SESSION["rol"] !== "root"
) {
    header("Location: ../giris.php");
    exit();
}

require_once(__DIR__ . "/../baglanti.php");

// Kullanıcının güncel rolünü ve durumunu veritabanından kontrol et
$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT rol, durum FROM kullanicilar WHERE id = ? LIMIT 1"
);

if (!$sorgu) {
    http_response_code(500);
    exit("Güvenlik kontrolü sırasında hata oluştu.");
}

$kullanici_id = (int) $_SESSION["kullanici_id"];

mysqli_stmt_bind_param($sorgu, "i", $kullanici_id);
mysqli_stmt_execute($sorgu);
mysqli_stmt_bind_result($sorgu, $rol, $durum);

$kullanici_bulundu = mysqli_stmt_fetch($sorgu);

mysqli_stmt_close($sorgu);

// Hesap silinmiş, pasif yapılmış veya ROOT yetkisi kaldırılmışsa
if (
    !$kullanici_bulundu ||
    $rol !== "root" ||
    $durum !== "aktif"
) {
    $_SESSION = [];
    session_destroy();

    header("Location: ../giris.php");
    exit();
}

// Oturum bilgilerini güncel tut
$_SESSION["rol"] = $rol;
?>
