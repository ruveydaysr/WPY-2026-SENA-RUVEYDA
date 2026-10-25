
<?php
session_start();
require_once("../baglanti.php");

// Yalnızca teknisyen giriş yapabilir
if (
    !isset($_SESSION["kullanici_id"]) ||
    ($_SESSION["rol"] ?? "") !== "teknisyen"
) {
    http_response_code(403);
    exit("Bu sayfaya erişim yetkiniz yok.");
}

$servis_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$servis_id || $servis_id < 1) {
    exit("Geçersiz servis numarası.");
}

$teknisyen_id = (int) $_SESSION["kullanici_id"];

// Yalnızca teknisyene atanmış ve tamamlanmış servisler
$sql = "SELECT
            s.id,
            s.cihaz,
            s.marka,
            s.model,
            s.ariza,
            s.ariza_tespiti,
            s.yapilan_islem,
            s.kullanilan_parca,
            s.parca_ucreti,
            s.iscilik_ucreti,
            s.tahmini_ucret,
            s.ucret_onay_durumu,
            s.durum,
            s.tarih,
            k.adsoyad AS musteri_adi
        FROM servisler s
        INNER JOIN kullanicilar k
            ON s.kullanici_id = k.id
        WHERE s.id = ?
          AND s.teknisyen_id = ?
          AND s.durum IN ('Tamamlandı', 'Teslim Edildi')";

$stmt = mysqli_prepare($baglanti, $sql);
mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $servis_id,
    $teknisyen_id
);
mysqli_stmt_execute($stmt);

$sonuc = mysqli_stmt_get_result($stmt);
$servis = mysqli_fetch_assoc($sonuc);
mysqli_stmt_close($stmt);

if (!$servis) {
    http_response_code(403);
    exit("Teslim fişi oluşturulabilecek servis bulunamadı.");
}

function guvenli($deger) {
    return htmlspecialchars(
        (string) ($deger ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function para($tutar) {
    return number_format((float) $tutar, 2, ",", ".") . " TL";
}

$toplam = (float) $servis["parca_ucreti"]
        + (float) $servis["iscilik_ucreti"];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Teslim Fişi - <?= (int)$servis["id"] ?></title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            padding: 25px;
        }

        .fis {
            background: white;
            max-width: 750px;
            margin: auto;
            padding: 30px;
            border: 1px solid #ccc;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
        }

        h2 {
            margin-top: 25px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 8px;
        }

        p {
            line-height: 1.7;
        }

        .butonlar {
            text-align: center;
            margin-bottom: 20px;
        }

        button {
            padding: 12px 25px;
            background: #2c3e50;
            color: white;
            border: none;
            cursor: pointer;
        }

        .imza {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }

        

.geri-buton {
    display: inline-block;
    background: #365cf5;
    color: white;
    padding: 12px 20px;
    text-decoration: none;
    margin-right: 10px;
}

.geri-buton:hover {
    background: #2448d8;
}

  @media print {
    @page {
        size: A4;
        margin: 10mm;
    }

    body {
        background: white;
        margin: 0;
        padding: 0;
        font-size: 15px;
    }

    .fis {
        border: none;
        max-width: 100%;
        margin: 0;
        padding: 0;
    }

    h1 {
        font-size: 26px;
        margin: 0 0 5px;
    }

    h2 {
        font-size: 19px;
        margin-top: 16px;
        margin-bottom: 6px;
        padding-bottom: 4px;
    }

    p {
        line-height: 1.5;
        margin: 9px 0;
    }

    .imza {
        margin-top: 35px;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .butonlar {
        display: none;
    }
    

}

        
    </style>
</head>
<body>

<div class="butonlar">
    <a href="servis-detay.php?id=<?= (int)$servis['id'] ?>"
       class="geri-buton">
        ← Servis Detayına Dön
    </a>

    <button onclick="window.print()">Fişi Yazdır</button>
</div>

<div class="fis">

    <h1>TEKNOSERVİS</h1>
    <p style="text-align:center;">SERVİS TESLİM FİŞİ</p>

    <h2>Servis Bilgileri</h2>

    <p><strong>Servis No:</strong>
        SRV-<?= str_pad($servis["id"], 3, "0", STR_PAD_LEFT) ?>
    </p>

    <p><strong>Kayıt Tarihi:</strong>
        <?= guvenli($servis["tarih"]) ?>
    </p>

    <p><strong>Servis Durumu:</strong>
        <?= guvenli($servis["durum"]) ?>
    </p>

    <h2>Müşteri Bilgileri</h2>

    <p><strong>Ad Soyad:</strong>
        <?= guvenli($servis["musteri_adi"]) ?>
    </p>

    <h2>Cihaz Bilgileri</h2>

    <p><strong>Cihaz:</strong>
        <?= guvenli($servis["cihaz"]) ?>
    </p>

    <p><strong>Marka / Model:</strong>
        <?= guvenli($servis["marka"] . " " . $servis["model"]) ?>
    </p>

    <p><strong>Şikayet:</strong>
        <?= guvenli($servis["ariza"]) ?>
    </p>

    <h2>Yapılan İşlemler</h2>

    <p><strong>Arıza Tespiti:</strong>
        <?= guvenli($servis["ariza_tespiti"]) ?>
    </p>

    <p><strong>Yapılan İşlem:</strong>
        <?= guvenli($servis["yapilan_islem"]) ?>
    </p>

    <p><strong>Kullanılan Parça:</strong>
        <?= guvenli($servis["kullanilan_parca"]) ?>
    </p>

    <h2>Ücret Bilgileri</h2>

    <p><strong>Parça Ücreti:</strong>
        <?= para($servis["parca_ucreti"]) ?>
    </p>

    <p><strong>İşçilik Ücreti:</strong>
        <?= para($servis["iscilik_ucreti"]) ?>
    </p>

    <p><strong>Toplam Ücret:</strong>
        <?= para($toplam) ?>
    </p>

    <p><strong>Müşteri Ücret Onayı:</strong>
        <?= guvenli($servis["ucret_onay_durumu"] ?: "Henüz teklif yok") ?>
    </p>

    <div class="imza">
        <p>Teknisyen İmzası: __________</p>
        <p>Müşteri İmzası: __________</p>
    </div>

</div>

</body>
</html>
