
<?php
session_start();

if (
    !isset($_SESSION["kullanici_id"]) ||
    !isset($_SESSION["rol"]) ||
    $_SESSION["rol"] !== "mudur"
) {
    http_response_code(403);
    exit("Bu sayfaya erişim yetkiniz yok.");
}

include("../baglanti.php");

function servisSayisi($baglanti, $durumlar = []) {
    if (empty($durumlar)) {
        $sonuc = mysqli_query($baglanti, "SELECT COUNT(*) AS toplam FROM servisler");
        return (int) mysqli_fetch_assoc($sonuc)["toplam"];
    }

    $durumlar = array_map(
        fn($durum) => "'" . mysqli_real_escape_string($baglanti, $durum) . "'",
        $durumlar
    );

    $liste = implode(",", $durumlar);
    $sonuc = mysqli_query(
        $baglanti,
        "SELECT COUNT(*) AS toplam FROM servisler WHERE durum IN ($liste)"
    );

    return (int) mysqli_fetch_assoc($sonuc)["toplam"];
}

$toplam = servisSayisi($baglanti);
$bekleyen = servisSayisi($baglanti, ["Bekliyor"]);
$devamEden = servisSayisi($baglanti, ["İnceleniyor", "İşlemde", "Parça Bekliyor"]);
$tamamlanan = servisSayisi($baglanti, ["Tamamlandı", "Teslim Edildi"]);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Müdürlük Paneli</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
        }

        header {
            background-color: #34495e;
            color: white;
            padding: 20px;
            text-align: center;
        }

        main {
            padding: 25px;
        }

        .kartlar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .kart {
            background-color: white;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 170px;
        }

        .kart h2 {
            font-size: 17px;
        }

        .kart p {
            font-size: 28px;
            font-weight: bold;
        }

        a {
            color: #34495e;
        }
    </style>
</head>
<body>

<header>
    <h1>Müdürlük Paneli</h1>
    <p>Bilgisayar Teknik Servis Sistemi</p>
</header>

<main>
    <h2>Servis Durumları</h2>

    <div class="kartlar">
        <div class="kart">
            <h2>Toplam Servis</h2>
            <p><?php echo $toplam; ?></p>
        </div>

        <div class="kart">
            <h2>Bekleyen Servis</h2>
            <p><?php echo $bekleyen; ?></p>
        </div>

        <div class="kart">
            <h2>Devam Eden Servis</h2>
            <p><?php echo $devamEden; ?></p>
        </div>

        <div class="kart">
            <h2>Tamamlanan Servis</h2>
            <p><?php echo $tamamlanan; ?></p>
        </div>
    </div>
    <p>
    <a href="servis-yonetimi.php">Servis Yönetimi</a>
</p>
    <p><a href="../cikis.php">Çıkış Yap</a></p>
</main>

</body>
</html>
