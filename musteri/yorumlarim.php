
<?php
require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["musteri"]);

$id = (int) $_SESSION["kullanici_id"];
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT puan, yorum, durum, tarih
     FROM musteri_yorumlari
     WHERE kullanici_id = ?
     ORDER BY tarih DESC, id DESC"
);

mysqli_stmt_bind_param($sorgu, "i", $id);
mysqli_stmt_execute($sorgu);

$sonuc = mysqli_stmt_get_result($sorgu);
$yorumlar = mysqli_fetch_all($sonuc, MYSQLI_ASSOC);
mysqli_stmt_close($sorgu);

function guvenli($deger) {
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yorumlarım | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">

    <style>
        .yorumlarim-alan {
            max-width: 1000px;
            margin: 35px auto;
            padding: 20px;
        }

        .yorumlarim-kart {
            background: white;
            border: 1px solid #dfe5f0;
            border-radius: 12px;
            padding: 22px;
            margin-top: 18px;
        }

        .yorumlarim-ust {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .yorumlarim-yildiz {
            color: #e6a500;
            font-size: 23px;
        }

        .yorumlarim-durum {
            font-weight: bold;
            color: #3659e3;
        }

        .yorumlarim-metin {
            margin: 18px 0;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .yorumlarim-tarih {
            color: #64748b;
            font-size: 14px;
        }
        
.yildiz-puanlama {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
    margin: 15px 0 25px;
}

.yildiz-puanlama input {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
}

.yildiz-puanlama label {
    font-size: 40px;
    color: #cbd5e1;
    cursor: pointer;
    transition: color 0.2s;
}

.yildiz-puanlama label:hover,
.yildiz-puanlama label:hover ~ label,
.yildiz-puanlama input:checked ~ label {
    color: #f5b301;
}

.yildiz-puanlama input:focus-visible + label {
    outline: 2px solid #3659e3;
    border-radius: 4px;
}

#yeni-yorum textarea {
    width: 100%;
    box-sizing: border-box;
    margin: 12px 0 18px;
    padding: 12px;
    border: 1px solid #dfe5f0;
    border-radius: 8px;
}

    </style>
</head>
<body>

<?php require_once(__DIR__ . "/../ortak-menu.php"); ?>

<main class="yorumlarim-alan">
    <h1>Yorumlarım</h1>
<div style="margin: 20px 0;">
    <a href="#yeni-yorum" class="buton">
        ⭐ Yeni Yorum Yap
    </a>
</div>
    <p>Gönderdiğiniz değerlendirmelerin durumunu buradan takip edebilirsiniz.</p>

<section id="yeni-yorum" class="yorumlarim-kart">

    <h2>⭐ Yeni Yorum Yap</h2>

    <p>TeknoServis deneyiminizi bizimle paylaşın.</p>

    <form action="/teknik-servis/yorum-kaydet.php" method="POST">

        <input type="hidden"
               name="csrf_token"
               value="<?= guvenli($_SESSION["csrf_token"]) ?>">

        <label>Puanınız</label>

        <div class="yildiz-puanlama">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio"
                       id="yildiz-<?= $i ?>"
                       name="puan"
                       value="<?= $i ?>"
                       required>

                <label for="yildiz-<?= $i ?>" title="<?= $i ?> yıldız">
                    ★
                </label>
            <?php endfor; ?>
        </div>

        <label for="yorum-metni">Yorumunuz</label>

        <textarea
            id="yorum-metni"
            name="yorum"
            rows="5"
            maxlength="1000"
            placeholder="Teknik servis deneyiminizi yazın..."
            required></textarea>

        <button type="submit" class="buton">
            Yorumu Gönder
        </button>

        <p class="aciklama">
            Yorumunuz yönetici onayından sonra yayınlanacaktır.
        </p>

    </form>

</section>

    <?php if (empty($yorumlar)): ?>
        <div class="yorumlarim-kart">
            <p>Henüz yorum göndermediniz.</p>
            <a class="buton" href="#yeni-yorum">
    ⭐ Yeni Yorum Yap
</a>
        </div>
    <?php else: ?>

        <?php foreach ($yorumlar as $yorum): ?>
            <article class="yorumlarim-kart">

                <div class="yorumlarim-ust">
                    <span class="yorumlarim-yildiz">
                        <?= str_repeat("★", (int)$yorum["puan"]) ?>
                        <?= str_repeat("☆", 5 - (int)$yorum["puan"]) ?>
                    </span>

                    <span class="yorumlarim-durum">
                        <?= guvenli($yorum["durum"]) ?>
                    </span>
                </div>

                <p class="yorumlarim-metin"><?= guvenli($yorum["yorum"]) ?></p>

                <p class="yorumlarim-tarih">
                    Gönderim tarihi: <?= guvenli($yorum["tarih"]) ?>
                </p>

            </article>
        <?php endforeach; ?>

    <?php endif; ?>
</main>

</body>
</html>
