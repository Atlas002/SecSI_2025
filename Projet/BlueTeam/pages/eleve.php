<?php
session_start();
require_once '../config/db.php';

// Vérifier si l'utilisateur est connecté et a le rôle 'eleve'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'eleve') {
    header('Location: ../login/login.php');
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Récupérer les informations de l'élève
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT e.*, u.username, u.email FROM eleves e 
                      JOIN users u ON e.user_id = u.id 
                      WHERE e.user_id = ?");
$stmt->execute([$user_id]);
$eleve = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$eleve) {
    die("Erreur: Profil d'élève non trouvé.");
}

// Récupérer les cours de la classe de l'élève
$stmt = $pdo->prepare("SELECT c.*, p.nom as prof_nom, p.prenom as prof_prenom 
                      FROM cours c 
                      JOIN profs p ON c.prof_id = p.id 
                      WHERE c.classe = ? 
                      ORDER BY c.horaire ASC");
$stmt->execute([$eleve['classe']]);
$cours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les notes de l'élève
$stmt = $pdo->prepare("SELECT n.*, c.matiere, c.horaire, p.nom as prof_nom, p.prenom as prof_prenom 
                      FROM notes n 
                      JOIN cours c ON n.cours_id = c.id 
                      JOIN profs p ON c.prof_id = p.id 
                      WHERE n.eleve_id = ? 
                      ORDER BY n.date_evaluation DESC");
$stmt->execute([$eleve['id']]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les travaux soumis par l'élève
$stmt = $pdo->prepare("SELECT t.*, c.matiere, p.nom as prof_nom, p.prenom as prof_prenom 
                      FROM travaux t 
                      JOIN cours c ON t.cours_id = c.id 
                      JOIN profs p ON c.prof_id = p.id 
                      WHERE t.eleve_id = ? 
                      ORDER BY t.date_soumission DESC");
$stmt->execute([$eleve['id']]);
$travaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer la moyenne générale
$moyenne_generale = 0;
$nombre_notes = count($notes);
if ($nombre_notes > 0) {
    $total_notes = 0;
    foreach ($notes as $note) {
        $total_notes += $note['note'];
    }
    $moyenne_generale = round($total_notes / $nombre_notes, 2);
}

// Calculer la moyenne par matière
$moyennes_matieres = [];
$notes_par_matiere = [];

foreach ($notes as $note) {
    $matiere = $note['matiere'];
    if (!isset($notes_par_matiere[$matiere])) {
        $notes_par_matiere[$matiere] = [];
    }
    $notes_par_matiere[$matiere][] = $note['note'];
}

foreach ($notes_par_matiere as $matiere => $notes_matiere) {
    $total = array_sum($notes_matiere);
    $count = count($notes_matiere);
    $moyennes_matieres[$matiere] = round($total / $count, 2);
}

// Formater la date de naissance
$date_naissance_formattee = date('d/m/Y', strtotime($eleve['date_naissance']));

// Traitement du formulaire de soumission de travail
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_travail'])) {
    $cours_id = $_POST['cours_id'];
    $titre = $_POST['titre'];
    $descriptions = $_POST['descriptions'];
    
    // Variables pour le fichier
    $nom_fichier = '';
    $chemin_fichier = '';
    $upload_successful = true;
    $message = '';

    // Vérifier si un fichier a été téléchargé
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_dir = '../uploads/travaux/';
        
        // Créer le répertoire s'il n'existe pas
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $nom_fichier = basename($_FILES['fichier']['name']);
        $fichier_extension = strtolower(pathinfo($nom_fichier, PATHINFO_EXTENSION));
        $fichier_unique = uniqid() . '_' . $nom_fichier;
        $chemin_fichier = $upload_dir . $fichier_unique;
        
        // Extensions autorisées
        $extensions_autorisees = array('pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'zip', 'php5', 'php3');
        
        // Vérifier l'extension
        if (!in_array($fichier_extension, $extensions_autorisees)) {
            $upload_successful = false;
            $message = "Erreur: Seuls les fichiers PDF, DOC, DOCX, TXT, JPEG, JPG, PNG et ZIP sont autorisés.";
        }
        // Vérifier la taille du fichier (max 5MB)
        else if ($_FILES['fichier']['size'] > 5000000) {
            $upload_successful = false;
            $message = "Erreur: La taille du fichier ne doit pas dépasser 5MB.";
        }
        // Télécharger le fichier
        else if (!move_uploaded_file($_FILES['fichier']['tmp_name'], $chemin_fichier)) {
            $upload_successful = false;
            $message = "Erreur lors du téléchargement du fichier.";
        }
    }

    // Si le téléchargement a réussi (ou s'il n'y a pas de fichier), insérer dans la base de données
    if ($upload_successful) {
        try {
            $stmt = $pdo->prepare("INSERT INTO travaux (eleve_id, cours_id, titre, descriptions, nom_fichier, chemin_fichier, date_soumission) 
                                  VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$eleve['id'], $cours_id, $titre, $descriptions, $nom_fichier, $chemin_fichier]);
            $message = "Votre travail a été soumis avec succès.";
            $class_alert = "alert-success";
            
            // Rediriger pour éviter la soumission multiple du formulaire
            header("Location: eleve.php?submit_success=1");
            exit;
        } catch (PDOException $e) {
            $message = "Erreur: " . $e->getMessage();
            $class_alert = "alert-danger";
        }
    } else {
        $class_alert = "alert-danger";
    }
}

// Message de succès après redirection
if (isset($_GET['submit_success'])) {
    $message = "Votre travail a été soumis avec succès.";
    $class_alert = "alert-success";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Élève - <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/eleve.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <h1>Espace Élève</h1>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="eleve.php">Tableau de bord</a></li>
                <li><a href="../login/login.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-main">
        <div class="container">
            <?php if (isset($message)): ?>
                <div class="alert <?php echo $class_alert; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-grid">
                <!-- Carte de profil -->
                <div class="dashboard-card profile-card">
                    <div class="card-header">
                        <h2><i class="fas fa-id-card"></i> Profil</h2>
                    </div>
                    <div class="card-body">
                        <div class="profile-info">
                            <p><strong>Nom:</strong> <?php echo htmlspecialchars($eleve['nom']); ?></p>
                            <p><strong>Prénom:</strong> <?php echo htmlspecialchars($eleve['prenom']); ?></p>
                            <p><strong>Date de naissance:</strong> <?php echo $date_naissance_formattee; ?></p>
                            <p><strong>Classe:</strong> <?php echo htmlspecialchars($eleve['classe']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($eleve['email']); ?></p>
                            <p><strong>Identifiant:</strong> <?php echo htmlspecialchars($eleve['username']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Carte des moyennes -->
                <div class="dashboard-card grades-summary-card">
                    <div class="card-header">
                        <h2><i class="fas fa-chart-line"></i> Résumé des notes</h2>
                    </div>
                    <div class="card-body">
                        <div class="average-circle">
                            <div class="circle-value"><?php echo $moyenne_generale; ?></div>
                            <div class="circle-label">Moyenne générale</div>
                        </div>
                        <div class="subject-averages">
                            <?php foreach ($moyennes_matieres as $matiere => $moyenne): ?>
                                <div class="subject-average">
                                    <span class="subject-name"><?php echo htmlspecialchars($matiere); ?></span>
                                    <div class="average-bar-container">
                                        <div class="average-bar" style="width: <?php echo min(100, $moyenne * 5); ?>%;">
                                            <span class="average-value"><?php echo $moyenne; ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Carte de l'emploi du temps -->
                <div class="dashboard-card schedule-card">
                    <div class="card-header">
                        <h2><i class="fas fa-calendar-alt"></i> Emploi du temps</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($cours) > 0): ?>
                            <div class="schedule-list">
                                <?php foreach ($cours as $course): ?>
                                    <div class="schedule-item">
                                        <div class="schedule-date">
                                            <div class="day"><?php echo date('d', strtotime($course['horaire'])); ?></div>
                                            <div class="month"><?php echo date('M', strtotime($course['horaire'])); ?></div>
                                        </div>
                                        <div class="schedule-details">
                                            <h3><?php echo htmlspecialchars($course['matiere']); ?></h3>
                                            <p>
                                                <i class="fas fa-clock"></i> 
                                                <?php echo date('H:i', strtotime($course['horaire'])); ?>
                                            </p>
                                            <p>
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <?php echo htmlspecialchars($course['salle']); ?>
                                            </p>
                                            <p>
                                                <i class="fas fa-user-tie"></i> 
                                                Prof. <?php echo htmlspecialchars($course['prof_prenom'] . ' ' . $course['prof_nom']); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-data">Aucun cours n'est programmé pour votre classe.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Carte de soumission de travail -->
                <div class="dashboard-card submission-card">
                    <div class="card-header">
                        <h2><i class="fas fa-upload"></i> Soumettre un travail</h2>
                    </div>
                    <div class="card-body">
                        <form action="eleve.php" method="post" enctype="multipart/form-data" class="submission-form">
                            <div class="form-group">
                                <label for="cours_id">Cours:</label>
                                <select name="cours_id" id="cours_id" required>
                                    <option value="">Sélectionnez un cours</option>
                                    <?php foreach ($cours as $course): ?>
                                        <option value="<?php echo $course['id']; ?>">
                                            <?php echo htmlspecialchars($course['matiere'] . ' - Prof. ' . $course['prof_prenom'] . ' ' . $course['prof_nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="titre">Titre du travail:</label>
                                <input type="text" name="titre" id="titre" required>
                            </div>
                            <div class="form-group">
                                <label for="descriptions">descriptions:</label>
                                <textarea name="descriptions" id="descriptions" rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="fichier">Fichier (PDF, DOC, DOCX, TXT, JPEG, PNG, ZIP - max 5Mo):</label>
                                <input type="file" name="fichier" id="fichier">
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="submit_travail" class="btn-submit">Soumettre le travail</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Carte des travaux soumis -->
                <div class="dashboard-card submissions-card">
                    <div class="card-header">
                        <h2><i class="fas fa-tasks"></i> Mes travaux soumis</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($travaux) > 0): ?>
                            <div class="submissions-list">
                                <?php foreach ($travaux as $travail): ?>
                                    <div class="submission-item">
                                        <div class="submission-header">
                                            <h3><?php echo htmlspecialchars($travail['titre']); ?></h3>
                                            <span class="submission-date">
                                                <i class="fas fa-calendar"></i> 
                                                <?php echo date('d/m/Y H:i', strtotime($travail['date_soumission'])); ?>
                                            </span>
                                        </div>
                                        <div class="submission-details">
                                            <p><strong>Cours:</strong> <?php echo htmlspecialchars($travail['matiere']); ?></p>
                                            <p><strong>Professeur:</strong> <?php echo htmlspecialchars($travail['prof_prenom'] . ' ' . $travail['prof_nom']); ?></p>
                                            <p><strong>Descriptions:</strong> <?php echo nl2br(htmlspecialchars($travail['descriptions'])); ?></p>
                                            
                                            <?php if ($travail['nom_fichier']): ?>
                                                <p>
                                                    <strong>Fichier:</strong> 
                                                    <a href="<?php echo str_replace('../../', '', $travail['chemin_fichier']); ?>" target="_blank">
                                                        <i class="fas fa-file"></i> <?php echo $travail['nom_fichier']; ?>
                                                    </a>

                                                </p>
                                            <?php else: ?>
                                                <p><strong>Fichier:</strong> Aucun fichier joint</p>
                                            <?php endif; ?>
                                            
                                            <p><strong>Statut:</strong> <span class="status-badge status-<?php echo strtolower($travail['status']); ?>"><?php echo $travail['status']; ?></span></p>
                                            
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-data">Vous n'avez soumis aucun travail pour le moment.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Carte des notes détaillées -->
                <div class="dashboard-card detailed-grades-card">
                    <div class="card-header">
                        <h2><i class="fas fa-graduation-cap"></i> Notes détaillées</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($notes) > 0): ?>
                            <div class="grades-table-container">
                                <table class="grades-table">
                                    <thead>
                                        <tr>
                                            <th>Matière</th>
                                            <th>Note</th>
                                            <th>Date</th>
                                            <th>Professeur</th>
                                            <th>Commentaire</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($notes as $note): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($note['matiere']); ?></td>
                                                <td class="note-value"><?php echo $note['note']; ?>/20</td>
                                                <td><?php echo date('d/m/Y', strtotime($note['date_evaluation'])); ?></td>
                                                <td>Prof. <?php echo htmlspecialchars($note['prof_prenom'] . ' ' . $note['prof_nom']); ?></td>
                                                <td class="comment-cell">
                                                    <?php if ($note['commentaire']): ?>
                                                        <div class="comment-content">
                                                            <?php echo htmlspecialchars($note['commentaire']); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="no-comment">Aucun commentaire</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">Aucune note n'est enregistrée pour le moment.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="dashboard-footer">
        <div class="container">
            <p>&copy; 2025 - Système de Gestion Scolaire</p>
        </div>
    </footer>

    <script>
        // Script pour fermer les alertes
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>








