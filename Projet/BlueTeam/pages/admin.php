<?php
session_start();
// Vérification de la session et redirection si non autorisé
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login/login.php');
    exit; // Ajout d'un exit après la redirection pour empêcher l'exécution du reste du script
}

// Titre de la page pour l'en-tête
$page_title = "Administration | ECE";

// Inclusion de la connexion à la base de données
require_once('../config/db.php');

// Traitement des actions sur les utilisateurs
if (isset($_POST['action']) && $_SESSION['role'] === 'admin') {
    $action = $_POST['action'];
    
    // Ajouter un utilisateur
    if ($action === 'add_user' && isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['role'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $role = $_POST['role'];
        
        // Validation des données
        if (empty($username) || empty($email) || empty($password) || empty($role)) {
            $error = "Tous les champs sont obligatoires.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "L'adresse e-mail n'est pas valide.";
        } else {
            // Vérification si l'utilisateur existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Ce nom d'utilisateur ou cet e-mail existe déjà.";
            } else {
                // Hachage du mot de passe avec MD5 (pour site de test uniquement)
                $hashed_password = md5($password);
                
                // Insertion de l'utilisateur
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                    $success = "L'utilisateur a été ajouté avec succès.";
                } else {
                    $error = "Erreur lors de l'ajout de l'utilisateur.";
                }
            }
        }
    }
    
    // Supprimer un utilisateur
    if ($action === 'delete_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Protection contre la suppression de son propre compte
        if ($user_id === $_SESSION['user_id']) {
            $error = "Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND username != 'admin'");
            if ($stmt->execute([$user_id])) {
                $success = "L'utilisateur a été supprimé avec succès.";
            } else {
                $error = "Erreur lors de la suppression de l'utilisateur.";
            }
        }
    }
    
    // Mettre à jour un utilisateur
    if ($action === 'update_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = trim($_POST['password']); // Optionnel
        
        // Validation
        if (empty($email) || empty($role)) {
            $error = "L'e-mail et le rôle sont obligatoires.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "L'adresse e-mail n'est pas valide.";
        } else {
            // Préparation de la requête de base
            $sql = "UPDATE users SET email = ?, role = ?";
            $params = [$email, $role];
            
            // Ajout du mot de passe s'il est fourni
            if (!empty($password)) {
                $sql .= ", password = ?";
                $params[] = md5($password); // Utilisation de MD5 pour le site de test
            }
            
            // Finalisation de la requête
            $sql .= " WHERE id = ?";
            $params[] = $user_id;
            
            // Exécution
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $success = "L'utilisateur a été mis à jour avec succès.";
            } else {
                $error = "Erreur lors de la mise à jour de l'utilisateur.";
            }
        }
    }
}

// Récupération de la liste des utilisateurs
$stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des statistiques
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'eleve'")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'prof'")->fetchColumn();

// Inclusion de l'en-tête
include('../includes/header.php');
?>

<div class="admin-dashboard">
    <div class="admin-header">
        <h1>Panneau d'Administration</h1>
        <p>Connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
    </div>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <div class="stat-info">
                <h3>Utilisateurs</h3>
                <p><?php echo $totalUsers; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-user-graduate"></i>
            <div class="stat-info">
                <h3>Élèves</h3>
                <p><?php echo $totalStudents; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-chalkboard-teacher"></i>
            <div class="stat-info">
                <h3>Professeurs</h3>
                <p><?php echo $totalTeachers; ?></p>
            </div>
        </div>
    </div>
    
    <div class="admin-sections">
        <!-- Section de gestion des utilisateurs -->
        <div class="admin-section">
            <h2><i class="fas fa-user-cog"></i> Gestion des Utilisateurs</h2>
            
            <div class="section-actions">
                <button class="btn btn-primary" id="showAddUserForm">
                    <i class="fas fa-user-plus"></i> Ajouter un utilisateur
                </button>
            </div>
            
            <!-- Formulaire d'ajout d'utilisateur (caché par défaut) -->
            <div class="form-container" id="addUserForm" style="display: none;">
                <h3>Ajouter un nouvel utilisateur</h3>
                <form method="post" action="">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Rôle</label>
                        <select id="role" name="role" required>
                            <option value="eleve">Élève</option>
                            <option value="prof">Professeur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                        <button type="button" class="btn btn-secondary" id="cancelAddUser">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Liste des utilisateurs -->
            <div class="user-list">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom d'utilisateur</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Date de création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            <td class="actions">
                                <button class="btn btn-sm btn-info edit-user" data-id="<?php echo $user['id']; ?>" 
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>" 
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>" 
                                        data-role="<?php echo $user['role']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($user['username'] !== 'admin' && $user['id'] !== $_SESSION['user_id']): ?>
                                <form method="post" action="" class="d-inline delete-form">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Modal d'édition d'utilisateur -->
            <div class="modal" id="editUserModal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3>Modifier l'utilisateur</h3>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="form-group">
                            <label for="edit_username">Nom d'utilisateur</label>
                            <input type="text" id="edit_username" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_password">Nouveau mot de passe (laisser vide pour conserver l'actuel)</label>
                            <input type="password" id="edit_password" name="password">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_role">Rôle</label>
                            <select id="edit_role" name="role" required>
                                <option value="eleve">Élève</option>
                                <option value="prof">Professeur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Section de gestion des cours -->
        <div class="admin-section">
            <h2><i class="fas fa-book"></i> Gestion des Cours</h2>
            <p>Cette section vous permet de gérer les cours, les plannings et les inscriptions.</p>
            
            <div class="placeholder-message">
                <i class="fas fa-info-circle"></i>
                <p>Le module de gestion des cours est en cours de développement. Il sera disponible prochainement.</p>
            </div>
        </div>
        
        <!-- Section des logs système -->
        <div class="admin-section">
            <h2><i class="fas fa-clipboard-list"></i> Logs Système</h2>
            <p>Consultez les journaux d'activité du système.</p>
            
            <div class="placeholder-message">
                <i class="fas fa-info-circle"></i>
                <p>Le module de logs système est en cours de développement. Il sera disponible prochainement.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire d'ajout d'utilisateur
    const showAddUserFormBtn = document.getElementById('showAddUserForm');
    const addUserForm = document.getElementById('addUserForm');
    const cancelAddUserBtn = document.getElementById('cancelAddUser');
    
    if (showAddUserFormBtn && addUserForm && cancelAddUserBtn) {
        showAddUserFormBtn.addEventListener('click', function() {
            addUserForm.style.display = 'block';
            showAddUserFormBtn.style.display = 'none';
        });
        
        cancelAddUserBtn.addEventListener('click', function() {
            addUserForm.style.display = 'none';
            showAddUserFormBtn.style.display = 'inline-block';
        });
    }
    
    // Gestion du modal d'édition
    const modal = document.getElementById('editUserModal');
    const editBtns = document.querySelectorAll('.edit-user');
    const closeBtn = document.querySelector('.close');
    
    if (modal && editBtns && closeBtn) {
        editBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const username = this.getAttribute('data-username');
                const email = this.getAttribute('data-email');
                const role = this.getAttribute('data-role');
                
                document.getElementById('edit_user_id').value = userId;
                document.getElementById('edit_username').value = username;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_role').value = role;
                
                modal.style.display = 'block';
            });
        });
        
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
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