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
               AND k.durum = 'aktif'"
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

        $_SESSION["atama_mesaji"] = $degisen > 0
            ? "Teknisyen ataması kaydedildi."
            : "Değişiklik yapılmadı. Aynı teknisyen zaten atanmış olabilir; servis ve teknisyen bilgilerini kontrol ediniz.";

        header("Location: yonetici-panel.php");
        exit();
    }
}

$mesaj = $_SESSION["atama_mesaji"] ?? "";
unset($_SESSION["atama_mesaji"]);

$teknisyenler = [];

$sonuc = mysqli_query(
    $baglanti,
    "SELECT id, adsoyad
     FROM kullanicilar
     WHERE rol IN ('teknisyen', 'teknik')
       AND durum = 'aktif'
     ORDER BY adsoyad"
);

while ($satir = mysqli_fetch_assoc($sonuc)) {
    $teknisyenler[] = $satir;
}

$servisler = mysqli_query(
    $baglanti,
    "SELECT s.id, s.cihaz, s.marka, s.model,
            s.ariza, s.durum, s.tarih, s.teknisyen_id,
            m.adsoyad AS musteri_adi,
            m.telefon AS musteri_telefon,
            t.adsoyad AS teknisyen_adi
     FROM servisler AS s
     LEFT JOIN kullanicilar AS m ON m.id = s.kullanici_id
     LEFT JOIN kullanicilar AS t ON t.id = s.teknisyen_id
     ORDER BY s.id DESC"
);
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
    </style>
</head>
<body>

<header>
    <h1>Bilgisayar Teknik Servis</h1>

    <nav>
        <a href="yonetici-panel.php">Yönetici Paneli</a>
        <a href="../cikis.php"
           onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');">
            Çıkış Yap
        </a>
    </nav>
</header>

<main>
    <section class="yonetici-alani">
        <h2>Müşteri Talepleri ve Teknisyen Atama</h2>

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
                        <td colspan="9">Henüz servis talebi yok.</td>
                    </tr>
                <?php } ?>

                <?php while ($servis = mysqli_fetch_assoc($servisler)) { ?>
                    <tr>
                        <td><?= (int) $servis["id"] ?></td>
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
                            <?php if (!empty($teknisyenler)) { ?>
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

                                        <?php foreach ($teknisyenler as $teknisyen) { ?>
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
                                Aktif teknisyen yok.
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

</body>
</html>