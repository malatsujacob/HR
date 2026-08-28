<?php
// logout.php - Root Level Logout Script
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect back to the main index page
header("Location: /HR/index.php");
exit;
?>