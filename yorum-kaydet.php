
<?php
require_once(__DIR__ . "/yetki-kontrol.php");
yetkiKontrol(["musteri"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /teknik-servis/index.php#yorumlar");
    exit();
}

// CSRF kontrolü
$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Güvenlik doğrulaması başarısız. Sayfayı yenileyin.");
}

$kullanici_id = (int) $_SESSION["kullanici_id"];
$puan = filter_input(INPUT_POST, "puan", FILTER_VALIDATE_INT);
$yorum = trim((string) ($_POST["yorum"] ?? ""));

// Puan kontrolü
if ($puan === false || $puan === null || $puan < 1 || $puan > 5) {
    exit("Lütfen 1 ile 5 arasında yıldız seçiniz.");
}

// Yorum kontrolü
if ($yorum === "" || mb_strlen($yorum, "UTF-8") > 1000) {
    exit("Yorumunuz 1 ile 1000 karakter arasında olmalıdır.");
}

try {
    require_once(__DIR__ . "/baglanti.php");
    mysqli_set_charset($baglanti, "utf8mb4");

    $sql = "INSERT INTO musteri_yorumlari
            (kullanici_id, puan, yorum, durum)
            VALUES (?, ?, ?, 'Bekliyor')";

    $sorgu = mysqli_prepare($baglanti, $sql);

    mysqli_stmt_bind_param(
        $sorgu,
        "iis",
        $kullanici_id,
        $puan,
        $yorum
    );

    mysqli_stmt_execute($sorgu);
    mysqli_stmt_close($sorgu);

    // Aynı formun yanlışlıkla tekrar gönderilmesini önle
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

    $_SESSION["yorum_bildirim"] =
        "Yorumunuz başarıyla gönderildi. Yönetici onayından sonra yayınlanacaktır.";

    header("Location: /teknik-servis/index.php#yorumlar");
    exit();

} catch (mysqli_sql_exception $hata) {
    error_log($hata->getMessage());

    http_response_code(500);
    exit("Yorum kaydedilemedi. Lütfen tekrar deneyin.");
}
