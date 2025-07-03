<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Récupérer l'emploi du temps actuel
    $emplois = $pdo->query("
        SELECT e.*, cr.jour, cr.heure_deb, 
               CASE cr.jour 
                   WHEN 'Lundi' THEN 1 
                   WHEN 'Mardi' THEN 2 
                   WHEN 'Mercredi' THEN 3 
                   WHEN 'Jeudi' THEN 4 
                   WHEN 'Vendredi' THEN 5 
                   WHEN 'Samedi' THEN 6 
               END as jour_num
        FROM Emploi_du_temps e
        JOIN Creneaux cr ON e.creneau_id = cr.id
        ORDER BY e.niveau_id, jour_num, cr.heure_deb
    ")->fetchAll();
    
    // Algorithmes d'optimisation
    
    // 1. Regroupement des cours par niveau par jour
    $optimisations = 0;
    $cours_par_niveau = [];
    
    foreach ($emplois as $emploi) {
        $cours_par_niveau[$emploi['niveau_id']][] = $emploi;
    }
    
    // 2. Réorganisation pour minimiser les trous dans l'emploi du temps
    foreach ($cours_par_niveau as $niveau_id => $cours_niveau) {
        $cours_par_jour = [];
        
        // Grouper par jour
        foreach ($cours_niveau as $cours) {
            $cours_par_jour[$cours['jour']][] = $cours;
        }
        
        // Pour chaque jour, essayer de regrouper les cours
        foreach ($cours_par_jour as $jour => $cours_jour) {
            if (count($cours_jour) <= 1) continue;
            
            // Trier par heure
            usort($cours_jour, function($a, $b) {
                return strcmp($a['heure_deb'], $b['heure_deb']);
            });
            
            // Vérifier s'il y a des trous et essayer de les combler
            for ($i = 0; $i < count($cours_jour) - 1; $i++) {
                $cours_actuel = $cours_jour[$i];
                $cours_suivant = $cours_jour[$i + 1];
                
                // Calculer l'écart en heures
                $heure_fin_actuel = date('H:i', strtotime($cours_actuel['heure_deb'] . ' +2 hours'));
                $ecart = (strtotime($cours_suivant['heure_deb']) - strtotime($heure_fin_actuel)) / 3600;
                
                // S'il y a un trou de plus d'une heure, essayer de décaler
                if ($ecart > 1) {
                    // Chercher un créneau plus proche
                    $nouveau_creneau = $pdo->prepare("
                        SELECT c.* FROM Creneaux c
                        WHERE c.jour = ? 
                        AND c.heure_deb > ? 
                        AND c.heure_deb < ?
                        AND NOT EXISTS (
                            SELECT 1 FROM Emploi_du_temps e2 
                            WHERE e2.creneau_id = c.id 
                            AND (e2.niveau_id = ? OR e2.enseignant_id = ? OR e2.salle_id = ?)
                        )
                        ORDER BY c.heure_deb
                        LIMIT 1
                    ");
                    
                    $nouveau_creneau->execute([
                        $jour,
                        $heure_fin_actuel,
                        $cours_suivant['heure_deb'],
                        $cours_suivant['niveau_id'],
                        $cours_suivant['enseignant_id'],
                        $cours_suivant['salle_id']
                    ]);
                    
                    $creneau_disponible = $nouveau_creneau->fetch();
                    
                    if ($creneau_disponible) {
                        // Déplacer le cours
                        $update_stmt = $pdo->prepare("UPDATE Emploi_du_temps SET creneau_id = ? WHERE id = ?");
                        $update_stmt->execute([$creneau_disponible['id'], $cours_suivant['id']]);
                        $optimisations++;
                    }
                }
            }
        }
    }
    
    // 3. Optimisation de l'utilisation des salles
    // Essayer de regrouper les cours dans les mêmes salles pour minimiser les déplacements
    $cours_sans_salle = $pdo->query("
        SELECT e.*, cr.jour, cr.heure_deb 
        FROM Emploi_du_temps e
        JOIN Creneaux cr ON e.creneau_id = cr.id
        WHERE e.salle_id IS NULL
    ")->fetchAll();
    
    foreach ($cours_sans_salle as $cours) {
        // Chercher une salle libre
        $salle_libre = $pdo->prepare("
            SELECT s.* FROM Classe s
            WHERE s.type_classe = CASE 
                WHEN ? = 'cours' THEN 'amphi'
                ELSE 'salle'
            END
            AND NOT EXISTS (
                SELECT 1 FROM Emploi_du_temps e2 
                WHERE e2.salle_id = s.id 
                AND e2.creneau_id = ?
            )
            ORDER BY s.nombrePlaces
            LIMIT 1
        ");
        
        $salle_libre->execute([$cours['type_seance'], $cours['creneau_id']]);
        $salle = $salle_libre->fetch();
        
        if ($salle) {
            $assign_salle = $pdo->prepare("UPDATE Emploi_du_temps SET salle_id = ? WHERE id = ?");
            $assign_salle->execute([$salle['id'], $cours['id']]);
            $optimisations++;
        }
    }
    
    // 4. Équilibrage de la charge de travail des enseignants
    $charge_enseignants = $pdo->query("
        SELECT enseignant_id, COUNT(*) as nb_cours,
               COUNT(DISTINCT creneau_id) as nb_creneaux
        FROM Emploi_du_temps
        GROUP BY enseignant_id
        HAVING nb_cours > 10  -- Seuil arbitraire
        ORDER BY nb_cours DESC
    ")->fetchAll();
    
    // Pour les enseignants surchargés, essayer de répartir certains cours
    foreach ($charge_enseignants as $enseignant) {
        if ($enseignant['nb_cours'] > 12) { // Seuil de surcharge
            // Trouver des enseignants moins chargés
            $autres_enseignants = $pdo->prepare("
                SELECT u.id, COUNT(e.id) as nb_cours
                FROM User u
                JOIN Enseignant ens ON u.id = ens.user_id
                LEFT JOIN Emploi_du_temps e ON u.id = e.enseignant_id
                WHERE u.id != ?
                GROUP BY u.id
                HAVING nb_cours < 8
                ORDER BY nb_cours
                LIMIT 3
            ");
            
            $autres_enseignants->execute([$enseignant['enseignant_id']]);
            $enseignants_disponibles = $autres_enseignants->fetchAll();
            
            if (!empty($enseignants_disponibles)) {
                // Transférer quelques cours (maximum 2 pour éviter trop de changements)
                $cours_a_transferer = $pdo->prepare("
                    SELECT * FROM Emploi_du_temps 
                    WHERE enseignant_id = ? 
                    ORDER BY RANDOM() 
                    LIMIT 2
                ");
                
                $cours_a_transferer->execute([$enseignant['enseignant_id']]);
                $cours = $cours_a_transferer->fetchAll();
                
                foreach ($cours as $i => $cours_item) {
                    if (isset($enseignants_disponibles[$i])) {
                        $nouvel_enseignant = $enseignants_disponibles[$i];
                        
                        // Vérifier qu'il n'y a pas de conflit
                        $conflit = $pdo->prepare("
                            SELECT COUNT(*) as nb FROM Emploi_du_temps 
                            WHERE enseignant_id = ? AND creneau_id = ?
                        ");
                        $conflit->execute([$nouvel_enseignant['id'], $cours_item['creneau_id']]);
                        
                        if ($conflit->fetch()['nb'] == 0) {
                            $transfert = $pdo->prepare("UPDATE Emploi_du_temps SET enseignant_id = ? WHERE id = ?");
                            $transfert->execute([$nouvel_enseignant['id'], $cours_item['id']]);
                            $optimisations++;
                        }
                    }
                }
            }
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "Optimisation terminée. $optimisations améliorations apportées."
    ]);
    
} catch (Exception $e) {
    $pdo->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>