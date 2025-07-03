<?php
session_start();
require_once '../../config/db.php';

try {
    $pdo->beginTransaction();
    
    // Vider l'emploi du temps existant
    $pdo->exec("DELETE FROM Emploi_du_temps");
    
    // Récupérer les données nécessaires
    $niveaux = $pdo->query("SELECT * FROM Niveau")->fetchAll();
    $modules = $pdo->query("SELECT * FROM Module")->fetchAll();
    $enseignants = $pdo->query("SELECT u.* FROM User u JOIN Enseignant e ON u.id = e.user_id")->fetchAll();
    $salles = $pdo->query("SELECT * FROM Classe")->fetchAll();
    $creneaux = $pdo->query("SELECT * FROM Creneaux ORDER BY 
        CASE jour 
            WHEN 'Lundi' THEN 1 
            WHEN 'Mardi' THEN 2 
            WHEN 'Mercredi' THEN 3 
            WHEN 'Jeudi' THEN 4 
            WHEN 'Vendredi' THEN 5 
            WHEN 'Samedi' THEN 6 
        END, heure_deb")->fetchAll();
    
    // Variables pour suivre les assignations
    $creneaux_occupes = []; // [niveau_id][creneau_id] = true
    $enseignants_occupes = []; // [enseignant_id][creneau_id] = true
    $salles_occupees = []; // [salle_id][creneau_id] = true
    
    $assignations_reussies = 0;
    $assignations_echouees = 0;
    
    // Algorithme de génération automatique
    foreach ($niveaux as $niveau) {
        foreach ($modules as $module) {
            // Déterminer le nombre de séances nécessaires
            $seances_cours = $module['heureCours'] > 0 ? ceil($module['heureCours'] / 2) : 0;
            $seances_td = $module['heureTD'] > 0 ? ceil($module['heureTD'] / 2) : 0;
            $seances_tp = $module['heureTP'] > 0 ? ceil($module['heureTP'] / 2) : 0;
            
            $types_seances = [];
            for ($i = 0; $i < $seances_cours; $i++) $types_seances[] = 'cours';
            for ($i = 0; $i < $seances_td; $i++) $types_seances[] = 'TD';
            for ($i = 0; $i < $seances_tp; $i++) $types_seances[] = 'TP';
            
            // Assigner chaque séance
            foreach ($types_seances as $type_seance) {
                $assigne = false;
                
                // Mélanger les créneaux pour une répartition aléatoire
                $creneaux_melange = $creneaux;
                shuffle($creneaux_melange);
                
                foreach ($creneaux_melange as $creneau) {
                    // Vérifier si le niveau est disponible
                    if (isset($creneaux_occupes[$niveau['id']][$creneau['id']])) {
                        continue;
                    }
                    
                    // Trouver un enseignant disponible
                    $enseignant_disponible = null;
                    $enseignants_melange = $enseignants;
                    shuffle($enseignants_melange);
                    
                    foreach ($enseignants_melange as $enseignant) {
                        if (!isset($enseignants_occupes[$enseignant['id']][$creneau['id']])) {
                            $enseignant_disponible = $enseignant;
                            break;
                        }
                    }
                    
                    if (!$enseignant_disponible) {
                        continue;
                    }
                    
                    // Trouver une salle disponible (optionnel)
                    $salle_disponible = null;
                    $salles_melange = $salles;
                    shuffle($salles_melange);
                    
                    foreach ($salles_melange as $salle) {
                        // Vérifier le type de salle (amphi pour cours, salle normale pour TD/TP)
                        if ($type_seance === 'cours' && $salle['type_classe'] !== 'amphi') {
                            continue;
                        }
                        
                        if (!isset($salles_occupees[$salle['id']][$creneau['id']])) {
                            $salle_disponible = $salle;
                            break;
                        }
                    }
                    
                    // Créer l'assignation
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO Emploi_du_temps (niveau_id, module_id, enseignant_id, salle_id, creneau_id, type_seance) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $niveau['id'],
                            $module['id'],
                            $enseignant_disponible['id'],
                            $salle_disponible ? $salle_disponible['id'] : null,
                            $creneau['id'],
                            $type_seance
                        ]);
                        
                        // Marquer les ressources comme occupées
                        $creneaux_occupes[$niveau['id']][$creneau['id']] = true;
                        $enseignants_occupes[$enseignant_disponible['id']][$creneau['id']] = true;
                        if ($salle_disponible) {
                            $salles_occupees[$salle_disponible['id']][$creneau['id']] = true;
                        }
                        
                        $assignations_reussies++;
                        $assigne = true;
                        break;
                        
                    } catch (PDOException $e) {
                        // Conflit détecté, continuer avec le créneau suivant
                        continue;
                    }
                }
                
                if (!$assigne) {
                    $assignations_echouees++;
                }
            }
        }
    }
    
    $pdo->commit();
    
    $_SESSION['generation_message'] = "Génération automatique terminée. $assignations_reussies cours assignés, $assignations_echouees échecs.";
    header('Location: view.php');
    
} catch (Exception $e) {
    $pdo->rollback();
    $_SESSION['generation_error'] = "Erreur lors de la génération: " . $e->getMessage();
    header('Location: edit.php');
}
?>