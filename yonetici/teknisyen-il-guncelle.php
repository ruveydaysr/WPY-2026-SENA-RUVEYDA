
<?php
session_start();

if (
    !isset($_SESSION["kullanici_id"]) ||
    ($_SESSION["rol"] ?? "") !== "yonetici"
) {
    header("Location: ../giris.php");
    exit();
}

require_once("../baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: teknisyen-yonetimi.php");
    exit();
}

$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("İşlem doğrulanamadı.");
}

$teknisyen_id = filter_input(
    INPUT_POST,
    "teknisyen_id",
    FILTER_VALIDATE_INT
);

$il = trim((string) ($_POST["il"] ?? ""));

$iller = [
    "Adana", "Adıyaman", "Afyonkarahisar", "Ağrı", "Aksaray",
    "Amasya", "Ankara", "Antalya", "Ardahan", "Artvin",
    "Aydın", "Balıkesir", "Bartın", "Batman", "Bayburt",
    "Bilecik", "Bingöl", "Bitlis", "Bolu", "Burdur",
    "Bursa", "Çanakkale", "Çankırı", "Çorum", "Denizli",
    "Diyarbakır", "Düzce", "Edirne", "Elazığ", "Erzincan",
    "Erzurum", "Eskişehir", "Gaziantep", "Giresun", "Gümüşhane",
    "Hakkari", "Hatay", "Iğdır", "Isparta", "İstanbul",
    "İzmir", "Kahramanmaraş", "Karabük", "Karaman", "Kars",
    "Kastamonu", "Kayseri", "Kırıkkale", "Kırklareli", "Kırşehir",
    "Kilis", "Kocaeli", "Konya", "Kütahya", "Malatya",
    "Manisa", "Mardin", "Mersin", "Muğla", "Muş",
    "Nevşehir", "Niğde", "Ordu", "Osmaniye", "Rize",
    "Sakarya", "Samsun", "Siirt", "Sinop", "Sivas",
    "Şanlıurfa", "Şırnak", "Tekirdağ", "Tokat", "Trabzon",
    "Tunceli", "Uşak", "Van", "Yalova", "Yozgat",
    "Zonguldak"
];

if (!$teknisyen_id || !in_array($il, $iller, true)) {
    $_SESSION["il_mesaji"] = "Geçersiz teknisyen veya il seçimi.";
    header("Location: teknisyen-yonetimi.php");
    exit();
}

$stmt = mysqli_prepare(
    $baglanti,
    "UPDATE kullanicilar
     SET il = ?
     WHERE id = ?
       AND rol IN ('teknisyen', 'teknik')"
);

mysqli_stmt_bind_param($stmt, "si", $il, $teknisyen_id);
mysqli_stmt_execute($stmt);

$degisen = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

$_SESSION["il_mesaji"] = $degisen > 0
    ? "Teknisyenin görev ili başarıyla güncellendi."
    : "Değişiklik yapılmadı. Teknisyen bulunamadı veya il zaten aynı.";

header("Location: teknisyen-yonetimi.php");
exit();
