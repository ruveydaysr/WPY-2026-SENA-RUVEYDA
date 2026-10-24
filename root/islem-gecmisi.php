<?php
require_once(__DIR__ . "/root-kontrol.php");

function guvenli($deger) {
    return htmlspecialchars((string) ($deger ?? ""), ENT_QUOTES, "UTF-8");
}

function islemAdi($tur) {
    $adlar = [
        "durum_degistirme" => "Durum Değişikliği",
        "rol_degistirme" => "Rol Değişikliği"
    ];

    return $adlar[$tur] ?? $tur;
}

$limit = 5;
$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = max(1, $sayfa ?: 1);

// Toplam işlem sayısı
$sonuc = mysqli_query(
    $baglanti,
    "SELECT COUNT(*) AS toplam FROM islem_kayitlari"
);

if (!$sonuc) {
    http_response_code(500);
    exit("İşlem geçmişi yüklenemedi.");
}

$toplam = (int) mysqli_fetch_assoc($sonuc)["toplam"];
$son_sayfa = max(1, (int) ceil($toplam / $limit));
$sayfa = min($sayfa, $son_sayfa);
$baslangic = ($sayfa - 1) * $limit;

// En yeni işlemler önce listelenir.
$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT i.id, i.yapan_kullanici_id, i.hedef_kullanici_id,
            i.islem_turu, i.eski_deger, i.yeni_deger, i.islem_tarihi,
            yapan.adsoyad AS yapan_ad,
            hedef.adsoyad AS hedef_ad
     FROM islem_kayitlari i
     LEFT JOIN kullanicilar yapan ON i.yapan_kullanici_id = yapan.id
     LEFT JOIN kullanicilar hedef ON i.hedef_kullanici_id = hedef.id
     ORDER BY i.islem_tarihi DESC, i.id DESC
     LIMIT ? OFFSET ?"
);

mysqli_stmt_bind_param($sorgu, "ii", $limit, $baslangic);
mysqli_stmt_execute($sorgu);
$sonuc = mysqli_stmt_get_result($sorgu);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İşlem Geçmişi | TeknoServis</title>
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
            <a href="/teknik-servis/root/kullanicilar.php">
                Kullanıcı Yönetimi
            </a>
            <a href="/teknik-servis/root/islem-gecmisi.php"
               aria-current="page">İşlem Geçmişi</a>
            <a href="/teknik-servis/musteri/cikis.php"
               onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                Çıkış Yap
            </a>
        </nav>
    </div>
</header>

<main>
    <h1>İşlem Geçmişi</h1>

    <p class="aciklama">
        Kullanıcıların rol ve hesap durumu değişikliklerini
        buradan takip edebilirsiniz.
    </p>

    <p>Toplam <?= $toplam ?> işlem kaydı bulunuyor.</p>

    <?php if ($toplam === 0): ?>
        <section class="kart">
            <p>Henüz işlem kaydı bulunmuyor.</p>
        </section>
    <?php else: ?>
        <div class="servis-alani">
            <table>
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">İşlemi Yapan</th>
                        <th scope="col">İşlem Yapılan Kullanıcı</th>
                        <th scope="col">İşlem Türü</th>
                        <th scope="col">Eski Değer</th>
                        <th scope="col">Yeni Değer</th>
                        <th scope="col">Tarih ve Saat</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($kayit = mysqli_fetch_assoc($sonuc)): ?>
                        <tr>
                            <td><?= (int) $kayit["id"] ?></td>

                            <td>
                                <?= guvenli($kayit["yapan_ad"] ?? "Silinmiş Kullanıcı") ?>
                                (ID: <?= guvenli($kayit["yapan_kullanici_id"]) ?>)
                            </td>

                            <td>
                                <?= guvenli($kayit["hedef_ad"] ?? "Silinmiş Kullanıcı") ?>
                                (ID: <?= guvenli($kayit["hedef_kullanici_id"]) ?>)
                            </td>

                            <td>
                                <?= guvenli(islemAdi($kayit["islem_turu"])) ?>
                            </td>

                            <td><?= guvenli($kayit["eski_deger"]) ?></td>
                            <td><?= guvenli($kayit["yeni_deger"]) ?></td>
                            <td><?= guvenli($kayit["islem_tarihi"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($son_sayfa > 1): ?>
            <nav class="butonlar" aria-label="İşlem geçmişi sayfaları">
                <?php if ($sayfa > 1): ?>
                    <a class="buton"
                       href="/teknik-servis/root/islem-gecmisi.php?sayfa=<?= $sayfa - 1 ?>">
                        Önceki
                    </a>
                <?php endif; ?>

                <span>Sayfa <?= $sayfa ?> / <?= $son_sayfa ?></span>

                <?php if ($sayfa < $son_sayfa): ?>
                    <a class="buton"
                       href="/teknik-servis/root/islem-gecmisi.php?sayfa=<?= $sayfa + 1 ?>">
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