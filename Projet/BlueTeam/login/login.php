<?php
session_start();
include('../config/db.php'); // Connexion à la base de données

/**if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        // Vérification des données utilisateur
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            // Vérification spéciale pour le compte "admin"
            if ($user['username'] === 'admin') {
                // Comparaison en clair pour le compte "admin"
                if ($password === $user['password']) {
                    $is_authenticated = true;
                } else {
                    $is_authenticated = false;
                }
            } else {
                // Comparaison avec le mot de passe haché pour les autres utilisateurs
                if (password_verify($password, $user['password'])) {
                    $is_authenticated = true;
                } else {
                    $is_authenticated = false;
                }
            }

            if ($is_authenticated) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($remember) {
                    // Création d'un token sécurisé
                    $token = bin2hex(random_bytes(32));
                    $expire_date = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 jours

                    // Insérer le token en base de données
                    $insertStmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
                    $insertStmt->execute([
                        'user_id' => $user['id'],
                        'token' => $token,
                        'expires_at' => $expire_date
                    ]);

                    // Créer le cookie sécurisé
                    setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
                }

                // Redirection vers la page correspondant au rôle
                if (isset($_SESSION['role'])) {
                    switch (strtolower($_SESSION['role'])) {
                        case 'admin':
                            header("Location: ../pages/admin.php");
                            break;
                        case 'prof':
                            header("Location: ../pages/prof.php");
                            break;
                        case 'eleve':
                            header("Location: ../pages/eleve.php");
                            break;
                        default:
                            header("Location: ../index.php"); // redirection par défaut en cas d'erreur
                            break;
                    }
                    exit;
                }
            } else {
                $error = "Nom d'utilisateur ou mot de passe incorrect.";
            }
        } else {
            $error = "Nom d'utilisateur ou mot de passe incorrect.";
        }
    }
}**/

if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        // Vérification des données utilisateur
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && (md5($password) === $user['password'])) {
            // Connexion réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($remember) {
                // Création d'un token sécurisé
                $token = bin2hex(random_bytes(32));
                $expire_date = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 jours

                // Insérer le token en base de données
                $insertStmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
                $insertStmt->execute([
                    'user_id' => $user['id'],
                    'token' => $token,
                    'expires_at' => $expire_date
                ]);

                // Créer le cookie sécurisé
                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
            }

            // Redirection vers la page correspondant au rôle
            if (isset($_SESSION['role'])) {
                switch (strtolower($_SESSION['role'])) {
                    case 'admin':
                        header("Location: ../pages/admin.php");
                        break;
                    case 'prof':
                        header("Location: ../pages/prof.php");
                        break;
                    case 'eleve':
                        header("Location: ../pages/eleve.php");
                        break;
                    default:
                        header("Location: ../index.php"); // redirection par défaut en cas d'erreur
                        break;
                }
                exit;
            }
        } else {
            $error = "Nom d'utilisateur ou mot de passe incorrect.";
        }
    }
}


// Vérification du cookie remember_token
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $stmt = $pdo->prepare("SELECT users.* FROM users JOIN remember_tokens ON users.id = remember_tokens.user_id WHERE remember_tokens.token = :token AND remember_tokens.expires_at > NOW()");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirection vers le tableau de bord
        header("Location: ../" . strtolower($_SESSION['role']) . "/dashboard.php");
        exit;
    }
}

// Vérification de la déconnexion
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    session_destroy();
    header("Location: login.php?message=Vous avez été déconnecté avec succès.");
    exit;
}


// Titre de la page
$page_title = "Connexion | ECE";
?>

<?php include('../includes/header.php'); ?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h2>Connexion à votre espace</h2>
            <p>Entrez vos identifiants pour accéder à votre compte</p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($logout_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $logout_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" class="login-form">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Votre identifiant" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Votre mot de passe" required>
                    <button type="button" class="toggle-password" title="Afficher/Masquer le mot de passe">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-options">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Se souvenir de moi</label>
                </div>
                <a href="../reset-password/request.php" class="forgot-password">Mot de passe oublié?</a>
            </div>
            
            <button type="submit" name="submit" class="btn btn-primary btn-block">
                Se connecter <i class="fas fa-sign-in-alt"></i>
            </button>
        </form>
        
        <div class="login-footer">
            <p>Vous n'avez pas de compte? <a href="../contact/assistance.php">Contactez l'administration</a></p>
        </div>
    </div>
    
    <div class="login-info">
        <div class="info-card">
            <h3>Aide à la connexion</h3>
            <ul>
                <li>Votre nom d'utilisateur est généralement votre numéro d'étudiant ou votre email professionnel.</li>
                <li>Si vous avez oublié votre mot de passe, cliquez sur "Mot de passe oublié".</li>
                <li>Pour toute assistance, contactez le support technique au <strong>01.XX.XX.XX.XX</strong>.</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour basculer la visibilité du mot de passe
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.querySelector('#password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            // Change le type de l'input
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Change l'icône
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
    
    // Fermeture automatique des alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});
</script>

<?php include('../includes/footer.php'); ?>
