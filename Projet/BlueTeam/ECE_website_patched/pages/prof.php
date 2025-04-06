<?php
session_start();
require_once '../config/db.php';

// Vérifier si l'utilisateur est connecté et est un professeur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'prof') {
    header('Location: ../login/login.php');
    exit;
}

// Récupérer les informations du professeur
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM profs WHERE user_id = ?");
$stmt->execute([$userId]);
$prof = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prof) {
    die("Erreur: Profil de professeur non trouvé.");
}

// Récupération des classes disponibles
$stmt = $pdo->prepare("SELECT DISTINCT classe FROM eleves ORDER BY classe");
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Initialisation des variables
$message = '';
$messageType = '';
$view = isset($_GET['view']) ? $_GET['view'] : 'cours';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$filterClasse = isset($_GET['filter_classe']) ? $_GET['filter_classe'] : '';
$coursId = isset($_GET['cours_id']) ? intval($_GET['cours_id']) : 0;

// Traitement des actions
// Ajouter un nouveau cours
if (isset($_POST['add_cours'])) {
    $classe = htmlspecialchars($_POST['classe']);
    $matiere = htmlspecialchars($_POST['matiere']);
    $horaire = htmlspecialchars($_POST['horaire']);
    $salle = htmlspecialchars($_POST['salle']);
    
    $stmt = $pdo->prepare("INSERT INTO cours (prof_id, classe, matiere, horaire, salle) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$prof['id'], $classe, $matiere, $horaire, $salle])) {
        $message = "Cours ajouté avec succès!";
        $messageType = "success";
        // Rafraîchir la liste des cours
        header("Location: prof.php?view=cours&message=$message&type=$messageType");
        exit;
    } else {
        $message = "Erreur lors de l'ajout du cours.";
        $messageType = "error";
    }
}

// Modifier un cours
if (isset($_POST['edit_cours']) && isset($_POST['cours_id'])) {
    $coursId = intval($_POST['cours_id']);
    $classe = htmlspecialchars($_POST['classe']);
    $matiere = htmlspecialchars($_POST['matiere']);
    $horaire = htmlspecialchars($_POST['horaire']);
    $salle = $_POST['salle']; // Suppression de htmlspecialchars
    
    // Vérifier que le cours appartient au professeur
    $stmt = $pdo->prepare("SELECT * FROM cours WHERE id = ? AND prof_id = ?");
    $stmt->execute([$coursId, $prof['id']]);
    if ($stmt->rowCount() > 0) {
        //
        // VULBNERABILITE: Injection SQL possible dans la variable $salle
        //
        // Requête vulnérable utilisant la concaténation directe
        $query = "UPDATE cours SET classe = '" . $classe . "', matiere = '" . $matiere . "', horaire = '" . $horaire . "', salle = '" . $salle . "' WHERE id = " . $coursId;
        
        if ($pdo->query($query)) {
            $message = "Cours modifié avec succès!";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la modification du cours: " . $pdo->errorInfo()[2];
            $messageType = "error";
        }
    } else {
        $message = "Vous n'êtes pas autorisé à modifier ce cours.";
        $messageType = "error";
    }
    
    header("Location: prof.php?view=cours&message=$message&type=$messageType");
    exit;
}

// Supprimer un cours
if (isset($_GET['delete_cours']) && is_numeric($_GET['delete_cours'])) {
    $coursId = $_GET['delete_cours'];
    
    // Vérifier que le cours appartient au professeur
    $stmt = $pdo->prepare("SELECT * FROM cours WHERE id = ? AND prof_id = ?");
    $stmt->execute([$coursId, $prof['id']]);
    if ($stmt->rowCount() > 0) {
        // Vérifier s'il y a des notes liées à ce cours
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE cours_id = ?");
        $stmt->execute([$coursId]);
        $notesCount = $stmt->fetchColumn();
        
        if ($notesCount > 0) {
            // Demander confirmation avant de supprimer
            if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
                // Supprimer d'abord les notes liées
                $stmt = $pdo->prepare("DELETE FROM notes WHERE cours_id = ?");
                $stmt->execute([$coursId]);
                
                // Puis supprimer le cours
                $stmt = $pdo->prepare("DELETE FROM cours WHERE id = ?");
                $stmt->execute([$coursId]);
                
                $message = "Cours et notes associées supprimés avec succès!";
                $messageType = "success";
            } else {
                // Rediriger vers une page de confirmation
                header("Location: prof.php?view=confirm_delete&cours_id=$coursId");
                exit;
            }
        } else {
            // Pas de notes, supprimer directement
            $stmt = $pdo->prepare("DELETE FROM cours WHERE id = ?");
            $stmt->execute([$coursId]);
            
            $message = "Cours supprimé avec succès!";
            $messageType = "success";
        }
    } else {
        $message = "Vous n'êtes pas autorisé à supprimer ce cours.";
        $messageType = "error";
    }
    
    if ($view !== 'confirm_delete') {
        header("Location: prof.php?view=cours&message=$message&type=$messageType");
        exit;
    }
}

// Gestion des notes
if ($view === 'notes' && $coursId > 0) {
    // Vérifier que le cours appartient au professeur
    $stmt = $pdo->prepare("SELECT * FROM cours WHERE id = ? AND prof_id = ?");
    $stmt->execute([$coursId, $prof['id']]);
    $currentCours = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentCours) {
        // Récupérer tous les élèves de la classe concernée
        $stmt = $pdo->prepare("SELECT e.* FROM eleves e WHERE e.classe = ?");
        $stmt->execute([$currentCours['classe']]);
        $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les notes existantes pour ce cours
        $stmt = $pdo->prepare("SELECT n.* FROM notes n WHERE n.cours_id = ?");
        $stmt->execute([$coursId]);
        $existingNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Création d'un tableau associatif pour faciliter l'accès aux notes
        $notesMap = [];
        foreach ($existingNotes as $note) {
            $notesMap[$note['eleve_id']] = $note;
        }
        
        // Ajouter ou mettre à jour les notes
        if (isset($_POST['save_notes'])) {
            $successCount = 0;
            $totalCount = 0;
            
            foreach ($_POST['notes'] as $eleveId => $noteData) {
                $totalCount++;
                $note = !empty($noteData['note']) ? floatval($noteData['note']) : null;
                $commentaire = htmlspecialchars($noteData['commentaire'] ?? '');
                
                if ($note !== null) {
                    // Vérifier si une note existe déjà pour cet élève dans ce cours
                    $stmt = $pdo->prepare("SELECT id FROM notes WHERE eleve_id = ? AND cours_id = ?");
                    $stmt->execute([$eleveId, $coursId]);
                    $existingNote = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existingNote) {
                        // Mettre à jour la note existante
                        $stmt = $pdo->prepare("UPDATE notes SET note = ?, commentaire = ? WHERE id = ?");
                        if ($stmt->execute([$note, $commentaire, $existingNote['id']])) {
                            $successCount++;
                        }
                    } else {
                        // Ajouter une nouvelle note
                        $stmt = $pdo->prepare("INSERT INTO notes (eleve_id, cours_id, note, commentaire) VALUES (?, ?, ?, ?)");
                        if ($stmt->execute([$eleveId, $coursId, $note, $commentaire])) {
                            $successCount++;
                        }
                    }
                }
            }
            
            if ($successCount > 0) {
                $message = "$successCount/$totalCount notes enregistrées avec succès!";
                $messageType = "success";
            } else {
                $message = "Aucune note n'a été enregistrée.";
                $messageType = "error";
            }
            
            // Rafraîchir la page pour afficher les notes mises à jour
            header("Location: prof.php?view=notes&cours_id=$coursId&message=$message&type=$messageType");
            exit;
        }
    } else {
        $message = "Vous n'êtes pas autorisé à accéder à ce cours.";
        $messageType = "error";
        header("Location: prof.php?view=cours&message=$message&type=$messageType");
        exit;
    }
}

// Récupérer les cours du professeur pour l'affichage
$coursQuery = "SELECT * FROM cours WHERE prof_id = ?";
$params = [$prof['id']];

// Ajouter le filtre de recherche si nécessaire
if (!empty($searchTerm)) {
    $coursQuery .= " AND (classe LIKE ? OR matiere LIKE ? OR salle LIKE ?)";
    $searchParam = "%$searchTerm%";
    array_push($params, $searchParam, $searchParam, $searchParam);
}

// Ajouter le filtre de classe si nécessaire
if (!empty($filterClasse)) {
    $coursQuery .= " AND classe = ?";
    array_push($params, $filterClasse);
}

// Ajouter l'ordre de tri
$coursQuery .= " ORDER BY horaire";

$stmt = $pdo->prepare($coursQuery);
$stmt->execute($params);
$cours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les statistiques de notes pour ce professeur
$statsQuery = "SELECT 
                c.id as cours_id,
                c.classe,
                c.matiere,
                c.horaire,
                COUNT(n.id) as nb_notes,
                AVG(n.note) as moyenne,
                MIN(n.note) as min_note,
                MAX(n.note) as max_note
              FROM cours c
              LEFT JOIN notes n ON c.id = n.cours_id
              WHERE c.prof_id = ?
              GROUP BY c.id
              ORDER BY c.horaire DESC";

$stmt = $pdo->prepare($statsQuery);
$stmt->execute([$prof['id']]);
$statsNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Charger les détails du cours pour l'édition
$coursToEdit = null;
if (isset($_GET['edit_cours']) && is_numeric($_GET['edit_cours'])) {
    $editCoursId = $_GET['edit_cours'];
    $stmt = $pdo->prepare("SELECT * FROM cours WHERE id = ? AND prof_id = ?");
    $stmt->execute([$editCoursId, $prof['id']]);
    $coursToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coursToEdit) {
        $message = "Cours non trouvé ou vous n'êtes pas autorisé à le modifier.";
        $messageType = "error";
    }
}

// Récupération des messages de redirection
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Professeur</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <h1>Espace Professeur</h1>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="prof.php">Tableau de bord</a></li>
                <li><a href="../login/login.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="container">
        <!-- Affichage des informations d'injection SQL -->
        <?php if (isset($_GET['injection']) && $_GET['injection'] == 'success'): ?>
            <div class="sql-injection-results" style="background-color: #ffdddd; border: 2px solid red; padding: 15px; margin-bottom: 20px;">
                <h3 style="color: red;">⚠️ Injection SQL détectée !</h3>
                <?php if (isset($_SESSION['last_query'])): ?>
                    <div>
                        <h4>Requête exécutée :</h4>
                        <pre style="background-color: #f5f5f5; padding: 10px; overflow-x: auto;"><?php echo htmlspecialchars($_SESSION['last_query']); ?></pre>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['sql_injection_results']) && !empty($_SESSION['sql_injection_results'])): ?>
                    <div>
                        <h4>Résultats des requêtes :</h4>
                        <?php foreach ($_SESSION['sql_injection_results'] as $index => $resultSet): ?>
                            <h5>Résultat #<?php echo $index + 1; ?> :</h5>
                            <?php if (empty($resultSet)): ?>
                                <p>Aucune donnée retournée ou commande exécutée avec succès</p>
                            <?php elseif (isset($resultSet[0]) && is_array($resultSet[0])): ?>
                                <div style="overflow-x: auto;">
                                    <table border="1" style="border-collapse: collapse; width: 100%;">
                                        <thead>
                                            <tr>
                                                <?php foreach (array_keys($resultSet[0]) as $column): ?>
                                                    <th style="padding: 8px; background-color: #f2f2f2;"><?php echo htmlspecialchars($column); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($resultSet as $row): ?>
                                                <tr>
                                                    <?php foreach ($row as $value): ?>
                                                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($value); ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <pre style="background-color: #f5f5f5; padding: 10px;"><?php print_r($resultSet); ?></pre>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Ajouter un compteur d'opérations pour les requêtes non SELECT -->
                <?php
                // Vérifier les modifications dans les tables principales
                $tableChanges = [];
                $tables = ['cours', 'notes', 'eleves', 'profs', 'users'];
                foreach ($tables as $table) {
                    try {
                        $countQuery = "SELECT COUNT(*) as count FROM $table";
                        $countResult = $pdo->query($countQuery);
                        $count = $countResult->fetch(PDO::FETCH_ASSOC)['count'];
                        $tableChanges[] = "$table: $count enregistrements";
                    } catch (Exception $e) {
                        $tableChanges[] = "$table: Erreur lors du comptage";
                    }
                }
                ?>
                <div>
                    <h4>État actuel des tables :</h4>
                    <ul>
                        <?php foreach ($tableChanges as $change): ?>
                            <li><?php echo htmlspecialchars($change); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <?php 
            // Nettoyage des variables de session après affichage
            unset($_SESSION['last_query']);
            unset($_SESSION['sql_injection_results']);
            ?>
        <?php endif; ?>

        <?php if (isset($_GET['error']) || isset($_SESSION['sql_error'])): ?>
            <div class="sql-error" style="background-color: #fff3cd; border: 2px solid #ffeeba; padding: 15px; margin-bottom: 20px;">
                <h3>Erreur SQL :</h3>
                <pre style="background-color: #f5f5f5; padding: 10px; overflow-x: auto;"><?php echo htmlspecialchars(isset($_GET['error']) ? urldecode($_GET['error']) : $_SESSION['sql_error']); ?></pre>
            </div>
            <?php unset($_SESSION['sql_error']); ?>
        <?php endif; ?>


        <!-- Affichage des messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Informations du professeur -->
        <section class="prof-info">
            <h2>Bienvenue, <?php echo htmlspecialchars($prof['prenom'] . ' ' . $prof['nom']); ?></h2>
            <p>Matière principale : <?php echo htmlspecialchars($prof['matiere']); ?></p>
        </section>
        
        <!-- Navigation interne -->
        <div class="tabs">
            <a href="?view=cours" class="tab <?php echo $view === 'cours' ? 'active' : ''; ?>">Mes cours</a>
            <a href="?view=stats" class="tab <?php echo $view === 'stats' ? 'active' : ''; ?>">Statistiques</a>
        </div>
        
        <?php if ($view === 'confirm_delete' && isset($_GET['cours_id'])): ?>
            <!-- Vue de confirmation de suppression -->
            <section class="confirmation">
                <h2>Confirmation de suppression</h2>
                <?php
                    $delCoursId = intval($_GET['cours_id']);
                    $stmt = $pdo->prepare("SELECT * FROM cours WHERE id = ?");
                    $stmt->execute([$delCoursId]);
                    $delCours = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE cours_id = ?");
                    $stmt->execute([$delCoursId]);
                    $notesCount = $stmt->fetchColumn();
                ?>
                
                <div class="warning-box">
                    <p><strong>Attention :</strong> Vous êtes sur le point de supprimer le cours suivant :</p>
                    <p>
                        <strong>Classe :</strong> <?php echo htmlspecialchars($delCours['classe']); ?><br>
                        <strong>Matière :</strong> <?php echo htmlspecialchars($delCours['matiere']); ?><br>
                        <strong>Horaire :</strong> <?php echo date('d/m/Y H:i', strtotime($delCours['horaire'])); ?><br>
                        <strong>Salle :</strong> <?php echo htmlspecialchars($delCours['salle']); ?>
                    </p>
                    
                    <?php if ($notesCount > 0): ?>
                        <p class="warning"><strong>Important :</strong> Ce cours contient <?php echo $notesCount; ?> note(s) qui seront également supprimées.</p>
                    <?php endif; ?>
                    
                    <p>Cette action est irréversible. Voulez-vous continuer ?</p>
                    
                    <div class="action-buttons">
                        <a href="?delete_cours=<?php echo $delCoursId; ?>&confirm=yes" class="btn-danger">Confirmer la suppression</a>
                        <a href="?view=cours" class="btn-secondary">Annuler</a>
                    </div>
                </div>
            </section>
            
        <?php elseif ($view === 'notes' && isset($currentCours)): ?>
            <!-- Vue de gestion des notes -->
            <section class="notes-section">
                <h2>Gestion des notes</h2>
                <div class="cours-details">
                    <p><strong>Classe :</strong> <?php echo htmlspecialchars($currentCours['classe']); ?></p>
                    <p><strong>Matière :</strong> <?php echo htmlspecialchars($currentCours['matiere']); ?></p>
                    <p><strong>Date :</strong> <?php echo date('d/m/Y H:i', strtotime($currentCours['horaire'])); ?></p>
                    <p><strong>Salle :</strong> <?php echo htmlspecialchars($currentCours['salle']); ?></p>
                </div>
                
                <?php if (empty($eleves)): ?>
                    <p class="no-data">Aucun élève trouvé pour cette classe.</p>
                <?php else: ?>
                    <form method="post" action="?view=notes&cours_id=<?php echo $coursId; ?>">
                        <table class="table-notes">
                            <thead>
                                <tr>
                                    <th>Élève</th>
                                    <th>Note /20</th>
                                    <th>Commentaire</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eleves as $eleve): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?></td>
                                        <td>
                                            <input type="number" step="0.25" min="0" max="20" 
                                                   name="notes[<?php echo $eleve['id']; ?>][note]" 
                                                   value="<?php echo isset($notesMap[$eleve['id']]) ? htmlspecialchars($notesMap[$eleve['id']]['note']) : ''; ?>" 
                                                   class="note-input">
                                        </td>
                                        <td>
                                            <textarea name="notes[<?php echo $eleve['id']; ?>][commentaire]" 
                                                      class="commentaire-input"><?php echo isset($notesMap[$eleve['id']]) ? htmlspecialchars($notesMap[$eleve['id']]['commentaire']) : ''; ?></textarea>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="form-actions">
                            <button type="submit" name="save_notes" class="btn-primary">Enregistrer les notes</button>
                            <a href="?view=cours" class="btn-secondary">Retour aux cours</a>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
            
        <?php elseif ($view === 'stats'): ?>
            <!-- Vue des statistiques -->
            <section class="stats-section">
                <h2>Statistiques des notes par cours</h2>
                
                <?php if (empty($statsNotes)): ?>
                    <p class="no-data">Aucune donnée disponible pour le moment.</p>
                <?php else: ?>
                    <table class="table-stats">
                        <thead>
                            <tr>
                                <th>Classe</th>
                                <th>Matière</th>
                                <th>Date</th>
                                <th>Nombre de notes</th>
                                <th>Moyenne</th>
                                <th>Note min</th>
                                <th>Note max</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statsNotes as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['classe']); ?></td>
                                    <td><?php echo htmlspecialchars($stat['matiere']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($stat['horaire'])); ?></td>
                                    <td><?php echo $stat['nb_notes']; ?></td>
                                    <td><?php echo $stat['nb_notes'] > 0 ? number_format($stat['moyenne'], 2) : '-'; ?></td>
                                    <td><?php echo $stat['nb_notes'] > 0 ? number_format($stat['min_note'], 2) : '-'; ?></td>
                                    <td><?php echo $stat['nb_notes'] > 0 ? number_format($stat['max_note'], 2) : '-'; ?></td>
                                    <td class="actions">
                                        <a href="?view=notes&cours_id=<?php echo $stat['cours_id']; ?>" class="btn-small">Voir les notes</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
            
        <?php else: ?>
            <!-- Vue par défaut - Liste des cours -->
            <section class="cours-section">
                <h2>Mes cours</h2>
                
                <!-- Filtres de recherche -->
                <div class="search-filters">
                    <form method="get" action="prof.php">
                        <input type="hidden" name="view" value="cours">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                            
                            <select name="filter_classe">
                                <option value="">Toutes les classes</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?php echo htmlspecialchars($classe); ?>" <?php echo $filterClasse === $classe ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($classe); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <button type="submit" class="btn-small">Filtrer</button>
                            <?php if (!empty($searchTerm) || !empty($filterClasse)): ?>
                                <a href="?view=cours" class="btn-small">Réinitialiser</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <?php if (empty($cours)): ?>
                    <p class="no-data">Aucun cours trouvé.</p>
                <?php else: ?>
                    <table class="table-cours">
                        <thead>
                            <tr>
                                <th>Classe</th>
                                <th>Matière</th>
                                <th>Date et heure</th>
                                <th>Salle</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cours as $c): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($c['classe']); ?></td>
                                    <td><?php echo htmlspecialchars($c['matiere']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($c['horaire'])); ?></td>
                                    <td><?php echo htmlspecialchars($c['salle']); ?></td>
                                    <td class="actions">
                                        <a href="?view=notes&cours_id=<?php echo $c['id']; ?>" class="btn-small">Notes</a>
                                        <a href="?view=cours&edit_cours=<?php echo $c['id']; ?>" class="btn-small">Modifier</a>
                                        <a href="?view=confirm_delete&cours_id=<?php echo $c['id']; ?>" class="btn-small btn-danger">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <!-- Formulaire d'ajout/modification de cours -->
                <div class="cours-form">
                    <h3><?php echo $coursToEdit ? 'Modifier un cours' : 'Ajouter un nouveau cours'; ?></h3>
                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <?php if ($coursToEdit): ?>
                            <input type="hidden" name="cours_id" value="<?php echo $coursToEdit['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="classe">Classe:</label>
                            <select name="classe" id="classe" required>
                                <option value="">Sélectionner une classe</option>
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?php echo htmlspecialchars($classe); ?>" <?php echo ($coursToEdit && $coursToEdit['classe'] === $classe) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($classe); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="matiere">Matière:</label>
                            <input type="text" name="matiere" id="matiere" 
                                   value="<?php echo $coursToEdit ? htmlspecialchars($coursToEdit['matiere']) : htmlspecialchars($prof['matiere']); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="horaire">Date et heure:</label>
                            <input type="datetime-local" name="horaire" id="horaire" 
                                   value="<?php echo $coursToEdit ? date('Y-m-d\TH:i', strtotime($coursToEdit['horaire'])) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="salle">Salle:</label>
                            <input type="text" name="salle" id="salle" 
                                   value="<?php echo $coursToEdit ? htmlspecialchars($coursToEdit['salle']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-actions">
                            <?php if ($coursToEdit): ?>
                                <button type="submit" name="edit_cours" class="btn-primary">Modifier le cours</button>
                                <a href="?view=cours" class="btn-secondary">Annuler</a>
                            <?php else: ?>
                                <button type="submit" name="add_cours" class="btn-primary">Ajouter le cours</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </main>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> - Système de Gestion Scolaire</p>
    </footer>
</body>
</html>