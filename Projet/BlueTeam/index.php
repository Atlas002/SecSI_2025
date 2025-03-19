<?php
// index.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST["name"]);
    echo "<p>Bonjour, $name !</p>";
}
?>
