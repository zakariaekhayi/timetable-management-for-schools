<?php
session_start();
$level = 2;
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Récupération des niveaux pour le filtrage
$niveaux_stmt = $pdo->query("SELECT DISTINCT nom FROM Niveau ORDER BY nom");
$niveaux = $niveaux_stmt->fetchAll();

// Construction de la requête avec filtres
$where_conditions = [];
$params = [];

if (!empty($_GET['niveau_principal'])) {
    $where_conditions[] = "n.nom LIKE ?";
    $params[] = $_GET['niveau_principal'] . '%';
}

if (!empty($_GET['niveau_secondaire'])) {
    if (in_array($_GET['niveau_principal'], ['CI1', 'CI2', 'CI3'])) {
        $where_conditions[] = "n.filiere = ?";
        $params[] = $_GET['niveau_secondaire'];
    } else if (in_array($_GET['niveau_principal'], ['AP1', 'AP2'])) {
        // CORRECTION: Pour AP1/AP2, filtrer par filière, pas par nom
        $where_conditions[] = "n.filiere = ?";
        $params[] = $_GET['niveau_secondaire'];
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$query = "
    SELECT e.*, n.nom as niveau_nom, n.filiere, m.nom as module_nom, u.nom as prof_nom, u.prenom as prof_prenom,
           c.numeroSalle, cr.jour, cr.heure_deb, cr.heure_fin
    FROM Emploi_du_temps e
    JOIN Niveau n ON e.niveau_id = n.id
    JOIN Module m ON e.module_id = m.id
    JOIN User u ON e.enseignant_id = u.id
    LEFT JOIN Classe c ON e.salle_id = c.id
    JOIN Creneaux cr ON e.creneau_id = cr.id
    $where_clause
    ORDER BY cr.jour, cr.heure_deb
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$emplois = $stmt->fetchAll();

// Organisation des données pour l'affichage en grille
$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
$heures = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
$grille = [];

foreach ($emplois as $emploi) {
    $jour = $emploi['jour'];
    // Convertir l'heure de la BD (format HH:MM:SS) vers le format HH:MM
    $heure = substr($emploi['heure_deb'], 0, 5);
    $grille[$jour][$heure] = $emploi;
}
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="mb-1">Emplois du Temps</h2>
                    <p class="text-muted mb-0">Consultez et gérez les plannings de vos classes</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Imprimer
                    </button>
                    <a href="edit.php" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Édition
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtres</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Planning</label>
                            <select name="niveau_principal" id="niveau_principal" class="form-control" onchange="updateSecondaryOptions()">
                                <option value="">Sélectionner un niveau</option>
                                <option value="AP1" <?= ($_GET['niveau_principal'] ?? '') == 'AP1' ? 'selected' : '' ?>>AP1</option>
                                <option value="AP2" <?= ($_GET['niveau_principal'] ?? '') == 'AP2' ? 'selected' : '' ?>>AP2</option>
                                <option value="CI1" <?= ($_GET['niveau_principal'] ?? '') == 'CI1' ? 'selected' : '' ?>>CI1</option>
                                <option value="CI2" <?= ($_GET['niveau_principal'] ?? '') == 'CI2' ? 'selected' : '' ?>>CI2</option>
                                <option value="CI3" <?= ($_GET['niveau_principal'] ?? '') == 'CI3' ? 'selected' : '' ?>>CI3</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Spécialisation/Section</label>
                            <select name="niveau_secondaire" id="niveau_secondaire" class="form-control">
                                <option value="">Toutes</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-2"></i>Filtrer
                            </button>
                            <a href="view.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug information (à supprimer en production) -->
    <?php if (!empty($_GET['niveau_principal']) || !empty($_GET['niveau_secondaire'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <strong>Debug:</strong> 
                Niveau principal: <?= htmlspecialchars($_GET['niveau_principal'] ?? 'Non défini') ?> | 
                Niveau secondaire: <?= htmlspecialchars($_GET['niveau_secondaire'] ?? 'Non défini') ?> | 
                Requête WHERE: <?= htmlspecialchars($where_clause) ?> | 
                Paramètres: <?= htmlspecialchars(implode(', ', $params)) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Emploi du temps en grille -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Emploi du temps
                        <?php if (!empty($_GET['niveau_principal'])): ?>
                            - <?= htmlspecialchars($_GET['niveau_principal']) ?>
                            <?php if (!empty($_GET['niveau_secondaire'])): ?>
                                <?= htmlspecialchars($_GET['niveau_secondaire']) ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-info"><?= count($emplois) ?> cours</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 timetable">
                            <thead>
                                <tr class="table-primary">
                                    <th class="timetable-header" style="width: 100px;">Horaires</th>
                                    <?php foreach($jours as $jour): ?>
                                        <th class="timetable-header text-center"><?= $jour ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($heures as $heure): ?>
                                <tr>
                                    <td class="timetable-time text-center fw-bold"><?= $heure ?></td>
                                    <?php foreach($jours as $jour): ?>
                                        <td class="timetable-cell" style="height: 80px; vertical-align: middle;">
                                            <?php if (isset($grille[$jour][$heure])): 
                                                $cours = $grille[$jour][$heure];
                                            ?>
                                                <div class="timetable-subject">
                                                    <div class="fw-bold"><?= htmlspecialchars($cours['module_nom']) ?></div>
                                                    <small class="timetable-room">
                                                        <?= htmlspecialchars($cours['prof_prenom'] . ' ' . $cours['prof_nom']) ?><br>
                                                        <?= htmlspecialchars($cours['numeroSalle'] ?? 'Salle non assignée') ?>
                                                        <span class="badge bg-light text-dark ms-1"><?= $cours['type_seance'] ?></span>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste détaillée (optionnelle) -->
    <?php if (!empty($emplois)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste détaillée</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Jour</th>
                                    <th>Heure</th>
                                    <th>Niveau</th>
                                    <th>Filière</th>
                                    <th>Module</th>
                                    <th>Enseignant</th>
                                    <th>Salle</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($emplois as $emploi): ?>
                                <tr>
                                    <td><span class="badge bg-primary"><?= $emploi['jour'] ?></span></td>
                                    <td><?= $emploi['heure_deb'] ?> - <?= $emploi['heure_fin'] ?></td>
                                    <td><?= htmlspecialchars($emploi['niveau_nom']) ?></td>
                                    <td><?= htmlspecialchars($emploi['filiere']) ?></td>
                                    <td><?= htmlspecialchars($emploi['module_nom']) ?></td>
                                    <td><?= htmlspecialchars($emploi['prof_prenom'] . ' ' . $emploi['prof_nom']) ?></td>
                                    <td><?= htmlspecialchars($emploi['numeroSalle'] ?? 'Non assignée') ?></td>
                                    <td><span class="badge bg-success"><?= $emploi['type_seance'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function updateSecondaryOptions() {
    const primary = document.getElementById('niveau_principal').value;
    const secondary = document.getElementById('niveau_secondaire');
    const currentValue = '<?= $_GET['niveau_secondaire'] ?? '' ?>';
    
    // Vider les options
    secondary.innerHTML = '<option value="">Toutes</option>';
    
    if (['CI1', 'CI2', 'CI3'].includes(primary)) {
        // Options pour les niveaux CI
        const options = [
            {value: 'GI', text: 'GI - Génie Informatique'},
            {value: 'GIND', text: 'GIND - Génie Industriel'},
            {value: 'GE', text: 'GE - Génie Électrique'},
            {value: 'GCivil', text: 'GCivil - Génie Civil'},
            {value: 'GM', text: 'GM - Génie Mécanique'},
            {value: 'RST', text: 'RST - Réseaux et Systèmes de Télécommunications'}
        ];
        
        options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.text = opt.text;
            if (opt.value === currentValue) option.selected = true;
            secondary.appendChild(option);
        });
    } else if (['AP1', 'AP2'].includes(primary)) {
        // Options pour les niveaux AP
        const options = [
            {value: 'Section A', text: 'Section A'},
            {value: 'Section B', text: 'Section B'}
        ];
        
        options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.text = opt.text;
            if (opt.value === currentValue) option.selected = true;
            secondary.appendChild(option);
        });
    }
}

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    updateSecondaryOptions();
});
</script>

<?php require_once '../../includes/footer.php'; ?>