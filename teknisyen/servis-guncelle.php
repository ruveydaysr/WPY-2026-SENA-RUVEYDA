
<?php
session_start();
require_once("../baglanti.php");

// Yalnızca giriş yapmış teknisyen ve admin işlem yapabilir.
if (
    !isset($_SESSION["kullanici_id"]) ||
    !in_array($_SESSION["rol"] ?? "", ["teknisyen", "admin"], true)
) {
    http_response_code(403);
    exit("Bu işlemi yapmaya yetkiniz yok.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Geçersiz istek.");
}

$id = filter_input(INPUT_POST, "servis_id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Geçersiz servis numarası.");
}

$ariza_tespiti = trim($_POST["ariza_tespiti"] ?? "");
$yapilan_islem = trim($_POST["yapilan_islem"] ?? "");
$kullanilan_parca = trim($_POST["kullanilan_parca"] ?? "");

$parca_ucreti = $_POST["parca_ucreti"] ?? "0";
$iscilik_ucreti = $_POST["iscilik_ucreti"] ?? "0";

$durum = $_POST["durum"] ?? "";

$gecerli_durumlar = [
    "Bekliyor",
    "İnceleniyor",
    "İşlemde",
    "Tamamlandı",
    "Teslim Edildi"
];

if (!in_array($durum, $gecerli_durumlar, true)) {
    exit("Geçersiz servis durumu.");
}

foreach ([$parca_ucreti, $iscilik_ucreti] as $ucret) {
    if (
        !is_numeric($ucret) ||
        (float)$ucret < 0 ||
        (float)$ucret > 99999999.99
    ) {
        exit("Geçersiz ücret bilgisi.");
    }
}

$parca_ucreti = (float)$parca_ucreti;
$iscilik_ucreti = (float)$iscilik_ucreti;
$teknisyen_id = (int) $_SESSION["kullanici_id"];

$sql = "UPDATE servisler
        SET ariza_tespiti = ?,
            yapilan_islem = ?,
            kullanilan_parca = ?,
            parca_ucreti = ?,
            iscilik_ucreti = ?,
            durum = ?
        WHERE id = ?
        AND teknisyen_id = ?";

$stmt = mysqli_prepare($baglanti, $sql);


mysqli_stmt_bind_param(
    $stmt,
    "sssddsii",
    $ariza_tespiti,
    $yapilan_islem,
    $kullanilan_parca,
    $parca_ucreti,
    $iscilik_ucreti,
    $durum,
    $id,
    $teknisyen_id
);


if (!mysqli_stmt_execute($stmt)) {
    exit("Servis güncellenirken hata oluştu.");
}
if (mysqli_stmt_affected_rows($stmt) === 0) {
    // Kayıt bulunamadı mı, yoksa bilgiler zaten aynı mı?
    $kontrol = mysqli_prepare(
        $baglanti,
        "SELECT id FROM servisler WHERE id = ? AND teknisyen_id = ?"
    );

    mysqli_stmt_bind_param($kontrol, "ii", $id, $teknisyen_id);
    mysqli_stmt_execute($kontrol);
    mysqli_stmt_store_result($kontrol);

    if (mysqli_stmt_num_rows($kontrol) === 0) {
        http_response_code(403);
        exit("Bu servisi güncelleme yetkiniz yok.");
    }

    mysqli_stmt_close($kontrol);
}

header("Location: servis-detay.php?id=" . $id);
exit();
?>
