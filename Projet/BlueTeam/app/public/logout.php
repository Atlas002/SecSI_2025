// Deconnecion de l'utilisateur (?)

// TO DO :

<?php
session_start();
session_destroy();
header("Location: login.php");
exit();
?>
