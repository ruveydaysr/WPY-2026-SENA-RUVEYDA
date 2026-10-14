
<?php
session_start();

// Oturum bilgilerini temizle
$_SESSION = [];

// Oturum çerezini sil
if (ini_get("session.use_cookies")) {
    $ayarlar = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        [
            "expires" => time() - 42000,
            "path" => $ayarlar["path"],
            "domain" => $ayarlar["domain"],
            "secure" => $ayarlar["secure"],
            "httponly" => $ayarlar["httponly"],
            "samesite" => $ayarlar["samesite"] ?? "Lax"
        ]
    );
}

// Oturumu sonlandır
session_destroy();

// Giriş sayfasına yönlendir
header("Location: giris.php");
exit();
?>
