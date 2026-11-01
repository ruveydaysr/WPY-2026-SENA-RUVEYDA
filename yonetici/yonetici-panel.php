<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
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

// Okunmamış iletişim mesajlarının sayısı
$yeniMesajSorgu = mysqli_query(
    $baglanti,
    "SELECT COUNT(*) AS toplam
     FROM iletisim_mesajlari
     WHERE durum = 'Yeni'"
);

$yeniMesajSayisi = 0;

if ($yeniMesajSorgu) {
    $yeniMesajVeri = mysqli_fetch_assoc($yeniMesajSorgu);
    $yeniMesajSayisi = (int)$yeniMesajVeri["toplam"];
}

function guvenli($deger) {
    return htmlspecialchars(
        (string) $deger,
        ENT_QUOTES,
        "UTF-8"
    );
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$hata = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    $servis_id = filter_input(
        INPUT_POST,
        "servis_id",
        FILTER_VALIDATE_INT
    );

    $teknisyen_id = filter_input(
        INPUT_POST,
        "teknisyen_id",
        FILTER_VALIDATE_INT
    );

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $hata = "İşlem doğrulanamadı. Sayfayı yenileyip tekrar deneyiniz.";
    } elseif (
        !$servis_id || $servis_id < 1 ||
        !$teknisyen_id || $teknisyen_id < 1
    ) {
        $hata = "Geçerli bir servis ve teknisyen seçiniz.";
    } else {
        // Yalnızca aktif teknisyenler atanabilir.
        $stmt = mysqli_prepare(
            $baglanti,
           
"UPDATE servisler AS s
 INNER JOIN kullanicilar AS k ON k.id = ?
 SET s.teknisyen_id = k.id
 WHERE s.id = ?
   AND k.rol IN ('teknisyen', 'teknik')
   AND k.durum = 'aktif'
   AND s.il IS NOT NULL
   AND TRIM(s.il) <> ''
   AND k.il IS NOT NULL
   AND TRIM(k.il) <> ''
   AND TRIM(s.il) = TRIM(k.il)"


        );

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $teknisyen_id,
            $servis_id
        );

        mysqli_stmt_execute($stmt);
        $degisen = mysqli_stmt_affected_rows($stmt);
        $zaten_atanmis = false;

if ($degisen === 0) {
    $kontrol = mysqli_prepare(
        $baglanti,
        "SELECT id FROM servisler
         WHERE id = ? AND teknisyen_id = ?"
    );

    mysqli_stmt_bind_param($kontrol, "ii", $servis_id, $teknisyen_id);
    mysqli_stmt_execute($kontrol);
    mysqli_stmt_store_result($kontrol);

    $zaten_atanmis = mysqli_stmt_num_rows($kontrol) > 0;

    mysqli_stmt_close($kontrol);
}
        mysqli_stmt_close($stmt);
if ($degisen > 0) {
    $_SESSION["atama_mesaji"] = "Teknisyen ataması başarıyla kaydedildi.";
} elseif ($zaten_atanmis) {
    $_SESSION["atama_mesaji"] = "Bu servis zaten seçilen teknisyene atanmış.";
} else {
    $_SESSION["atama_mesaji"] = "Atama yapılamadı. Servis ile teknisyenin ili aynı olmalı ve teknisyen hesabı aktif olmalıdır.";
}
header("Location: yonetici-panel.php");
        exit();
    }
}

$mesaj = $_SESSION["atama_mesaji"] ?? "";
unset($_SESSION["atama_mesaji"]);

// Yönetici paneli servis istatistikleri
$istatistik_sql = "SELECT
    COUNT(*) AS toplam,
    SUM(CASE WHEN teknisyen_id IS NULL THEN 1 ELSE 0 END) AS atama_bekleyen,
    SUM(CASE WHEN durum IN ('İnceleniyor', 'İşlemde', 'Parça Bekliyor')
        THEN 1 ELSE 0 END) AS devam_eden,
    SUM(CASE WHEN durum IN ('Tamamlandı', 'Teslim Edildi')
        THEN 1 ELSE 0 END) AS tamamlanan
    FROM servisler";

$istatistik_sonuc = mysqli_query($baglanti, $istatistik_sql);
$istatistik = mysqli_fetch_assoc($istatistik_sonuc);

$teknisyenler = [];

$sonuc = mysqli_query(
    $baglanti,
   "SELECT id, adsoyad, il
FROM kullanicilar
WHERE rol IN ('teknisyen', 'teknik')
AND durum = 'aktif'
ORDER BY il, adsoyad"
);

while ($satir = mysqli_fetch_assoc($sonuc)) {
    $teknisyenler[] = $satir;
}
// Sayfalama ayarları
$sayfa_basina = 5;

$toplam_sonuc = mysqli_query(
    $baglanti,
    "SELECT COUNT(*) AS toplam FROM servisler"
);

$toplam_kayit = (int) mysqli_fetch_assoc($toplam_sonuc)["toplam"];

$toplam_sayfa = max(1, (int) ceil($toplam_kayit / $sayfa_basina));

$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = ($sayfa && $sayfa > 0) ? min($sayfa, $toplam_sayfa) : 1;

$baslangic = ($sayfa - 1) * $sayfa_basina;
$servisler = mysqli_query(
    $baglanti,
    "SELECT s.id, s.il, s.cihaz, s.marka, s.model,
            s.ariza, s.durum, s.tarih, s.teknisyen_id,
            m.adsoyad AS musteri_adi,
            m.telefon AS musteri_telefon,
            t.adsoyad AS teknisyen_adi
     FROM servisler AS s
     LEFT JOIN kullanicilar AS m ON m.id = s.kullanici_id
     LEFT JOIN kullanicilar AS t ON t.id = s.teknisyen_id
     ORDER BY s.id DESC
LIMIT $baslangic, $sayfa_basina"
);

$servisListesi = mysqli_fetch_all($servisler, MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli</title>
    <link rel="stylesheet" href="../style.css">

    <style>
        .yonetici-alani {
            max-width: 1400px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
        }

        .tablo-kapsayici {
            overflow-x: auto;
        }

        .yonetici-alani table {
            width: 100%;
            border-collapse: collapse;
        }

        .yonetici-alani th,
        .yonetici-alani td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }

        .yonetici-alani th {
            background: #f2f2f2;
        }

        .atama-formu {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 170px;
            margin: 0;
        }

        .atama-formu select,
        .atama-formu button {
            padding: 10px;
        }

        .bilgi {
            padding: 12px;
            background: #e8f5e9;
            color: #205b28;
        }

        .hata {
            padding: 12px;
            background: #ffebee;
            color: #a31818;
        }
        
.yonetici-ozet {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 35px;
}

.ozet-kart {
    background: #f5f7ff;
    border: 1px solid #dfe6fa;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 4px 12px rgba(20, 40, 90, 0.05);
}

.ozet-kart span {
    display: block;
    color: #52617d;
    font-weight: bold;
    font-size: 14px;
}

.ozet-kart strong {
    display: block;
    color: #365de5;
    font-size: 36px;
    margin: 8px 0;
}

.ozet-kart p {
    margin: 0;
    color: #64718a;
    font-size: 13px;
}

@media (max-width: 950px) {
    .yonetici-ozet {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 550px) {
    .yonetici-ozet {
        grid-template-columns: 1fr;
    }
}

/* Yönetici servis tablosu düzeni */
.yonetici-alani .tablo-kapsayici {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #e3e8f2;
    border-radius: 12px;
}

.yonetici-alani table {
    width: 100%;
    min-width: 1250px;
    border-collapse: collapse;
}

.yonetici-alani th {
    background: #eef2ff;
    color: #17233d;
    font-size: 14px;
    white-space: nowrap;
}

.yonetici-alani td {
    font-size: 14px;
    line-height: 1.5;
    overflow-wrap: normal;
    word-break: normal;
}

.yonetici-alani th,
.yonetici-alani td {
    padding: 14px 12px;
    vertical-align: middle;
}

.yonetici-alani tbody tr:hover {
    background: #f5f7ff;
}

/* İl, durum ve tarih bilgileri bölünmesin */
.yonetici-alani td:nth-child(2),
.yonetici-alani td:nth-child(6),
.yonetici-alani td:nth-child(8) {
    white-space: nowrap;
}

/* Atama alanı */
.atama-formu {
    min-width: 190px;
}

.atama-formu button {
    width: 100%;
}

/* Servis arama ve filtreleme alanı */
.servis-filtreleri {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
    margin: 22px 0;
    padding: 20px;
    background: #f5f7ff;
    border: 1px solid #dce4ff;
    border-radius: 12px;
}

.servis-filtreleri input,
.servis-filtreleri select {
    width: 100%;
    height: 46px;
    padding: 10px 14px;
    background: white;
    border: 1px solid #d4dff2;
    border-radius: 8px;
    font-size: 14px;
}

.servis-filtreleri input:focus,
.servis-filtreleri select:focus {
    outline: 2px solid #365de5;
    outline-offset: 2px;
}

@media (max-width: 750px) {
    .servis-filtreleri {
        grid-template-columns: 1fr;
    }
}

    </style>
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="/teknik-servis/index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav aria-label="Yönetici menüsü">
            <a href="/teknik-servis/yonetici/yonetici-panel.php"
               aria-current="page">
                Yönetici Paneli
            </a>

            <a href="/teknik-servis/cikis.php"
               onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                Çıkış Yap
            </a>
        </nav>
    </div>
</header>

<main>
    <section class="yonetici-alani">
        
<div class="yonetici-ozet">
    <div class="ozet-kart">
        <span>Toplam Servis</span>
        <strong><?= (int)$istatistik["toplam"] ?></strong>
        <p>Sistemdeki tüm servis kayıtları</p>
    </div>

    <div class="ozet-kart">
        <span>Atama Bekleyen</span>
        <strong><?= (int)$istatistik["atama_bekleyen"] ?></strong>
        <p>Henüz teknisyen atanmayanlar</p>
    </div>

    <div class="ozet-kart">
        <span>Devam Eden</span>
        <strong><?= (int)$istatistik["devam_eden"] ?></strong>
        <p>İnceleme ve onarım aşamasında</p>
    </div>

    <div class="ozet-kart">
        <span>Tamamlanan</span>
        <strong><?= (int)$istatistik["tamamlanan"] ?></strong>
        <p>Tamamlanan ve teslim edilenler</p>
    </div>
</div>

<!-- Gelen Mesajlar Bölümü -->
<div class="yonetici-mesaj-alani">
    <div>
        <h2>📩 Gelen Mesajlar</h2>
        
<p class="yeni-mesaj-sayaci">
    <?php if ($yeniMesajSayisi > 0): ?>
        🔔 <?= $yeniMesajSayisi ?> yeni mesajınız var.
    <?php else: ?>
        ✓ Yeni mesajınız bulunmuyor.
    <?php endif; ?>
</p>

        <p>
            Müşterilerin iletişim ve destek formundan
            gönderdiği mesajları buradan yönetebilirsiniz.
        </p>
    </div>

    <a href="gelen-mesajlar.php" class="mesajlar-buton">
        Mesajları Görüntüle
    </a>
</div>

        <h2>Müşteri Talepleri ve Teknisyen Atama</h2>

<div class="servis-filtreleri">
    <input type="number" id="servisAra"
           placeholder="Servis numarası ara..." min="1">

    <select id="ilFiltre">
        <option value="">Tüm İller</option>
        <?php
        $iller = [];
        foreach ($servisListesi as $kayit) {
            if (!empty($kayit["il"])) {
                $iller[] = $kayit["il"];
            }
        }
        $iller = array_unique($iller);
        sort($iller);

        foreach ($iller as $il):
        ?>
            <option value="<?= guvenli($il) ?>">
                <?= guvenli($il) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select id="durumFiltre">
        <option value="">Tüm Durumlar</option>
        <option value="Bekliyor">Bekliyor</option>
        <option value="İnceleniyor">İnceleniyor</option>
        <option value="İşlemde">İşlemde</option>
        <option value="Parça Bekliyor">Parça Bekliyor</option>
        <option value="Tamamlandı">Tamamlandı</option>
        <option value="Teslim Edildi">Teslim Edildi</option>
    </select>
</div>

        <?php if ($mesaj !== "") { ?>
            <p class="bilgi"><?= guvenli($mesaj) ?></p>
        <?php } ?>

        <?php if ($hata !== "") { ?>
            <p class="hata"><?= guvenli($hata) ?></p>
        <?php } ?>

        <?php if (empty($teknisyenler)) { ?>
            <p class="hata">
                Aktif teknisyen bulunamadı.
                Teknisyen hesabının rolü teknisyen,
                durumu aktif olmalıdır.
            </p>
        <?php } ?>

        <div class="tablo-kapsayici">
            <table>
                <thead>
                    <tr>
                        <th>Servis No</th>
                        <th>İl</th>
                        <th>Müşteri</th>
                        <th>Telefon</th>
                        <th>Cihaz</th>
                        <th>Arıza / Talep</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th>Atanan Teknisyen</th>
                        <th>Teknisyen Ata</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (mysqli_num_rows($servisler) === 0) { ?>
                    <tr>
                        <td colspan="10">Henüz servis talebi yok.</td>
                    </tr>
                <?php } ?>
                <?php foreach ($servisListesi as $servis) { ?>
                    <tr>
                        <td><?= (int) $servis["id"] ?></td>
                        <td><?= guvenli($servis["il"] ?? "Belirtilmedi") ?></td>
                        <td><?= guvenli($servis["musteri_adi"]) ?></td>
                        <td><?= guvenli($servis["musteri_telefon"]) ?></td>

                        <td>
                            <?= guvenli($servis["cihaz"]) ?><br>
                            <?= guvenli($servis["marka"]) ?>
                            <?= guvenli($servis["model"]) ?>
                        </td>

                        <td><?= nl2br(guvenli($servis["ariza"])) ?></td>
                        <td><?= guvenli($servis["durum"]) ?></td>
                        <td><?= guvenli($servis["tarih"]) ?></td>

                        <td>
                            <?= guvenli(
                                $servis["teknisyen_adi"] ?? "Henüz atanmadı"
                            ) ?>
                        </td>

                        <td>
                            
<?php
$uygunTeknisyenler = array_filter(
    $teknisyenler,
    function ($teknisyen) use ($servis) {
        $teknisyenIl = trim((string) ($teknisyen["il"] ?? ""));
        $servisIl = trim((string) ($servis["il"] ?? ""));

        return $servisIl !== ""
            && $teknisyenIl !== ""
            && $teknisyenIl === $servisIl;
    }
);
?>

                          <?php if (!empty($uygunTeknisyenler)) { ?>
                                <form method="POST" class="atama-formu">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= guvenli($_SESSION["csrf_token"]) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="servis_id"
                                        value="<?= (int) $servis["id"] ?>"
                                    >

                                    <select
                                        name="teknisyen_id"
                                        aria-label="Servis <?= (int) $servis["id"] ?> için teknisyen"
                                        required
                                    >
                                        <option value="">Teknisyen seçiniz</option>

                                        
<?php foreach ($teknisyenler as $teknisyen) {
    if (
        trim((string) ($teknisyen["il"] ?? "")) !==
        trim((string) ($servis["il"] ?? ""))
    ) {
        continue;
    }
?>
                                            <option
                                                value="<?= (int) $teknisyen["id"] ?>"
                                                <?= (int) $servis["teknisyen_id"] === (int) $teknisyen["id"] ? "selected" : "" ?>
                                            >
                                                <?= guvenli($teknisyen["adsoyad"]) ?>
                                            </option>
                                        <?php } ?>
                                    </select>

                                    <button type="submit">Atamayı Kaydet</button>
                                </form>
                            <?php } else { ?>
                                Bu ilde uygun teknisyen bulunamadı.
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <?php if ($toplam_sayfa > 1): ?>
    <div style="display:flex; justify-content:center; gap:10px; margin:25px 0;">
        <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
            <a href="?sayfa=<?= $i ?>"
               style="padding:10px 15px; background:<?= $i == $sayfa ? '#3659e3' : '#eef2ff' ?>; color:<?= $i == $sayfa ? 'white' : '#243b75' ?>; border-radius:7px; text-decoration:none;">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>
        </div>
    </section>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const servisAra = document.getElementById("servisAra");
    const ilFiltre = document.getElementById("ilFiltre");
    const durumFiltre = document.getElementById("durumFiltre");

    const tablo = document.querySelector(".tablo-kapsayici table");
    const satirlar = tablo.querySelectorAll("tbody tr");

    function filtrele() {
        const arananNo = servisAra.value.trim();
        const secilenIl = ilFiltre.value.trim().toLocaleLowerCase("tr-TR");
        const secilenDurum = durumFiltre.value.trim().toLocaleLowerCase("tr-TR");

        satirlar.forEach(function (satir) {
            const hucreler = satir.querySelectorAll("td");

            if (hucreler.length < 7) return;

            const servisNo = hucreler[0].textContent.trim();
            const il = hucreler[1].textContent.trim().toLocaleLowerCase("tr-TR");
            const durum = hucreler[6].textContent.trim().toLocaleLowerCase("tr-TR");

            const noUygun = arananNo === "" || servisNo === arananNo;
            const ilUygun = secilenIl === "" || il === secilenIl;
            const durumUygun = secilenDurum === "" || durum === secilenDurum;

            satir.style.display =
                noUygun && ilUygun && durumUygun ? "" : "none";
        });
    }

    servisAra.addEventListener("input", filtrele);
    ilFiltre.addEventListener("change", filtrele);
    durumFiltre.addEventListener("change", filtrele);
});
</script>

</body>
</html>