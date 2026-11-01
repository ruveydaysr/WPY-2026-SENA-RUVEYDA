
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["musteri"]);

require_once(__DIR__ . "/../baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");

// Yalnızca formdan gelen POST isteğini kabul et
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /teknik-servis/musteri/servis-olustur.php");
    exit();
}

// CSRF güvenlik kontrolü
$token = $_POST["csrf"] ?? "";

if (
    !is_string($token) ||
    empty($_SESSION["csrf"]) ||
    !hash_equals($_SESSION["csrf"], $token)
) {
    http_response_code(403);
    exit("Formun süresi dolmuş. Servis oluşturma sayfasını yenileyin.");
}

// Form bilgilerini al
$hizmet_turu = $_POST["hizmet_turu"] ?? "";
$il = $_POST["il"] ?? "";
$cihaz = $_POST["cihaz"] ?? "";
$marka = $_POST["marka"] ?? "";
$model = $_POST["model"] ?? "";
$ariza = $_POST["ariza"] ?? "";

$servis_noktasi_id = filter_input(
    INPUT_POST,
    "servis_noktasi_id",
    FILTER_VALIDATE_INT
);

$cihazlar = [
    "Masaüstü Bilgisayar",
    "Dizüstü Bilgisayar",
    "Tablet",
    "Diğer"
];

$mesaj = "";

// Gönderilen bilgilerin türünü kontrol et
if (
    !is_string($hizmet_turu) ||
    !is_string($il) ||
    !is_string($cihaz) ||
    !is_string($marka) ||
    !is_string($model) ||
    !is_string($ariza)
) {
    $mesaj = "Gönderilen bilgiler geçersiz.";
} else {
    $hizmet_turu = trim($hizmet_turu);
    $il = trim($il);
    $cihaz = trim($cihaz);
    $marka = trim($marka);
    $model = trim($model);
    $ariza = trim($ariza);
}

// Ortak alanları doğrula
if ($mesaj === "") {
    if (
        !in_array($hizmet_turu, ["Online", "Elden Teslim"], true) ||
        !in_array($cihaz, $cihazlar, true) ||
        $marka === "" ||
        $model === "" ||
        $ariza === ""
    ) {
        $mesaj = "Lütfen bütün alanları doğru şekilde doldurun.";
    } elseif (
        mb_strlen($marka) > 50 ||
        mb_strlen($model) > 50 ||
        mb_strlen($ariza) > 1000
    ) {
        $mesaj = "Girdiğiniz bilgiler izin verilen uzunluğu aşıyor.";
    }
}

// Hizmet türüne göre il ve şube doğrulaması
if ($mesaj === "") {

    if ($hizmet_turu === "Online") {

        // Online destek için şube seçimi gerekmiyor.
        $il = null;
        $servis_noktasi_id = null;

    } else {

        // Elden teslim için il ve şube zorunlu.
        if (
            $il === "" ||
            mb_strlen($il) > 100 ||
            !$servis_noktasi_id
        ) {
            $mesaj = "Elden teslim için il ve servis şubesi seçmelisiniz.";
        }
    }
}

if ($mesaj === "") {

    try {

        // Elden teslimde şubenin aktif olduğunu kontrol et
        if ($hizmet_turu === "Elden Teslim") {

            $subeSorgu = mysqli_prepare(
                $baglanti,
                "SELECT id
                 FROM servis_noktalari
                 WHERE id = ?
                   AND il = ?
                   AND durum = 'Aktif'
                 LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $subeSorgu,
                "is",
                $servis_noktasi_id,
                $il
            );

            mysqli_stmt_execute($subeSorgu);
            mysqli_stmt_store_result($subeSorgu);

            $subeGecerli =
                mysqli_stmt_num_rows($subeSorgu) === 1;

            mysqli_stmt_close($subeSorgu);

            if (!$subeGecerli) {
                throw new RuntimeException(
                    "Seçilen servis şubesi geçersiz veya aktif değil."
                );
            }
        }

        $kullanici_id = (int) $_SESSION["kullanici_id"];

        // Online başvuruda il ve şube NULL kaydedilir.
        // Elden teslimde seçilen il ve şube kaydedilir.
        $sorgu = mysqli_prepare(
            $baglanti,
            "INSERT INTO servisler
            (
                kullanici_id,
                hizmet_turu,
                il,
                servis_noktasi_id,
                cihaz,
                marka,
                model,
                ariza,
                durum
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Bekliyor')"
        );

        mysqli_stmt_bind_param(
            $sorgu,
            "ississss",
            $kullanici_id,
            $hizmet_turu,
            $il,
            $servis_noktasi_id,
            $cihaz,
            $marka,
            $model,
            $ariza
        );

        mysqli_stmt_execute($sorgu);
        mysqli_stmt_close($sorgu);

        // Aynı formun yeniden gönderilmesini önle
        $_SESSION["csrf"] = bin2hex(random_bytes(32));

        header(
            "Location: /teknik-servis/musteri/servis-takip.php",
            true,
            303
        );
        exit();

    } catch (mysqli_sql_exception | RuntimeException $hata) {

        error_log($hata->getMessage());

        $mesaj = "Servis kaydedilemedi. Lütfen bilgilerinizi kontrol edip tekrar deneyin.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Sonucu | TeknoServis</title>

    <link rel="stylesheet"
          href="/teknik-servis/style.css?v=4">
</head>
<body>

<main>
    <section class="form-alani">

        <h1>Kayıt Oluşturulamadı</h1>

        <p role="alert">
            <?= htmlspecialchars($mesaj, ENT_QUOTES, "UTF-8") ?>
        </p>

        <a href="/teknik-servis/musteri/servis-olustur.php"
           class="buton">
            Servis Formuna Dön
        </a>

    </section>
</main>

</body>
</html>
