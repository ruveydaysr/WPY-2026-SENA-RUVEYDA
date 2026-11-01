
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
            max-width: 1100px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .sube-baslik {
            text-align: center;
            margin-bottom: 35px;
        }

        .sube-baslik h1 {
            color: #17233d;
            font-size: 32px;
        }

        .sube-baslik p {
            color: #64748b;
            line-height: 1.7;
        }

        .sube-filtre {
            background: white;
            padding: 25px;
            border: 1px solid #e0e6f0;
            border-radius: 14px;
            margin-bottom: 30px;
        }

        .sube-filtre label {
            display: block;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .sube-filtre select {
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
        }

        .sube-liste {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(280px, 1fr)
            );
            gap: 20px;
        }

        .sube-kart {
            background: white;
            border: 1px solid #e0e6f0;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(20, 40, 80, 0.05);
        }

        .sube-kart h2 {
            color: #2447b2;
            font-size: 21px;
        }

        .sube-kart p {
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .sube-etiket {
            display: inline-block;
            background: #e8efff;
            color: #2447b2;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .sube-bilgi {
            background: #eef3ff;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            color: #2447b2;
        }

        .sube-geri {
            display: inline-block;
            margin-bottom: 25px;
            color: #2447b2;
            text-decoration: none;
            font-weight: bold;
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
