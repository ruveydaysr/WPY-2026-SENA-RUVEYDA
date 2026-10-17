
<?php
session_start();
require_once("../baglanti.php");

// Yalnızca teknisyen ve admin erişebilir
if (
    !isset($_SESSION["kullanici_id"]) ||
    !in_array($_SESSION["rol"] ?? "", ["teknisyen", "admin"], true)
) {
    http_response_code(403);
    exit("Bu sayfaya erişim yetkiniz yok.");
}


// Veritabanından servis kayıtlarını getir

$teknisyen_id = (int) $_SESSION["kullanici_id"];

$sql = "SELECT
            servisler.id,
            kullanicilar.adsoyad,
            servisler.cihaz,
            servisler.marka,
            servisler.model,
            servisler.ariza,
            servisler.durum,
            servisler.tarih
        FROM servisler
        INNER JOIN kullanicilar
        ON servisler.kullanici_id = kullanicilar.id
        WHERE servisler.teknisyen_id = ?
        ORDER BY servisler.id DESC";

$stmt = mysqli_prepare($baglanti, $sql);
mysqli_stmt_bind_param($stmt, "i", $teknisyen_id);
mysqli_stmt_execute($stmt);
$sonuc = mysqli_stmt_get_result($stmt);


if (!$sonuc) {
    die("Servis kayıtları alınamadı.");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atanan Servisler</title>
    <link rel="stylesheet" href="teknisyen-style.css">
</head>
<body>

<div class="teknisyen-panel">

    <h1>Atanan Servisler</h1>
   
<a href="teknisyen-panel.php" class="geri-butonu">
    ← Teknisyen Paneline Dön
</a>


    <table>
        <thead>
            <tr>
                <th>Servis No</th>
                <th>Müşteri</th>
                <th>Cihaz</th>
                <th>Arıza</th>
                <th>Durum</th>
                <th>Tarih</th>
                <th>İşlem</th>
            </tr>
        </thead>

        <tbody>
            <?php if (mysqli_num_rows($sonuc) > 0): ?>
                <?php while ($servis = mysqli_fetch_assoc($sonuc)): ?>
                    <tr>
                        <td><?php echo (int)$servis["id"]; ?></td>
                        <td><?php echo htmlspecialchars($servis["adsoyad"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($servis["marka"] . " " . $servis["model"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($servis["ariza"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($servis["durum"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($servis["tarih"], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
    <a href="servis-detay.php?id=<?php echo (int)$servis['id']; ?>"
       class="geri-butonu">
        Detay
    </a>
</td>
                   
                    </tr>

                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">Henüz servis kaydı bulunmuyor.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>