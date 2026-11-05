
<?php
require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["yonetici"]);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Geçersiz istek.");
}

// Form güvenliği
$token = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["csrf_token"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Geçersiz güvenlik anahtarı.");
}

$teknisyen_id = filter_input(
    INPUT_POST,
    "teknisyen_id",
    FILTER_VALIDATE_INT
);

if (!$teknisyen_id || $teknisyen_id < 1) {
    http_response_code(400);
    exit("Geçersiz teknisyen.");
}

$islem_basladi = false;

try {
    mysqli_begin_transaction($baglanti);
    $islem_basladi = true;

    // Yalnızca teknisyen hesaplarını bul
    $sorgu = mysqli_prepare(
        $baglanti,
        "SELECT rol, durum
         FROM kullanicilar
         WHERE id = ?
         FOR UPDATE"
    );

    mysqli_stmt_bind_param($sorgu, "i", $teknisyen_id);
    mysqli_stmt_execute($sorgu);
    mysqli_stmt_bind_result($sorgu, $rol, $eski_durum);
    $bulundu = mysqli_stmt_fetch($sorgu);
    mysqli_stmt_close($sorgu);

    if (
        !$bulundu ||
        !in_array($rol, ["teknisyen", "teknik"], true)
    ) {
        mysqli_rollback($baglanti);
        $islem_basladi = false;

        http_response_code(403);
        exit("Bu hesap üzerinde işlem yapamazsınız.");
    }

    if (!in_array($eski_durum, ["aktif", "pasif"], true)) {
        mysqli_rollback($baglanti);
        $islem_basladi = false;

        http_response_code(400);
        exit("Bilinmeyen hesap durumu.");
    }

    $yeni_durum = $eski_durum === "aktif"
        ? "pasif"
        : "aktif";

    $guncelle = mysqli_prepare(
        $baglanti,
        "UPDATE kullanicilar
         SET durum = ?
         WHERE id = ?
         AND rol IN ('teknisyen', 'teknik')"
    );

    mysqli_stmt_bind_param(
        $guncelle,
        "si",
        $yeni_durum,
        $teknisyen_id
    );

    mysqli_stmt_execute($guncelle);
    mysqli_stmt_close($guncelle);

    mysqli_commit($baglanti);
    $islem_basladi = false;

    $_SESSION["il_mesaji"] =
        "Teknisyen hesabının durumu başarıyla güncellendi.";

    header(
        "Location: teknisyen-yonetimi.php",
        true,
        303
    );
    exit();

} catch (Throwable $hata) {
    if ($islem_basladi) {
        mysqli_rollback($baglanti);
    }

    error_log($hata->getMessage());

    http_response_code(500);
    exit("Hesap durumu güncellenemedi.");
}
