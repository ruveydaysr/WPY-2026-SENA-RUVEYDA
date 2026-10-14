
<?php
session_start();

if (
    !isset($_SESSION["kullanici_id"]) ||
    !isset($_SESSION["rol"]) ||
    $_SESSION["rol"] !== "mudur"
) {
    http_response_code(403);
    exit("Bu işlem için yetkiniz yok.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: servis-yonetimi.php");
    exit();
}

require_once("../baglanti.php");

$servis_id = filter_input(
    INPUT_POST,
    "servis_id",
    FILTER_VALIDATE_INT
);

$teknisyen_id = filter_input(
    INPUT_POST,
    "teknisyen_id",
    FILTER_VALIDATE_INT
);

if (!$servis_id || !$teknisyen_id) {
    exit("Geçersiz servis veya teknisyen seçimi.");
}

// Seçilen kişinin aktif bir teknisyen olduğunu kontrol et
$sorgu = "SELECT id FROM kullanicilar
          WHERE id = ?
          AND rol IN ('teknisyen', 'teknik')
          AND durum = 'aktif'";

$stmt = mysqli_prepare($baglanti, $sorgu);
mysqli_stmt_bind_param($stmt, "i", $teknisyen_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    exit("Seçilen teknisyen bulunamadı.");
}

mysqli_stmt_close($stmt);

// Servis kaydına teknisyen ata
$sorgu = "UPDATE servisler
          SET teknisyen_id = ?
          WHERE id = ?";

$stmt = mysqli_prepare($baglanti, $sorgu);
mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $teknisyen_id,
    $servis_id
);

mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) === 0) {
    // Aynı teknisyen zaten atanmış olabilir
    $kontrol = mysqli_prepare(
        $baglanti,
        "SELECT id FROM servisler WHERE id = ?"
    );
    mysqli_stmt_bind_param($kontrol, "i", $servis_id);
    mysqli_stmt_execute($kontrol);
    mysqli_stmt_store_result($kontrol);

    if (mysqli_stmt_num_rows($kontrol) === 0) {
        exit("Servis kaydı bulunamadı.");
    }

    mysqli_stmt_close($kontrol);
}

mysqli_stmt_close($stmt);

header("Location: servis-detay.php?id=" . $servis_id);
exit();
?>
