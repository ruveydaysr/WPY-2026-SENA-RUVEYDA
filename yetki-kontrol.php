<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . "/baglanti.php");
mysqli_set_charset($baglanti, "utf8mb4");

function yetkiKontrol($izinli_roller) {
    global $baglanti;

    if (empty($_SESSION["kullanici_id"])) {
        header("Location: /teknik-servis/giris.php");
        exit();
    }

    $id = (int) $_SESSION["kullanici_id"];

    $sorgu = mysqli_prepare(
        $baglanti,
        "SELECT rol, durum
         FROM kullanicilar
         WHERE id = ?
         LIMIT 1"
    );

    if (!$sorgu) {
        http_response_code(500);
        exit("Yetki kontrolü yapılamadı.");
    }

    mysqli_stmt_bind_param($sorgu, "i", $id);

    if (!mysqli_stmt_execute($sorgu)) {
        mysqli_stmt_close($sorgu);
        http_response_code(500);
        exit("Yetki kontrolü yapılamadı.");
    }

    mysqli_stmt_bind_result($sorgu, $rol, $durum);
    $bulundu = mysqli_stmt_fetch($sorgu);

    mysqli_stmt_close($sorgu);

    // Pasif veya silinmiş hesabın oturumunu kapat.
    if (!$bulundu || $durum !== "aktif") {
        $_SESSION = [];
        session_destroy();

        header("Location: /teknik-servis/giris.php");
        exit();
    }

    // Veritabanındaki güncel rolü oturuma aktar.
    $_SESSION["rol"] = $rol;

    if (!in_array($rol, $izinli_roller, true)) {
        http_response_code(403);
        exit("Bu sayfaya erişim yetkiniz yok.");
    }
}