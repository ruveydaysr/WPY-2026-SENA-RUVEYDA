
<?php
require_once("root-kontrol.php");

// Sadece POST istekleri kabul edilir
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Geçersiz istek.");
}

// CSRF kontrolü
$gelen_token = $_POST["csrf_token"] ?? "";

if (
    !isset($_SESSION["csrf_token"]) ||
    !is_string($gelen_token) ||
    !hash_equals($_SESSION["csrf_token"], $gelen_token)
) {
    http_response_code(403);
    exit("Güvenlik doğrulaması başarısız.");
}

// Kullanıcı ID kontrolü
$kullanici_id = filter_input(
    INPUT_POST,
    "kullanici_id",
    FILTER_VALIDATE_INT
);

if (!$kullanici_id || $kullanici_id <= 0) {
    exit("Geçersiz kullanıcı.");
}

if ($kullanici_id == $_SESSION["kullanici_id"]) {
    exit("Kendi hesabınızın durumunu değiştiremezsiniz.");
}

try {
    // Güncelleme ve log kaydı birlikte yapılacak
    mysqli_begin_transaction($baglanti);

    // Kullanıcıyı kilitleyerek güncel bilgilerini al
    $sorgu = mysqli_prepare(
        $baglanti,
        "SELECT rol, durum
         FROM kullanicilar
         WHERE id = ?
         FOR UPDATE"
    );

    mysqli_stmt_bind_param($sorgu, "i", $kullanici_id);
    mysqli_stmt_execute($sorgu);
    mysqli_stmt_bind_result($sorgu, $rol, $eski_durum);

    $bulundu = mysqli_stmt_fetch($sorgu);
    mysqli_stmt_close($sorgu);

    if (!$bulundu) {
        throw new Exception("Kullanıcı bulunamadı.");
    }

    if ($rol === "root") {
        throw new Exception("ROOT hesapları değiştirilemez.");
    }

    if ($eski_durum === "aktif") {
        $yeni_durum = "pasif";
    } elseif ($eski_durum === "pasif") {
        $yeni_durum = "aktif";
    } else {
        throw new Exception("Bilinmeyen hesap durumu.");
    }

    // Kullanıcının durumunu güncelle
    $guncelle = mysqli_prepare(
        $baglanti,
        "UPDATE kullanicilar
         SET durum = ?
         WHERE id = ? AND rol <> 'root'"
    );

    mysqli_stmt_bind_param(
        $guncelle,
        "si",
        $yeni_durum,
        $kullanici_id
    );

    mysqli_stmt_execute($guncelle);
    mysqli_stmt_close($guncelle);

    // İşlemi yapan ROOT hesabı
    $yapan_id = (int) $_SESSION["kullanici_id"];
    $islem_turu = "durum_degistirme";

    // İşlem geçmişine kaydet
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
        $eski_durum,
        $yeni_durum
    );

    mysqli_stmt_execute($log);
    mysqli_stmt_close($log);

    // Her iki işlem de başarılıysa kaydet
    mysqli_commit($baglanti);

    header("Location: kullanicilar.php");
    exit();

} catch (Throwable $hata) {
    mysqli_rollback($baglanti);

    http_response_code(500);
    exit("İşlem tamamlanamadı. Veritabanını kontrol ediniz.");
}
?>
