
<?php
require_once("root-kontrol.php");

// İşlem geçmişini veritabanından getir
$sorgu = "
    SELECT
        i.id,
        i.yapan_kullanici_id,
        i.hedef_kullanici_id,
        i.islem_turu,
        i.eski_deger,
        i.yeni_deger,
        i.islem_tarihi,
        yapan.adsoyad AS yapan_ad,
        hedef.adsoyad AS hedef_ad
    FROM islem_kayitlari AS i
    LEFT JOIN kullanicilar AS yapan
        ON i.yapan_kullanici_id = yapan.id
    LEFT JOIN kullanicilar AS hedef
        ON i.hedef_kullanici_id = hedef.id
    ORDER BY i.islem_tarihi DESC, i.id DESC
";

$sonuc = mysqli_query($baglanti, $sorgu);

if (!$sonuc) {
    http_response_code(500);
    exit("İşlem geçmişi yüklenirken hata oluştu.");
}

// HTML çıktısını güvenli hale getir
function guvenli($deger) {
    return htmlspecialchars(
        (string)($deger ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// İşlem türünü anlaşılır şekilde göster
function islemAdi($tur) {
    if ($tur === "durum_degistirme") {
        return "Durum Değişikliği";
    }

    if ($tur === "rol_degistirme") {
        return "Rol Değişikliği";
    }

    return $tur;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>İşlem Geçmişi - ROOT</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
        }

        header {
            background-color: #202c3c;
            color: white;
            padding: 25px;
            text-align: center;
        }

        main {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .geri {
            display: inline-block;
            margin-bottom: 20px;
            color: #202c3c;
            text-decoration: none;
            font-weight: bold;
        }

        .kutu {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #00000012;
            overflow-x: auto;
        }

        h2 {
            margin-top: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #eaf0f7;
        }

        tr:hover {
            background-color: #f8fafc;
        }

        .rol {
            color: #2563a6;
            font-weight: bold;
        }

        .durum {
            color: #218c4a;
            font-weight: bold;
        }

        .bos {
            text-align: center;
            color: #777;
            padding: 25px;
        }
    </style>
</head>

<body>

<header>
    <h1>İşlem Geçmişi</h1>
    <p>ROOT Yönetim Paneli</p>
</header>

<main>

    <a href="root-panel.php" class="geri">
        ← ROOT Paneline Dön
    </a>

    <div class="kutu">

        <h2>Yapılan İşlemler</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>İşlemi Yapan</th>
                    <th>İşlem Yapılan Kullanıcı</th>
                    <th>İşlem Türü</th>
                    <th>Eski Değer</th>
                    <th>Yeni Değer</th>
                    <th>Tarih ve Saat</th>
                </tr>
            </thead>

            <tbody>

                <?php if (mysqli_num_rows($sonuc) > 0): ?>

                    <?php while ($kayit = mysqli_fetch_assoc($sonuc)): ?>

                        <tr>
                            <td>
                                <?= guvenli($kayit["id"]) ?>
                            </td>

                            <td>
                                <?= guvenli($kayit["yapan_ad"] ?? "Silinmiş Kullanıcı") ?>
                                (ID: <?= guvenli($kayit["yapan_kullanici_id"]) ?>)
                            </td>

                            <td>
                                <?= guvenli($kayit["hedef_ad"] ?? "Silinmiş Kullanıcı") ?>
                                (ID: <?= guvenli($kayit["hedef_kullanici_id"]) ?>)
                            </td>

                            <td>
                                <?php if ($kayit["islem_turu"] === "rol_degistirme"): ?>
                                    <span class="rol">
                                        <?= guvenli(islemAdi($kayit["islem_turu"])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="durum">
                                        <?= guvenli(islemAdi($kayit["islem_turu"])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= guvenli($kayit["eski_deger"]) ?>
                            </td>

                            <td>
                                <?= guvenli($kayit["yeni_deger"]) ?>
                            </td>

                            <td>
                                <?= guvenli($kayit["islem_tarihi"]) ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="bos">
                            Henüz işlem kaydı bulunmuyor.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>

    </div>

</main>

</body>
</html>
