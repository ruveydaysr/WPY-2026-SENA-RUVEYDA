
<?php
session_start();

if (
    !isset($_SESSION["kullanici_id"]) ||
    ($_SESSION["rol"] ?? "") !== "yonetici"
) {
    header("Location: ../giris.php");
    exit();
}

require_once("../baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function guvenli($deger) {
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

$sql = "SELECT id, adsoyad, email, telefon, il, durum
        FROM kullanicilar
        WHERE rol IN ('teknisyen', 'teknik')
        ORDER BY il, adsoyad";

$sonuc = mysqli_query($baglanti, $sql);
$il_mesaji = $_SESSION["il_mesaji"] ?? "";
unset($_SESSION["il_mesaji"]);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teknisyen Yönetimi | TeknoServis</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="../index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav>
            <a href="yonetici-panel.php">Yönetici Paneli</a>
            <a href="teknisyen-yonetimi.php" aria-current="page">Teknisyen Yönetimi</a>
            <a href="../cikis.php">Çıkış Yap</a>
        </nav>
    </div>
</header>

<main>
    <h1>Teknisyen Yönetimi</h1>
    <p class="aciklama">
        Sistemde kayıtlı teknisyenleri ve görev yaptıkları illeri görüntüleyin.
    </p>
<?php if ($il_mesaji !== ""): ?>
    <p style="background:#e8f5e9; color:#205b28; padding:15px; border-radius:8px;">
        <?= guvenli($il_mesaji) ?>
    </p>
<?php endif; ?>
    <div class="servis-alani">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Telefon</th>
                    <th>Görev İli</th>
                    <th>Durum</th>
                    <th>İl Değiştir</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($teknisyen = mysqli_fetch_assoc($sonuc)): ?>
                    <tr>
                        <td><?= (int) $teknisyen["id"] ?></td>
                        <td><?= guvenli($teknisyen["adsoyad"]) ?></td>
                        <td><?= guvenli($teknisyen["email"]) ?></td>
                        <td><?= guvenli($teknisyen["telefon"]) ?></td>
                        <td><?= guvenli($teknisyen["il"] ?? "Belirtilmedi") ?></td>
                        <td><?= guvenli($teknisyen["durum"]) ?></td>
                        <td>
    <form action="teknisyen-il-guncelle.php" method="POST">
        <input type="hidden" name="teknisyen_id"
               value="<?= (int) $teknisyen["id"] ?>">

        <input type="hidden" name="csrf_token"
               value="<?= guvenli($_SESSION["csrf_token"]) ?>">

        <select name="il" required>
            <option value="">İl seçiniz</option>
            <?php foreach (["Ankara", "İstanbul", "İzmir", "Yozgat", "Kayseri", "Antalya", "Bursa", "Konya", "Adana"] as $il): ?>
                <option value="<?= guvenli($il) ?>"
                    <?= ($teknisyen["il"] === $il) ? "selected" : "" ?>>
                    <?= guvenli($il) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Kaydet</button>
    </form>
</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
