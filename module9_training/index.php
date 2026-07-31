<?php
// module9_training/index.php - Training central module entry
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Automatically route users directly to the master management and viewing dashboard
header("Location: manage_training.php");
exit;
?>