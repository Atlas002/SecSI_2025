<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login/login.php');
}
?>

<?php include('includes/header.php'); ?>

<div class="content">
    <h1>Bienvenue, Administrateur</h1>
    <p>Voici votre espace de gestion.</p>
    <div class="section">
        <h2>Gestion des utilisateurs</h2>
        <p>Ajoutez, modifiez ou supprimez des utilisateurs.</p>
        <!-- Ajouter un formulaire ou des options de gestion -->
    </div>
    <div class="section">
        <h2>Gestion des Cours</h2>
        <p>Gérez les matières, les plannings et l'inscription des élèves.</p>
        <!-- Ajouter des liens ou des formulaires pour la gestion des cours -->
    </div>
</div>

<?php include('includes/footer.php'); ?>
