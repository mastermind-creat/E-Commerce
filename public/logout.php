<?php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page (or landing page)
header("Location: ../public/index.php");
exit();