
<?php
require_once("root-kontrol.php");

// Yalnızca POST istekleri kabul edilir
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Geçersiz istek.");
}

// CSRF güvenlik kontrolü
$gelen_token = $_POST["csrf_token"] ?? "";

if (
    !isset($_SESSION["csrf_token"]) ||
    !is_string($gelen_token) ||
    !hash_equals($_SESSION["csrf_token"], $gelen_token)
) {
    http_response_code(403);
    exit("Güvenlik doğrulaması başarısız.");
}

// Kullanıcı bilgilerini al
$kullanici_id = filter_input(
    INPUT_POST,
    "kullanici_id",
    FILTER_VALIDATE_INT
);

$yeni_rol = $_POST["yeni_rol"] ?? "";

// İzin verilen roller
$izin_verilen_roller = [
    "musteri",
    "teknik",
    "mudurluk"
];

if (!$kullanici_id || $kullanici_id <= 0) {
    exit("Geçersiz kullanıcı.");
}

if (
    !is_string($yeni_rol) ||
    !in_array($yeni_rol, $izin_verilen_roller, true)
) {
    exit("Geçersiz rol.");
}

// ROOT kendi rolünü değiştiremez
if ($kullanici_id == $_SESSION["kullanici_id"]) {
    exit("Kendi ROOT rolünüzü değiştiremezsiniz.");
}

try {
    // Rol güncelleme ve log kaydı birlikte yapılır
    mysqli_begin_transaction($baglanti);

    // Kullanıcının mevcut rolünü al
    $sorgu = mysqli_prepare(
        $baglanti,
        "SELECT rol
         FROM kullanicilar
         WHERE id = ?
         FOR UPDATE"
    );

    mysqli_stmt_bind_param($sorgu, "i", $kullanici_id);
    mysqli_stmt_execute($sorgu);
    mysqli_stmt_bind_result($sorgu, $eski_rol);

    $bulundu = mysqli_stmt_fetch($sorgu);
    mysqli_stmt_close($sorgu);

    if (!$bulundu) {
        throw new Exception("Kullanıcı bulunamadı.");
    }

    // ROOT hesaplarının rolü değiştirilemez
    if ($eski_rol === "root") {
        throw new Exception("ROOT hesaplarının rolü değiştirilemez.");
    }

    // Aynı rol seçildiyse işlem yapma
    if ($eski_rol === $yeni_rol) {
        mysqli_commit($baglanti);
        header("Location: kullanicilar.php");
        exit();
    }

    // Kullanıcının rolünü güncelle
    $guncelle = mysqli_prepare(
        $baglanti,
        "UPDATE kullanicilar
         SET rol = ?
         WHERE id = ? AND rol <> 'root'"
    );

    mysqli_stmt_bind_param(
        $guncelle,
        "si",
        $yeni_rol,
        $kullanici_id
    );

    mysqli_stmt_execute($guncelle);
    mysqli_stmt_close($guncelle);

    // İşlem geçmişi bilgileri
    $yapan_id = (int) $_SESSION["kullanici_id"];
    $islem_turu = "rol_degistirme";

    // Log kaydı oluştur
    $log = mysqli_prepare(
        $baglanti,
        "INSERT INTO islem_kayitlari
        (
            yapan_kullanici_id,
            hedef_kullanici_id,
            islem_turu,
            eski_deger,
            yeni_deger
        )
        VALUES (?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $log,
        "iisss",
        $yapan_id,
        $kullanici_id,
        $islem_turu,
        $eski_rol,
        $yeni_rol
    );

    mysqli_stmt_execute($log);
    mysqli_stmt_close($log);

    // İşlemleri kaydet
    mysqli_commit($baglanti);

    header("Location: kullanicilar.php");
    exit();

} catch (Throwable $hata) {
    mysqli_rollback($baglanti);

    http_response_code(500);
    exit("Rol değiştirilemedi. Veritabanını kontrol ediniz.");
}
?>
