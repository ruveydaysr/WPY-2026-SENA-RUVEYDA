<?php
session_start();

if (!isset($_SESSION["kullanici_id"])) {
    header("Location: giris.php");
    exit();
}

if (($_SESSION["rol"] ?? "") !== "musteri") {
    http_response_code(403);
    exit("Bu sayfaya erişiminiz yok.");
}

require_once(__DIR__ . "/../baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");

$id = (int) $_SESSION["kullanici_id"];
$limit = 5;

$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = max(1, $sayfa ?: 1);

// Kullanıcının toplam servis sayısı
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

// Yalnızca giriş yapan müşterinin servisleri
$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT id, cihaz, marka, model, ariza, durum, tarih
     FROM servisler
     WHERE kullanici_id = ?
     ORDER BY id DESC
     LIMIT ? OFFSET ?"
);
mysqli_stmt_bind_param($sorgu, "iii", $id, $limit, $baslangic);
mysqli_stmt_execute($sorgu);
$sonuc = mysqli_stmt_get_result($sorgu);

function guvenli($deger) {
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Takip | TeknoServis</title>
    <link rel="stylesheet" href="../style.css?v=4">

</head>
<body>

<header>
    <div class="ust-menu">
        <a href="../index.php" class="logo">Tekno<span>Servis</span></a>

        <nav aria-label="Müşteri menüsü">
            <a href="musteri-panel.php">Panelim</a>
            <a href="servis-olustur.php">Servis Oluştur</a>
            <a href="/teknik-servis/musteri/servis-takip.php">Servis Takip</a>
            <a href="profil.php">Profilim</a>
            <a href="cikis.php"
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
            <a href="servis-olustur.php" class="buton">Servis Oluştur</a>
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
                    </tr>
                </thead>

                <tbody>
                    <?php while ($servis = mysqli_fetch_assoc($sonuc)): ?>
                        <tr>
                            <td><?= (int) $servis["id"] ?></td>
                            <td><?= guvenli($servis["cihaz"]) ?></td>
                            <td>
                                <?= guvenli($servis["marka"]) ?>
                                <?= guvenli($servis["model"]) ?>
                            </td>
                            <td><?= guvenli($servis["ariza"]) ?></td>
                            <td><?= guvenli($servis["durum"]) ?></td>
                            <td><?= guvenli($servis["tarih"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($son_sayfa > 1): ?>
            <nav class="butonlar" aria-label="Servis sayfaları">
                <?php if ($sayfa > 1): ?>
                    <a class="buton" href="?sayfa=<?= $sayfa - 1 ?>">
                        Önceki
                    </a>
                <?php endif; ?>

                <span>Sayfa <?= $sayfa ?> / <?= $son_sayfa ?></span>

                <?php if ($sayfa < $son_sayfa): ?>
                    <a class="buton" href="?sayfa=<?= $sayfa + 1 ?>">
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