
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


$sorgu = "SELECT servisler.id, kullanicilar.adsoyad,
                 servisler.cihaz, servisler.marka,
                 servisler.model, servisler.ariza,
                 servisler.durum, servisler.tarih,
                 teknisyen.adsoyad AS teknisyen_adi
          FROM servisler
          INNER JOIN kullanicilar
          ON servisler.kullanici_id = kullanicilar.id
          LEFT JOIN kullanicilar AS teknisyen
          ON servisler.teknisyen_id = teknisyen.id
          ORDER BY servisler.id DESC";

$sonuc = mysqli_query($baglanti, $sorgu);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Servis Yönetimi</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #e5e5e5;
        }

        a {
            color: #34495e;
        }
    </style>
</head>
<body>

<header>
    <h1>Servis Yönetimi</h1>
    <p>Bilgisayar Teknik Servis Sistemi</p>
</header>

<main>
    <h2>Tüm Servis Kayıtları</h2>
    <input
    type="text"
    id="servisAra"
    placeholder="Müşteri adı veya servis no ara..."
    onkeyup="servisAra()"
    style="padding: 10px; width: 280px; margin-bottom: 15px;"
>
<select id="durumFiltre" onchange="servisFiltrele()" style="padding: 10px; margin-bottom: 15px;">
    <option value="">Tüm Servisler</option>
    <option value="Bekliyor">Bekleyen Servisler</option>
    <option value="devam">Devam Eden Servisler</option>
    <option value="tamam">Tamamlanan Servisler</option>
</select>
    <table id="servisTablosu">
        <tr>
            <th>Servis No</th>
            <th>Müşteri</th>
            <th>Cihaz</th>
            <th>Marka / Model</th>
            <th>Arıza</th>
            <th>Durum</th>
            <th>Atanan Teknisyen</th>
            <th>Tarih</th>
            <th>İşlem</th>
        </tr>

        <?php while ($servis = mysqli_fetch_assoc($sonuc)): ?>
        <tr>
            <td><?php echo (int)$servis["id"]; ?></td>
            <td><?php echo htmlspecialchars($servis["adsoyad"]); ?></td>
            <td><?php echo htmlspecialchars($servis["cihaz"]); ?></td>
            <td>
                <?php
                echo htmlspecialchars(
                    $servis["marka"] . " " . $servis["model"]
                );
                ?>
            </td>
            <td><?php echo htmlspecialchars($servis["ariza"]); ?></td>
            <td><?php echo htmlspecialchars($servis["durum"]); ?></td>
            <td>
    <?php
    echo htmlspecialchars(
        $servis["teknisyen_adi"] ?? "Henüz atanmadı",
        ENT_QUOTES,
        "UTF-8"
    );
    ?>
</td>
            <td><?php echo htmlspecialchars($servis["tarih"]); ?></td>
            <td>
    <a href="servis-detay.php?id=<?php echo (int)$servis['id']; ?>">
        Detay
    </a>
</td>
        </tr>
        <?php endwhile; ?>
    </table>

    <p><a href="mudur-panel.php">Müdürlük Paneline Dön</a></p>
</main>

<script>
function servisFiltrele() {
    let arama = document.getElementById("servisAra").value.toLocaleLowerCase("tr");
    let filtre = document.getElementById("durumFiltre").value;
    let satirlar = document.querySelectorAll("#servisTablosu tr");

    for (let i = 1; i < satirlar.length; i++) {
        let servisNo = satirlar[i].cells[0].textContent.toLocaleLowerCase("tr");
        let musteri = satirlar[i].cells[1].textContent.toLocaleLowerCase("tr");
        let durum = satirlar[i].cells[5].textContent.trim();

        let aramaUygun = servisNo.includes(arama) || musteri.includes(arama);
        let durumUygun = false;

        if (filtre === "") {
            durumUygun = true;
        } else if (filtre === "devam") {
            durumUygun = ["İnceleniyor", "İşlemde", "Parça Bekliyor"].includes(durum);
        } else if (filtre === "tamam") {
            durumUygun = ["Tamamlandı", "Teslim Edildi"].includes(durum);
        } else {
            durumUygun = durum === filtre;
        }

        satirlar[i].style.display =
            aramaUygun && durumUygun ? "" : "none";
    }
}

function servisAra() {
    servisFiltrele();
}
</script>

</body>
</html>
