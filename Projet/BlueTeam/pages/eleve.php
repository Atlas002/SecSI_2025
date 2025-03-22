<?php
session_start();
require_once '../config/db.php';

// Vérifier si l'utilisateur est connecté et a le rôle 'eleve'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'eleve') {
    header('Location: login.php');
    exit;
}

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
        <h1>Espace Professeur</h1>
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