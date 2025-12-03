<?php
session_start();

// Smaž všechny session proměnné
$_SESSION = array();

// Zničení session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Zničení session
session_destroy();

// Přesměruj zpět na přihlašovací stránku
header('Location: index.html');
exit;
?>