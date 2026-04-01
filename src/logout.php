<?php
session_start();
session_destroy();
// update session
setcookie("username", "", time() - 3600, "/");

header("Location: login.php");
exit();