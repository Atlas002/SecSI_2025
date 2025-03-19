<?php
// index.php
if ($_SERVER["REQUEST_&METHOD"] == "POST") {
    $name = htmlspecialchars($_POST["name"]);
    echo "<p>Bonjour, $name !</p>";
}
?>
