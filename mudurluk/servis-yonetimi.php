<?php
require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["mudur"]);

function temizle($deger) {
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

$arama = trim((string) ($_GET["arama"] ?? ""));
$durum = (string) ($_GET["durum"] ?? "");
$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = max(1, $sayfa ?: 1);
$limit = 5;

if (!in_array($durum, ["", "Bekliyor", "devam", "tamam"], true)) {
    $durum = "";
}

$kosul = " WHERE (k.adsoyad LIKE ? OR CAST(s.id AS CHAR) = ?)";

if ($durum === "Bekliyor") {
    $kosul .= " AND s.durum = 'Bekliyor'";
} elseif ($durum === "devam") {
    $kosul .= " AND s.durum IN ('İnceleniyor', 'İşlemde', 'Parça Bekliyor')";
} elseif ($durum === "tamam") {
    $kosul .= " AND s.durum IN ('Tamamlandı', 'Teslim Edildi')";
}

$aranan = "%" . $arama . "%";
$tablolar = " FROM servisler s
             INNER JOIN kullanicilar k ON s.kullanici_id = k.id";

// Aramaya uyan servis sayısı
$stmt = mysqli_prepare(
    $baglanti,
    "SELECT COUNT(*)" . $tablolar . $kosul
);
mysqli_stmt_bind_param($stmt, "ss", $aranan, $arama);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $toplam);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$son_sayfa = max(1, (int) ceil($toplam / $limit));
$sayfa = min($sayfa, $son_sayfa);
$baslangic = ($sayfa - 1) * $limit;

// Sayfada gösterilecek servisler
$sql = "SELECT s.id, k.adsoyad, s.cihaz, s.marka, s.model,
               s.ariza, s.durum, s.tarih,
               t.adsoyad AS teknisyen_adi"
       . $tablolar .
       " LEFT JOIN kullanicilar t ON s.teknisyen_id = t.id"
       . $kosul .
       " ORDER BY s.id DESC LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($baglanti, $sql);
mysqli_stmt_bind_param(
    $stmt, "ssii", $aranan, $arama, $limit, $baslangic
);
mysqli_stmt_execute($stmt);
$sonuc = mysqli_stmt_get_result($stmt);

function sayfaYolu($sayfa, $arama, $durum) {
    return "/teknik-servis/mudurluk/servis-yonetimi.php?" .
        http_build_query([
            "sayfa" => $sayfa,
            "arama" => $arama,
            "durum" => $durum
        ]);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Yönetimi | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="/teknik-servis/index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav aria-label="Müdürlük menüsü">
            <a href="/teknik-servis/mudurluk/mudur-panel.php">Panelim</a>
            <a href="/teknik-servis/mudurluk/servis-yonetimi.php"
               aria-current="page">Servis Yönetimi</a>
            <a href="/teknik-servis/musteri/cikis.php"
               onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                Çıkış Yap
            </a>
        </nav>
    </div>
</header>

<main>
    <h1>Servis Yönetimi</h1>

    <form method="GET"
          action="/teknik-servis/mudurluk/servis-yonetimi.php"
          class="kart">

        <label for="arama">Müşteri adı veya servis numarası</label>
        <input type="search" id="arama" name="arama"
               value="<?= temizle($arama) ?>"
               placeholder="Müşteri adı veya tam servis numarası">

        <label for="durum">Servis Durumu</label>
        <select id="durum" name="durum">
            <option value="">Tüm Servisler</option>
            <option value="Bekliyor"
                <?= $durum === "Bekliyor" ? "selected" : "" ?>>
                Bekleyen Servisler
            </option>
            <option value="devam"
                <?= $durum === "devam" ? "selected" : "" ?>>
                Devam Eden Servisler
            </option>
            <option value="tamam"
                <?= $durum === "tamam" ? "selected" : "" ?>>
                Tamamlanan Servisler
            </option>
        </select>

        <div class="butonlar">
            <button type="submit">Ara / Filtrele</button>
            <a href="/teknik-servis/mudurluk/servis-yonetimi.php"
               class="buton ikincil">Temizle</a>
        </div>
    </form>

    <p>Toplam <?= (int) $toplam ?> servis bulundu.</p>

    <?php if ($toplam == 0): ?>
        <p class="kart">Uygun servis kaydı bulunamadı.</p>
    <?php else: ?>
        <div class="servis-alani">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Servis No</th>
                        <th scope="col">Müşteri</th>
                        <th scope="col">Cihaz</th>
                        <th scope="col">Marka / Model</th>
                        <th scope="col">Arıza</th>
                        <th scope="col">Durum</th>
                        <th scope="col">Teknisyen</th>
                        <th scope="col">Tarih</th>
                        <th scope="col">İşlem</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($servis = mysqli_fetch_assoc($sonuc)): ?>
                        <tr>
                            <td><?= (int) $servis["id"] ?></td>
                            <td><?= temizle($servis["adsoyad"]) ?></td>
                            <td><?= temizle($servis["cihaz"]) ?></td>
                            <td>
                                <?= temizle($servis["marka"] . " " . $servis["model"]) ?>
                            </td>
                            <td><?= temizle($servis["ariza"]) ?></td>
                            <td><?= temizle($servis["durum"]) ?></td>
                            <td>
                                <?= temizle($servis["teknisyen_adi"] ?? "Henüz atanmadı") ?>
                            </td>
                            <td><?= temizle($servis["tarih"]) ?></td>
                            <td>
                                <a href="/teknik-servis/mudurluk/servis-detay.php?id=<?= (int) $servis["id"] ?>">
                                    Detay
                                </a>
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
                       href="<?= temizle(sayfaYolu($sayfa - 1, $arama, $durum)) ?>">
                        Önceki
                    </a>
                <?php endif; ?>

                <span>Sayfa <?= $sayfa ?> / <?= $son_sayfa ?></span>

                <?php if ($sayfa < $son_sayfa): ?>
                    <a class="buton"
                       href="<?= temizle(sayfaYolu($sayfa + 1, $arama, $durum)) ?>">
                        Sonraki
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

</body>
</html>

<?php mysqli_stmt_close($stmt); ?>