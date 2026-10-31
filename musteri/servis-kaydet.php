<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["musteri"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /teknik-servis/musteri/servis-olustur.php");
    exit();
}

$token = $_POST["csrf"] ?? "";

if (
    !is_string($token) ||
    empty($_SESSION["csrf"]) ||
    !hash_equals($_SESSION["csrf"], $token)
) {
    http_response_code(403);
    exit("Formun süresi dolmuş. Servis oluşturma sayfasını yenileyin.");
}

$il = trim((string) ($_POST["il"] ?? ""));
$cihaz = trim((string) ($_POST["cihaz"] ?? ""));
$marka = trim((string) ($_POST["marka"] ?? ""));
$model = trim((string) ($_POST["model"] ?? ""));
$ariza = trim((string) ($_POST["ariza"] ?? ""));

$cihazlar = [
    "Masaüstü Bilgisayar",
    "Dizüstü Bilgisayar",
    "Tablet",
    "Diğer"
];

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
    "Tunceli", "Uşak", "Van", "Yalova", "Yozgat", "Zonguldak"
];
$mesaj = "";

if (
    !in_array($il, $iller, true) ||
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
} else {
    try {
        $id = (int) $_SESSION["kullanici_id"];

        $sorgu = mysqli_prepare(
            $baglanti,
        "INSERT INTO servisler
        (kullanici_id, il, cihaz, marka, model, ariza, durum)
        VALUES (?, ?, ?, ?, ?, ?, 'Bekliyor')"

        );

mysqli_stmt_bind_param(
    $sorgu,
    "isssss",
    $id,
    $il,
    $cihaz,
    $marka,
    $model,
    $ariza
);

        mysqli_stmt_execute($sorgu);
        mysqli_stmt_close($sorgu);

        $_SESSION["csrf"] = bin2hex(random_bytes(32));

        header(
            "Location: /teknik-servis/musteri/servis-takip.php",
            true,
            303
        );
        exit();
    } catch (mysqli_sql_exception $hata) {
        error_log($hata->getMessage());
        $mesaj = "Servis kaydedilemedi. Lütfen tekrar deneyin.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Sonucu | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">
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