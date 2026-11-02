
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

// Yalnızca giriş yapan müşterinin servis kayıtları
// Şube ve teknisyen bilgileri de getiriliyor.
$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT
        s.id,
        s.cihaz,
        s.marka,
        s.model,
        s.ariza,
        s.durum,
        s.tarih,
        s.hizmet_turu,
        s.il,
        s.servis_noktasi_id,
        s.teslim_durumu,
        s.tahmini_ucret,
        s.ucret_onay_durumu,
        sn.sube_adi,
        sn.ilce AS sube_ilce,
        sn.adres AS sube_adres,
        t.adsoyad AS teknisyen_adi
     FROM servisler AS s
     LEFT JOIN servis_noktalari AS sn
        ON sn.id = s.servis_noktasi_id
     LEFT JOIN kullanicilar AS t
        ON t.id = s.teknisyen_id
     WHERE s.kullanici_id = ?
     ORDER BY s.id DESC
     LIMIT ? OFFSET ?"
);

mysqli_stmt_bind_param(
    $sorgu,
    "iii",
    $id,
    $limit,
    $baslangic
);

mysqli_stmt_execute($sorgu);
$sonuc = mysqli_stmt_get_result($sorgu);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Servis Takip | TeknoServis</title>

    <link rel="stylesheet"
          href="/teknik-servis/style.css?v=4">
          
<style>
    .servis-alani {
        width: 100%;
        overflow-x: auto;
        margin-top: 20px;
        border-radius: 10px;
    }

    .servis-alani table {
        width: 100%;
        min-width: 1500px;
        border-collapse: collapse;
    }

    .servis-alani th,
    .servis-alani td {
        padding: 14px 12px;
        min-width: 110px;
        vertical-align: middle;
        word-break: normal;
        overflow-wrap: normal;
        line-height: 1.6;
    }

    .servis-alani th:nth-child(3),
    .servis-alani td:nth-child(3) {
        min-width: 220px;
    }

    .servis-alani th:nth-child(8),
    .servis-alani td:nth-child(8) {
        min-width: 190px;
    }

    .servis-alani th:nth-child(10),
    .servis-alani td:nth-child(10) {
        min-width: 145px;
    }
</style>

</head>

<body>

<?php require_once(__DIR__ . "/../ortak-menu.php"); ?>

<main>

    <h1>Servis Kayıtlarım</h1>

    <p class="aciklama">
        Toplam <?= (int) $toplam ?> servis kaydınız bulunuyor.
    </p>

    <?php if ($toplam == 0): ?>

        <section class="kart">

            <p>Henüz servis kaydı oluşturmadınız.</p>

            <a href="/teknik-servis/musteri/servis-olustur.php"
               class="buton">
                Servis Oluştur
            </a>

        </section>

    <?php else: ?>

        <div class="servis-alani">

            <table>

                <thead>
                    <tr>
                        <th scope="col">Servis No</th>
                        <th scope="col">Hizmet Türü</th>
                        <th scope="col">Teslim Şubesi</th>
                        <th scope="col">Teslim Durumu</th>
                        <th scope="col">Atanan Teknisyen</th>
                        <th scope="col">Cihaz</th>
                        <th scope="col">Marka / Model</th>
                        <th scope="col">Arıza</th>
                        <th scope="col">Servis Durumu</th>
                        <th scope="col">Tarih</th>
                        <th scope="col">Tahmini Ücret</th>
                        <th scope="col">Ücret Onayı</th>
                    </tr>
                </thead>

                <tbody>

                <?php while ($servis = mysqli_fetch_assoc($sonuc)): ?>

                    <tr>

                        <!-- SERVİS NUMARASI -->
                        <td>
                            <?= (int) $servis["id"] ?>
                        </td>

                        <!-- HİZMET TÜRÜ -->
                        <td>
                            <?php if ($servis["hizmet_turu"] === "Online"): ?>

                                Online Teknik Destek

                            <?php else: ?>

                                Elden Teslim

                            <?php endif; ?>
                        </td>

                        <!-- TESLİM ŞUBESİ -->
                        <td>

                            <?php if ($servis["hizmet_turu"] === "Online"): ?>

                                Şube gerekmiyor.

                            <?php elseif (!empty($servis["sube_adi"])): ?>

                                <strong>
                                    <?= guvenli($servis["sube_adi"]) ?>
                                </strong>

                                <p>
                                    <?= guvenli($servis["sube_ilce"]) ?>
                                    /
                                    <?= guvenli($servis["il"]) ?>
                                </p>

                                <p>
                                    <?= guvenli($servis["sube_adres"]) ?>
                                </p>

                            <?php else: ?>

                                Şube belirtilmedi.

                            <?php endif; ?>

                        </td>

                        <!-- TESLİM DURUMU -->
                        <td>

                            <?php if ($servis["hizmet_turu"] === "Online"): ?>

                                Fiziksel teslim gerekmiyor.

                            <?php elseif ($servis["teslim_durumu"] === "Teslim Alındı"): ?>

                                <strong>✓ Teslim Alındı</strong>

                            <?php else: ?>

                                Teslim Bekleniyor

                            <?php endif; ?>

                        </td>

                        <!-- ATANAN TEKNİSYEN -->
                        <td>

                            <?php if (!empty($servis["teknisyen_adi"])): ?>

                                <?= guvenli($servis["teknisyen_adi"]) ?>

                            <?php else: ?>

                                Henüz teknisyen atanmadı.

                            <?php endif; ?>

                        </td>

                        <!-- CİHAZ -->
                        <td>
                            <?= guvenli($servis["cihaz"]) ?>
                        </td>

                        <!-- MARKA / MODEL -->
                        <td>
                            <?= guvenli(
                                $servis["marka"] . " " . $servis["model"]
                            ) ?>
                        </td>

                        <!-- ARIZA -->
                        <td>
                            <?= guvenli($servis["ariza"]) ?>
                        </td>

                        <!-- SERVİS DURUMU -->
                        <td>
                            <?= guvenli($servis["durum"]) ?>
                        </td>

                        <!-- TARİH -->
                        <td>
                            <?= guvenli($servis["tarih"]) ?>
                        </td>

                        <!-- TAHMİNİ ÜCRET -->
                        <td>

                            <?php if ($servis["tahmini_ucret"] !== null): ?>

                                <?= number_format(
                                    (float) $servis["tahmini_ucret"],
                                    2,
                                    ",",
                                    "."
                                ) ?> TL

                            <?php else: ?>

                                Henüz belirlenmedi

                            <?php endif; ?>

                        </td>

                        <!-- ÜCRET ONAYI -->
                        <td>

                            <?php if ($servis["ucret_onay_durumu"] === "Bekliyor"): ?>

                                <form action="ucret-onay-islem.php"
                                      method="POST">

                                    <input
                                        type="hidden"
                                        name="servis_id"
                                        value="<?= (int)$servis["id"] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= guvenli($_SESSION["csrf_token"]) ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="karar"
                                        value="Onaylandı">
                                        Onayla
                                    </button>

                                    <button
                                        type="submit"
                                        name="karar"
                                        value="Reddedildi">
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

        <!-- SAYFALAMA -->
        <?php if ($son_sayfa > 1): ?>

            <nav class="butonlar" aria-label="Servis sayfaları">

                <?php if ($sayfa > 1): ?>

                    <a class="buton"
                       href="/teknik-servis/musteri/servis-takip.php?sayfa=<?= $sayfa - 1 ?>">
                        Önceki
                    </a>

                <?php endif; ?>

                <span>
                    Sayfa <?= $sayfa ?> / <?= $son_sayfa ?>
                </span>

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
