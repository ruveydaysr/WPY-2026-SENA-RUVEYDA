<?php
require_once(__DIR__ . "/root-kontrol.php");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function guvenli($deger) {
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

$arama = trim((string) ($_GET["arama"] ?? ""));
$aranan = "%" . $arama . "%";
$limit = 5;

$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = max(1, $sayfa ?: 1);

// Aramaya uyan kullanıcı sayısı
$stmt = mysqli_prepare(
    $baglanti,
    "SELECT COUNT(*) FROM kullanicilar
     WHERE adsoyad LIKE ? OR email LIKE ?"
);
mysqli_stmt_bind_param($stmt, "ss", $aranan, $aranan);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $toplam);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$son_sayfa = max(1, (int) ceil($toplam / $limit));
$sayfa = min($sayfa, $son_sayfa);
$baslangic = ($sayfa - 1) * $limit;

// Kullanıcı listesi
$stmt = mysqli_prepare(
    $baglanti,
    "SELECT id, adsoyad, email, telefon, rol, durum
     FROM kullanicilar
     WHERE adsoyad LIKE ? OR email LIKE ?
     ORDER BY id DESC LIMIT ? OFFSET ?"
);
mysqli_stmt_bind_param(
    $stmt, "ssii", $aranan, $aranan, $limit, $baslangic
);
mysqli_stmt_execute($stmt);
$sonuc = mysqli_stmt_get_result($stmt);

$roller = [
    "musteri" => "Müşteri",
    "teknisyen" => "Teknisyen",
    "teknik" => "Teknik Ekip",
    "mudur" => "Müdür"
];

function sayfaYolu($sayfa, $arama) {
    return "/teknik-servis/root/kullanicilar.php?" .
        http_build_query(["sayfa" => $sayfa, "arama" => $arama]);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="/teknik-servis/index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav aria-label="Root menüsü">
            <a href="/teknik-servis/root/root-panel.php">Panelim</a>
            <a href="/teknik-servis/root/kullanicilar.php"
               aria-current="page">Kullanıcı Yönetimi</a>
            <a href="/teknik-servis/root/islem-gecmisi.php">İşlem Geçmişi</a>
            <a href="/teknik-servis/musteri/cikis.php"
               onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                Çıkış Yap
            </a>
        </nav>
    </div>
</header>

<main>
    <h1>Kullanıcı Yönetimi</h1>
    <p class="aciklama">
        Kullanıcı rollerini ve hesap durumlarını yönetebilirsiniz.
        Root hesapları bu ekrandan değiştirilemez.
    </p>

    <form method="GET" action="/teknik-servis/root/kullanicilar.php"
          class="kart">
        <label for="arama">Ad soyad veya e-posta</label>
        <input type="search" id="arama" name="arama"
               value="<?= guvenli($arama) ?>" maxlength="100">

        <div class="butonlar">
            <button type="submit">Ara</button>
            <a href="/teknik-servis/root/kullanicilar.php"
               class="buton ikincil">Temizle</a>
        </div>
    </form>

    <p>Toplam <?= (int) $toplam ?> kullanıcı bulundu.</p>

    <div class="servis-alani">
        <table>
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Ad Soyad</th>
                    <th scope="col">E-posta</th>
                    <th scope="col">Telefon</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Durum</th>
                    <th scope="col">Rol Değiştir</th>
                    <th scope="col">Hesap İşlemi</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($toplam == 0): ?>
                    <tr>
                        <td colspan="8">Kullanıcı bulunamadı.</td>
                    </tr>
                <?php endif; ?>

                <?php while ($kisi = mysqli_fetch_assoc($sonuc)): ?>
                    <tr>
                        <td><?= (int) $kisi["id"] ?></td>
                        <td><?= guvenli($kisi["adsoyad"]) ?></td>
                        <td><?= guvenli($kisi["email"]) ?></td>
                        <td><?= guvenli($kisi["telefon"]) ?></td>
                        <td>
                            <?= guvenli($roller[$kisi["rol"]] ?? $kisi["rol"]) ?>
                        </td>
                        <td><?= guvenli($kisi["durum"]) ?></td>

                        <td>
                            <?php if ($kisi["rol"] === "root"): ?>
                                Root — Değiştirilemez
                            <?php else: ?>
                                <form action="/teknik-servis/root/kullanici-rol.php"
                                      method="POST"
                                      onsubmit="return confirm('Kullanıcının rolünü değiştirmek istiyor musunuz?')">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= guvenli($_SESSION["csrf_token"]) ?>">
                                    <input type="hidden" name="kullanici_id"
                                           value="<?= (int) $kisi["id"] ?>">

                                    <select name="yeni_rol"
                                            aria-label="<?= guvenli($kisi["adsoyad"]) ?> için yeni rol"
                                            required>
                                        <option value="">Rol seçin</option>
                                        <?php foreach ($roller as $deger => $baslik): ?>
                                            <option value="<?= guvenli($deger) ?>"
                                                <?= $kisi["rol"] === $deger ? "selected" : "" ?>>
                                                <?= guvenli($baslik) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <div class="butonlar">
                                        <button type="submit">Rolü Kaydet</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($kisi["rol"] === "root"): ?>
                                Root — İşlem yapılamaz
                            <?php elseif (in_array($kisi["durum"], ["aktif", "pasif"], true)): ?>
                                <form action="/teknik-servis/root/kullanici-durum.php"
                                      method="POST"
                                      onsubmit="return confirm('Kullanıcının hesap durumunu değiştirmek istiyor musunuz?')">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= guvenli($_SESSION["csrf_token"]) ?>">
                                    <input type="hidden" name="kullanici_id"
                                           value="<?= (int) $kisi["id"] ?>">

                                    <button type="submit">
                                        <?= $kisi["durum"] === "aktif" ? "Pasif Yap" : "Aktif Yap" ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                Bilinmeyen durum
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($son_sayfa > 1): ?>
        <nav class="butonlar" aria-label="Kullanıcı sayfaları">
            <?php if ($sayfa > 1): ?>
                <a class="buton"
                   href="<?= guvenli(sayfaYolu($sayfa - 1, $arama)) ?>">
                    Önceki
                </a>
            <?php endif; ?>

            <span>Sayfa <?= $sayfa ?> / <?= $son_sayfa ?></span>

            <?php if ($sayfa < $son_sayfa): ?>
                <a class="buton"
                   href="<?= guvenli(sayfaYolu($sayfa + 1, $arama)) ?>">
                    Sonraki
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</main>

</body>
</html>

<?php mysqli_stmt_close($stmt); ?>