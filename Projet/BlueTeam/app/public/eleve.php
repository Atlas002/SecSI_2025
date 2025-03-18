// Page eleve

// TO DO :

<?php
include "../includes/auth.php";
if ($_SESSION['role'] !== 'eleve') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Élève</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <h1>Bienvenue Élève</h1>
    <a href="logout.php">Déconnexion</a>
</body>
</html>

