<?php
session_start();

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Clear cart cookie if you want
setcookie('cart', '', time()-3600, '/');

// Redirect to home page
header('Location: index.php');
exit;
?>