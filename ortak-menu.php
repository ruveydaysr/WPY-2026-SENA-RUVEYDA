
<?php
// Bu dosyayı çağıran sayfada oturum başlatılmış olmalı.
$menu_giris = !empty($_SESSION["kullanici_id"]);
$menu_rol = $_SESSION["rol"] ?? "";
$menu_ad = "";

if ($menu_giris && isset($baglanti)) {
    $menu_id = (int) $_SESSION["kullanici_id"];

    $menu_sorgu = mysqli_prepare(
        $baglanti,
        "SELECT adsoyad FROM kullanicilar WHERE id = ?"
    );

    mysqli_stmt_bind_param($menu_sorgu, "i", $menu_id);
    mysqli_stmt_execute($menu_sorgu);
    mysqli_stmt_bind_result($menu_sorgu, $menu_ad);
    mysqli_stmt_fetch($menu_sorgu);
    mysqli_stmt_close($menu_sorgu);
}

$menu_ad = htmlspecialchars(
    (string) $menu_ad,
    ENT_QUOTES,
    "UTF-8"
);
?>

<style>
.ts-menu {
    background: #ffffff;
    border-bottom: 1px solid #e0e7f5;
    padding: 18px 8%;
}

.ts-menu-icerik {
    max-width: 1300px;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.ts-logo {
    font-size: 25px;
    font-weight: bold;
    text-decoration: none;
    color: #10254d;
}

.ts-logo span {
    color: #3659e3;
}

.ts-baglantilar {
    display: flex;
    align-items: center;
    gap: 22px;
    flex-wrap: wrap;
}

.ts-baglantilar a {
    color: #172d58;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
}

.ts-baglantilar a:hover {
    color: #3659e3;
}

.ts-hesap {
    position: relative;
}

.ts-hesap summary {
    cursor: pointer;
    list-style: none;
    background: #edf2ff;
    color: #183b83;
    border-radius: 8px;
    padding: 11px 15px;
    font-weight: bold;
}

.ts-hesap summary::-webkit-details-marker {
    display: none;
}

.ts-acilir {
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    min-width: 210px;
    padding: 10px;
    background: white;
    border: 1px solid #dce5ff;
    border-radius: 10px;
    box-shadow: 0 8px 25px rgba(0,0,0,.10);
    z-index: 1000;
}

.ts-acilir a {
    display: block;
    padding: 11px;
    border-radius: 6px;
}

.ts-acilir a:hover {
    background: #edf2ff;
}

@media (max-width: 800px) {
    .ts-menu {
        padding: 16px 5%;
    }

    .ts-baglantilar {
        gap: 12px;
    }
}
</style>

<header class="ts-menu">
    <div class="ts-menu-icerik">

        <a href="/teknik-servis/index.php" class="ts-logo">
            Tekno<span>Servis</span>
        </a>

<nav class="ts-baglantilar" aria-label="Ana menü">

    <a href="/teknik-servis/index.php">Ana Sayfa</a>

    <a href="/teknik-servis/index.php#hizmetlerimiz">
        Hizmetler
    </a>

    <a href="/teknik-servis/index.php#iletisim">
        İletişim
    </a>

    <?php if ($menu_giris && $menu_rol === "musteri"): ?>

        <details class="ts-hesap">
            <summary>
                👤 <?= $menu_ad !== "" ? $menu_ad : "Hesabım" ?> ▾
            </summary>

            <div class="ts-acilir">

                <a href="/teknik-servis/musteri/servis-takip.php">
                    Servislerim
                </a>

                <a href="/teknik-servis/musteri/servis-olustur.php">
                    Yeni Servis Talebi
                </a>
            
<a href="/teknik-servis/musteri/yorumlarim.php">
    Yorumlarım
</a>


                <a href="/teknik-servis/musteri/profil.php">
                    Profilim
                </a>

                <a href="/teknik-servis/musteri/cikis.php"
                   onclick="return confirm('Çıkış yapmak istiyor musunuz?')">
                    Çıkış Yap
                </a>

            </div>
        </details>

    <?php elseif (!$menu_giris): ?>

        <a href="/teknik-servis/giris.php">Giriş Yap</a>
        <a href="/teknik-servis/kayit.php">Kayıt Ol</a>

    <?php endif; ?>

</nav>

    </div>
</header>
