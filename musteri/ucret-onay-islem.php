
<?php
require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["musteri"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Geçersiz istek.");
}

// CSRF güvenlik kontrolü
if (
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )
) {
    http_response_code(403);
    exit("Güvenlik doğrulaması başarısız.");
}

$servis_id = filter_input(
    INPUT_POST,
    "servis_id",
    FILTER_VALIDATE_INT
);

$karar = $_POST["karar"] ?? "";

if (!$servis_id || $servis_id < 1) {
    exit("Geçersiz servis numarası.");
}

if (!in_array($karar, ["Onaylandı", "Reddedildi"], true)) {
    exit("Geçersiz onay işlemi.");
}

$musteri_id = (int) $_SESSION["kullanici_id"];

// Yalnızca müşterinin kendi servisini güncelle
// ve sadece bekleyen teklif için karar ver.
$sql = "UPDATE servisler
        SET ucret_onay_durumu = ?,
            ucret_onay_tarihi = NOW()
        WHERE id = ?
          AND kullanici_id = ?
          AND ucret_onay_durumu = 'Bekliyor'
          AND tahmini_ucret IS NOT NULL";

$stmt = mysqli_prepare($baglanti, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "sii",
    $karar,
    $servis_id,
    $musteri_id
);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    exit("Ücret onayı kaydedilemedi.");
}

if (mysqli_stmt_affected_rows($stmt) !== 1) {
    mysqli_stmt_close($stmt);
    exit("Bekleyen teklif bulunamadı veya bu işlem için yetkiniz yok.");
}

mysqli_stmt_close($stmt);

header("Location: servis-takip.php");
exit();
