<?php
// logout.php - Simple version
session_start();
session_destroy();
echo "<script>window.location.href = 'auth.php';</script>";
exit();
?>