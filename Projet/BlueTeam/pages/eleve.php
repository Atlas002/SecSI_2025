<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'eleve') {
    header('Location: login/login.php');
}
?>

<?php include('includes/header.php'); ?>

<div class="content">
    <h1>Bienvenue, Élève</h1>
    <p>Voici votre espace personnel.</p>
    <div class="section">
        <h2>Vos résultats</h2>
        <p>Consultez vos résultats et progrès ici.</p>
        <!-- Ajouter des liens ou des sections de résultats -->
    </div>
    <div class="section">
        <h2>Vos horaires</h2>
        <p>Consultez vos horaires de cours et événements importants.</p>
        <!-- Ajouter les horaires des cours -->
    </div>
</div>

<?php include('includes/footer.php'); ?>
