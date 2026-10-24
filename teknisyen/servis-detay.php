<?php
session_start();
require_once("../baglanti.php");

if (
    !isset($_SESSION["kullanici_id"]) ||
    !in_array($_SESSION["rol"] ?? "", ["teknisyen", "admin"], true)
) {
    http_response_code(403);
    exit("Bu sayfaya erişim yetkiniz yok.");
}


$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("Geçerli bir servis numarası belirtilmedi.");
}

$sorgu = "SELECT servisler.id,
                 kullanicilar.adsoyad,
                 servisler.cihaz,
                 servisler.marka,
                 servisler.model,
                 
servisler.ariza,
servisler.durum,
servisler.ariza_tespiti,
servisler.yapilan_islem,
servisler.kullanilan_parca,
servisler.parca_ucreti,
servisler.iscilik_ucreti

          FROM servisler
          INNER JOIN kullanicilar
          ON servisler.kullanici_id = kullanicilar.id
          WHERE servisler.id = ?
          AND servisler.teknisyen_id = ?";

$stmt = mysqli_prepare($baglanti, $sorgu);
$teknisyen_id = (int) $_SESSION["kullanici_id"];

mysqli_stmt_bind_param($stmt, "ii", $id, $teknisyen_id);
mysqli_stmt_execute($stmt);

$sonuc = mysqli_stmt_get_result($stmt);
$servis = mysqli_fetch_assoc($sonuc);

if (!$servis) {
    die("Servis kaydı bulunamadı.");
}

$gecmis_sql = "SELECT
                  servis_islem_gecmisi.islem_tarihi,
                  servis_islem_gecmisi.islem_aciklamasi,
                  servis_islem_gecmisi.eski_durum,
                  servis_islem_gecmisi.yeni_durum,
                  kullanicilar.adsoyad AS teknisyen_adi
               FROM servis_islem_gecmisi
               INNER JOIN kullanicilar
                   ON servis_islem_gecmisi.teknisyen_id = kullanicilar.id
               WHERE servis_islem_gecmisi.servis_id = ?
               ORDER BY servis_islem_gecmisi.islem_tarihi DESC,
                        servis_islem_gecmisi.id DESC";

$gecmis_stmt = mysqli_prepare($baglanti, $gecmis_sql);
mysqli_stmt_bind_param($gecmis_stmt, "i", $id);
mysqli_stmt_execute($gecmis_stmt);
$gecmis_sonuc = mysqli_stmt_get_result($gecmis_stmt);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Detayı</title>
    <link rel="stylesheet" href="teknisyen-style.css">
</head>
<body>

<div class="servis-detay">

    <h1>Servis Detayı</h1>

    <a href="atanan-servisler.php" class="geri-butonu">
        ← Atanan Servislere Dön
    </a>

    <div class="cihaz-bilgileri">
        <h2>Cihaz Bilgileri</h2>

        <p><strong>Servis No:</strong>
            <?= htmlspecialchars($servis["id"]) ?>
        </p>

        <p><strong>Müşteri:</strong>
            <?= htmlspecialchars($servis["adsoyad"]) ?>
        </p>

        <p><strong>Cihaz:</strong>
            <?= htmlspecialchars(
                $servis["cihaz"] . " - " .
                $servis["marka"] . " " .
                $servis["model"]
            ) ?>
        </p>

        <p><strong>Şikayet:</strong>
            <?= htmlspecialchars($servis["ariza"]) ?>
        </p>

        <p><strong>Durum:</strong>
            <?= htmlspecialchars($servis["durum"]) ?>
        </p>
    </div>

<h2>Teknisyen İşlemleri</h2>

<form action="servis-guncelle.php" method="POST">

    <input type="hidden" name="servis_id"
           value="<?= (int)$servis['id'] ?>">

    <label>Arıza Tespiti:</label>
    <textarea name="ariza_tespiti" rows="3"><?= htmlspecialchars($servis["ariza_tespiti"] ?? "") ?></textarea>

    <label>Yapılan İşlem:</label>
    <textarea name="yapilan_islem" rows="3"><?= htmlspecialchars($servis["yapilan_islem"] ?? "") ?></textarea>

    <label>Kullanılan Parça:</label>
    <textarea name="kullanilan_parca" rows="3"><?= htmlspecialchars($servis["kullanilan_parca"] ?? "") ?></textarea>

    <label>Parça Ücreti (TL):</label>
    <input type="number" name="parca_ucreti" min="0" step="0.01"
           value="<?= htmlspecialchars($servis["parca_ucreti"] ?? "0") ?>">

    <label>İşçilik Ücreti (TL):</label>
    <input type="number" name="iscilik_ucreti" min="0" step="0.01"
           value="<?= htmlspecialchars($servis["iscilik_ucreti"] ?? "0") ?>">

    <label>Servis Durumu:</label>
    <select name="durum">
        <?php foreach (["Bekliyor", "İnceleniyor", "İşlemde", "Tamamlandı", "Teslim Edildi"] as $durum): ?>
            <option value="<?= htmlspecialchars($durum) ?>"
                <?= $servis["durum"] === $durum ? "selected" : "" ?>>
                <?= htmlspecialchars($durum) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Değişiklikleri Kaydet</button>

</form>

<h2>Servis İşlem Geçmişi</h2>

<table border="1" cellpadding="10" cellspacing="0">
    <thead>
        <tr>
            <th>Tarih</th>
            <th>Teknisyen</th>
            <th>Yapılan İşlem</th>
            <th>Durum Değişikliği</th>
        </tr>
    </thead>

    <tbody>
        <?php if (mysqli_num_rows($gecmis_sonuc) > 0): ?>

            <?php while ($kayit = mysqli_fetch_assoc($gecmis_sonuc)): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($kayit["islem_tarihi"]) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($kayit["teknisyen_adi"]) ?>
                    </td>

                    <td>
                        <?= nl2br(htmlspecialchars($kayit["islem_aciklamasi"])) ?>
                    </td>

                    <td>
                        <?php if ($kayit["eski_durum"] !== $kayit["yeni_durum"]): ?>
                            <?= htmlspecialchars($kayit["eski_durum"]) ?>
                            →
                            <?= htmlspecialchars($kayit["yeni_durum"]) ?>
                        <?php else: ?>
                            Değişmedi
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>

        <?php else: ?>
            <tr>
                <td colspan="4">
                    Henüz işlem geçmişi bulunmuyor.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</div>

</body>
</html>