
<?php
session_start();
require_once("../baglanti.php");

// Teknisyen ve admin yetki kontrolü
if (
    !isset($_SESSION["kullanici_id"]) ||
    !in_array($_SESSION["rol"] ?? "", ["teknisyen", "admin"], true)
) {
    http_response_code(403);
    exit("Bu sayfaya erişim yetkiniz yok.");
}

 // Sayfalama ayarları
$limit = 5;

$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = ($sayfa && $sayfa > 0) ? $sayfa : 1;

$teknisyen_id = (int) $_SESSION["kullanici_id"];

// Devam eden toplam servis sayısı
$sayac = mysqli_prepare(
    $baglanti,
    "SELECT COUNT(*)
     FROM servisler
     WHERE teknisyen_id = ?
     AND durum IN ('İnceleniyor', 'İşlemde', 'Parça Bekliyor')"
);

mysqli_stmt_bind_param($sayac, "i", $teknisyen_id);
mysqli_stmt_execute($sayac);
mysqli_stmt_bind_result($sayac, $toplam_servis);
mysqli_stmt_fetch($sayac);
mysqli_stmt_close($sayac);

// Toplam sayfa sayısı
$toplam_sayfa = max(1, (int) ceil($toplam_servis / $limit));
$sayfa = min($sayfa, $toplam_sayfa);
$baslangic = ($sayfa - 1) * $limit;

// Yalnızca ilgili sayfanın kayıtlarını getir
$sql = "SELECT
            servisler.id,
            kullanicilar.adsoyad,
            servisler.cihaz,
            servisler.marka,
            servisler.model,
            servisler.ariza,
            servisler.durum
        FROM servisler
        INNER JOIN kullanicilar
            ON servisler.kullanici_id = kullanicilar.id
        WHERE servisler.teknisyen_id = ?
        AND servisler.durum IN (
            'İnceleniyor',
            'İşlemde',
            'Parça Bekliyor'
        )
        ORDER BY servisler.id DESC
        LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($baglanti, $sql);
mysqli_stmt_bind_param(
    $stmt,
    "iii",
    $teknisyen_id,
    $limit,
    $baslangic
);

mysqli_stmt_execute($stmt);
$sonuc = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devam Eden Servisler | Bilgisayar Teknik Servis</title>

    <link rel="stylesheet" href="teknisyen-style.css">
</head>

<body>

    <div class="teknisyen-panel">

        <h1>Devam Eden Servisler</h1>
        
<a href="teknisyen-panel.php" class="geri-butonu">
    ← Teknisyen Paneline Dön
</a>

        <p>İşlemi devam eden servis kayıtlarını aşağıdan görüntüleyebilirsiniz.</p>

       
         
<table>
    <tr>
        <th>Servis No</th>
        <th>Müşteri</th>
        <th>Cihaz</th>
        <th>Şikayet</th>
        <th>Durum</th>
        <th>İşlem</th>
    </tr>

    <?php if (mysqli_num_rows($sonuc) > 0): ?>

        <?php while ($servis = mysqli_fetch_assoc($sonuc)): ?>
            <tr>
                <td>
                    SRV-<?= str_pad($servis["id"], 3, "0", STR_PAD_LEFT) ?>
                </td>

                <td>
                    <?= htmlspecialchars($servis["adsoyad"]) ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $servis["cihaz"] . " - " .
                        $servis["marka"] . " " .
                        $servis["model"]
                    ) ?>
                </td>

                <td>
                    <?= htmlspecialchars($servis["ariza"]) ?>
                </td>

                <td>
                    <?= htmlspecialchars($servis["durum"]) ?>
                </td>

                <td>
                    <a href="servis-detay.php?id=<?= (int)$servis["id"] ?>">
                        <button type="button">Detay</button>
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>

    <?php else: ?>
        <tr>
            <td colspan="6">
                Şu anda devam eden servis kaydı bulunmuyor.
            </td>
        </tr>
    <?php endif; ?>
</table>

<div style="margin-top: 25px; display: flex; align-items: center; gap: 12px;">

    <?php if ($sayfa > 1): ?>
        <a href="?sayfa=<?= $sayfa - 1 ?>" class="geri-butonu">
            Önceki
        </a>
    <?php endif; ?>

    <span>Sayfa <?= $sayfa ?> / <?= $toplam_sayfa ?></span>

    <?php if ($sayfa < $toplam_sayfa): ?>
        <a href="?sayfa=<?= $sayfa + 1 ?>" class="geri-butonu">
            Sonraki
        </a>
    <?php endif; ?>

</div>

    </div>

</body>

</html>