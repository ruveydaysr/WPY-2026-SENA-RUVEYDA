
<?php
session_start();

$_SESSION = [];
session_destroy();

header("Location: /teknik-servis/giris.php");
exit();
