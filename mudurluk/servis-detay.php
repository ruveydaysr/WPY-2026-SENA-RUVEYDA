
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

require_once("../baglanti.php");

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Geçersiz servis numarası.");
}

$sorgu = "SELECT s.*, k.adsoyad, k.email, k.telefon
          FROM servisler s
          INNER JOIN kullanicilar k
          ON s.kullanici_id = k.id
          WHERE s.id = ?";

$stmt = mysqli_prepare($baglanti, $sorgu);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$sonuc = mysqli_stmt_get_result($stmt);
$servis = mysqli_fetch_assoc($sonuc);
if (!$servis) {
    exit("Servis kaydı bulunamadı.");
}

$teknisyenler = mysqli_query(
    $baglanti,
    "SELECT id, adsoyad
     FROM kullanicilar
     WHERE rol IN ('teknisyen', 'teknik')
       AND durum = 'aktif'
     ORDER BY adsoyad"
);

function temizle($deger) {
    return htmlspecialchars(
        (string)($deger ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Servis Detayı</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
        }

        header {
            background-color: #34495e;
            color: white;
            text-align: center;
            padding: 20px;
        }

        main {
            max-width: 850px;
            margin: 25px auto;
            padding: 20px;
            background-color: white;
            border: 1px solid #ddd;
        }

        h2 {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .bilgi {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .bilgi strong {
            display: inline-block;
            min-width: 160px;
        }

        a {
            color: #34495e;
        }
    </style>
</head>

<body>

<header>
    <h1>Servis Detayı</h1>
    <p>Bilgisayar Teknik Servis Sistemi</p>
</header>

<main>
    <h2>Müşteri Bilgileri</h2>

    <div class="bilgi">
        <strong>Ad Soyad:</strong>
        <?php echo temizle($servis["adsoyad"]); ?>
    </div>

    <div class="bilgi">
        <strong>E-posta:</strong>
        <?php echo temizle($servis["email"]); ?>
    </div>

    <div class="bilgi">
        <strong>Telefon:</strong>
        <?php echo temizle($servis["telefon"]); ?>
    </div>

    <h2>Cihaz ve Servis Bilgileri</h2>

    <div class="bilgi">
        <strong>Servis No:</strong>
        <?php echo (int)$servis["id"]; ?>
    </div>

    <div class="bilgi">
        <strong>Cihaz:</strong>
        <?php echo temizle($servis["cihaz"]); ?>
    </div>

    <div class="bilgi">
        <strong>Marka:</strong>
        <?php echo temizle($servis["marka"]); ?>
    </div>

    <div class="bilgi">
        <strong>Model:</strong>
        <?php echo temizle($servis["model"]); ?>
    </div>

    <div class="bilgi">
        <strong>Arıza:</strong>
        <?php echo temizle($servis["ariza"]); ?>
    </div>

    <div class="bilgi">
        <strong>Durum:</strong>
        <?php echo temizle($servis["durum"]); ?>
    </div>

    <div class="bilgi">
        <strong>Kayıt Tarihi:</strong>
        <?php echo temizle($servis["tarih"]); ?>
    </div>

    <h2>Teknisyen İşlemleri</h2>

    <div class="bilgi">
        <strong>Arıza Tespiti:</strong>
        <?php echo temizle($servis["ariza_tespiti"]); ?>
    </div>

    <div class="bilgi">
        <strong>Yapılan İşlem:</strong>
        <?php echo temizle($servis["yapilan_islem"]); ?>
    </div>

    <div class="bilgi">
        <strong>Kullanılan Parça:</strong>
        <?php echo temizle($servis["kullanilan_parca"]); ?>
    </div>

    <div class="bilgi">
        <strong>Parça Ücreti:</strong>
        <?php echo number_format((float)$servis["parca_ucreti"], 2, ",", "."); ?> TL
    </div>

    <div class="bilgi">
        <strong>İşçilik Ücreti:</strong>
        <?php echo number_format((float)$servis["iscilik_ucreti"], 2, ",", "."); ?> TL
    </div>

    <div class="bilgi">
        <strong>Toplam Ücret:</strong>
        <?php
        $toplamUcret =
            (float)$servis["parca_ucreti"] +
            (float)$servis["iscilik_ucreti"];

        echo number_format($toplamUcret, 2, ",", ".");
        ?> TL
    </div>

<h2>Teknisyen Atama</h2>

<form action="teknisyen-ata.php" method="POST">
    <input
        type="hidden"
        name="servis_id"
        value="<?php echo (int)$servis['id']; ?>"
    >

    <label for="teknisyen_id">Teknisyen Seç:</label>

    <select name="teknisyen_id" id="teknisyen_id" required>
        <option value="">Teknisyen seçiniz</option>

        <?php while ($teknisyen = mysqli_fetch_assoc($teknisyenler)): ?>
            <option
                value="<?php echo (int)$teknisyen['id']; ?>"
                <?php
                if (
                    (int)($servis['teknisyen_id'] ?? 0) ===
                    (int)$teknisyen['id']
                ) {
                    echo "selected";
                }
                ?>
            >
                <?php
                echo temizle($teknisyen['adsoyad']);
                ?>
            </option>
        <?php endwhile; ?>
    </select>

    <button type="submit">Teknisyene Ata</button>
</form>

    <p>
        <a href="servis-yonetimi.php">Servis Yönetimine Dön</a>
    </p>
</main>

</body>
</html>
