<?php
session_start();
session_destroy();

// Clear remember me cookies
setcookie('admin_email', '', time() - 3600, "/");
setcookie('admin_password', '', time() - 3600, "/");

header('Location: login.php');
exit();
?>