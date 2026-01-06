<?php
require 'config/security.php';

// Properly destroy session
destroySession();

header('Location: index.php');
exit;
?>

