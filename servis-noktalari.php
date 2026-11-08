
<?php
require_once("baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");

function guvenli($deger) {
    return htmlspecialchars(
        (string)$deger,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Aktif şubelerin bulunduğu illeri getir
$ilSorgu = mysqli_query(
    $baglanti,
    "SELECT DISTINCT il
     FROM servis_noktalari
     WHERE durum = 'Aktif'
     ORDER BY il ASC"
);

if (!$ilSorgu) {
    http_response_code(500);
    exit("İller yüklenemedi.");
}

$iller = [];

while ($satir = mysqli_fetch_assoc($ilSorgu)) {
    $iller[] = $satir["il"];
}

// Seçilen ili al
$seciliIl = trim($_GET["il"] ?? "");

if (!is_string($_GET["il"] ?? "")) {
    $seciliIl = "";
}

$subeler = [];

// İl seçildiyse o ildeki aktif şubeleri getir
if ($seciliIl !== "" && in_array($seciliIl, $iller, true)) {

    $stmt = mysqli_prepare(
        $baglanti,
        "SELECT id, il, ilce, sube_adi,
                adres, telefon, calisma_saatleri
         FROM servis_noktalari
         WHERE il = ? AND durum = 'Aktif'
         ORDER BY ilce, sube_adi"
    );

    mysqli_stmt_bind_param($stmt, "s", $seciliIl);
    mysqli_stmt_execute($stmt);

    $sonuc = mysqli_stmt_get_result($stmt);

    while ($satir = mysqli_fetch_assoc($sonuc)) {
        $subeler[] = $satir;
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Noktalarımız | TeknoServis</title>

    <link rel="stylesheet" href="style.css?v=6">

<style>
    .sube-sayfasi {
        max-width: 1150px;
        margin: 45px auto 70px;
        padding: 0 20px;
    }

    .sube-geri {
        display: inline-block;
        margin-bottom: 25px;
        color: #3158d9;
        text-decoration: none;
        font-weight: 600;
    }

    .sube-baslik {
        text-align: center;
        padding: 45px 25px;
        margin-bottom: 30px;
        border-radius: 22px;
        background: linear-gradient(135deg, #102347, #315ce5);
        color: white;
        box-shadow: 0 12px 30px rgba(30, 65, 150, .15);
    }

    .sube-baslik h1 {
        color: white;
        font-size: 36px;
        margin: 0 0 15px;
    }

    .sube-baslik p {
        color: #e3ebff;
        font-size: 16px;
        line-height: 1.8;
        margin: 0;
    }

    .sube-filtre {
        background: white;
        padding: 30px;
        border: 1px solid #e0e7f3;
        border-radius: 18px;
        margin-bottom: 25px;
        box-shadow: 0 6px 22px rgba(30, 50, 90, .05);
    }

    .sube-filtre label {
        display: block;
        font-size: 16px;
        font-weight: 700;
        color: #17233d;
        margin-bottom: 13px;
    }

    .sube-filtre select {
        width: 100%;
        padding: 16px;
        border: 1px solid #cbd5e1;
        border-radius: 11px;
        font-size: 16px;
        background: #f8faff;
        color: #17233d;
        cursor: pointer;
    }

    .sube-filtre select:focus {
        outline: 2px solid #4775f5;
        border-color: #4775f5;
    }

    .sube-liste {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 22px;
    }

    .sube-kart {
        background: white;
        border: 1px solid #e1e8f5;
        border-radius: 18px;
        padding: 28px;
        box-shadow: 0 7px 24px rgba(20, 40, 80, .07);
        transition: transform .2s, box-shadow .2s;
        border-top: 4px solid #315ce5;
    }

    .sube-kart:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 30px rgba(20, 40, 80, .12);
    }

    .sube-kart h2 {
        color: #172b55;
        font-size: 23px;
        margin: 20px 0;
    }

    .sube-kart p {
        color: #52627b;
        line-height: 1.8;
        margin: 12px 0;
        overflow-wrap: anywhere;
    }

    .sube-kart strong {
        color: #17233d;
    }

    .sube-etiket {
        display: inline-block;
        background: #e5f7ed;
        color: #16804a;
        padding: 8px 14px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 700;
    }

    .sube-bilgi {
        background: #edf3ff;
        padding: 28px;
        border-radius: 15px;
        text-align: center;
        color: #3154b8;
        font-size: 16px;
    }

    @media (max-width: 600px) {
        .sube-sayfasi {
            margin-top: 25px;
            padding: 0 15px;
        }

        .sube-baslik {
            padding: 35px 20px;
        }

        .sube-baslik h1 {
            font-size: 27px;
        }

        .sube-filtre {
            padding: 20px;
        }

        .sube-kart {
            padding: 22px;
        }
    }
    
/* Daha kompakt sayfa düzeni */
.sube-sayfasi {
    margin-top: 25px;
}

.sube-geri {
    margin-bottom: 18px;
}

.sube-baslik {
    padding: 28px 25px;
    margin-bottom: 22px;
}

.sube-baslik h1 {
    font-size: 30px;
}

.sube-filtre {
    padding: 22px;
    margin-bottom: 22px;
}

</style>

</head>

<body>

<header>
    <div class="ust-menu">
        <a href="index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav>
            <a href="index.php">Ana Sayfa</a>
            <a href="servis-noktalari.php"
               aria-current="page">Servis Noktalarımız</a>
            <a href="giris.php">Giriş Yap</a>
            <a href="kayit.php">Kayıt Ol</a>
        </nav>
    </div>
</header>

<main class="sube-sayfasi">

    <a href="index.php#bolgelerimiz" class="sube-geri">
        ← Ana Sayfaya Dön
    </a>

    <div class="sube-baslik">
        <h1>📍 Servis Noktalarımız</h1>
        <p>
            Bilgisayarınızı teslim edebileceğiniz
            TeknoServis şubelerini il seçerek inceleyin.
        </p>
    </div>

    <form method="GET" class="sube-filtre">
        <label for="il">Hizmet Almak İstediğiniz İl</label>

        <select name="il" id="il"
                onchange="this.form.submit()">

            <option value="">İl Seçiniz</option>

            <?php foreach ($iller as $il): ?>
                <option
                    value="<?= guvenli($il) ?>"
                    <?= $seciliIl === $il ? "selected" : "" ?>>
                    <?= guvenli($il) ?>
                </option>
            <?php endforeach; ?>

        </select>
    </form>

    <?php if (count($iller) === 0): ?>

        <div class="sube-bilgi">
            Henüz aktif servis noktası eklenmemiştir.
        </div>

    <?php elseif ($seciliIl === ""): ?>

        <div class="sube-bilgi">
            Servis noktalarını görmek için yukarıdan
            bir il seçiniz.
        </div>

    <?php elseif (count($subeler) === 0): ?>

        <div class="sube-bilgi">
            Seçtiğiniz ilde aktif servis noktası bulunamadı.
        </div>

    <?php else: ?>

        <div class="sube-liste">

            <?php foreach ($subeler as $sube): ?>

                <div class="sube-kart">

                    <span class="sube-etiket">
                        Aktif Servis Noktası
                    </span>

                    <h2><?= guvenli($sube["sube_adi"]) ?></h2>

                    <p>
                        <strong>📍 İl / İlçe:</strong>
                        <?= guvenli($sube["il"]) ?> /
                        <?= guvenli($sube["ilce"]) ?>
                    </p>

                    <p>
                        <strong>Adres:</strong>
                        <?= guvenli($sube["adres"]) ?>
                    </p>

                    <p>
                        <strong>Telefon:</strong>
                        <?= guvenli($sube["telefon"]) ?>
                    </p>

                    <p>
                        <strong>Çalışma Saatleri:</strong>
                        <?= guvenli($sube["calisma_saatleri"]) ?>
                    </p>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

</body>
</html>
