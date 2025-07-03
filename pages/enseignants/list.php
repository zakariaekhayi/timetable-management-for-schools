<?php
session_start();
$level = 2;
require_once '../../config/db.php';
require_once '../../includes/header.php';

$stmt = $pdo->query("SELECT u.*, e.user_id FROM User u JOIN Enseignant e ON u.id = e.user_id ORDER BY u.nom");
$enseignants = $stmt->fetchAll();
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Liste des Enseignants</h2>
        <a href="add.php" class="btn btn-success">Ajouter Enseignant</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($enseignants as $prof): ?>
                <tr>
                    <td><?= htmlspecialchars($prof['nom']) ?></td>
                    <td><?= htmlspecialchars($prof['prenom']) ?></td>
                    <td><?= htmlspecialchars($prof['email']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>