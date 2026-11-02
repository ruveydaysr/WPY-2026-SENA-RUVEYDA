
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

function guvenli($deger) {
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$hata = "";

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["islem"] ?? "") === "teslim_al"
) {
    $token = $_POST["csrf_token"] ?? "";

    $servis_id = filter_input(
        INPUT_POST,
        "servis_id",
        FILTER_VALIDATE_INT
    );

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Geçersiz işlem.");
    }

    if (!$servis_id) {
        http_response_code(400);
        exit("Geçersiz servis numarası.");
    }

    $stmt = mysqli_prepare(
        $baglanti,
        "UPDATE servisler
         SET teslim_durumu = 'Teslim Alındı'
         WHERE id = ?
           AND hizmet_turu = 'Elden Teslim'
           AND (
               teslim_durumu IS NULL
               OR teslim_durumu = 'Teslim Bekleniyor'
           )"
    );

    mysqli_stmt_bind_param($stmt, "i", $servis_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $_SESSION["atama_mesaji"] =
        "Cihaz teslim durumu güncellendi.";

    header("Location: yonetici-panel.php", true, 303);
    exit();
}

// TEKNİSYEN ATAMA
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
        $hata = "İşlem doğrulanamadı.";
    } elseif (!$servis_id || !$teknisyen_id) {
        $hata = "Geçerli bir servis ve teknisyen seçiniz.";
    } else {

        // Online destek: herhangi bir aktif teknisyen
        // Elden teslim: aynı ildeki aktif teknisyen
        $stmt = mysqli_prepare(
            $baglanti,
            "UPDATE servisler AS s
             INNER JOIN kullanicilar AS k ON k.id = ?
             SET s.teknisyen_id = k.id
             WHERE s.id = ?
               AND k.rol IN ('teknisyen', 'teknik')
               AND k.durum = 'aktif'
               AND (
                   s.hizmet_turu = 'Online'
                   OR (
                       s.hizmet_turu = 'Elden Teslim'
                       AND s.il IS NOT NULL
                       AND k.il IS NOT NULL
                       AND TRIM(s.il) <> ''
                       AND TRIM(s.il) = TRIM(k.il)
                   )
               )"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $teknisyen_id,
            $servis_id
        );

        mysqli_stmt_execute($stmt);
        $degisen = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($degisen > 0) {
            $_SESSION["atama_mesaji"] =
                "Teknisyen ataması başarıyla kaydedildi.";
        } else {
            $_SESSION["atama_mesaji"] =
                "Atama yapılamadı veya teknisyen zaten atanmış.";
        }

        header("Location: yonetici-panel.php");
        exit();
    }
}

$mesaj = $_SESSION["atama_mesaji"] ?? "";
unset($_SESSION["atama_mesaji"]);

 // ONAY BEKLEYEN MÜŞTERİ YORUMLARI
$yorumSayisiSonuc = mysqli_query(
    $baglanti,
    "SELECT COUNT(*) AS toplam
     FROM musteri_yorumlari
     WHERE durum = 'Bekliyor'"
);

$bekleyenYorumSayisi = (int) mysqli_fetch_assoc(
    $yorumSayisiSonuc
)["toplam"];

// GELEN MESAJ SAYISI
$yeniMesajSonuc = mysqli_query(
    $baglanti,
    "SELECT COUNT(*) AS toplam
     FROM iletisim_mesajlari
     WHERE durum = 'Yeni'"
);

$yeniMesajVeri = mysqli_fetch_assoc($yeniMesajSonuc);
$yeniMesajSayisi = (int)$yeniMesajVeri["toplam"];

// SERVİS İSTATİSTİKLERİ
$istatistikSonuc = mysqli_query(
    $baglanti,
    "SELECT
        COUNT(*) AS toplam,
        SUM(CASE WHEN teknisyen_id IS NULL THEN 1 ELSE 0 END)
            AS atama_bekleyen,
        SUM(CASE WHEN durum IN
            ('İnceleniyor', 'İşlemde', 'Parça Bekliyor')
            THEN 1 ELSE 0 END) AS devam_eden,
        SUM(CASE WHEN durum IN
            ('Tamamlandı', 'Teslim Edildi')
            THEN 1 ELSE 0 END) AS tamamlanan
     FROM servisler"
);

$istatistik = mysqli_fetch_assoc($istatistikSonuc);

// AKTİF TEKNİSYENLER
$teknisyenler = [];

$teknisyenSonuc = mysqli_query(
    $baglanti,
    "SELECT id, adsoyad, il
     FROM kullanicilar
     WHERE rol IN ('teknisyen', 'teknik')
       AND durum = 'aktif'
     ORDER BY il, adsoyad"
);

while ($satir = mysqli_fetch_assoc($teknisyenSonuc)) {
    $teknisyenler[] = $satir;
}

// ARAMA VE FİLTRELEME
$aranan_servis = trim((string)($_GET["servis_no"] ?? ""));
$secilen_il = trim((string)($_GET["il"] ?? ""));
$secilen_durum = trim((string)($_GET["durum"] ?? ""));

$kosullar = [];
$parametreler = [];
$tipler = "";

if ($aranan_servis !== "") {
    if (ctype_digit($aranan_servis) && (int)$aranan_servis > 0) {
        $kosullar[] = "s.id = ?";
        $parametreler[] = (int)$aranan_servis;
        $tipler .= "i";
    } else {
        $kosullar[] = "1 = 0";
    }
}

if ($secilen_il !== "") {
    $kosullar[] = "s.il = ?";
    $parametreler[] = $secilen_il;
    $tipler .= "s";
}

if ($secilen_durum !== "") {
    $kosullar[] = "s.durum = ?";
    $parametreler[] = $secilen_durum;
    $tipler .= "s";
}

$where = empty($kosullar)
    ? ""
    : " WHERE " . implode(" AND ", $kosullar);

// SAYFALAMA
$sayfa_basina = 5;

$toplamSorgu = mysqli_prepare(
    $baglanti,
    "SELECT COUNT(*) AS toplam FROM servisler AS s $where"
);

if ($tipler !== "") {
    mysqli_stmt_bind_param($toplamSorgu, $tipler, ...$parametreler);
}

mysqli_stmt_execute($toplamSorgu);
$toplamSonuc = mysqli_stmt_get_result($toplamSorgu);
$toplam_kayit = (int)mysqli_fetch_assoc($toplamSonuc)["toplam"];
mysqli_stmt_close($toplamSorgu);

$toplam_sayfa = max(1, (int)ceil($toplam_kayit / $sayfa_basina));

$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = ($sayfa && $sayfa > 0)
    ? min($sayfa, $toplam_sayfa)
    : 1;

$baslangic = ($sayfa - 1) * $sayfa_basina;

// SERVİS KAYITLARI VE ŞUBE BİLGİLERİ
$sql = "SELECT
    s.id,
    s.il,
    s.hizmet_turu,
    s.teslim_durumu,
    s.servis_noktasi_id,
    s.cihaz,
    s.marka,
    s.model,
    s.ariza,
    s.durum,
    s.tarih,
    s.teknisyen_id,
    m.adsoyad AS musteri_adi,
    m.telefon AS musteri_telefon,
    t.adsoyad AS teknisyen_adi,
    sn.sube_adi,
    sn.ilce AS sube_ilce
FROM servisler AS s
LEFT JOIN kullanicilar AS m
    ON m.id = s.kullanici_id
LEFT JOIN kullanicilar AS t
    ON t.id = s.teknisyen_id
LEFT JOIN servis_noktalari AS sn
    ON sn.id = s.servis_noktasi_id
$where
ORDER BY s.id DESC
LIMIT ?, ?";

$servisSorgu = mysqli_prepare($baglanti, $sql);

$listeTipler = $tipler . "ii";
$listeParametreler = array_merge(
    $parametreler,
    [$baslangic, $sayfa_basina]
);

mysqli_stmt_bind_param(
    $servisSorgu,
    $listeTipler,
    ...$listeParametreler
);

mysqli_stmt_execute($servisSorgu);

$servisSonuc = mysqli_stmt_get_result($servisSorgu);
$servisListesi = mysqli_fetch_all($servisSonuc, MYSQLI_ASSOC);

mysqli_stmt_close($servisSorgu);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli | TeknoServis</title>
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

        .yonetici-alani .tablo-kapsayici {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e3e8f2;
            border-radius: 12px;
        }

        .yonetici-alani table {
            width: 100%;
            min-width: 1450px;
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

        .atama-formu {
            min-width: 190px;
        }

        .atama-formu button {
            width: 100%;
        }

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

    <!-- İSTATİSTİKLER -->
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

    <!-- GELEN MESAJLAR -->
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

    <!-- SERVİS NOKTASI YÖNETİMİ -->
    <div class="yonetici-mesaj-alani">
        <div>
            <h2>📍 Servis Noktası Yönetimi</h2>

            <p>
                Hizmet verilen illerdeki şubeleri ekleyin,
                adreslerini görüntüleyin ve aktif/pasif
                durumlarını yönetin.
            </p>
        </div>

        <a href="servis-noktasi-yonetimi.php"
           class="mesajlar-buton">
            Şubeleri Yönet
        </a>
    </div>

<!-- MÜŞTERİ HESAP YÖNETİMİ -->
<div class="yonetici-mesaj-alani">
    <div>
        <h2>👥 Müşteri Yönetimi</h2>

        <p>
            Kayıtlı müşterileri görüntüleyebilir,
            hesaplarını aktif veya pasif yapabilirsiniz.
        </p>
    </div>

    <a href="musteri-yonetimi.php" class="mesajlar-buton">
        Müşterileri Yönet
    </a>
</div>

<!-- MÜŞTERİ YORUMLARI YÖNETİMİ -->
<div class="yonetici-mesaj-alani">
    <div>
        <h2>⭐ Müşteri Yorumları</h2>

<p class="yeni-mesaj-sayaci">
    <?php if ($bekleyenYorumSayisi > 0): ?>
        🔔 <?= $bekleyenYorumSayisi ?> yorum onay bekliyor.
    <?php else: ?>
        ✓ Onay bekleyen yorum bulunmuyor.
    <?php endif; ?>
</p>

        <p>
            Müşterilerin verdiği yıldız puanlarını ve
            yorumlarını inceleyebilir, onaylayabilir
            veya reddedebilirsiniz.
        </p>
    </div>

    <a href="musteri-yorumlari.php" class="mesajlar-buton">
        Yorumları Yönet
    </a>
</div>

    <h2>Müşteri Talepleri ve Teknisyen Atama</h2>

    <!-- FİLTRELER -->
    
<!-- SERVİS ARAMA VE FİLTRELEME -->
<form method="GET" action="yonetici-panel.php" class="servis-filtreleri">

    <input
        type="number"
        name="servis_no"
        id="servisAra"
        placeholder="Servis numarası ara..."
        min="1"
        value="<?= guvenli($aranan_servis) ?>"
    >

    <select name="il" id="ilFiltre">
        <option value="">Tüm İller</option>

        <?php
        $ilSonuc = mysqli_query(
            $baglanti,
            "SELECT DISTINCT il
             FROM servisler
             WHERE il IS NOT NULL AND TRIM(il) <> ''
             ORDER BY il"
        );

        while ($ilSatir = mysqli_fetch_assoc($ilSonuc)):
            $il = $ilSatir["il"];
        ?>
            <option value="<?= guvenli($il) ?>"
                <?= $secilen_il === $il ? "selected" : "" ?>>
                <?= guvenli($il) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <select name="durum" id="durumFiltre">
        <option value="">Tüm Durumlar</option>

        <?php
        $durumlar = [
            "Bekliyor",
            "İnceleniyor",
            "İşlemde",
            "Parça Bekliyor",
            "Tamamlandı",
            "Teslim Edildi"
        ];

        foreach ($durumlar as $durum):
        ?>
            <option value="<?= guvenli($durum) ?>"
                <?= $secilen_durum === $durum ? "selected" : "" ?>>
                <?= guvenli($durum) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="buton">
        Ara / Filtrele
    </button>

    <a href="yonetici-panel.php" class="buton"
       style="background: #64748b; text-align: center;">
        Temizle
    </a>

</form>

    <?php if ($mesaj !== ""): ?>
        <p class="bilgi"><?= guvenli($mesaj) ?></p>
    <?php endif; ?>

    <?php if ($hata !== ""): ?>
        <p class="hata"><?= guvenli($hata) ?></p>
    <?php endif; ?>

    <?php if (empty($teknisyenler)): ?>
        <p class="hata">
            Aktif teknisyen bulunamadı.
        </p>
    <?php endif; ?>

    <!-- SERVİS TABLOSU -->
    <div class="tablo-kapsayici">

        <table>
            <thead>
                <tr>
                    <th>Servis No</th>
                    <th>Hizmet Türü</th>
                    <th>İl</th>
                    <th>Teslim Şubesi</th>
                    <th>Teslim Durumu</th>
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

            <?php if (empty($servisListesi)): ?>
                <tr>
                    <td colspan="13">
                        Henüz servis talebi bulunmuyor.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($servisListesi as $servis): ?>

                <tr>
                    <td><?= (int)$servis["id"] ?></td>

                    <td>
                        <?= guvenli($servis["hizmet_turu"]) ?>
                    </td>

                    <td>
                        <?= guvenli($servis["il"] ?? "—") ?>
                    </td>

                    <td>
                        <?php if ($servis["hizmet_turu"] === "Online"): ?>
                            Online Destek
                        <?php elseif (!empty($servis["sube_adi"])): ?>
                            <?= guvenli($servis["sube_adi"]) ?>
                            <br>
                            <?= guvenli($servis["sube_ilce"]) ?>
                        <?php else: ?>
                            Şube belirtilmedi
                        <?php endif; ?>
                    </td>

<td>
    <?php if ($servis["hizmet_turu"] === "Online"): ?>

        Fiziksel teslim gerekmiyor.

    <?php elseif ($servis["teslim_durumu"] === "Teslim Alındı"): ?>

        <strong>✓ Teslim Alındı</strong>

    <?php else: ?>

        <p>Teslim Bekleniyor</p>

        <form method="POST"
              onsubmit="return confirm('Cihazın fiziksel olarak teslim alındığını onaylıyor musunuz?')">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= guvenli($_SESSION["csrf_token"]) ?>">

            <input type="hidden"
                   name="islem"
                   value="teslim_al">

            <input type="hidden"
                   name="servis_id"
                   value="<?= (int)$servis["id"] ?>">

            <button type="submit">
                Teslim Alındı
            </button>
        </form>

    <?php endif; ?>
</td>

                    <td>
                        <?= guvenli($servis["musteri_adi"]) ?>
                    </td>

                    <td>
                        <?= guvenli($servis["musteri_telefon"]) ?>
                    </td>

                    <td>
                        <?= guvenli($servis["cihaz"]) ?><br>
                        <?= guvenli($servis["marka"]) ?>
                        <?= guvenli($servis["model"]) ?>
                    </td>

                    <td>
                        <?= nl2br(guvenli($servis["ariza"])) ?>
                    </td>

                    <td>
                        <?= guvenli($servis["durum"]) ?>
                    </td>

                    <td>
                        <?= guvenli($servis["tarih"]) ?>
                    </td>

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

                                if ($servis["hizmet_turu"] === "Online") {
                                    return true;
                                }

                                $teknisyenIl = trim(
                                    (string)($teknisyen["il"] ?? "")
                                );

                                $servisIl = trim(
                                    (string)($servis["il"] ?? "")
                                );

                                return $servisIl !== ""
                                    && $teknisyenIl !== ""
                                    && $teknisyenIl === $servisIl;
                            }
                        );
                        ?>

                        <?php if (!empty($uygunTeknisyenler)): ?>

                            <form method="POST" class="atama-formu">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= guvenli($_SESSION["csrf_token"]) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="servis_id"
                                    value="<?= (int)$servis["id"] ?>"
                                >

                                <select name="teknisyen_id" required>
                                    <option value="">Teknisyen seçiniz</option>

                                    <?php foreach ($uygunTeknisyenler as $teknisyen): ?>

                                        <option
                                            value="<?= (int)$teknisyen["id"] ?>"
                                            <?= (int)$servis["teknisyen_id"] ===
                                                (int)$teknisyen["id"]
                                                ? "selected" : "" ?>
                                        >
                                            <?= guvenli($teknisyen["adsoyad"]) ?>
                                        </option>

                                    <?php endforeach; ?>
                                </select>

                                <button type="submit">
                                    Atamayı Kaydet
                                </button>

                            </form>

                        <?php else: ?>

                            Bu servis için uygun teknisyen bulunamadı.

                        <?php endif; ?>
                    </td>
                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>

        <!-- SAYFALAMA -->
        <?php if ($toplam_sayfa > 1): ?>

            <div style="display:flex; justify-content:center; gap:10px; margin:25px 0;">

                <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>

                    <a
                        href="?<?= guvenli(http_build_query([
    'servis_no' => $aranan_servis,
    'il' => $secilen_il,
    'durum' => $secilen_durum,
    'sayfa' => $i
])) ?>"
                        style="padding:10px 15px;
                               background:<?= $i == $sayfa ? '#3659e3' : '#eef2ff' ?>;
                               color:<?= $i == $sayfa ? 'white' : '#243b75' ?>;
                               border-radius:7px;
                               text-decoration:none;"
                    >
                        <?= $i ?>
                    </a>

                <?php endfor; ?>

            </div>

        <?php endif; ?>

    </div>

</section>
</main>

</body>
</html>
