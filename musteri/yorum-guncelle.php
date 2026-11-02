
<?php
require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["musteri"]);

require_once(__DIR__ . "/../baglanti.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: yorumlarim.php");
    exit();
}

// Güvenlik kontrolü
$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Güvenlik doğrulaması başarısız.");
}

$kullanici_id = (int) $_SESSION["kullanici_id"];

$yorum_id = filter_input(INPUT_POST, "yorum_id", FILTER_VALIDATE_INT);
$puan = filter_input(INPUT_POST, "puan", FILTER_VALIDATE_INT);
$yorum = trim((string) ($_POST["yorum"] ?? ""));

// Gelen bilgileri kontrol et
if (!$yorum_id || $yorum_id < 1) {
    exit("Geçersiz yorum.");
}

if ($puan === false || $puan === null || $puan < 1 || $puan > 5) {
    exit("Lütfen 1 ile 5 arasında puan seçiniz.");
}

if ($yorum === "" || mb_strlen($yorum, "UTF-8") > 1000) {
    exit("Yorum 1 ile 1000 karakter arasında olmalıdır.");
}

try {
    mysqli_set_charset($baglanti, "utf8mb4");

    $sorgu = mysqli_prepare(
        $baglanti,
        "UPDATE musteri_yorumlari
         SET puan = ?, yorum = ?, durum = 'Bekliyor'
         WHERE id = ? AND kullanici_id = ?"
    );

    mysqli_stmt_bind_param(
        $sorgu,
        "isii",
        $puan,
        $yorum,
        $yorum_id,
        $kullanici_id
    );

    mysqli_stmt_execute($sorgu);
    $etkilenen = mysqli_stmt_affected_rows($sorgu);
    mysqli_stmt_close($sorgu);

    if ($etkilenen === 0) {
        // Yorum değişmemiş olabilir veya bulunamamış olabilir.
        // Kullanıcıya yanlış başarı mesajı göstermemek için kontrol ediyoruz.
        $kontrol = mysqli_prepare(
            $baglanti,
            "SELECT id FROM musteri_yorumlari
             WHERE id = ? AND kullanici_id = ?"
        );
        mysqli_stmt_bind_param($kontrol, "ii", $yorum_id, $kullanici_id);
        mysqli_stmt_execute($kontrol);
        mysqli_stmt_store_result($kontrol);
        $var_mi = mysqli_stmt_num_rows($kontrol) > 0;
        mysqli_stmt_close($kontrol);

        if (!$var_mi) {
            http_response_code(404);
            exit("Yorum bulunamadı.");
        }
    }

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

    $_SESSION["yorum_bildirim"] =
        "Yorumunuz güncellendi. Yönetici onayından sonra yayınlanacaktır.";

    header("Location: yorumlarim.php");
    exit();

} catch (mysqli_sql_exception $hata) {
    error_log($hata->getMessage());

    http_response_code(500);
    exit("Yorum güncellenemedi. Lütfen tekrar deneyin.");
}
