<?php
require_once(__DIR__ . "/../yetki-kontrol.php");
yetkiKontrol(["musteri"]);

require_once(__DIR__ . "/../baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");

// Aktif servis noktalarını getir
$subeler = [];

$subeSorgu = mysqli_query(
    $baglanti,
    "SELECT id, il, ilce, sube_adi
     FROM servis_noktalari
     WHERE durum = 'Aktif'
     ORDER BY il, ilce, sube_adi"
);

if (!$subeSorgu) {
    http_response_code(500);
    exit("Servis noktaları yüklenemedi.");
}

while ($satir = mysqli_fetch_assoc($subeSorgu)) {
    $subeler[] = $satir;
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
            <a href="/teknik-servis/musteri/musteri-panel.php">
                Panelim
            </a>
            <a href="/teknik-servis/musteri/servis-olustur.php"
               aria-current="page">
                Servis Oluştur
            </a>
            <a href="/teknik-servis/musteri/servis-takip.php">
                Servis Takip
            </a>
            <a href="/teknik-servis/musteri/profil.php">
                Profilim
            </a>
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

<label for="il">Servis Hizmeti Alınacak İl</label>
<select id="il" name="il" required>
    <option value="">İl seçiniz</option>

    <?php
    $aktifIller = array_unique(
        array_column($subeler, "il")
    );

    foreach ($aktifIller as $il):
    ?>
        <option value="<?= htmlspecialchars($il, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($il, ENT_QUOTES, 'UTF-8') ?>
        </option>
    <?php endforeach; ?>
</select>

<label for="servis_noktasi_id">
    Bilgisayarınızı Teslim Edeceğiniz Şube
</label>

<select id="servis_noktasi_id"
        name="servis_noktasi_id"
        required disabled>
    <option value="">Önce il seçiniz</option>
</select>

<p class="aciklama">
    Bilgisayarınızı seçtiğiniz şubeye fiziksel olarak
    teslim etmeniz gerekmektedir.
</p>


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

<script>
const subeler = <?= json_encode(
    $subeler,
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
) ?>;

const ilSecimi = document.getElementById("il");
const subeSecimi = document.getElementById("servis_noktasi_id");

ilSecimi.addEventListener("change", function () {
    const secilenIl = this.value;

    subeSecimi.innerHTML = "";
    subeSecimi.disabled = true;

    const ilkSecenek = document.createElement("option");
    ilkSecenek.value = "";
    ilkSecenek.textContent = "Şube seçiniz";
    subeSecimi.appendChild(ilkSecenek);

    if (!secilenIl) {
        ilkSecenek.textContent = "Önce il seçiniz";
        return;
    }

    const uygunSubeler = subeler.filter(
        sube => sube.il === secilenIl
    );

    uygunSubeler.forEach(function (sube) {
        const secenek = document.createElement("option");

        secenek.value = sube.id;
        secenek.textContent =
            sube.sube_adi + " - " + sube.ilce;

        subeSecimi.appendChild(secenek);
    });

    subeSecimi.disabled = uygunSubeler.length === 0;
});
</script>

</body>
</html>