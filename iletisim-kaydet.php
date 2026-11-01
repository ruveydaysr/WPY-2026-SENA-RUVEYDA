
<?php
session_start();
require_once("baglanti.php");

// Yalnızca POST isteklerini kabul et
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Geçersiz istek.");
}

// Form bilgilerini al
$adsoyad = trim($_POST["adsoyad"] ?? "");
$email = trim($_POST["email"] ?? "");
$konu = trim($_POST["konu"] ?? "");
$mesaj = trim($_POST["mesaj"] ?? "");

// Bilgileri kontrol et
if (
    $adsoyad === "" ||
    $email === "" ||
    $konu === "" ||
    $mesaj === ""
) {
    $_SESSION["iletisim_hata"] = "Lütfen bütün alanları doldurun.";
    header("Location: index.php#iletisim");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION["iletisim_hata"] = "Geçerli bir e-posta adresi girin.";
    header("Location: index.php#iletisim");
    exit();
}

if (
    mb_strlen($adsoyad) > 100 ||
    mb_strlen($email) > 150 ||
    mb_strlen($konu) > 200 ||
    mb_strlen($mesaj) > 5000
) {
    $_SESSION["iletisim_hata"] = "Girilen bilgiler izin verilen uzunluğu aşıyor.";
    header("Location: index.php#iletisim");
    exit();
}

// Mesajı veritabanına kaydet
$sql = "INSERT INTO iletisim_mesajlari
        (adsoyad, email, konu, mesaj)
        VALUES (?, ?, ?, ?)";

$stmt = mysqli_prepare($baglanti, $sql);

if (!$stmt) {
    http_response_code(500);
    exit("Mesaj kaydı hazırlanamadı.");
}

mysqli_stmt_bind_param(
    $stmt,
    "ssss",
    $adsoyad,
    $email,
    $konu,
    $mesaj
);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION["iletisim_basarili"] =
        "Mesajınız başarıyla gönderildi.";
} else {
    $_SESSION["iletisim_hata"] =
        "Mesaj gönderilirken bir sorun oluştu.";
}

mysqli_stmt_close($stmt);

header("Location: index.php#iletisim");
exit();
?>
