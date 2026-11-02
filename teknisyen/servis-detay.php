
<?php
session_start();
require_once("../baglanti.php");

mysqli_set_charset($baglanti, "utf8mb4");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Yetki kontrolü
if (
    !isset($_SESSION["kullanici_id"]) ||
    !in_array(
        $_SESSION["rol"] ?? "",
        ["teknisyen", "admin"],
        true
    )
) {
    http_response_code(403);
    exit("Bu sayfaya erişim yetkiniz yok.");
}

function guvenli($deger) {
    return htmlspecialchars(
        (string)($deger ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("Geçerli bir servis numarası belirtilmedi.");
}

$teknisyen_id = (int)$_SESSION["kullanici_id"];

// Servis bilgileri
$sorgu = "
    SELECT
        s.id,
        s.cihaz,
        s.marka,
        s.model,
        s.ariza,
        s.durum,
        s.hizmet_turu,
        s.teslim_durumu,
        s.il,
        s.ariza_tespiti,
        s.yapilan_islem,
        s.kullanilan_parca,
        s.parca_ucreti,
        s.iscilik_ucreti,
        s.tahmini_ucret,
        s.ucret_onay_durumu,
        s.ucret_onay_tarihi,
        k.adsoyad,
        sn.sube_adi,
        sn.ilce AS sube_ilce,
        sn.adres AS sube_adres
    FROM servisler AS s
    INNER JOIN kullanicilar AS k
        ON s.kullanici_id = k.id
    LEFT JOIN servis_noktalari AS sn
        ON s.servis_noktasi_id = sn.id
    WHERE s.id = ?
      AND s.teknisyen_id = ?
";

$stmt = mysqli_prepare($baglanti, $sorgu);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id,
    $teknisyen_id
);

mysqli_stmt_execute($stmt);

$sonuc = mysqli_stmt_get_result($stmt);
$servis = mysqli_fetch_assoc($sonuc);

mysqli_stmt_close($stmt);

if (!$servis) {
    http_response_code(404);
    die("Servis kaydı bulunamadı veya bu servis size atanmadı.");
}

$online_mi = $servis["hizmet_turu"] === "Online";

// İşlem geçmişi
$gecmis_sql = "
    SELECT
        g.islem_tarihi,
        g.islem_aciklamasi,
        g.eski_durum,
        g.yeni_durum,
        k.adsoyad AS teknisyen_adi
    FROM servis_islem_gecmisi AS g
    INNER JOIN kullanicilar AS k
        ON g.teknisyen_id = k.id
    WHERE g.servis_id = ?
    ORDER BY
        g.islem_tarihi DESC,
        g.id DESC
";

$gecmis_stmt = mysqli_prepare($baglanti, $gecmis_sql);

mysqli_stmt_bind_param(
    $gecmis_stmt,
    "i",
    $id
);

mysqli_stmt_execute($gecmis_stmt);

$gecmis_sonuc = mysqli_stmt_get_result($gecmis_stmt);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Servis Detayı | TeknoServis</title>

    <link rel="stylesheet" href="teknisyen-style.css">

    <style>
        .hizmet-bilgi-kutusu {
            background: #f2f6ff;
            border: 1px solid #dce5ff;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            line-height: 1.7;
        }

        .hizmet-bilgi-kutusu h2 {
            color: #243b75;
            margin-top: 0;
        }

        .hizmet-etiketi {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 7px;
            background: #dce7ff;
            color: #243b75;
            font-weight: bold;
        }

        .bilgi-aciklamasi {
            background: #fff;
            border-left: 4px solid #3659e3;
            padding: 12px;
            margin-top: 15px;
            border-radius: 5px;
        }

        .gecmis-tablo-alani {
            width: 100%;
            overflow-x: auto;
            margin-top: 15px;
        }

        .gecmis-tablo-alani table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
        }

        .gecmis-tablo-alani th,
        .gecmis-tablo-alani td {
            padding: 12px;
            vertical-align: top;
            line-height: 1.5;
        }
    </style>
</head>

<body>

<div class="servis-detay">

    <h1>Servis Detayı</h1>

    <?php if (
        isset($_GET["hata"]) &&
        $_GET["hata"] === "ucret_onayi"
    ): ?>

<?php if (($_GET["hata"] ?? "") === "teslim_bekleniyor"): ?>
    <div style="background:#ffe5e5; color:#a00000;
                padding:15px; border-radius:8px; margin:15px 0;">
        Cihaz henüz şubeye teslim alınmadı.
        Servis işlemlerine başlayabilmek için yöneticinin
        cihazı teslim aldığını onaylaması gerekiyor.
    </div>
<?php endif; ?>

<?php if (($_GET["hata"] ?? "") === "online_teslim"): ?>
    <div style="background:#ffe5e5; color:#a00000;
                padding:15px; border-radius:8px; margin:15px 0;">
        Online teknik destek başvuruları fiziksel olarak
        teslim edilemez. İşlem bittiyse servis durumunu
        "Tamamlandı" olarak seçebilirsiniz.
    </div>
<?php endif; ?>

        <div style="
            background-color: #ffe5e5;
            color: #a00000;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        ">
            Müşteri ücret teklifini onaylamadan
            servis tamamlanamaz veya teslim edilemez.
        </div>

    <?php endif; ?>

    <a href="atanan-servisler.php" class="geri-butonu">
        ← Atanan Servislere Dön
    </a>

    <!-- CİHAZ BİLGİLERİ -->

    <div class="cihaz-bilgileri">

        <h2>Cihaz Bilgileri</h2>

        <p>
            <strong>Servis No:</strong>
            SRV-<?= str_pad(
                $servis["id"],
                3,
                "0",
                STR_PAD_LEFT
            ) ?>
        </p>

        <p>
            <strong>Müşteri:</strong>
            <?= guvenli($servis["adsoyad"]) ?>
        </p>

        <p>
            <strong>Cihaz:</strong>
            <?= guvenli(
                $servis["cihaz"] . " - " .
                $servis["marka"] . " " .
                $servis["model"]
            ) ?>
        </p>

        <p>
            <strong>Şikayet:</strong>
            <?= guvenli($servis["ariza"]) ?>
        </p>

        <p>
            <strong>Durum:</strong>
            <?= guvenli($servis["durum"]) ?>
        </p>

    </div>

    <!-- HİZMET TÜRÜ VE TESLİM BİLGİLERİ -->

    <div class="hizmet-bilgi-kutusu">

        <h2>Hizmet ve Teslim Bilgileri</h2>

        <p>
            <strong>Hizmet Türü:</strong>

            <span class="hizmet-etiketi">
                <?= $online_mi
                    ? "Online Teknik Destek"
                    : "Elden Teslim" ?>
            </span>
        </p>

        <?php if ($online_mi): ?>

            <p>
                <strong>Teslim Şubesi:</strong>
                Şube gerekmiyor.
            </p>

            <p>
                <strong>Teslim Durumu:</strong>
                Fiziksel teslim gerekmiyor.
            </p>

            <div class="bilgi-aciklamasi">
                <strong>Online Teknik Destek</strong>
                <p>
                    Bu başvuruda cihazın fiziksel olarak
                    şubeye getirilmesi gerekmiyor.
                    Teknisyen, müşteriye uzaktan destek
                    sağlayabilir ve yapılan işlemleri
                    aşağıdaki forma kaydedebilir.
                </p>
            </div>

        <?php else: ?>

            <p>
                <strong>Teslim Şubesi:</strong>

                <?php if (!empty($servis["sube_adi"])): ?>

                    <?= guvenli($servis["sube_adi"]) ?>

                    <?php if (!empty($servis["sube_ilce"])): ?>
                        -
                        <?= guvenli($servis["sube_ilce"]) ?>
                    <?php endif; ?>

                    <?php if (!empty($servis["il"])): ?>
                        /
                        <?= guvenli($servis["il"]) ?>
                    <?php endif; ?>

                <?php else: ?>

                    Şube belirtilmedi.

                <?php endif; ?>
            </p>

            <?php if (!empty($servis["sube_adres"])): ?>

                <p>
                    <strong>Şube Adresi:</strong>
                    <?= guvenli($servis["sube_adres"]) ?>
                </p>

            <?php endif; ?>

            <p>
                <strong>Cihaz Teslim Durumu:</strong>

                <?php if (
                    $servis["teslim_durumu"] === "Teslim Alındı"
                ): ?>

                    <strong>✓ Teslim Alındı</strong>

                <?php else: ?>

                    <strong>Teslim Bekleniyor</strong>

                <?php endif; ?>
            </p>

            <div class="bilgi-aciklamasi">
                <strong>Elden Teslim Servisi</strong>
                <p>
                    Bu başvuruda müşteri cihazını
                    belirtilen şubeye teslim eder.
                    Cihazın teslim alındı bilgisi
                    yönetici panelinden güncellenir.
                </p>
            </div>

        <?php endif; ?>

    </div>

    <!-- ÜCRET ONAYI -->

    <div class="cihaz-bilgileri">

        <h2>Müşteri Ücret Onayı</h2>

        <p>
            <strong>Tahmini Toplam Ücret:</strong>

            <?= $servis["tahmini_ucret"] !== null
                ? number_format(
                    (float)$servis["tahmini_ucret"],
                    2,
                    ",",
                    "."
                ) . " TL"
                : "Henüz ücret belirlenmedi" ?>
        </p>

        <p>
            <strong>Onay Durumu:</strong>
            <?= guvenli(
                $servis["ucret_onay_durumu"]
                ?? "Henüz teklif yok"
            ) ?>
        </p>

        <p>
            <strong>Onay Tarihi:</strong>
            <?= guvenli(
                $servis["ucret_onay_tarihi"] ?? "-"
            ) ?>
        </p>

    </div>

    <!-- TESLİM FİŞİ -->

    <?php if (
        in_array(
            $servis["durum"],
            ["Tamamlandı", "Teslim Edildi"],
            true
        )
    ): ?>

        <div style="margin: 20px 0;">

            <a
                href="servis-teslim-fisi.php?id=<?= (int)$servis["id"] ?>"
                target="_blank"
                style="
                    display: inline-block;
                    padding: 12px 20px;
                    background-color: #2c3e50;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                "
            >
                Servis Teslim Fişini Görüntüle
            </a>

        </div>

    <?php endif; ?>

    <!-- TEKNİSYEN İŞLEMLERİ -->

    <h2>Teknisyen İşlemleri</h2>

    <?php if ($online_mi): ?>

        <p>
            Bu online destek başvurusunda yapılan
            uzaktan işlemleri aşağıdaki alana yazabilirsiniz.
        </p>

    <?php else: ?>

        <p>
            Cihaz üzerinde yapılan inceleme, parça
            değişimi ve servis işlemlerini kaydedebilirsiniz.
        </p>

    <?php endif; ?>

    <form action="servis-guncelle.php" method="POST">

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

        <label>Arıza Tespiti:</label>

        <textarea
            name="ariza_tespiti"
            rows="3"
        ><?= guvenli($servis["ariza_tespiti"]) ?></textarea>

        <label>
            <?= $online_mi
                ? "Uzaktan Yapılan İşlem:"
                : "Yapılan İşlem:" ?>
        </label>

        <textarea
            name="yapilan_islem"
            rows="3"
        ><?= guvenli($servis["yapilan_islem"]) ?></textarea>

        <label>Kullanılan Parça:</label>

        <textarea
            name="kullanilan_parca"
            rows="3"
        ><?= guvenli($servis["kullanilan_parca"]) ?></textarea>

        <label>Parça Ücreti (TL):</label>

        <input
            type="number"
            name="parca_ucreti"
            min="0"
            step="0.01"
            value="<?= guvenli(
                $servis["parca_ucreti"] ?? "0"
            ) ?>"
        >

        <label>İşçilik Ücreti (TL):</label>

        <input
            type="number"
            name="iscilik_ucreti"
            min="0"
            step="0.01"
            value="<?= guvenli(
                $servis["iscilik_ucreti"] ?? "0"
            ) ?>"
        >

        <label>Servis Durumu:</label>

        <select name="durum">

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

                <option
                    value="<?= guvenli($durum) ?>"
                    <?= $servis["durum"] === $durum
                        ? "selected"
                        : "" ?>
                >
                    <?= guvenli($durum) ?>
                </option>

            <?php endforeach; ?>

        </select>

        <button type="submit">
            Değişiklikleri Kaydet
        </button>

    </form>

    <!-- İŞLEM GEÇMİŞİ -->

    <h2>Servis İşlem Geçmişi</h2>

    <div class="gecmis-tablo-alani">

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

                <?php if (
                    mysqli_num_rows($gecmis_sonuc) > 0
                ): ?>

                    <?php while (
                        $kayit = mysqli_fetch_assoc($gecmis_sonuc)
                    ): ?>

                        <tr>

                            <td>
                                <?= guvenli(
                                    $kayit["islem_tarihi"]
                                ) ?>
                            </td>

                            <td>
                                <?= guvenli(
                                    $kayit["teknisyen_adi"]
                                ) ?>
                            </td>

                            <td>
                                <?= nl2br(guvenli(
                                    $kayit["islem_aciklamasi"]
                                )) ?>
                            </td>

                            <td>

                                <?php if (
                                    $kayit["eski_durum"] !==
                                    $kayit["yeni_durum"]
                                ): ?>

                                    <?= guvenli(
                                        $kayit["eski_durum"]
                                    ) ?>

                                    →

                                    <?= guvenli(
                                        $kayit["yeni_durum"]
                                    ) ?>

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

</div>

</body>
</html>

<?php mysqli_stmt_close($gecmis_stmt); ?>
