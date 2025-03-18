// Vérification connexion

// TO DO :
// Vérifier la sécurite et l'utilité

<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}
?>
