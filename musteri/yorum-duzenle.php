
<?php
require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["musteri"]);

require_once(__DIR__ . "/../baglanti.php");

$kullanici_id = (int) $_SESSION["kullanici_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$yorum_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$yorum_id || $yorum_id < 1) {
    header("Location: yorumlarim.php");
    exit();
}

$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT id, puan, yorum
     FROM musteri_yorumlari
     WHERE id = ? AND kullanici_id = ?"
);

mysqli_stmt_bind_param($sorgu, "ii", $yorum_id, $kullanici_id);
mysqli_stmt_execute($sorgu);

$sonuc = mysqli_stmt_get_result($sorgu);
$yorum = mysqli_fetch_assoc($sonuc);
mysqli_stmt_close($sorgu);

if (!$yorum) {
    http_response_code(404);
    exit("Yorum bulunamadı.");
}

function guvenli($deger) {
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yorum Düzenle | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css">

    <style>
        .duzenle-alan {
            max-width: 650px;
            margin: 50px auto;
            padding: 25px;
            background: white;
            border: 1px solid #dfe5f0;
            border-radius: 12px;
        }

        .duzenle-alan textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            margin: 12px 0 20px;
            border: 1px solid #dfe5f0;
            border-radius: 8px;
        }

        .duzenle-alan select {
            padding: 10px;
            margin: 12px 0 20px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<?php require_once(__DIR__ . "/../ortak-menu.php"); ?>

<main class="duzenle-alan">
    <h1>Yorum Düzenle</h1>

    <p>Yorumunuzu ve yıldız puanınızı güncelleyebilirsiniz.</p>

    <form action="yorum-guncelle.php" method="POST">

        <input type="hidden" name="yorum_id"
               value="<?= (int)$yorum["id"] ?>">

        <input type="hidden" name="csrf_token"
               value="<?= guvenli($_SESSION["csrf_token"]) ?>">

        <label for="puan">Puanınız</label>

        <br>

        <select name="puan" id="puan" required>
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?= $i ?>"
                    <?= (int)$yorum["puan"] === $i ? "selected" : "" ?>>
                    <?= $i ?> Yıldız
                </option>
            <?php endfor; ?>
        </select>

        <br>

        <label for="yorum">Yorumunuz</label>

        <textarea name="yorum" id="yorum"
                  rows="6" maxlength="1000"
                  required><?= guvenli($yorum["yorum"]) ?></textarea>

        <button type="submit" class="buton">
            Değişiklikleri Kaydet
        </button>

        <a href="yorumlarim.php" class="buton"
           style="background: #64748b;">
            İptal
        </a>

    </form>
</main>

</body>
</html>
