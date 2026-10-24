<?php
session_start();

if (!isset($_SESSION["kullanici_id"])) {
    header("Location: /teknik-servis/musteri/giris.php");
    exit();
}

if (($_SESSION["rol"] ?? "") !== "musteri") {
    http_response_code(403);
    exit("Bu sayfaya erişiminiz yok.");
}

if (empty($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Oluştur | TeknoServis</title>
    <link rel="stylesheet" href="/teknik-servis/style.css?v=4">
</head>
<body>

<header>
    <div class="ust-menu">
        <a href="/teknik-servis/index.php" class="logo">
            Tekno<span>Servis</span>
        </a>

        <nav aria-label="Müşteri menüsü">
            <a href="/teknik-servis/musteri/musteri-panel.php">Panelim</a>
            <a href="/teknik-servis/musteri/servis-olustur.php"
               aria-current="page">Servis Oluştur</a>
            <a href="/teknik-servis/musteri/servis-takip.php">Servis Takip</a>
            <a href="/teknik-servis/musteri/profil.php">Profilim</a>
            <a href="/teknik-servis/musteri/cikis.php"
               onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                Çıkış Yap
            </a>
        </nav>
    </div>
</header>

<main>
    <section class="form-alani">
        <h1>Servis Oluştur</h1>
        <p class="aciklama">
            Cihaz bilgilerinizi ve yaşadığınız sorunu yazın.
        </p>

        <form action="/teknik-servis/musteri/servis-kaydet.php"
              method="POST">

            <input type="hidden" name="csrf"
                   value="<?= htmlspecialchars($_SESSION["csrf"], ENT_QUOTES, "UTF-8") ?>">

            <label for="cihaz">Cihaz Türü</label>
            <select id="cihaz" name="cihaz" required>
                <option value="">Cihaz seçin</option>
                <option>Masaüstü Bilgisayar</option>
                <option>Dizüstü Bilgisayar</option>
                <option>Tablet</option>
                <option>Diğer</option>
            </select>

            <label for="marka">Marka</label>
            <input type="text" id="marka" name="marka"
                   maxlength="50" required>

            <label for="model">Model</label>
            <input type="text" id="model" name="model"
                   maxlength="50" required>

            <label for="ariza">Arıza Açıklaması</label>
            <textarea id="ariza" name="ariza" rows="5"
                      maxlength="1000" required></textarea>

            <button type="submit">Servis Kaydı Oluştur</button>
        </form>
    </section>
</main>

</body>
</html>