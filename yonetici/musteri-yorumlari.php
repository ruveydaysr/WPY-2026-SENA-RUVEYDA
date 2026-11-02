
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["yonetici"]);

mysqli_set_charset($baglanti, "utf8mb4");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function guvenli($deger) {
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

// Yorum onaylama ve reddetme
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";
    $yorum_id = filter_input(
        INPUT_POST,
        "yorum_id",
        FILTER_VALIDATE_INT
    );
    $karar = $_POST["karar"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Geçersiz güvenlik anahtarı.");
    }

    if (!$yorum_id || !in_array(
        $karar,
        ["Onaylandı", "Reddedildi"],
        true
    )) {
        http_response_code(400);
        exit("Geçersiz işlem.");
    }

    $sorgu = mysqli_prepare(
        $baglanti,
        "UPDATE musteri_yorumlari
         SET durum = ?
         WHERE id = ? AND durum = 'Bekliyor'"
    );

    mysqli_stmt_bind_param(
        $sorgu,
        "si",
        $karar,
        $yorum_id
    );

    mysqli_stmt_execute($sorgu);
    $degisen = mysqli_stmt_affected_rows($sorgu);
    mysqli_stmt_close($sorgu);

    $_SESSION["yorum_yonetim_mesaji"] = $degisen > 0
        ? "Yorum durumu başarıyla güncellendi."
        : "Bu yorum daha önce değerlendirilmiş.";

    header(
        "Location: /teknik-servis/yonetici/musteri-yorumlari.php",
        true,
        303
    );
    exit();
}

$mesaj = $_SESSION["yorum_yonetim_mesaji"] ?? "";
unset($_SESSION["yorum_yonetim_mesaji"]);

// Yorumları durumlarına göre filtrele
$filtre = $_GET["durum"] ?? "Tümü";

$gecerliFiltreler = [
    "Tümü",
    "Bekliyor",
    "Onaylandı",
    "Reddedildi"
];

if (!is_string($filtre) ||
    !in_array($filtre, $gecerliFiltreler, true)) {
    $filtre = "Tümü";
}

$sql = "SELECT
            y.id,
            y.puan,
            y.yorum,
            y.durum,
            y.tarih,
            k.adsoyad
        FROM musteri_yorumlari AS y
        INNER JOIN kullanicilar AS k
            ON k.id = y.kullanici_id";

if ($filtre !== "Tümü") {
    $sql .= " WHERE y.durum = ?";
}

$sql .= " ORDER BY
          CASE WHEN y.durum = 'Bekliyor' THEN 0 ELSE 1 END,
          y.tarih DESC, y.id DESC";

$sorgu = mysqli_prepare($baglanti, $sql);

if ($filtre !== "Tümü") {
    mysqli_stmt_bind_param($sorgu, "s", $filtre);
}

mysqli_stmt_execute($sorgu);

$sonuc = mysqli_stmt_get_result($sorgu);
$yorumlar = mysqli_fetch_all($sonuc, MYSQLI_ASSOC);

mysqli_stmt_close($sorgu);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Yorumları | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">

    <style>
        .yorum-yonetim {
            max-width: 1150px;
            margin: 35px auto;
            padding: 25px;
        }

        .yorum-yonetim h1 {
            margin-bottom: 12px;
        }

        .yorum-tablo-alani {
            overflow-x: auto;
            margin-top: 25px;
        }

        .yorum-tablo {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .yorum-tablo th,
        .yorum-tablo td {
            padding: 15px;
            border: 1px solid #dfe5f0;
            text-align: left;
            vertical-align: top;
        }

        .yorum-tablo th {
            background: #eef2ff;
        }

        .yorum-tablo td:nth-child(3) {
            min-width: 240px;
            overflow-wrap: anywhere;
        }

        .yorum-yildiz {
            color: #e6a500;
            white-space: nowrap;
            font-size: 19px;
        }

        .yorum-islemler {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .yorum-islemler button {
            padding: 9px 13px;
            cursor: pointer;
        }

        .yorum-bilgi {
            padding: 14px;
            background: #eaf7ed;
            border-radius: 8px;
            margin: 15px 0;
        }
        
.yorum-filtreler {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 25px;
}

.yorum-filtre {
    padding: 10px 17px;
    background: white;
    border: 1px solid #dfe5f0;
    border-radius: 8px;
    color: #18315b;
    text-decoration: none;
    font-weight: 600;
}

.yorum-filtre:hover {
    background: #eef2ff;
}

.yorum-filtre.aktif {
    background: #3659e3;
    color: white;
    border-color: #3659e3;
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
            <a href="/teknik-servis/yonetici/yonetici-panel.php">
                Yönetici Paneli
            </a>

            <a href="/teknik-servis/yonetici/musteri-yorumlari.php"
               aria-current="page">
                Müşteri Yorumları
            </a>

            <a href="/teknik-servis/cikis.php"
               onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                Çıkış Yap
            </a>
        </nav>
    </div>
</header>

<main class="yorum-yonetim">

    <h1>Müşteri Yorumları</h1>

    <p>
        Müşterilerin değerlendirmelerini buradan
        inceleyip onaylayabilir veya reddedebilirsiniz.
    </p>

    <?php if ($mesaj !== ""): ?>
        <p class="yorum-bilgi" role="status">
            <?= guvenli($mesaj) ?>
        </p>
    <?php endif; ?>

<div class="yorum-filtreler">
    <?php foreach ($gecerliFiltreler as $secenek): ?>
        <a
            href="?durum=<?= urlencode($secenek) ?>"
            class="yorum-filtre <?= $filtre === $secenek ? 'aktif' : '' ?>"
        >
            <?= guvenli($secenek) ?>
        </a>
    <?php endforeach; ?>
</div>

    <div class="yorum-tablo-alani">
        <table class="yorum-tablo">
            <thead>
                <tr>
                    <th>Müşteri</th>
                    <th>Puan</th>
                    <th>Yorum</th>
                    <th>Tarih</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($yorumlar)): ?>
                    <tr>
                        <td colspan="6">
                            Henüz müşteri yorumu bulunmuyor.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($yorumlar as $yorum): ?>
                    <tr>
                        <td><?= guvenli($yorum["adsoyad"]) ?></td>

                        <td>
                            <span class="yorum-yildiz">
                                <?= str_repeat(
                                    "★",
                                    (int)$yorum["puan"]
                                ) ?>
                            </span>
                        </td>

                        <td><?= nl2br(guvenli($yorum["yorum"])) ?></td>

                        <td><?= guvenli($yorum["tarih"]) ?></td>

                        <td><?= guvenli($yorum["durum"]) ?></td>

                        <td>
                            <?php if ($yorum["durum"] === "Bekliyor"): ?>

                                <div class="yorum-islemler">
                                    <form method="POST">
                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= guvenli($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden"
                                               name="yorum_id"
                                               value="<?= (int)$yorum["id"] ?>">

                                        <button type="submit"
                                                name="karar"
                                                value="Onaylandı">
                                            Onayla
                                        </button>
                                    </form>

                                    <form method="POST"
                                          onsubmit="return confirm('Bu yorumu reddetmek istiyor musunuz?')">
                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= guvenli($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden"
                                               name="yorum_id"
                                               value="<?= (int)$yorum["id"] ?>">

                                        <button type="submit"
                                                name="karar"
                                                value="Reddedildi">
                                            Reddet
                                        </button>
                                    </form>
                                </div>

                            <?php else: ?>
                                Değerlendirildi
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</main>

</body>
</html>
