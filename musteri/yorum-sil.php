
<?php
require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["musteri"]);

require_once(__DIR__ . "/../baglanti.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: yorumlarim.php");
    exit();
}

// CSRF güvenlik kontrolü
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

if (!$yorum_id || $yorum_id < 1) {
    http_response_code(400);
    exit("Geçersiz yorum.");
}

try {
    $sorgu = mysqli_prepare(
        $baglanti,
        "DELETE FROM musteri_yorumlari
         WHERE id = ? AND kullanici_id = ?"
    );

    mysqli_stmt_bind_param(
        $sorgu,
        "ii",
        $yorum_id,
        $kullanici_id
    );

    mysqli_stmt_execute($sorgu);
    $silinen = mysqli_stmt_affected_rows($sorgu);
    mysqli_stmt_close($sorgu);

    if ($silinen === 0) {
        http_response_code(404);
        exit("Yorum bulunamadı veya silme yetkiniz yok.");
    }

    // Formun tekrar gönderilmesini önle
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

    $_SESSION["yorum_bildirim"] = "Yorumunuz başarıyla silindi.";

    header("Location: yorumlarim.php");
    exit();

} catch (mysqli_sql_exception $hata) {
    error_log($hata->getMessage());

    http_response_code(500);
    exit("Yorum silinemedi. Lütfen tekrar deneyin.");
}
