<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Fonction pour logger les erreurs
function logError($message) {
    error_log(date('Y-m-d H:i:s') . " - Erreur save_changes: " . $message . "\n", 3, 'error.log');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et valider les données d'entrée
$input_raw = file_get_contents('php://input');
if (!$input_raw) {
    echo json_encode(['success' => false, 'message' => 'Aucune donnée reçue']);
    exit;
}

$input = json_decode($input_raw, true);
if (!$input) {
    $error_msg = 'Erreur de décodage JSON: ' . json_last_error_msg();
    logError($error_msg);
    echo json_encode(['success' => false, 'message' => 'Données JSON invalides: ' . json_last_error_msg()]);
    exit;
}

// Logger les données reçues pour déboguer
logError('Données reçues: ' . print_r($input, true));

try {
    $pdo->beginTransaction();
    
    $processed_count = 0;
    foreach ($input as $change) {
        // Validation des données requises
        if (!isset($change['jour']) || !isset($change['heure'])) {
            throw new Exception("Jour et heure sont requis pour chaque changement");
        }
        
        if ($change['isNew']) {
            // Nouveau cours à ajouter
            
            // Validation des données requises pour un nouveau cours
            if (!isset($change['moduleId']) || !isset($change['niveau']) || !isset($change['enseignant']) || !isset($change['type'])) {
                throw new Exception("Données manquantes pour le nouveau cours: moduleId, niveau, enseignant et type sont requis");
            }
            
            // Trouver l'ID du créneau correspondant
            $creneau_stmt = $pdo->prepare("SELECT id FROM Creneaux WHERE jour = ? AND heure_deb = ?");
            $creneau_stmt->execute([$change['jour'], $change['heure']]);
            $creneau = $creneau_stmt->fetch();
            
            if (!$creneau) {
                throw new Exception("Créneau non trouvé pour {$change['jour']} à {$change['heure']}");
            }
            
            // Vérifier si les IDs existent
            $niveau_check = $pdo->prepare("SELECT id FROM Niveau WHERE id = ?");
            $niveau_check->execute([$change['niveau']]);
            if (!$niveau_check->fetch()) {
                throw new Exception("Niveau avec ID {$change['niveau']} non trouvé");
            }
            
            $module_check = $pdo->prepare("SELECT id FROM Module WHERE id = ?");
            $module_check->execute([$change['moduleId']]);
            if (!$module_check->fetch()) {
                throw new Exception("Module avec ID {$change['moduleId']} non trouvé");
            }
            
            $enseignant_check = $pdo->prepare("SELECT u.id FROM User u JOIN Enseignant e ON u.id = e.user_id WHERE u.id = ?");
            $enseignant_check->execute([$change['enseignant']]);
            if (!$enseignant_check->fetch()) {
                throw new Exception("Enseignant avec ID {$change['enseignant']} non trouvé");
            }
            
            // Vérifier les conflits avant l'insertion
            $conflict_check = $pdo->prepare("SELECT COUNT(*) as count FROM Emploi_du_temps WHERE creneau_id = ? AND (niveau_id = ? OR enseignant_id = ? OR (salle_id = ? AND salle_id IS NOT NULL))");
            $salle_id = !empty($change['salle']) ? $change['salle'] : null;
            $conflict_check->execute([$creneau['id'], $change['niveau'], $change['enseignant'], $salle_id]);
            $conflict = $conflict_check->fetch();
            
            if ($conflict['count'] > 0) {
                throw new Exception("Conflit détecté pour le créneau {$change['jour']} à {$change['heure']}");
            }
            
            // Insérer le nouveau cours
            $stmt = $pdo->prepare("
                INSERT INTO Emploi_du_temps (niveau_id, module_id, enseignant_id, salle_id, creneau_id, type_seance) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $change['niveau'],
                $change['moduleId'],
                $change['enseignant'],
                $salle_id,
                $creneau['id'],
                $change['type']
            ]);
            
            logError("Nouveau cours ajouté: Module {$change['moduleId']}, Niveau {$change['niveau']}, {$change['jour']} à {$change['heure']}");
            
        } else {
            // Cours existant à déplacer
            
            if (!isset($change['id'])) {
                throw new Exception("ID requis pour déplacer un cours existant");
            }
            
            // Vérifier que le cours existe
            $course_check = $pdo->prepare("SELECT id FROM Emploi_du_temps WHERE id = ?");
            $course_check->execute([$change['id']]);
            if (!$course_check->fetch()) {
                throw new Exception("Cours avec ID {$change['id']} non trouvé");
            }
            
            // Trouver le nouveau créneau
            $creneau_stmt = $pdo->prepare("SELECT id FROM Creneaux WHERE jour = ? AND heure_deb = ?");
            $creneau_stmt->execute([$change['jour'], $change['heure']]);
            $creneau = $creneau_stmt->fetch();
            
            if (!$creneau) {
                throw new Exception("Créneau non trouvé pour {$change['jour']} à {$change['heure']}");
            }
            
            // Vérifier les conflits (exclure le cours actuel)
            $conflict_check = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM Emploi_du_temps e1
                WHERE e1.creneau_id = ? 
                AND e1.id != ?
                AND EXISTS (
                    SELECT 1 FROM Emploi_du_temps e2 
                    WHERE e2.id = ? 
                    AND (e1.niveau_id = e2.niveau_id OR e1.enseignant_id = e2.enseignant_id OR (e1.salle_id = e2.salle_id AND e1.salle_id IS NOT NULL))
                )
            ");
            $conflict_check->execute([$creneau['id'], $change['id'], $change['id']]);
            $conflict = $conflict_check->fetch();
            
            if ($conflict['count'] > 0) {
                throw new Exception("Conflit détecté lors du déplacement vers {$change['jour']} à {$change['heure']}");
            }
            
            // Mettre à jour le cours existant
            $stmt = $pdo->prepare("UPDATE Emploi_du_temps SET creneau_id = ? WHERE id = ?");
            $stmt->execute([$creneau['id'], $change['id']]);
            
            logError("Cours déplacé: ID {$change['id']} vers {$change['jour']} à {$change['heure']}");
        }
        
        $processed_count++;
    }
    
    $pdo->commit();
    echo json_encode([
        'success' => true, 
        'message' => "Changements sauvegardés avec succès ($processed_count modifications)"
    ]);
    
} catch (Exception $e) {
    $pdo->rollback();
    $error_message = $e->getMessage();
    logError("Erreur lors de la sauvegarde: " . $error_message);
    echo json_encode(['success' => false, 'message' => $error_message]);
}
?>