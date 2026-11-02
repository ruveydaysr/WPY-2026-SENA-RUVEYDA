
<?php
session_start();
require_once("../baglanti.php");

mysqli_set_charset($baglanti, "utf8mb4");

// Yetki kontrolü
if (
    !isset($_SESSION["kullanici_id"]) ||
    !in_array(
        $_SESSION["rol"] ?? "",
        ["teknisyen", "admin"],
        true
    )
) {
    http_response_code(403);
    exit("Bu işlemi yapmaya yetkiniz yok.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Geçersiz istek.");
}

// CSRF kontrolü
if (
    !isset($_SESSION["csrf_token"]) ||
    !is_string($_POST["csrf_token"] ?? null) ||
    !hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"]
    )
) {
    http_response_code(403);
    exit("Güvenlik doğrulaması başarısız.");
}

$id = filter_input(
    INPUT_POST,
    "servis_id",
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {
    exit("Geçersiz servis numarası.");
}

$teknisyen_id = (int)$_SESSION["kullanici_id"];

$ariza_tespiti = trim(
    (string)($_POST["ariza_tespiti"] ?? "")
);

$yapilan_islem = trim(
    (string)($_POST["yapilan_islem"] ?? "")
);

$kullanilan_parca = trim(
    (string)($_POST["kullanilan_parca"] ?? "")
);

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

// Ücret doğrulama
foreach ([$parca_ucreti, $iscilik_ucreti] as $ucret) {
    if (
        !is_scalar($ucret) ||
        !is_numeric($ucret) ||
        !is_finite((float)$ucret) ||
        (float)$ucret < 0 ||
        (float)$ucret > 99999999.99
    ) {
        exit("Geçersiz ücret bilgisi.");
    }
}

$parca_ucreti = round((float)$parca_ucreti, 2);
$iscilik_ucreti = round((float)$iscilik_ucreti, 2);

$tahmini_ucret = round(
    $parca_ucreti + $iscilik_ucreti,
    2
);

try {
    mysqli_begin_transaction($baglanti);

    // Servis kaydını kilitleyerek getir
    $kontrol = mysqli_prepare(
        $baglanti,
        "SELECT
            ariza_tespiti,
            yapilan_islem,
            kullanilan_parca,
            parca_ucreti,
            iscilik_ucreti,
            durum,
            tahmini_ucret,
            ucret_onay_durumu,
            hizmet_turu,
            teslim_durumu
         FROM servisler
         WHERE id = ?
           AND teknisyen_id = ?
         FOR UPDATE"
    );

    mysqli_stmt_bind_param(
        $kontrol,
        "ii",
        $id,
        $teknisyen_id
    );

    mysqli_stmt_execute($kontrol);

    $sonuc = mysqli_stmt_get_result($kontrol);
    $eski = mysqli_fetch_assoc($sonuc);

    mysqli_stmt_close($kontrol);

    if (!$eski) {
        mysqli_rollback($baglanti);
        http_response_code(403);
        exit("Bu servisi güncelleme yetkiniz yok.");
    }

    $online_mi = $eski["hizmet_turu"] === "Online";

    // Online başvuruda fiziksel teslim olmaz
    if (
        $online_mi &&
        $durum === "Teslim Edildi"
    ) {
        mysqli_rollback($baglanti);

        header(
            "Location: servis-detay.php?id=" .
            $id . "&hata=online_teslim"
        );
        exit();
    }

    // Elden teslim cihaz önce şubeye ulaşmalı
    if (
        !$online_mi &&
        $eski["teslim_durumu"] !== "Teslim Alındı" &&
        in_array(
            $durum,
            [
                "İnceleniyor",
                "İşlemde",
                "Parça Bekliyor",
                "Tamamlandı",
                "Teslim Edildi"
            ],
            true
        )
    ) {
        mysqli_rollback($baglanti);

        header(
            "Location: servis-detay.php?id=" .
            $id . "&hata=teslim_bekleniyor"
        );
        exit();
    }

    $degisiklikler = [];

    // Arıza tespiti
    if (
        ($eski["ariza_tespiti"] ?? "") !==
        $ariza_tespiti
    ) {
        $degisiklikler[] =
            "Arıza tespiti güncellendi: " .
            $ariza_tespiti;
    }

    // Yapılan işlem
    if (
        ($eski["yapilan_islem"] ?? "") !==
        $yapilan_islem
    ) {
        $islem_basligi = $online_mi
            ? "Uzaktan yapılan işlem"
            : "Yapılan işlem";

        $degisiklikler[] =
            $islem_basligi .
            " güncellendi: " .
            $yapilan_islem;
    }

    // Kullanılan parça
    if (
        ($eski["kullanilan_parca"] ?? "") !==
        $kullanilan_parca
    ) {
        $degisiklikler[] =
            "Kullanılan parça güncellendi: " .
            $kullanilan_parca;
    }

    // Parça ücreti
    if (
        round((float)$eski["parca_ucreti"], 2) !==
        $parca_ucreti
    ) {
        $degisiklikler[] =
            "Parça ücreti güncellendi: " .
            number_format(
                $parca_ucreti,
                2,
                ",",
                "."
            ) . " TL";
    }

    // İşçilik ücreti
    if (
        round((float)$eski["iscilik_ucreti"], 2) !==
        $iscilik_ucreti
    ) {
        $degisiklikler[] =
            "İşçilik ücreti güncellendi: " .
            number_format(
                $iscilik_ucreti,
                2,
                ",",
                "."
            ) . " TL";
    }

    // Durum değişikliği
    if ($eski["durum"] !== $durum) {
        $degisiklikler[] =
            "Servis durumu değiştirildi.";
    }

    // İlk ücret teklifi veya ücret değişikliği
    $eski_tahmini_ucret = $eski["tahmini_ucret"];

    $ucret_degisti = (
        $eski_tahmini_ucret === null ||
        round(
            (float)$eski_tahmini_ucret,
            2
        ) !== $tahmini_ucret
    );

    // İlk ücret teklifi henüz yoksa ve toplam 0 ise
    // yalnızca arıza/durum güncellemesinde teklif oluşturma.
    $ilk_teklif_yok = (
        $eski_tahmini_ucret === null
    );

    $teklif_olustur = !(
        $ilk_teklif_yok &&
        $tahmini_ucret == 0
    );

    if ($teklif_olustur && $ucret_degisti) {
        $yeni_onay_durumu = "Bekliyor";

        $degisiklikler[] =
            "Tahmini ücret müşteri onayına sunuldu: " .
            number_format(
                $tahmini_ucret,
                2,
                ",",
                "."
            ) . " TL";
    } else {
        $yeni_onay_durumu =
            $eski["ucret_onay_durumu"];

        if (!$teklif_olustur) {
            $ucret_degisti = false;
        }
    }

    $yeni_tahmini_ucret = $teklif_olustur
        ? $tahmini_ucret
        : null;

    // Müşteri onayı olmadan servis tamamlanamaz
    if (
        in_array(
            $durum,
            ["Tamamlandı", "Teslim Edildi"],
            true
        ) &&
        $yeni_onay_durumu !== "Onaylandı"
    ) {
        mysqli_rollback($baglanti);

        header(
            "Location: servis-detay.php?id=" .
            $id . "&hata=ucret_onayi"
        );
        exit();
    }

    // Servisi güncelle
    $sql = "
        UPDATE servisler
        SET
            ariza_tespiti = ?,
            yapilan_islem = ?,
            kullanilan_parca = ?,
            parca_ucreti = ?,
            iscilik_ucreti = ?,
            durum = ?,
            tahmini_ucret = ?,
            ucret_onay_durumu = ?,
            ucret_onay_tarihi =
                CASE
                    WHEN ? = 1
                    THEN NULL
                    ELSE ucret_onay_tarihi
                END
        WHERE id = ?
          AND teknisyen_id = ?
    ";

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
        $yeni_tahmini_ucret,
        $yeni_onay_durumu,
        $onay_sifirlansin,
        $id,
        $teknisyen_id
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Servis güncellenemedi.");
    }

    mysqli_stmt_close($stmt);

    // İşlem geçmişine kaydet
    if (!empty($degisiklikler)) {
        $aciklama = implode(
            " | ",
            $degisiklikler
        );

        $eski_durum = $eski["durum"];

        $gecmis = mysqli_prepare(
            $baglanti,
            "INSERT INTO servis_islem_gecmisi
             (
                 servis_id,
                 teknisyen_id,
                 islem_aciklamasi,
                 eski_durum,
                 yeni_durum
             )
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
            throw new Exception(
                "İşlem geçmişi kaydedilemedi."
            );
        }

        mysqli_stmt_close($gecmis);
    }

    mysqli_commit($baglanti);

    header(
        "Location: servis-detay.php?id=" . $id
    );
    exit();

} catch (Throwable $e) {
    mysqli_rollback($baglanti);

    error_log($e->getMessage());

    http_response_code(500);

    exit(
        "Servis güncellenirken bir hata oluştu."
    );
}
