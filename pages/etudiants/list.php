<?php
session_start();
$level = 2;
require_once '../../config/db.php';
require_once '../../includes/header.php';

$stmt = $pdo->query("SELECT u.*, n.nom as niveau_nom FROM User u JOIN Etudiant e ON u.id = e.user_id LEFT JOIN Niveau n ON e.niveau_id = n.id ORDER BY u.nom");
$etudiants = $stmt->fetchAll();
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Liste des Étudiants</h2>
        <a href="add.php" class="btn btn-success">Ajouter Étudiant</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Niveau</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($etudiants as $etudiant): ?>
                <tr>
                    <td><?= htmlspecialchars($etudiant['nom']) ?></td>
                    <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
                    <td><?= htmlspecialchars($etudiant['email']) ?></td>
                    <td><?= htmlspecialchars($etudiant['niveau_nom'] ?? 'Non assigné') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
