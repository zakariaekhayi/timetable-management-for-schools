<?php
session_start();
$level = 2;
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Récupération des données
$niveaux = $pdo->query("SELECT * FROM Niveau ORDER BY nom")->fetchAll();
$modules = $pdo->query("SELECT * FROM Module ORDER BY nom")->fetchAll();
$enseignants = $pdo->query("SELECT u.* FROM User u JOIN Enseignant e ON u.id = e.user_id ORDER BY u.nom, u.prenom")->fetchAll();
$salles = $pdo->query("SELECT * FROM Classe ORDER BY numeroSalle")->fetchAll();
$creneaux = $pdo->query("SELECT * FROM Creneaux ORDER BY 
    CASE jour 
        WHEN 'Lundi' THEN 1 
        WHEN 'Mardi' THEN 2 
        WHEN 'Mercredi' THEN 3 
        WHEN 'Jeudi' THEN 4 
        WHEN 'Vendredi' THEN 5 
        WHEN 'Samedi' THEN 6 
    END, heure_deb")->fetchAll();

// Construction de la requête avec filtres
$where_conditions = [];
$params = [];

if (!empty($_GET['niveau_principal'])) {
    $niveau_principal = $_GET['niveau_principal'];
    
    if (in_array($niveau_principal, ['CI1', 'CI2', 'CI3'])) {
        $where_conditions[] = "n.nom LIKE ?";
        $params[] = $niveau_principal . '%';
        
        if (!empty($_GET['niveau_secondaire'])) {
            $where_conditions[] = "n.filiere = ?";
            $params[] = $_GET['niveau_secondaire'];
        }
    } else if (in_array($niveau_principal, ['AP1', 'AP2'])) {
        if (!empty($_GET['niveau_secondaire'])) {
            $where_conditions[] = "n.nom = ?";
            $params[] = $niveau_principal . ' ' . $_GET['niveau_secondaire'];
        } else {
            $where_conditions[] = "n.nom LIKE ?";
            $params[] = $niveau_principal . '%';
        }
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
$emploi_existant = $stmt->fetchAll();

$grille_existante = [];
foreach ($emploi_existant as $cours) {
    $grille_existante[$cours['jour']][$cours['heure_deb']] = $cours;
}

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
$heures = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="mb-1">Édition d'Emploi du Temps</h2>
                    <p class="text-muted mb-0">Modifiez dynamiquement les horaires en glissant-déposant</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" onclick="saveChanges()">
                        <i class="fas fa-save me-2"></i>Sauvegarder
                    </button>
                    <button class="btn btn-warning" onclick="autoGenerate()">
                        <i class="fas fa-magic me-2"></i>Générer automatiquement
                    </button>
                    <button class="btn btn-outline-secondary" onclick="cancelChanges()">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <a href="view.php" class="btn btn-outline-primary">
                        <i class="fas fa-eye me-2"></i>Vider le planning
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Panneau de planning -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Planning
                        <?php if (!empty($_GET['niveau_principal'])): ?>
                            - <?= htmlspecialchars($_GET['niveau_principal']) ?>
                            <?php if (!empty($_GET['niveau_secondaire'])): ?>
                                <?= htmlspecialchars($_GET['niveau_secondaire']) ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </h5>
                    <div class="d-flex gap-2">
                        <!-- Filtres -->
                        <form method="GET" class="d-flex gap-2">
                            <select name="niveau_principal" id="niveau_principal" class="form-select" onchange="updateSecondaryOptions()" style="width: 150px;">
                                <option value="">Tous les niveaux</option>
                                <option value="AP1" <?= ($_GET['niveau_principal'] ?? '') == 'AP1' ? 'selected' : '' ?>>AP1</option>
                                <option value="AP2" <?= ($_GET['niveau_principal'] ?? '') == 'AP2' ? 'selected' : '' ?>>AP2</option>
                                <option value="CI1" <?= ($_GET['niveau_principal'] ?? '') == 'CI1' ? 'selected' : '' ?>>CI1</option>
                                <option value="CI2" <?= ($_GET['niveau_principal'] ?? '') == 'CI2' ? 'selected' : '' ?>>CI2</option>
                                <option value="CI3" <?= ($_GET['niveau_principal'] ?? '') == 'CI3' ? 'selected' : '' ?>>CI3</option>
                            </select>
                            <select name="niveau_secondaire" id="niveau_secondaire" class="form-select" style="width: 150px;">
                                <option value="">Toutes</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-filter"></i>
                            </button>
                            <a href="edit.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </form>
                        <button class="btn btn-sm btn-outline-primary" onclick="toggleView()">
                            <i class="fas fa-th me-1"></i>Vue grille
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr class="table-primary">
                                    <th style="width: 100px;" class="text-center">Horaires</th>
                                    <?php foreach($jours as $jour): ?>
                                        <th class="text-center"><?= $jour ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($heures as $heure): ?>
                                <tr>
                                    <td class="text-center fw-bold bg-light"><?= $heure ?></td>
                                    <?php foreach($jours as $jour): ?>
                                        <td class="p-1" style="height: 80px; min-width: 150px;">
                                            <div class="drop-zone h-100 d-flex align-items-center justify-content-center" 
                                                 ondrop="drop(event)" 
                                                 ondragover="allowDrop(event)"
                                                 data-jour="<?= $jour ?>"
                                                 data-heure="<?= $heure ?>">
                                                <?php 
                                                $cours_existant = null;
                                                if (isset($grille_existante[$jour][$heure])) {
                                                    $cours_existant = $grille_existante[$jour][$heure];
                                                }
                                                
                                                if ($cours_existant): ?>
                                                    <div class="draggable-subject bg-primary text-white p-2 rounded w-100 text-center" 
                                                         draggable="true" 
                                                         ondragstart="drag(event)"
                                                         data-id="<?= $cours_existant['id'] ?>">
                                                        <div class="fw-bold small"><?= htmlspecialchars($cours_existant['module_nom']) ?></div>
                                                        <div class="small"><?= htmlspecialchars($cours_existant['niveau_nom']) ?></div>
                                                        <div class="small"><?= $cours_existant['type_seance'] ?></div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">Glissez un cours ici</span>
                                                <?php endif; ?>
                                            </div>
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

        <!-- Panneau des matières disponibles -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-book me-2"></i>Matières disponibles</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $couleurs = ['bg-primary', 'bg-danger', 'bg-success', 'bg-warning', 'bg-info', 'bg-secondary'];
                    $couleur_index = 0;
                    foreach($modules as $module): ?>
                        <div class="draggable-subject <?= $couleurs[$couleur_index % count($couleurs)] ?> text-white mb-2 p-2 rounded text-center" 
                             draggable="true" 
                             ondragstart="dragNew(event)"
                             data-module-id="<?= $module['id'] ?>"
                             data-module-name="<?= htmlspecialchars($module['nom']) ?>">
                            <div class="fw-bold"><?= htmlspecialchars($module['nom']) ?></div>
                            <small>
                                <?php if($module['heureCours'] > 0): ?>Cours: <?= $module['heureCours'] ?>h <?php endif; ?>
                                <?php if($module['heureTD'] > 0): ?>TD: <?= $module['heureTD'] ?>h <?php endif; ?>
                                <?php if($module['heureTP'] > 0): ?>TP: <?= $module['heureTP'] ?>h <?php endif; ?>
                            </small>
                        </div>
                    <?php 
                    $couleur_index++;
                    endforeach; ?>
                </div>
            </div>

            <!-- Outils d'édition -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Outils d'édition</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="viewPlanning()">
                            <i class="fas fa-eye me-2"></i>Vider le planning
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="autoFill()">
                            <i class="fas fa-fill me-2"></i>Remplissage automatique
                        </button>
                        <button class="btn btn-outline-warning btn-sm" onclick="checkConflicts()">
                            <i class="fas fa-exclamation-triangle me-2"></i>Vérifier les conflits
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="optimize()">
                            <i class="fas fa-cog me-2"></i>Optimiser
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.drop-zone {
    border: 2px dashed transparent;
    transition: all 0.3s ease;
}

.drop-zone.drag-over {
    border-color: #007bff;
    background-color: rgba(0, 123, 255, 0.1);
}

.draggable-subject {
    cursor: move;
    transition: all 0.3s ease;
}

.draggable-subject:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.table td {
    vertical-align: middle;
}
</style>

<script>
// Variables globales
let draggedElement = null;
let isNewModule = false;

function allowDrop(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.add('drag-over');
}

function drag(ev) {
    draggedElement = ev.target;
    isNewModule = false;
    ev.dataTransfer.setData("text", ev.target.dataset.id);
}

function dragNew(ev) {
    draggedElement = ev.target;
    isNewModule = true;
    ev.dataTransfer.setData("text", ev.target.dataset.moduleId);
}

function drop(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.remove('drag-over');
    
    const dropZone = ev.currentTarget;
    const jour = dropZone.dataset.jour;
    const heure = dropZone.dataset.heure;
    
    if (isNewModule) {
        // Créer un nouvel élément de cours
        const moduleId = draggedElement.dataset.moduleId;
        const moduleName = draggedElement.dataset.moduleName;
        
        // Demander des informations supplémentaires
        const niveau = prompt("Niveau (ID):");
        const enseignant = prompt("Enseignant (ID):");
        const salle = prompt("Salle (ID, optionnel):");
        const type = prompt("Type de séance (cours/TD/TP):");
        
        if (niveau && enseignant && type) {
            // Générer un ID temporaire unique pour les nouveaux éléments
            const tempId = 'new_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            // Créer l'élément visuel
            dropZone.innerHTML = `
                <div class="draggable-subject bg-primary text-white p-2 rounded w-100 text-center" 
                     draggable="true" 
                     ondragstart="drag(event)"
                     data-id="${tempId}"
                     data-new="true"
                     data-module-id="${moduleId}"
                     data-niveau="${niveau}"
                     data-enseignant="${enseignant}"
                     data-salle="${salle || ''}"
                     data-type="${type}"
                     data-jour="${jour}"
                     data-heure="${heure}">
                    <div class="fw-bold small">${moduleName}</div>
                    <div class="small">Niveau ${niveau}</div>
                    <div class="small">${type}</div>
                </div>
            `;
        }
    } else {
        // Déplacer un cours existant
        if (draggedElement.parentNode !== dropZone) {
            // Vider la zone de dépôt actuelle
            const oldParent = draggedElement.parentNode;
            if (oldParent && oldParent.classList.contains('drop-zone')) {
                oldParent.innerHTML = '<span class="text-muted small">Glissez un cours ici</span>';
            }
            
            // Vider la nouvelle zone de dépôt
            dropZone.innerHTML = '';
            
            // Ajouter l'élément déplacé
            dropZone.appendChild(draggedElement);
            
            // Mettre à jour les données de position
            draggedElement.dataset.jour = jour;
            draggedElement.dataset.heure = heure;
        }
    }
}

function saveChanges() {
    const changes = [];
    
    // Collecter seulement les cours qui sont dans le planning (dans les drop-zones)
    document.querySelectorAll('.drop-zone .draggable-subject').forEach(element => {
        // Récupérer la zone de dépôt parente pour obtenir jour et heure
        const dropZone = element.closest('.drop-zone');
        if (!dropZone) return;
        
        const jour = dropZone.dataset.jour;
        const heure = dropZone.dataset.heure;
        
        // Ignorer les éléments qui n'ont pas de jour/heure (ne sont pas dans le planning)
        if (!jour || !heure) return;
        
        const data = {
            id: element.dataset.id,
            isNew: element.dataset.new === 'true',
            moduleId: element.dataset.moduleId,
            niveau: element.dataset.niveau,
            enseignant: element.dataset.enseignant,
            salle: element.dataset.salle || null,
            type: element.dataset.type,
            jour: jour,
            heure: heure
        };
        
        // Pour les nouveaux cours, vérifier que toutes les données requises sont présentes
        if (data.isNew && (!data.moduleId || !data.niveau || !data.enseignant || !data.type)) {
            console.warn('Nouveau cours avec données manquantes ignoré:', data);
            return;
        }
        
        // Pour les cours existants, vérifier qu'ils ont un ID
        if (!data.isNew && !data.id) {
            console.warn('Cours existant sans ID ignoré:', data);
            return;
        }
        
        changes.push(data);
    });
    
    console.log('Changes to save:', changes); // Pour déboguer
    
    if (changes.length === 0) {
        alert('Aucun changement à sauvegarder');
        return;
    }
    
    // Envoyer les changements au serveur
    fetch('save_changes.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(changes)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data); // Pour déboguer
        if (data.success) {
            alert('Changements sauvegardés avec succès!');
            window.location.reload();
        } else {
            alert('Erreur lors de la sauvegarde: ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la sauvegarde: ' + error.message);
    });
}

function autoGenerate() {
    if (confirm('Voulez-vous générer automatiquement l\'emploi du temps? Cela remplacera le planning actuel.')) {
        window.location.href = 'auto_generate.php';
    }
}

function cancelChanges() {
    if (confirm('Voulez-vous annuler tous les changements?')) {
        window.location.reload();
    }
}

function filterByNiveau() {
    const niveau_principal = document.getElementById('niveau_principal').value;
    const niveau_secondaire = document.getElementById('niveau_secondaire').value;
    
    let url = 'edit.php';
    let params = [];
    
    if (niveau_principal) {
        params.push('niveau_principal=' + encodeURIComponent(niveau_principal));
    }
    if (niveau_secondaire) {
        params.push('niveau_secondaire=' + encodeURIComponent(niveau_secondaire));
    }
    
    if (params.length > 0) {
        url += '?' + params.join('&');
    }
    
    window.location.href = url;
}

function updateSecondaryOptions() {
    const primary = document.getElementById('niveau_principal').value;
    const secondary = document.getElementById('niveau_secondaire');
    const currentValue = '<?= $_GET['niveau_secondaire'] ?? '' ?>';
    
    // Vider les options
    secondary.innerHTML = '<option value="">Toutes</option>';
    
    if (['CI1', 'CI2', 'CI3'].includes(primary)) {
        // Options pour les niveaux CI
        const options = [
            {value: 'GI', text: 'GI'},
            {value: 'GIND', text: 'GIND'},
            {value: 'GE', text: 'GE'},
            {value: 'GCivil', text: 'GCivil'},
            {value: 'GM', text: 'GM'},
            {value: 'RST', text: 'RST'}
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

function viewPlanning() {
    window.location.href = 'view.php';
}

function autoFill() {
    alert('Fonctionnalité de remplissage automatique à implémenter');
}

function checkConflicts() {
    // Vérifier les conflits dans le planning actuel
    const conflicts = [];
    const used = {};
    
    document.querySelectorAll('.draggable-subject').forEach(element => {
        const dropZone = element.closest('.drop-zone');
        if (!dropZone) return;
        
        const key = `${dropZone.dataset.jour}-${dropZone.dataset.heure}`;
        if (used[key]) {
            conflicts.push(`Conflit détecté: ${dropZone.dataset.jour} à ${dropZone.dataset.heure}`);
        }
        used[key] = true;
    });
    
    if (conflicts.length > 0) {
        alert('Conflits détectés:\n' + conflicts.join('\n'));
    } else {
        alert('Aucun conflit détecté dans le planning actuel.');
    }
}

function optimize() {
    alert('Fonctionnalité d\'optimisation à implémenter');
}

function toggleView() {
    alert('Fonctionnalité de changement de vue à implémenter');
}

// Gestionnaire d'événements pour éviter les conflits de glisser-déposer
document.addEventListener('dragend', function(e) {
    document.querySelectorAll('.drop-zone').forEach(zone => {
        zone.classList.remove('drag-over');
    });
});

// Empêcher le comportement par défaut pour les zones de dépôt
document.addEventListener('dragover', function(e) {
    if (e.target.classList.contains('drop-zone')) {
        e.preventDefault();
    }
});

// Initialiser les options secondaires au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    updateSecondaryOptions();
});
</script>

<?php require_once '../../includes/footer.php'; ?>