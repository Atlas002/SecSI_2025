<?php
// Inclure la connexion à la base de données
require_once '../config/db.php';

// Démarrer la session
session_start();

// Récupérer toutes les classes disponibles
$stmt = $pdo->query("SELECT DISTINCT classe FROM cours ORDER BY classe");
$classes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Définir la classe par défaut (première classe ou classe sélectionnée)
$selectedClass = isset($_GET['classe']) ? $_GET['classe'] : $classes[0];

// Récupérer la semaine en cours ou la semaine sélectionnée
$week = isset($_GET['semaine']) ? intval($_GET['semaine']) : 0;
$nextWeek = $week + 1;
$prevWeek = $week - 1;

// Calculer les dates de début et de fin de la semaine
$startDate = new DateTime();
$startDate->modify('monday this week');
$startDate->modify("+$week week");
$endDate = clone $startDate;
$endDate->modify('+6 days');

// Formater les dates pour l'affichage
$startDateFormatted = $startDate->format('d/m/Y');
$endDateFormatted = $endDate->format('d/m/Y');

// Récupérer les cours pour la classe et la semaine sélectionnées
$startDateSql = $startDate->format('Y-m-d') . ' 00:00:00';
$endDateSql = $endDate->format('Y-m-d') . ' 23:59:59';

$stmt = $pdo->prepare("
    SELECT c.*, p.nom as prof_nom, p.prenom as prof_prenom 
    FROM cours c
    JOIN profs p ON c.prof_id = p.id
    WHERE c.classe = :classe 
    AND c.horaire BETWEEN :start_date AND :end_date
    ORDER BY c.horaire
");

$stmt->bindParam(':classe', $selectedClass);
$stmt->bindParam(':start_date', $startDateSql);
$stmt->bindParam(':end_date', $endDateSql);
$stmt->execute();
$cours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les cours par jour et par heure
$coursParJour = [];
$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

foreach ($cours as $cours_item) {
    $date = new DateTime($cours_item['horaire']);
    $jourSemaine = $date->format('w'); // 0 (dimanche) à 6 (samedi)
    
    // Ajuster pour commencer par lundi (1)
    if ($jourSemaine == 0) $jourSemaine = 7;
    $jourSemaine--; // 0 (lundi) à 6 (dimanche)
    
    $heureDebut = $date->format('H:i');
    
    if (!isset($coursParJour[$jourSemaine])) {
        $coursParJour[$jourSemaine] = [];
    }
    
    $coursParJour[$jourSemaine][] = [
        'id' => $cours_item['id'],
        'matiere' => $cours_item['matiere'],
        'prof' => $cours_item['prof_prenom'] . ' ' . $cours_item['prof_nom'],
        'horaire' => $heureDebut,
        'salle' => $cours_item['salle']
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier des cours</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Calendrier des cours</h1>
        
        <!-- Sélection de la classe -->
        <div class="class-selection">
            <form action="" method="GET">
                <label for="classe">Classe :</label>
                <select name="classe" id="classe" onchange="this.form.submit()">
                    <?php foreach ($classes as $classe): ?>
                        <option value="<?= htmlspecialchars($classe) ?>" <?= $selectedClass === $classe ? 'selected' : '' ?>>
                            <?= htmlspecialchars($classe) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="semaine" value="<?= $week ?>">
            </form>
        </div>
        
        <!-- Navigation des semaines -->
        <div class="week-navigation">
            <a href="?classe=<?= urlencode($selectedClass) ?>&semaine=<?= $prevWeek ?>" class="btn">Semaine précédente</a>
            <span class="current-week">Semaine du <?= $startDateFormatted ?> au <?= $endDateFormatted ?></span>
            <a href="?classe=<?= urlencode($selectedClass) ?>&semaine=<?= $nextWeek ?>" class="btn">Semaine suivante</a>
        </div>
        
        <!-- Calendrier -->
        <div class="calendar">
            <table>
                <thead>
                    <tr>
                        <th>Horaire</th>
                        <?php 
                        // Afficher les jours avec leurs dates
                        $currentDate = clone $startDate;
                        for ($i = 0; $i < 5; $i++): // On affiche seulement les 5 jours de la semaine (lundi-vendredi)
                            $dayDate = $currentDate->format('d/m');
                            $currentDate->modify('+1 day');
                        ?>
                            <th><?= $jours[$i] ?><br><?= $dayDate ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Heures de cours (de 8h à 18h par incréments de 2 heures)
                    for ($heure = 8; $heure <= 18; $heure += 2): 
                        $heureFormatee = sprintf("%02d:00", $heure);
                    ?>
                        <tr>
                            <td class="time-slot"><?= $heureFormatee ?></td>
                            <?php for ($jour = 0; $jour < 5; $jour++): ?>
                                <td class="course-cell">
                                    <?php
                                    // Afficher les cours pour ce jour et cette plage horaire
                                    if (isset($coursParJour[$jour])) {
                                        foreach ($coursParJour[$jour] as $cours_item) {
                                            $courseHour = (int)substr($cours_item['horaire'], 0, 2);
                                            // Si le cours est dans cette plage horaire (heure ou heure+1)
                                            if ($courseHour >= $heure && $courseHour < $heure + 2) {
                                                echo '<div class="course">';
                                                echo '<div class="course-name">' . htmlspecialchars($cours_item['matiere']) . '</div>';
                                                echo '<div class="course-details">';
                                                echo '<div>Prof: ' . htmlspecialchars($cours_item['prof']) . '</div>';
                                                echo '<div>Horaire: ' . htmlspecialchars($cours_item['horaire']) . '</div>';
                                                echo '<div>Salle: ' . htmlspecialchars($cours_item['salle']) . '</div>';
                                                echo '</div>';
                                                echo '</div>';
                                            }
                                        }
                                    }
                                    ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>