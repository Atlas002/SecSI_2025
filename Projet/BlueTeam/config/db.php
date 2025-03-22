<?php
$host = 'db'; // Le nom du service MySQL dans docker-compose
$dbname = 'ece_db';
$username = 'user';
$password = 'nv7_4f8X.g1qPPP';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connexion échouée : " . $e->getMessage());
}
?>