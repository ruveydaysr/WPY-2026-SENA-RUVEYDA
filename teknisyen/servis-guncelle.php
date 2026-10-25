
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
    "Parça Bekliyor",
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
// Müşteriye sunulacak toplam tahmini ücret
$tahmini_ucret = round($parca_ucreti + $iscilik_ucreti, 2);

$teknisyen_id = (int) $_SESSION["kullanici_id"];

try {
    mysqli_begin_transaction($baglanti);

    // Servisin mevcut bilgilerini getir ve kilitle
    $kontrol = mysqli_prepare(
        $baglanti,
"SELECT ariza_tespiti, yapilan_islem, kullanilan_parca,
       parca_ucreti, iscilik_ucreti, durum,
       tahmini_ucret, ucret_onay_durumu
         FROM servisler
         WHERE id = ? AND teknisyen_id = ?
         FOR UPDATE"
    );

    mysqli_stmt_bind_param($kontrol, "ii", $id, $teknisyen_id);
    mysqli_stmt_execute($kontrol);

    $sonuc = mysqli_stmt_get_result($kontrol);
    $eski = mysqli_fetch_assoc($sonuc);
    mysqli_stmt_close($kontrol);

    if (!$eski) {
        mysqli_rollback($baglanti);
        http_response_code(403);
        exit("Bu servisi güncelleme yetkiniz yok.");
    }

    // Değişiklikleri açıklama olarak hazırla
    $degisiklikler = [];

    if (($eski["ariza_tespiti"] ?? "") !== $ariza_tespiti) {
        $degisiklikler[] = "Arıza tespiti güncellendi: " . $ariza_tespiti;
    }

    if (($eski["yapilan_islem"] ?? "") !== $yapilan_islem) {
        $degisiklikler[] = "Yapılan işlem güncellendi: " . $yapilan_islem;
    }

    if (($eski["kullanilan_parca"] ?? "") !== $kullanilan_parca) {
        $degisiklikler[] = "Kullanılan parça güncellendi: " . $kullanilan_parca;
    }

    if ((float)$eski["parca_ucreti"] != $parca_ucreti) {
        $degisiklikler[] = "Parça ücreti güncellendi: " . $parca_ucreti . " TL";
    }

    if ((float)$eski["iscilik_ucreti"] != $iscilik_ucreti) {
        $degisiklikler[] = "İşçilik ücreti güncellendi: " . $iscilik_ucreti . " TL";
    }

    if ($eski["durum"] !== $durum) {
        $degisiklikler[] = "Servis durumu değiştirildi.";
    }
    // İlk ücret teklifi veya ücret değişikliği varsa onay iste
$eski_tahmini_ucret = $eski["tahmini_ucret"];

$ucret_degisti = (
    $eski_tahmini_ucret === null ||
    round((float)$eski_tahmini_ucret, 2) != $tahmini_ucret
);

if ($ucret_degisti) {
    $yeni_onay_durumu = "Bekliyor";

    $degisiklikler[] =
        "Tahmini ücret müşteri onayına sunuldu: " .
        number_format($tahmini_ucret, 2, ",", ".") . " TL";
} else {
    $yeni_onay_durumu = $eski["ucret_onay_durumu"];
}
    // Servis bilgilerini güncelle
$sql = "UPDATE servisler
        SET ariza_tespiti = ?,
            yapilan_islem = ?,
            kullanilan_parca = ?,
            parca_ucreti = ?,
            iscilik_ucreti = ?,
            durum = ?,
            tahmini_ucret = ?,
            ucret_onay_durumu = ?,
            ucret_onay_tarihi =
                CASE WHEN ? = 1 THEN NULL ELSE ucret_onay_tarihi END
        WHERE id = ? AND teknisyen_id = ?";

    $stmt = mysqli_prepare($baglanti, $sql);

$onay_sifirlansin = $ucret_degisti ? 1 : 0;

mysqli_stmt_bind_param(
    $stmt,
    "sssddsdsiii",
    $ariza_tespiti,
    $yapilan_islem,
    $kullanilan_parca,
    $parca_ucreti,
    $iscilik_ucreti,
    $durum,
    $tahmini_ucret,
    $yeni_onay_durumu,
    $onay_sifirlansin,
    $id,
    $teknisyen_id
);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Servis güncellenemedi.");
    }

    mysqli_stmt_close($stmt);

    // Değişiklik varsa işlem geçmişine kaydet
    if (!empty($degisiklikler)) {
        $aciklama = implode(" | ", $degisiklikler);
        $eski_durum = $eski["durum"];

        $gecmis = mysqli_prepare(
            $baglanti,
            "INSERT INTO servis_islem_gecmisi
             (servis_id, teknisyen_id, islem_aciklamasi,
              eski_durum, yeni_durum)
             VALUES (?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $gecmis,
            "iisss",
            $id,
            $teknisyen_id,
            $aciklama,
            $eski_durum,
            $durum
        );

        if (!mysqli_stmt_execute($gecmis)) {
            throw new Exception("İşlem geçmişi kaydedilemedi.");
        }

        mysqli_stmt_close($gecmis);
    }

    // Her iki işlem de başarılıysa kaydet
    mysqli_commit($baglanti);

    header("Location: servis-detay.php?id=" . $id);
    exit();

} catch (Throwable $e) {
    mysqli_rollback($baglanti);
    http_response_code(500);
    exit("Servis güncellenirken bir hata oluştu.");
}
