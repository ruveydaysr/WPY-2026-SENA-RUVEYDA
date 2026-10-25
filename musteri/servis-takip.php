<?php
require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["musteri"]);
// Ücret onay işlemleri için güvenlik anahtarı
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function guvenli($deger) {
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

$id = (int) $_SESSION["kullanici_id"];
$limit = 5;

$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = max(1, $sayfa ?: 1);

// Müşterinin toplam servis sayısı
$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT COUNT(*) FROM servisler WHERE kullanici_id = ?"
);
mysqli_stmt_bind_param($sorgu, "i", $id);
mysqli_stmt_execute($sorgu);
mysqli_stmt_bind_result($sorgu, $toplam);
mysqli_stmt_fetch($sorgu);
mysqli_stmt_close($sorgu);

$son_sayfa = max(1, (int) ceil($toplam / $limit));
$sayfa = min($sayfa, $son_sayfa);
$baslangic = ($sayfa - 1) * $limit;

// Yalnızca giriş yapan müşterinin kayıtları
$sorgu = mysqli_prepare(
    $baglanti,
"SELECT id, cihaz, marka, model, ariza, durum, tarih,
        tahmini_ucret, ucret_onay_durumu
 FROM servisler
 WHERE kullanici_id = ?
 ORDER BY id DESC
 LIMIT ? OFFSET ?"
);
mysqli_stmt_bind_param($sorgu, "iii", $id, $limit, $baslangic);
mysqli_stmt_execute($sorgu);
$sonuc = mysqli_stmt_get_result($sorgu);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Takip | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="/teknik-servis/index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav aria-label="Müşteri menüsü">
            <a href="/teknik-servis/musteri/musteri-panel.php">Panelim</a>
            <a href="/teknik-servis/musteri/servis-olustur.php">
                Servis Oluştur
            </a>
            <a href="/teknik-servis/musteri/servis-takip.php"
               aria-current="page">Servis Takip</a>
            <a href="/teknik-servis/musteri/profil.php">Profilim</a>
            <a href="/teknik-servis/musteri/cikis.php"
               onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                Çıkış Yap
            </a>
        </nav>
    </div>
</header>

<main>
    <h1>Servis Kayıtlarım</h1>

    <p class="aciklama">
        Toplam <?= (int) $toplam ?> servis kaydınız bulunuyor.
    </p>

    <?php if ($toplam == 0): ?>
        <section class="kart">
            <p>Henüz servis kaydı oluşturmadınız.</p>
            <a href="/teknik-servis/musteri/servis-olustur.php"
               class="buton">Servis Oluştur</a>
        </section>
    <?php else: ?>
        <div class="servis-alani">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Servis No</th>
                        <th scope="col">Cihaz</th>
                        <th scope="col">Marka / Model</th>
                        <th scope="col">Arıza</th>
                        <th scope="col">Durum</th>
                        <th scope="col">Tarih</th>
                        <th scope="col">Tahmini Ücret</th>
                        <th scope="col">Ücret Onayı</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($servis = mysqli_fetch_assoc($sonuc)): ?>
                        <tr>
                            <td><?= (int) $servis["id"] ?></td>
                            <td><?= guvenli($servis["cihaz"]) ?></td>
                            <td>
                                <?= guvenli($servis["marka"] . " " . $servis["model"]) ?>
                            </td>
                            <td><?= guvenli($servis["ariza"]) ?></td>
                            <td><?= guvenli($servis["durum"]) ?></td>
                            <td><?= guvenli($servis["tarih"]) ?></td>
                            
<td>
    <?php if ($servis["tahmini_ucret"] !== null): ?>
        <?= number_format((float)$servis["tahmini_ucret"], 2, ",", ".") ?> TL
    <?php else: ?>
        Henüz belirlenmedi
    <?php endif; ?>
</td>

<td>
    
<?php if ($servis["ucret_onay_durumu"] === "Bekliyor"): ?>

    <form action="ucret-onay-islem.php" method="POST">
        <input type="hidden" name="servis_id"
               value="<?= (int)$servis["id"] ?>">

        <input type="hidden" name="csrf_token"
               value="<?= guvenli($_SESSION["csrf_token"]) ?>">

        <button type="submit" name="karar" value="Onaylandı">
            Onayla
        </button>

        <button type="submit" name="karar" value="Reddedildi">
            Reddet
        </button>
    </form>

    <?php elseif ($servis["ucret_onay_durumu"] === "Onaylandı"): ?>
        <span>Onaylandı</span>
    <?php elseif ($servis["ucret_onay_durumu"] === "Reddedildi"): ?>
        <span>Reddedildi</span>
    <?php else: ?>
        <span>Teklif Yok</span>
    <?php endif; ?>
</td>

                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($son_sayfa > 1): ?>
            <nav class="butonlar" aria-label="Servis sayfaları">
                <?php if ($sayfa > 1): ?>
                    <a class="buton"
                       href="/teknik-servis/musteri/servis-takip.php?sayfa=<?= $sayfa - 1 ?>">
                        Önceki
                    </a>
                <?php endif; ?>

                <span>Sayfa <?= $sayfa ?> / <?= $son_sayfa ?></span>

                <?php if ($sayfa < $son_sayfa): ?>
                    <a class="buton"
                       href="/teknik-servis/musteri/servis-takip.php?sayfa=<?= $sayfa + 1 ?>">
                        Sonraki
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

</body>
</html>

<?php mysqli_stmt_close($sorgu); ?>