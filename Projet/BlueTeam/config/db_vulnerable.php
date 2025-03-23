<?php
function getVulnerableConnection() {
    $host = getenv('DB_HOST');
    $dbname = getenv('DB_NAME');
    $username = getenv('DB_USER');
    $password = getenv('DB_PASS');
    
    // Créer une connexion mysqli qui permet les requêtes multiples
    $mysqli = new mysqli($host, $username, $password, $dbname);
    
    if ($mysqli->connect_error) {
        die("Connexion échouée : " . $mysqli->connect_error);
    }
    
    return $mysqli;
}
?>