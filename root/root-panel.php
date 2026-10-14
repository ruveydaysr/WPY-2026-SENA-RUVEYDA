<?php
require_once("root-kontrol.php");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROOT Yönetim Paneli</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f4f6f9;
        }

        header {
            background-color: #202c3c;
            color: white;
            padding: 25px;
            text-align: center;
        }

        main {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .panel {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #00000015;
        }

        .kartlar {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .kart {
            background-color: #eaf0f7;
            padding: 20px;
            border-radius: 8px;
            flex: 1;
            min-width: 180px;
        }

        .kart h3 {
            margin-top: 0;
        }

        .bilgi {
            color: #555;
        }
    </style>
</head>

<body>

<header>
    <h1>ROOT Yönetim Paneli</h1>
    <p>Bilgisayar Teknik Servis Sistemi</p>
</header>

<main>
    <section class="panel">
        <h2>ROOT Paneline Hoş Geldiniz</h2>

        <p>
            Sisteme ROOT yetkisiyle başarıyla giriş yaptınız.
        </p>

        <div class="kartlar">
            <div class="kart">
                <h3>Kullanıcı Yönetimi</h3>
                <p>Kullanıcıları yönetme bölümü.</p>
            </div>

            <div class="kart">
    <h3>Kullanıcı Yönetimi</h3>
    <p>Sistemde kayıtlı kullanıcıları görüntüle.</p>
    <a href="kullanicilar.php">Kullanıcıları Görüntüle</a>
</div>

            <div class="kart">
    <h3>İşlem Geçmişi</h3>
    <p>ROOT tarafından yapılan kullanıcı işlemlerini görüntüle.</p>
    <a href="islem-gecmisi.php">İşlem Geçmişini Görüntüle</a>
</div>
        </div>

        <p class="bilgi">
            Yönetim işlemleri sonraki aşamalarda eklenecektir.
        </p>
    </section>
</main>
<p style="text-align:center; margin:30px;">
    <a href="../cikis.php">Güvenli Çıkış Yap</a>
</p>
</body>
</html>
