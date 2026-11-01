
<?php
session_start();

// Yalnızca yönetici giriş yapabilir
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
        (string)$deger,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Form güvenliği
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$hata = "";
$basari = "";

// Şube ekleme ve durum değiştirme
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $hata = "İşlem doğrulanamadı.";
    } else {

        $islem = $_POST["islem"] ?? "";

        if ($islem === "ekle") {

            $il = trim($_POST["il"] ?? "");
            $ilce = trim($_POST["ilce"] ?? "");
            $sube_adi = trim($_POST["sube_adi"] ?? "");
            $adres = trim($_POST["adres"] ?? "");
            $telefon = trim($_POST["telefon"] ?? "");
            $calisma = trim($_POST["calisma_saatleri"] ?? "");

            if (
                $il === "" ||
                $ilce === "" ||
                $sube_adi === "" ||
                $adres === "" ||
                $telefon === "" ||
                $calisma === ""
            ) {
                $hata = "Lütfen tüm alanları doldurun.";
            } elseif (
                mb_strlen($il) > 100 ||
                mb_strlen($ilce) > 100 ||
                mb_strlen($sube_adi) > 150 ||
                mb_strlen($telefon) > 20 ||
                mb_strlen($calisma) > 150
            ) {
                $hata = "Girilen bilgiler çok uzun.";
            } else {

                $sql = "INSERT INTO servis_noktalari
                        (il, ilce, sube_adi, adres,
                         telefon, calisma_saatleri)
                        VALUES (?, ?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($baglanti, $sql);

                if ($stmt) {
                    mysqli_stmt_bind_param(
                        $stmt,
                        "ssssss",
                        $il,
                        $ilce,
                        $sube_adi,
                        $adres,
                        $telefon,
                        $calisma
                    );

                    if (mysqli_stmt_execute($stmt)) {
                        $basari = "Servis noktası başarıyla eklendi.";
                    } else {
                        $hata = "Şube eklenirken hata oluştu.";
                    }

                    mysqli_stmt_close($stmt);
                } else {
                    $hata = "Veritabanı işlemi hazırlanamadı.";
                }
            }

        } elseif ($islem === "durum") {

            $sube_id = filter_input(
                INPUT_POST,
                "sube_id",
                FILTER_VALIDATE_INT
            );

            $durum = $_POST["durum"] ?? "";

            if (
                !$sube_id ||
                !in_array($durum, ["Aktif", "Pasif"], true)
            ) {
                $hata = "Geçersiz şube veya durum.";
            } else {

                $stmt = mysqli_prepare(
                    $baglanti,
                    "UPDATE servis_noktalari
                     SET durum = ?
                     WHERE id = ?"
                );

                if ($stmt) {
                    mysqli_stmt_bind_param(
                        $stmt,
                        "si",
                        $durum,
                        $sube_id
                    );

                    if (mysqli_stmt_execute($stmt)) {
                        $basari = "Şube durumu güncellendi.";
                    } else {
                        $hata = "Durum güncellenemedi.";
                    }

                    mysqli_stmt_close($stmt);
                } else {
                    $hata = "Veritabanı işlemi hazırlanamadı.";
                }
            }
        }
    }
}

// Şubeleri listele
$subeler = [];

$sonuc = mysqli_query(
    $baglanti,
    "SELECT * FROM servis_noktalari
     ORDER BY il, ilce, sube_adi"
);

if ($sonuc) {
    while ($satir = mysqli_fetch_assoc($sonuc)) {
        $subeler[] = $satir;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Noktası Yönetimi | TeknoServis</title>
    <link rel="stylesheet" href="../style.css?v=6">

    <style>
        .sube-yonetim {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .yonetim-kart {
            background: white;
            padding: 30px;
            border: 1px solid #e0e6f0;
            border-radius: 14px;
            margin-bottom: 25px;
        }

        .sube-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .sube-form label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .sube-form input,
        .sube-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .tam-genislik {
            grid-column: 1 / -1;
        }

        .sube-buton {
            background: #365de5;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 13px 20px;
            cursor: pointer;
            font-weight: bold;
        }

        .sube-buton:hover {
            background: #2447b2;
        }

        .sube-kayit {
            border: 1px solid #e0e6f0;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
        }

        .sube-kayit p {
            line-height: 1.7;
        }

        .geri-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #365de5;
            text-decoration: none;
            font-weight: bold;
        }

        .basari {
            color: #166534;
            margin-bottom: 20px;
        }

        .hata {
            color: #b91c1c;
            margin-bottom: 20px;
        }

        @media (max-width: 700px) {
            .sube-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="ust-menu">
        <a href="../index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav>
            <a href="yonetici-panel.php">Yönetici Paneli</a>
            <a href="../cikis.php">Çıkış Yap</a>
        </nav>
    </div>
</header>

<main class="sube-yonetim">

    <a href="yonetici-panel.php" class="geri-link">
        ← Yönetici Paneline Dön
    </a>

    <h1>Servis Noktası Yönetimi</h1>
    <p>Yeni şube ekleyin ve mevcut servis noktalarını yönetin.</p>

    <?php if ($hata !== ""): ?>
        <p class="hata"><?= guvenli($hata) ?></p>
    <?php endif; ?>

    <?php if ($basari !== ""): ?>
        <p class="basari"><?= guvenli($basari) ?></p>
    <?php endif; ?>

    <section class="yonetim-kart">
        <h2>Yeni Servis Noktası Ekle</h2>

        <form method="POST" class="sube-form">

            <input type="hidden" name="islem" value="ekle">

            <input type="hidden" name="csrf_token"
                   value="<?= guvenli($_SESSION["csrf_token"]) ?>">

            <div>
                <label for="il">İl</label>
                <input type="text" id="il" name="il"
                       maxlength="100" required>
            </div>

            <div>
                <label for="ilce">İlçe</label>
                <input type="text" id="ilce" name="ilce"
                       maxlength="100" required>
            </div>

            <div>
                <label for="sube_adi">Şube Adı</label>
                <input type="text" id="sube_adi" name="sube_adi"
                       maxlength="150" required>
            </div>

            <div>
                <label for="telefon">Telefon</label>
                <input type="tel" id="telefon" name="telefon"
                       maxlength="20" required>
            </div>

            <div class="tam-genislik">
                <label for="adres">Açık Adres</label>
                <textarea id="adres" name="adres"
                          rows="3" required></textarea>
            </div>

            <div class="tam-genislik">
                <label for="calisma_saatleri">Çalışma Saatleri</label>
                <input type="text" id="calisma_saatleri"
                       name="calisma_saatleri"
                       placeholder="Örn: Hafta içi 09:00 - 18:00"
                       maxlength="150" required>
            </div>

            <div class="tam-genislik">
                <button type="submit" class="sube-buton">
                    Servis Noktası Ekle
                </button>
            </div>

        </form>
    </section>

    <section class="yonetim-kart">
        <h2>Mevcut Servis Noktaları</h2>

        <?php if (count($subeler) === 0): ?>

            <p>Henüz kayıtlı servis noktası bulunmuyor.</p>

        <?php else: ?>

            <?php foreach ($subeler as $sube): ?>

                <div class="sube-kayit">

                    <h3><?= guvenli($sube["sube_adi"]) ?></h3>

                    <p>
                        <strong>İl / İlçe:</strong>
                        <?= guvenli($sube["il"]) ?> /
                        <?= guvenli($sube["ilce"]) ?>
                    </p>

                    <p>
                        <strong>Adres:</strong>
                        <?= guvenli($sube["adres"]) ?>
                    </p>

                    <p>
                        <strong>Telefon:</strong>
                        <?= guvenli($sube["telefon"]) ?>
                    </p>

                    <p>
                        <strong>Çalışma Saatleri:</strong>
                        <?= guvenli($sube["calisma_saatleri"]) ?>
                    </p>

                    <p>
                        <strong>Durum:</strong>
                        <?= guvenli($sube["durum"]) ?>
                    </p>

                    <form method="POST">
                        <input type="hidden" name="islem" value="durum">

                        <input type="hidden" name="csrf_token"
                               value="<?= guvenli($_SESSION["csrf_token"]) ?>">

                        <input type="hidden" name="sube_id"
                               value="<?= (int)$sube["id"] ?>">

                        <?php if ($sube["durum"] === "Aktif"): ?>
                            <button type="submit"
                                    name="durum"
                                    value="Pasif"
                                    class="sube-buton">
                                Pasif Yap
                            </button>
                        <?php else: ?>
                            <button type="submit"
                                    name="durum"
                                    value="Aktif"
                                    class="sube-buton">
                                Aktif Yap
                            </button>
                        <?php endif; ?>
                    </form>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>
    </section>

</main>

</body>
</html>
