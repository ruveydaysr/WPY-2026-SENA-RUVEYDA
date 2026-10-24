<?php
session_start();

if (!isset($_SESSION["kullanici_id"])) {
    header("Location: giris.php");
    exit();
}

if (($_SESSION["rol"] ?? "") !== "musteri") {
    http_response_code(403);
    exit("Bu sayfaya erişiminiz yok.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once(__DIR__ . "/../baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");

$id = (int) $_SESSION["kullanici_id"];
$mesaj = "";

if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}

function guvenli($deger) {
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

// Giriş yapan müşterinin bilgileri
$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT adsoyad, email, telefon, sifre
     FROM kullanicilar WHERE id = ?"
);
mysqli_stmt_bind_param($sorgu, "i", $id);
mysqli_stmt_execute($sorgu);
mysqli_stmt_bind_result($sorgu, $adsoyad, $email, $telefon, $kayitli_sifre);
$bulundu = mysqli_stmt_fetch($sorgu);
mysqli_stmt_close($sorgu);

if (!$bulundu) {
    session_unset();
    session_destroy();
    header("Location: giris.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf"] ?? "";

    if (!is_string($token) || !hash_equals($_SESSION["csrf"], $token)) {
        http_response_code(403);
        exit("Formun süresi dolmuş. Sayfayı yenileyin.");
    }

    $islem = $_POST["islem"] ?? "";

    try {
        if ($islem === "bilgi") {
            $yeni_ad = trim((string) ($_POST["adsoyad"] ?? ""));
            $yeni_telefon = trim((string) ($_POST["telefon"] ?? ""));

            if ($yeni_ad === "" || mb_strlen($yeni_ad) > 100) {
                $mesaj = "Ad soyad 1–100 karakter olmalıdır.";
            } elseif (!preg_match('/^05[0-9]{9}$/', $yeni_telefon)) {
                $mesaj = "Telefon numarası 05 ile başlayan 11 rakam olmalıdır.";
            } else {
                $sorgu = mysqli_prepare(
                    $baglanti,
                    "UPDATE kullanicilar SET adsoyad = ?, telefon = ?
                     WHERE id = ?"
                );
                mysqli_stmt_bind_param(
                    $sorgu, "ssi", $yeni_ad, $yeni_telefon, $id
                );
                mysqli_stmt_execute($sorgu);
                mysqli_stmt_close($sorgu);

                $adsoyad = $yeni_ad;
                $telefon = $yeni_telefon;
                $mesaj = "Profil bilgileriniz güncellendi.";
            }
        } elseif ($islem === "sifre") {
            $mevcut = (string) ($_POST["mevcut_sifre"] ?? "");
            $yeni = (string) ($_POST["yeni_sifre"] ?? "");
            $tekrar = (string) ($_POST["sifre_tekrar"] ?? "");

            if (!password_verify($mevcut, $kayitli_sifre)) {
                $mesaj = "Mevcut şifreniz yanlış.";
            } elseif (mb_strlen($yeni) < 8 || strlen($yeni) > 72) {
                $mesaj = "Yeni şifre en az 8 karakter, en fazla 72 bayt olmalıdır.";
            } elseif ($yeni !== $tekrar) {
                $mesaj = "Yeni şifreler birbiriyle eşleşmiyor.";
            } else {
                $hash = password_hash($yeni, PASSWORD_DEFAULT);

                $sorgu = mysqli_prepare(
                    $baglanti,
                    "UPDATE kullanicilar SET sifre = ? WHERE id = ?"
                );
                mysqli_stmt_bind_param($sorgu, "si", $hash, $id);
                mysqli_stmt_execute($sorgu);
                mysqli_stmt_close($sorgu);

                session_regenerate_id(true);
                $mesaj = "Şifreniz değiştirildi.";
            }
        } else {
            $mesaj = "Geçersiz işlem.";
        }
    } catch (mysqli_sql_exception $hata) {
        error_log($hata->getMessage());
        $mesaj = "İşlem tamamlanamadı. Lütfen tekrar deneyin.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim | TeknoServis</title>
    <link rel="stylesheet" href="../style.css?v=4">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="../index.php" class="logo">Tekno<span>Servis</span></a>

        <nav aria-label="Müşteri menüsü">
            <a href="musteri-panel.php">Panelim</a>
            <a href="servis-olustur.php">Servis Oluştur</a>
            <a href="servis-takip.php">Servis Takip</a>
            <a href="profil.php" aria-current="page">Profilim</a>
            <a href="cikis.php"
               onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                Çıkış Yap
            </a>
        </nav>
    </div>
</header>

<main>
    <h1>Profilim</h1>

    <?php if ($mesaj !== ""): ?>
        <p class="kart" role="status"><?= guvenli($mesaj) ?></p>
    <?php endif; ?>

    <div class="panel-kartlari">
        <section class="panel-kart">
            <h2>Profil Bilgileri</h2>

            <form method="POST" action="profil.php">
                <input type="hidden" name="csrf"
                       value="<?= guvenli($_SESSION["csrf"]) ?>">
                <input type="hidden" name="islem" value="bilgi">

                <label for="adsoyad">Ad Soyad</label>
                <input id="adsoyad" name="adsoyad" type="text"
                       value="<?= guvenli($adsoyad) ?>"
                       autocomplete="name" maxlength="100" required>

                <label for="email">E-posta</label>
                <input id="email" type="email"
                       value="<?= guvenli($email) ?>" readonly>

                <label for="telefon">Telefon</label>
                <input id="telefon" name="telefon" type="tel"
                       value="<?= guvenli($telefon) ?>"
                       autocomplete="tel" pattern="05[0-9]{9}"
                       maxlength="11" required>

                <div class="butonlar">
                    <button type="submit">Bilgileri Kaydet</button>
                </div>
            </form>
        </section>

        <section class="panel-kart">
            <h2>Şifre Değiştir</h2>

            <form method="POST" action="profil.php">
                <input type="hidden" name="csrf"
                       value="<?= guvenli($_SESSION["csrf"]) ?>">
                <input type="hidden" name="islem" value="sifre">

                <label for="mevcut">Mevcut Şifre</label>
                <input id="mevcut" name="mevcut_sifre" type="password"
                       autocomplete="current-password" required>

                <label for="yeni">Yeni Şifre</label>
                <input id="yeni" name="yeni_sifre" type="password"
                       autocomplete="new-password" minlength="8" required>

                <label for="tekrar">Yeni Şifre Tekrar</label>
                <input id="tekrar" name="sifre_tekrar" type="password"
                       autocomplete="new-password" minlength="8" required>

                <div class="butonlar">
                    <button type="submit">Şifreyi Değiştir</button>
                </div>
            </form>
        </section>
    </div>
</main>

</body>
</html>