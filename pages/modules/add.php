<?php
session_start();
$level = 2;
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("INSERT INTO Module (nom, heureCours, heureTD, heureTP) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['nom'], $_POST['heureCours'], $_POST['heureTD'], $_POST['heureTP']]);
    header('Location: list.php');
    exit;
}
require_once '../../includes/header.php';
?>
<div class="container mt-4">
    <h2>Ajouter un Module</h2>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nom du module</label>
            <input type="text" name="nom" class="form-control" required>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Heures Cours</label>
                <input type="number" name="heureCours" class="form-control" value="0">
            </div>
            <div class="col-md-4">
                <label class="form-label">Heures TD</label>
                <input type="number" name="heureTD" class="form-control" value="0">
            </div>
            <div class="col-md-4">
                <label class="form-label">Heures TP</label>
                <input type="number" name="heureTP" class="form-control" value="0">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Ajouter</button>
            <a href="list.php" class="btn btn-secondary">Retour</a>
        </div>
    </form>
</div>
<?php require_once '../../includes/footer.php'; ?>