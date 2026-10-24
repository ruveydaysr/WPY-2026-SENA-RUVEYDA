<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["mudur"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /teknik-servis/mudurluk/servis-yonetimi.php");
    exit();
}

// Form güvenliği
$token = $_POST["csrf"] ?? "";

if (
    !is_string($token) ||
    empty($_SESSION["csrf"]) ||
    !hash_equals($_SESSION["csrf"], $token)
) {
    http_response_code(403);
    exit("Formun süresi dolmuş. Servis detayını yenileyin.");
}

$servis_id = filter_input(INPUT_POST, "servis_id", FILTER_VALIDATE_INT);
$teknisyen_id = filter_input(INPUT_POST, "teknisyen_id", FILTER_VALIDATE_INT);

if (
    !$servis_id || $servis_id < 1 ||
    !$teknisyen_id || $teknisyen_id < 1
) {
    http_response_code(400);
    exit("Geçersiz servis veya teknisyen seçimi.");
}

try {
    // Servis kaydı mevcut mu?
    $sorgu = mysqli_prepare(
        $baglanti,
        "SELECT id FROM servisler WHERE id = ?"
    );
    mysqli_stmt_bind_param($sorgu, "i", $servis_id);
    mysqli_stmt_execute($sorgu);
    mysqli_stmt_store_result($sorgu);

    $servis_var = mysqli_stmt_num_rows($sorgu) > 0;
    mysqli_stmt_close($sorgu);

    if (!$servis_var) {
        http_response_code(404);
        exit("Servis kaydı bulunamadı.");
    }

    // Yalnızca aktif teknisyene atama yap.
    $sorgu = mysqli_prepare(
        $baglanti,
        "UPDATE servisler s
         INNER JOIN kullanicilar k ON k.id = ?
         SET s.teknisyen_id = k.id
         WHERE s.id = ?
           AND k.rol IN ('teknisyen', 'teknik')
           AND k.durum = 'aktif'"
    );
    mysqli_stmt_bind_param($sorgu, "ii", $teknisyen_id, $servis_id);
    mysqli_stmt_execute($sorgu);
    $degisen = mysqli_stmt_affected_rows($sorgu);
    mysqli_stmt_close($sorgu);

    if ($degisen === 0) {
        // Aynı teknisyen zaten atanmış olabilir.
        $sorgu = mysqli_prepare(
            $baglanti,
            "SELECT s.id
             FROM servisler s
             INNER JOIN kullanicilar k ON s.teknisyen_id = k.id
             WHERE s.id = ? AND k.id = ?
               AND k.rol IN ('teknisyen', 'teknik')
               AND k.durum = 'aktif'"
        );
        mysqli_stmt_bind_param($sorgu, "ii", $servis_id, $teknisyen_id);
        mysqli_stmt_execute($sorgu);
        mysqli_stmt_store_result($sorgu);

        $atama_var = mysqli_stmt_num_rows($sorgu) > 0;
        mysqli_stmt_close($sorgu);

        if (!$atama_var) {
            http_response_code(400);
            exit("Atama yapılamadı. Aktif bir teknisyen seçin.");
        }
    }

    header(
        "Location: /teknik-servis/mudurluk/servis-detay.php?id=" . $servis_id,
        true,
        303
    );
    exit();

} catch (mysqli_sql_exception $hata) {
    error_log($hata->getMessage());
    http_response_code(500);
    exit("Atama kaydedilemedi. Lütfen tekrar deneyin.");
}