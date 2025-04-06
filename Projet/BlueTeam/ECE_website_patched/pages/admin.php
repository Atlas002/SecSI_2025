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

// Définir la section active
$active_section = isset($_GET['section']) ? $_GET['section'] : 'users';

// ====================== GESTION DES UTILISATEURS ======================
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
            if (!empty($_POST['date_naissance'])) {
                $date_naissance = DateTime::createFromFormat('d/m/Y', $_POST['date_naissance']);
                if ($date_naissance) {
                    $date_naissance = $date_naissance->format('Y-m-d'); // Conversion pour MySQL
                } else {
                    $error = "Format de date invalide. Utilisez JJ/MM/AAAA.";
                }
            } else {
                $date_naissance = '2000-01-01'; // Date par défaut
            }
                       
            // Vérification si l'utilisateur existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Ce nom d'utilisateur ou cet e-mail existe déjà.";
            } else {
                // Hachage du mot de passe avec MD5
                $hashed_password = md5($password);
                
                // Début de la transaction
                $pdo->beginTransaction();
                try {
                    // Insertion de l'utilisateur
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password, $role]);
                    $user_id = $pdo->lastInsertId();
                    
                    // Si le rôle est élève, demander plus d'informations
                    if ($role === 'eleve' && isset($_POST['nom'], $_POST['prenom'], $_POST['date_naissance'], $_POST['classe'])) {
                        $stmt = $pdo->prepare("INSERT INTO eleves (user_id, nom, prenom, date_naissance, classe) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $user_id, 
                            trim($_POST['nom']), 
                            trim($_POST['prenom']), 
                            $date_naissance, 
                            $_POST['classe']
                        ]);
                    }
                    
                    // Si le rôle est prof, demander plus d'informations
                    if ($role === 'prof' && isset($_POST['nom'], $_POST['prenom'], $_POST['date_naissance'], $_POST['matiere'])) {
                        $stmt = $pdo->prepare("INSERT INTO profs (user_id, nom, prenom, date_naissance, matiere) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $user_id, 
                            trim($_POST['nom']), 
                            trim($_POST['prenom']), 
                            $date_naissance, 
                            $_POST['matiere']
                        ]);
                    }
                    
                    $pdo->commit();
                    $success = "L'utilisateur a été ajouté avec succès.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Erreur lors de l'ajout de l'utilisateur: " . $e->getMessage();
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
            if (!empty($_POST['date_naissance'])) {
                $date_naissance = DateTime::createFromFormat('d/m/Y', $_POST['date_naissance']);
                if ($date_naissance) {
                    $date_naissance = $date_naissance->format('Y-m-d'); // Conversion pour MySQL
                } else {
                    $error = "Format de date invalide. Utilisez JJ/MM/AAAA.";
                }
            } else {
                $date_naissance = '2000-01-01';
            }
            
            $pdo->beginTransaction();
            try {
                // Mise à jour de l'utilisateur
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
                $stmt->execute($params);
                
                // Vérification du rôle précédent et actuel
                $stmtRole = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmtRole->execute([$user_id]);
                $currentRole = $stmtRole->fetchColumn();
                
                // Mettre à jour les infos supplémentaires en fonction du rôle
                if ($role === 'eleve' && isset($_POST['nom'], $_POST['prenom'], $_POST['date_naissance'], $_POST['classe'])) {
                    // Vérifier si l'élève existe déjà
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM eleves WHERE user_id = ?");
                    $stmtCheck->execute([$user_id]);
                    
                    if ($stmtCheck->fetchColumn() > 0) {
                        $stmt = $pdo->prepare("UPDATE eleves SET nom = ?, prenom = ?, date_naissance = ?, classe = ? WHERE user_id = ?");
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO eleves (nom, prenom, date_naissance, classe, user_id) VALUES (?, ?, ?, ?, ?)");
                    }
                    
                    $stmt->execute([
                        trim($_POST['nom']), 
                        trim($_POST['prenom']), 
                        $date_naissance, 
                        $_POST['classe'],
                        $user_id
                    ]);
                }
                
                if ($role === 'prof' && isset($_POST['nom'], $_POST['prenom'], $_POST['date_naissance'], $_POST['matiere'])) {
                    // Vérifier si le prof existe déjà
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM profs WHERE user_id = ?");
                    $stmtCheck->execute([$user_id]);
                    
                    if ($stmtCheck->fetchColumn() > 0) {
                        $stmt = $pdo->prepare("UPDATE profs SET nom = ?, prenom = ?, date_naissance = ?, matiere = ? WHERE user_id = ?");
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO profs (nom, prenom, date_naissance, matiere, user_id) VALUES (?, ?, ?, ?, ?)");
                    }
                    
                    $stmt->execute([
                        trim($_POST['nom']), 
                        trim($_POST['prenom']), 
                        $date_naissance, 
                        $_POST['matiere'],
                        $user_id
                    ]);
                }
                
                $pdo->commit();
                $success = "L'utilisateur a été mis à jour avec succès.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erreur lors de la mise à jour: " . $e->getMessage();
            }
        }
    }
    
    // ====================== GESTION DES COURS ======================
    // Ajouter un cours
    if ($action === 'add_cours' && 
        isset($_POST['prof_id'], $_POST['classe'], $_POST['matiere'], $_POST['horaire'], $_POST['salle'])) {
        
        $prof_id = (int)$_POST['prof_id'];
        $classe = trim($_POST['classe']);
        $matiere = trim($_POST['matiere']);
        $horaire = $_POST['horaire'];
        $salle = trim($_POST['salle']);
        
        // Validation
        if (empty($prof_id) || empty($classe) || empty($matiere) || empty($horaire) || empty($salle)) {
            $error = "Tous les champs sont obligatoires.";
        } else {
            // Insertion du cours
            $stmt = $pdo->prepare("INSERT INTO cours (prof_id, classe, matiere, horaire, salle) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$prof_id, $classe, $matiere, $horaire, $salle])) {
                $success = "Le cours a été ajouté avec succès.";
            } else {
                $error = "Erreur lors de l'ajout du cours.";
            }
        }
    }
    
    // Supprimer un cours
    if ($action === 'delete_cours' && isset($_POST['cours_id'])) {
        $cours_id = (int)$_POST['cours_id'];
        
        $stmt = $pdo->prepare("DELETE FROM cours WHERE id = ?");
        if ($stmt->execute([$cours_id])) {
            $success = "Le cours a été supprimé avec succès.";
        } else {
            $error = "Erreur lors de la suppression du cours.";
        }
    }
    
    // Mettre à jour un cours
    if ($action === 'update_cours' && isset($_POST['cours_id'])) {
        $cours_id = (int)$_POST['cours_id'];
        $prof_id = (int)$_POST['prof_id'];
        $classe = trim($_POST['classe']);
        $matiere = trim($_POST['matiere']);
        $horaire = $_POST['horaire'];
        $salle = trim($_POST['salle']);
        
        // Validation
        if (empty($prof_id) || empty($classe) || empty($matiere) || empty($horaire) || empty($salle)) {
            $error = "Tous les champs sont obligatoires.";
        } else {
            // Mise à jour du cours
            $stmt = $pdo->prepare("UPDATE cours SET prof_id = ?, classe = ?, matiere = ?, horaire = ?, salle = ? WHERE id = ?");
            if ($stmt->execute([$prof_id, $classe, $matiere, $horaire, $salle, $cours_id])) {
                $success = "Le cours a été mis à jour avec succès.";
            } else {
                $error = "Erreur lors de la mise à jour du cours.";
            }
        }
    }
}

// Récupération des données pour l'affichage
// Users
$stmt = $pdo->query("SELECT u.id, u.username, u.email, u.role, u.created_at, 
                     e.nom as eleve_nom, e.prenom as eleve_prenom, e.classe as eleve_classe, e.date_naissance as eleve_naissance,
                     p.nom as prof_nom, p.prenom as prof_prenom, p.matiere as prof_matiere, p.date_naissance as prof_naissance
                     FROM users u 
                     LEFT JOIN eleves e ON u.id = e.user_id 
                     LEFT JOIN profs p ON u.id = p.user_id 
                     ORDER BY u.created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cours
$stmt = $pdo->query("SELECT c.*, p.nom, p.prenom, p.matiere as prof_matiere 
                     FROM cours c 
                     JOIN profs p ON c.prof_id = p.id 
                     ORDER BY c.horaire");
$cours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Liste des professeurs pour le formulaire des cours
$stmt = $pdo->query("SELECT id, nom, prenom, matiere FROM profs ORDER BY nom, prenom");
$profs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des statistiques
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'eleve'")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'prof'")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM cours")->fetchColumn();

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
        <div class="stat-card">
            <i class="fas fa-book"></i>
            <div class="stat-info">
                <h3>Cours</h3>
                <p><?php echo $totalCourses; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Navigation des sections -->
    <div class="admin-nav">
        <a href="?section=users" class="nav-item <?php echo $active_section === 'users' ? 'active' : ''; ?>">
            <i class="fas fa-user-cog"></i> Gestion Utilisateurs
        </a>
        <a href="?section=cours" class="nav-item <?php echo $active_section === 'cours' ? 'active' : ''; ?>">
            <i class="fas fa-book"></i> Gestion Cours
        </a>
        <a href="?section=logs" class="nav-item <?php echo $active_section === 'logs' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list"></i> Logs Système
        </a>
    </div>
    
    <div class="admin-sections">
        <!-- SECTION 1: GESTION DES UTILISATEURS -->
        <div class="admin-section <?php echo $active_section === 'users' ? 'active' : ''; ?>">
            <h2><i class="fas fa-user-cog"></i> Gestion des Utilisateurs</h2>
            
            <div class="section-actions">
                <button class="btn btn-primary" id="showAddUserForm">
                    <i class="fas fa-user-plus"></i> Ajouter un utilisateur
                </button>
            </div>
            
            <!-- Formulaire d'ajout d'utilisateur (caché par défaut) -->
            <div class="form-container" id="addUserForm" style="display: none;">
                <h3>Ajouter un nouvel utilisateur</h3>
                <form method="post" action="" id="userForm">
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
                    
                    <!-- Champs spécifiques aux élèves -->
                    <div id="eleve_fields" class="role-specific-fields">
                        <h4>Informations de l'élève</h4>
                        
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom">
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_naissance">Date de naissance</label>
                            <input type="date" id="date_naissance" name="date_naissance" placeholder="JJ/MM/AAAA" required>

                        </div>
                        
                        <div class="form-group">
                            <label for="classe">Classe</label>
                            <input type="text" id="classe" name="classe">
                        </div>
                    </div>
                    
                    <!-- Champs spécifiques aux professeurs -->
                    <div id="prof_fields" class="role-specific-fields" style="display:none;">
                        <h4>Informations du professeur</h4>
                        
                        <div class="form-group">
                            <label for="nom_prof">Nom</label>
                            <input type="text" id="nom_prof" name="nom">
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom_prof">Prénom</label>
                            <input type="text" id="prenom_prof" name="prenom">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_naissance_prof">Date de naissance</label>
                            <input type="date" id="date_naissance_prof" name="date_naissance">
                        </div>
                        
                        <div class="form-group">
                            <label for="matiere">Matière</label>
                            <input type="text" id="matiere" name="matiere">
                        </div>
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
                            <th>Informations</th>
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
                            <td>
                                <?php if ($user['role'] === 'eleve' && !empty($user['eleve_nom'])): ?>
                                    <span class="info-badge">
                                        <?php echo htmlspecialchars($user['eleve_nom'] . ' ' . $user['eleve_prenom']); ?><br>
                                        Classe: <?php echo htmlspecialchars($user['eleve_classe']); ?>
                                    </span>
                                <?php elseif ($user['role'] === 'prof' && !empty($user['prof_nom'])): ?>
                                    <span class="info-badge">
                                        <?php echo htmlspecialchars($user['prof_nom'] . ' ' . $user['prof_prenom']); ?><br>
                                        Matière: <?php echo htmlspecialchars($user['prof_matiere']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            <td class="actions">
                                <button class="btn btn-sm btn-info edit-user" 
                                        data-id="<?php echo $user['id']; ?>" 
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>" 
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>" 
                                        data-role="<?php echo $user['role']; ?>"
                                        data-eleve-nom="<?php echo htmlspecialchars($user['eleve_nom'] ?? ''); ?>"
                                        data-eleve-prenom="<?php echo htmlspecialchars($user['eleve_prenom'] ?? ''); ?>"
                                        data-eleve-naissance="<?php echo $user['eleve_naissance'] ?? ''; ?>"
                                        data-eleve-classe="<?php echo htmlspecialchars($user['eleve_classe'] ?? ''); ?>"
                                        data-prof-nom="<?php echo htmlspecialchars($user['prof_nom'] ?? ''); ?>"
                                        data-prof-prenom="<?php echo htmlspecialchars($user['prof_prenom'] ?? ''); ?>"
                                        data-prof-naissance="<?php echo $user['prof_naissance'] ?? ''; ?>"
                                        data-prof-matiere="<?php echo htmlspecialchars($user['prof_matiere'] ?? ''); ?>">
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
                        
                        <!-- Champs spécifiques aux élèves -->
                        <div id="edit_eleve_fields" class="role-specific-fields">
                            <h4>Informations de l'élève</h4>
                            
                            <div class="form-group">
                                <label for="edit_nom">Nom</label>
                                <input type="text" id="edit_nom" name="nom">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_prenom">Prénom</label>
                                <input type="text" id="edit_prenom" name="prenom">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_date_naissance">Date de naissance</label>
                                <input type="date" id="edit_date_naissance" name="date_naissance">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_classe">Classe</label>
                                <input type="text" id="edit_classe" name="classe">
                            </div>
                        </div>
                        
                        <!-- Champs spécifiques aux professeurs -->
                        <div id="edit_prof_fields" class="role-specific-fields" style="display:none;">
                            <h4>Informations du professeur</h4>
                            
                            <div class="form-group">
                                <label for="edit_nom_prof">Nom</label>
                                <input type="text" id="edit_nom_prof" name="nom">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_prenom_prof">Prénom</label>
                                <input type="text" id="edit_prenom_prof" name="prenom">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_date_naissance_prof">Date de naissance</label>
                                <input type="date" id="edit_date_naissance_prof" name="date_naissance">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_matiere">Matière</label>
                                <input type="text" id="edit_matiere" name="matiere">
                            </div>
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
        
        <!-- SECTION 2: GESTION DES COURS -->
        <div class="admin-section <?php echo $active_section === 'cours' ? 'active' : ''; ?>">
            <h2><i class="fas fa-book"></i> Gestion des Cours</h2>
            <p>Cette section vous permet de gérer les cours, les plannings et les inscriptions.</p>
            
            <div class="section-actions">
                <button class="btn btn-primary" id="showAddCoursForm">
                    <i class="fas fa-plus-circle"></i> Ajouter un cours
                </button>
            </div>
            <!-- Formulaire d'ajout de cours (caché par défaut) -->
            <div class="form-container" id="addCoursForm" style="display: none;">
                <h3>Ajouter un nouveau cours</h3>
                <form method="post" action="">
                    <input type="hidden" name="action" value="add_cours">
                    
                    <div class="form-group">
                        <label for="prof_id">Professeur</label>
                        <select id="prof_id" name="prof_id" required>
                            <option value="">-- Sélectionner un professeur --</option>
                            <?php foreach ($profs as $prof): ?>
                            <option value="<?php echo $prof['id']; ?>">
                                <?php echo htmlspecialchars($prof['nom'] . ' ' . $prof['prenom'] . ' (' . $prof['matiere'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="matiere">Matière</label>
                        <input type="text" id="matiere_cours" name="matiere" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="classe">Classe</label>
                        <input type="text" id="classe_cours" name="classe" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="horaire">Horaire</label>
                        <input type="datetime-local" id="horaire" name="horaire" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="salle">Salle</label>
                        <input type="text" id="salle" name="salle" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                        <button type="button" class="btn btn-secondary" id="cancelAddCours">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Liste des cours -->
            <div class="cours-list">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Professeur</th>
                            <th>Matière</th>
                            <th>Classe</th>
                            <th>Horaire</th>
                            <th>Salle</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cours as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td><?php echo htmlspecialchars($c['nom'] . ' ' . $c['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($c['matiere']); ?></td>
                            <td><?php echo htmlspecialchars($c['classe']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($c['horaire'])); ?></td>
                            <td><?php echo htmlspecialchars($c['salle']); ?></td>
                            <td class="actions">
                                <button class="btn btn-sm btn-info edit-cours" 
                                        data-id="<?php echo $c['id']; ?>"
                                        data-prof-id="<?php echo $c['prof_id']; ?>"
                                        data-matiere="<?php echo htmlspecialchars($c['matiere']); ?>"
                                        data-classe="<?php echo htmlspecialchars($c['classe']); ?>"
                                        data-horaire="<?php echo date('Y-m-d\TH:i', strtotime($c['horaire'])); ?>"
                                        data-salle="<?php echo htmlspecialchars($c['salle']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="post" action="" class="d-inline delete-form">
                                    <input type="hidden" name="action" value="delete_cours">
                                    <input type="hidden" name="cours_id" value="<?php echo $c['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce cours ?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Modal d'édition de cours -->
            <div class="modal" id="editCoursModal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3>Modifier le cours</h3>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="update_cours">
                        <input type="hidden" name="cours_id" id="edit_cours_id">
                        
                        <div class="form-group">
                            <label for="edit_prof_id">Professeur</label>
                            <select id="edit_prof_id" name="prof_id" required>
                                <?php foreach ($profs as $prof): ?>
                                <option value="<?php echo $prof['id']; ?>">
                                    <?php echo htmlspecialchars($prof['nom'] . ' ' . $prof['prenom'] . ' (' . $prof['matiere'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_matiere_cours">Matière</label>
                            <input type="text" id="edit_matiere_cours" name="matiere" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_classe_cours">Classe</label>
                            <input type="text" id="edit_classe_cours" name="classe" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_horaire">Horaire</label>
                            <input type="datetime-local" id="edit_horaire" name="horaire" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_salle">Salle</label>
                            <input type="text" id="edit_salle" name="salle" required>
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
        
        <!-- SECTION 3: LOGS SYSTÈME -->
        <div class="admin-section <?php echo $active_section === 'logs' ? 'active' : ''; ?>">
            <h2><i class="fas fa-clipboard-list"></i> Logs Système</h2>
            <p>Consultez les journaux d'activité du système.</p>
            
            <div class="logs-filters">
                <form method="get" action="">
                    <input type="hidden" name="section" value="logs">
                    
                    <div class="form-group">
                        <label for="log_type">Type de log</label>
                        <select id="log_type" name="log_type">
                            <option value="all">Tous</option>
                            <option value="login">Connexions</option>
                            <option value="action">Actions</option>
                            <option value="error">Erreurs</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="log_date">Date</label>
                        <input type="date" id="log_date" name="log_date">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </form>
            </div>
            
            <div class="logs-list">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Utilisateur</th>
                            <th>IP</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Simulations de logs pour la démonstration -->
                        <tr>
                            <td>1</td>
                            <td><span class="log-type login">Connexion</span></td>
                            <td>Connexion réussie</td>
                            <td>admin</td>
                            <td>192.168.1.100</td>
                            <td><?php echo date('d/m/Y H:i:s'); ?></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><span class="log-type action">Action</span></td>
                            <td>Ajout d'un nouvel utilisateur (ID: 15)</td>
                            <td>admin</td>
                            <td>192.168.1.100</td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime('-1 hour')); ?></td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td><span class="log-type error">Erreur</span></td>
                            <td>Tentative de connexion échouée</td>
                            <td>utilisateur_inconnu</td>
                            <td>192.168.1.200</td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime('-2 hours')); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion des sections d'administration
document.addEventListener('DOMContentLoaded', function() {
    // Fonctions pour le formulaire d'ajout d'utilisateur
    document.getElementById('showAddUserForm').addEventListener('click', function() {
        document.getElementById('addUserForm').style.display = 'block';
    });
    
    document.getElementById('cancelAddUser').addEventListener('click', function() {
        document.getElementById('addUserForm').style.display = 'none';
    });
    
    // Gestion des champs spécifiques aux rôles dans le formulaire d'ajout
    document.getElementById('role').addEventListener('change', function() {
        var role = this.value;
        if (role === 'eleve') {
            document.getElementById('eleve_fields').style.display = 'block';
            document.getElementById('prof_fields').style.display = 'none';
        } else if (role === 'prof') {
            document.getElementById('eleve_fields').style.display = 'none';
            document.getElementById('prof_fields').style.display = 'block';
        } else {
            document.getElementById('eleve_fields').style.display = 'none';
            document.getElementById('prof_fields').style.display = 'none';
        }
    });
    
    // Gestion du modal d'édition d'utilisateur
    var editUserModal = document.getElementById('editUserModal');
    var editUserBtns = document.querySelectorAll('.edit-user');
    var closeEditUserModal = editUserModal.querySelector('.close');
    
    editUserBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var userId = this.getAttribute('data-id');
            var username = this.getAttribute('data-username');
            var email = this.getAttribute('data-email');
            var role = this.getAttribute('data-role');
            
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            
            // Remplir les champs spécifiques en fonction du rôle
            if (role === 'eleve') {
                document.getElementById('edit_eleve_fields').style.display = 'block';
                document.getElementById('edit_prof_fields').style.display = 'none';
                document.getElementById('edit_nom').value = this.getAttribute('data-eleve-nom');
                document.getElementById('edit_prenom').value = this.getAttribute('data-eleve-prenom');
                document.getElementById('edit_date_naissance').value = this.getAttribute('data-eleve-naissance');
                document.getElementById('edit_classe').value = this.getAttribute('data-eleve-classe');
            } else if (role === 'prof') {
                document.getElementById('edit_eleve_fields').style.display = 'none';
                document.getElementById('edit_prof_fields').style.display = 'block';
                document.getElementById('edit_nom_prof').value = this.getAttribute('data-prof-nom');
                document.getElementById('edit_prenom_prof').value = this.getAttribute('data-prof-prenom');
                document.getElementById('edit_date_naissance_prof').value = this.getAttribute('data-prof-naissance');
                document.getElementById('edit_matiere').value = this.getAttribute('data-prof-matiere');
            } else {
                document.getElementById('edit_eleve_fields').style.display = 'none';
                document.getElementById('edit_prof_fields').style.display = 'none';
            }
            
            editUserModal.style.display = 'block';
        });
    });
    
    // Fermer le modal avec le X
    closeEditUserModal.addEventListener('click', function() {
        editUserModal.style.display = 'none';
    });
    
    // Fermer le modal si on clique en dehors
    window.addEventListener('click', function(event) {
        if (event.target === editUserModal) {
            editUserModal.style.display = 'none';
        }
    });
    
    // Gestion des champs spécifiques aux rôles dans le modal d'édition
    document.getElementById('edit_role').addEventListener('change', function() {
        var role = this.value;
        if (role === 'eleve') {
            document.getElementById('edit_eleve_fields').style.display = 'block';
            document.getElementById('edit_prof_fields').style.display = 'none';
        } else if (role === 'prof') {
            document.getElementById('edit_eleve_fields').style.display = 'none';
            document.getElementById('edit_prof_fields').style.display = 'block';
        } else {
            document.getElementById('edit_eleve_fields').style.display = 'none';
            document.getElementById('edit_prof_fields').style.display = 'none';
        }
    });
    
    // Gestion du formulaire d'ajout de cours
    document.getElementById('showAddCoursForm').addEventListener('click', function() {
        document.getElementById('addCoursForm').style.display = 'block';
    });
    
    document.getElementById('cancelAddCours').addEventListener('click', function() {
        document.getElementById('addCoursForm').style.display = 'none';
    });
    
    // Gestion du modal d'édition de cours
    var editCoursModal = document.getElementById('editCoursModal');
    var editCoursBtns = document.querySelectorAll('.edit-cours');
    var closeEditCoursModal = editCoursModal.querySelector('.close');
    
    editCoursBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var coursId = this.getAttribute('data-id');
            var profId = this.getAttribute('data-prof-id');
            var matiere = this.getAttribute('data-matiere');
            var classe = this.getAttribute('data-classe');
            var horaire = this.getAttribute('data-horaire');
            var salle = this.getAttribute('data-salle');
            
            document.getElementById('edit_cours_id').value = coursId;
            document.getElementById('edit_prof_id').value = profId;
            document.getElementById('edit_matiere_cours').value = matiere;
            document.getElementById('edit_classe_cours').value = classe;
            document.getElementById('edit_horaire').value = horaire;
            document.getElementById('edit_salle').value = salle;
            
            editCoursModal.style.display = 'block';
        });
    });
    
    // Fermer le modal avec le X
    closeEditCoursModal.addEventListener('click', function() {
        editCoursModal.style.display = 'none';
    });
    
    // Fermer le modal si on clique en dehors
    window.addEventListener('click', function(event) {
        if (event.target === editCoursModal) {
            editCoursModal.style.display = 'none';
        }
    });
});
</script>

<?php
// Inclusion du pied de page
include('../includes/footer.php');
?>