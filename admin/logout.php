<?php
// logout.php - Simple version
session_start();
session_destroy();
echo "<script>window.location.href = 'index.php';</script>";
exit();
?>