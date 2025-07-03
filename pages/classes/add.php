<?php
session_start();
$level = 2;
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("INSERT INTO Classe (numeroSalle, batiment, nombrePlaces, type_classe) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['numeroSalle'], $_POST['batiment'], $_POST['nombrePlaces'], $_POST['type_classe']]);
    header('Location: list.php');
    exit;
}
require_once '../../includes/header.php';
?>
<div class="container mt-4">
    <h2>Ajouter une Classe</h2>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Numéro de salle</label>
            <input type="text" name="numeroSalle" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Bâtiment</label>
            <input type="text" name="batiment" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nombre de places</label>
            <input type="number" name="nombrePlaces" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type_classe" class="form-control" required>
                <option value="salle">Salle</option>
                <option value="amphi">Amphi</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="list.php" class="btn btn-secondary">Retour</a>
    </form>
</div>
<?php require_once '../../includes/footer.php'; ?>
