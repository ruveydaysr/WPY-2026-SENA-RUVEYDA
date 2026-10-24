<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once(__DIR__ . "/root-kontrol.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
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
    exit("Formun süresi dolmuş. Kullanıcı listesini yenileyin.");
}

$kullanici_id = filter_input(
    INPUT_POST,
    "kullanici_id",
    FILTER_VALIDATE_INT
);

if (!$kullanici_id || $kullanici_id < 1) {
    http_response_code(400);
    exit("Geçersiz kullanıcı.");
}

if ($kullanici_id === (int) $_SESSION["kullanici_id"]) {
    http_response_code(403);
    exit("Kendi hesabınızın durumunu değiştiremezsiniz.");
}

$islem_basladi = false;

try {
    mysqli_begin_transaction($baglanti);
    $islem_basladi = true;

    // Hesabın güncel rolünü ve durumunu al.
    $sorgu = mysqli_prepare(
        $baglanti,
        "SELECT rol, durum FROM kullanicilar
         WHERE id = ? FOR UPDATE"
    );

    mysqli_stmt_bind_param($sorgu, "i", $kullanici_id);
    mysqli_stmt_execute($sorgu);
    mysqli_stmt_bind_result($sorgu, $rol, $eski_durum);
    $bulundu = mysqli_stmt_fetch($sorgu);
    mysqli_stmt_close($sorgu);

    if (!$bulundu || $rol === "root") {
        mysqli_rollback($baglanti);
        $islem_basladi = false;

        http_response_code($bulundu ? 403 : 404);
        exit(
            $bulundu
                ? "Root hesaplarının durumu değiştirilemez."
                : "Kullanıcı bulunamadı."
        );
    }

    if (!in_array($eski_durum, ["aktif", "pasif"], true)) {
        mysqli_rollback($baglanti);
        $islem_basladi = false;

        http_response_code(400);
        exit("Bilinmeyen hesap durumu.");
    }

    $yeni_durum = $eski_durum === "aktif" ? "pasif" : "aktif";

    $sorgu = mysqli_prepare(
        $baglanti,
        "UPDATE kullanicilar SET durum = ?
         WHERE id = ? AND rol <> 'root'"
    );

    mysqli_stmt_bind_param($sorgu, "si", $yeni_durum, $kullanici_id);
    mysqli_stmt_execute($sorgu);
    mysqli_stmt_close($sorgu);

    // İşlem geçmişine kaydet.
    $yapan_id = (int) $_SESSION["kullanici_id"];
    $islem_turu = "durum_degistirme";

    $log = mysqli_prepare(
        $baglanti,
        "INSERT INTO islem_kayitlari
         (yapan_kullanici_id, hedef_kullanici_id,
          islem_turu, eski_deger, yeni_deger)
         VALUES (?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $log,
        "iisss",
        $yapan_id,
        $kullanici_id,
        $islem_turu,
        $eski_durum,
        $yeni_durum
    );

    mysqli_stmt_execute($log);
    mysqli_stmt_close($log);

    mysqli_commit($baglanti);
    $islem_basladi = false;

    header("Location: /teknik-servis/root/kullanicilar.php", true, 303);
    exit();

} catch (Throwable $hata) {
    if ($islem_basladi) {
        mysqli_rollback($baglanti);
    }

    error_log($hata->getMessage());
    http_response_code(500);
    exit("Hesap durumu değiştirilemedi. Lütfen tekrar deneyin.");
}