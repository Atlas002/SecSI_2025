// Connexion BDD

// TO DO :
// - Adapter les informations de connexion à la base de données


<?php
$host = "localhost";
$user = "root"; 
$pass = ""; 
$db = "users_db"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}
?>
