<?php
session_start();
if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Connexion à la base de données
    include('../config/db.php');
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
    $stmt->execute(['username' => $username, 'password' => md5($password)]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role']; // 'eleve', 'prof', 'admin'
        header('Location: ' . $_SESSION['role'] . '.php');
    } else {
        $error = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
?>

<?php include('../includes/header.php'); ?>

<div class="login-form">
    <h2>Se connecter</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit" name="submit" class="btn">Se connecter</button>
    </form>
    <?php if(isset($error)) { echo "<p class='error'>$error</p>"; } ?>
</div>

<?php include('../includes/footer.php'); ?>
