
<?php
session_start();

// Yalnızca ROOT erişebilir
if (
    !isset($_SESSION["kullanici_id"]) ||
    !isset($_SESSION["rol"]) ||
    $_SESSION["rol"] !== "root"
) {
    header("Location: ../giris.php");
    exit();
}

require_once("../baglanti.php");

// CSRF güvenlik anahtarı
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Kullanıcıları listele
$sorgu = "SELECT id, adsoyad, email, telefon, rol, durum
          FROM kullanicilar
          ORDER BY id DESC";

$sonuc = mysqli_query($baglanti, $sorgu);

if (!$sonuc) {
    die("Kullanıcılar listelenirken hata oluştu.");
}

// Güvenli HTML çıktısı
function guvenli($deger) {
    return htmlspecialchars(
        (string)$deger,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
        }

        header {
            background: #202c3c;
            color: white;
            text-align: center;
            padding: 25px;
        }

        main {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .kutu {
            background: white;
            padding: 25px;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 8px #00000012;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #eaf0f7;
        }

        .geri {
            display: inline-block;
            margin-bottom: 20px;
            color: #202c3c;
            text-decoration: none;
            font-weight: bold;
        }

        .buton {
            border: none;
            padding: 9px 14px;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }

        .pasif-yap {
            background: #c0392b;
        }

        .aktif-yap {
            background: #218c4a;
        }

        .rol-buton {
            background: #2563a6;
        }

        .kilitli {
            color: #777;
            font-size: 13px;
        }

        select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 8px;
        }

        .rol-form {
            min-width: 160px;
        }

        .rol-form button {
            display: block;
        }
    </style>
</head>

<body>

<header>
    <h1>Kullanıcı Yönetimi</h1>
    <p>ROOT Yönetim Paneli</p>
</header>

<main>

    <a class="geri" href="root-panel.php">
        ← ROOT Paneline Dön
    </a>

    <div class="kutu">
        <h2>Kayıtlı Kullanıcılar</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Telefon</th>
                    <th>Rol</th>
                    <th>Durum</th>
                    <th>Rol Değiştir</th>
                    <th>İşlem</th>
                </tr>
            </thead>

            <tbody>
                <?php if (mysqli_num_rows($sonuc) > 0): ?>

                    <?php while ($kullanici = mysqli_fetch_assoc($sonuc)): ?>
                        <tr>
                            <td><?= guvenli($kullanici["id"]) ?></td>
                            <td><?= guvenli($kullanici["adsoyad"]) ?></td>
                            <td><?= guvenli($kullanici["email"]) ?></td>
                            <td><?= guvenli($kullanici["telefon"]) ?></td>
                            <td><?= guvenli($kullanici["rol"]) ?></td>
                            <td><?= guvenli($kullanici["durum"]) ?></td>

                            <!-- ROL DEĞİŞTİRME -->
                            <td>
                                <?php if ($kullanici["rol"] === "root"): ?>

                                    <span class="kilitli">
                                        ROOT - Değiştirilemez
                                    </span>

                                <?php else: ?>

                                    <form
                                        class="rol-form"
                                        action="kullanici-rol.php"
                                        method="POST"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= guvenli($_SESSION["csrf_token"]) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="kullanici_id"
                                            value="<?= guvenli($kullanici["id"]) ?>"
                                        >

                                        <select name="yeni_rol" required>
                                            <option
                                                value="musteri"
                                                <?= $kullanici["rol"] === "musteri" ? "selected" : "" ?>
                                            >
                                                Müşteri
                                            </option>

                                            <option
                                                value="teknik"
                                                <?= $kullanici["rol"] === "teknik" ? "selected" : "" ?>
                                            >
                                                Teknik Ekip
                                            </option>

                                            <option
                                                value="mudurluk"
                                                <?= $kullanici["rol"] === "mudurluk" ? "selected" : "" ?>
                                            >
                                                Müdürlük
                                            </option>
                                        </select>

                                        <button
                                            type="submit"
                                            class="buton rol-buton"
                                            onclick="return confirm('Kullanıcının rolünü değiştirmek istiyor musunuz?');"
                                        >
                                            Rolü Güncelle
                                        </button>
                                    </form>

                                <?php endif; ?>
                            </td>

                            <!-- AKTİF / PASİF İŞLEMİ -->
                            <td>
                                <?php if ($kullanici["rol"] === "root"): ?>

                                    <span class="kilitli">
                                        ROOT - İşlem yapılamaz
                                    </span>

                                <?php elseif (
                                    $kullanici["durum"] === "aktif" ||
                                    $kullanici["durum"] === "pasif"
                                ): ?>

                                    <form
                                        action="kullanici-durum.php"
                                        method="POST"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= guvenli($_SESSION["csrf_token"]) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="kullanici_id"
                                            value="<?= guvenli($kullanici["id"]) ?>"
                                        >

                                        <?php if ($kullanici["durum"] === "aktif"): ?>

                                            <button
                                                type="submit"
                                                class="buton pasif-yap"
                                                onclick="return confirm('Bu kullanıcıyı pasif yapmak istiyor musunuz?');"
                                            >
                                                Pasif Yap
                                            </button>

                                        <?php else: ?>

                                            <button
                                                type="submit"
                                                class="buton aktif-yap"
                                                onclick="return confirm('Bu kullanıcıyı aktif yapmak istiyor musunuz?');"
                                            >
                                                Aktif Yap
                                            </button>

                                        <?php endif; ?>
                                    </form>

                                <?php else: ?>

                                    <span class="kilitli">
                                        Bilinmeyen durum
                                    </span>

                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="8">
                            Kayıtlı kullanıcı bulunamadı.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>

</body>
</html>
