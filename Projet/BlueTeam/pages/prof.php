<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'prof') {
    header('Location: login/login.php');
}
?>

<?php include('includes/header.php'); ?>

<div class="content">
    <h1>Bienvenue, Professeur</h1>
    <p>Voici votre espace pour gérer vos cours, noter vos élèves, et plus encore.</p>
    <div class="section">
        <h2>Mes Cours</h2>
        <p>Gérez vos cours et les matières enseignées.</p>
        <!-- Ajouter des liens ou des sections pour la gestion des cours -->
    </div>
    <div class="section">
        <h2>Évaluations</h2>
        <p>Notez les travaux des étudiants et consultez leurs résultats.</p>
        <!-- Ajouter des liens pour les évaluations -->
    </div>
</div>

<?php include('includes/footer.php'); ?>
