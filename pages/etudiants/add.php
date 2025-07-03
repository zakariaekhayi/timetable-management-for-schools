<?php
session_start();
$level = 2;
require_once '../../config/db.php';

$niveaux = $pdo->query("SELECT * FROM Niveau")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO User (nom, prenom, email, password_hashed, type_user) VALUES (?, ?, ?, ?, 'etudiant')");
    $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $password]);
    $user_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO Etudiant (user_id, niveau_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $_POST['niveau_id']]);
    header('Location: list.php');
    exit;
}
require_once '../../includes/header.php';
?>
<div class="container mt-4">
    <h2>Ajouter un Étudiant</h2>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nom</label>
            <input type="text" name="nom" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Prénom</label>
            <input type="text" name="prenom" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Mot de passe</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Niveau</label>
            <select name="niveau_id" class="form-control">
                <option value="">Sélectionner un niveau</option>
                <?php foreach($niveaux as $niveau): ?>
                    <option value="<?= $niveau['id'] ?>"><?= htmlspecialchars($niveau['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="list.php" class="btn btn-secondary">Retour</a>
    </form>
</div>
<?php require_once '../../includes/footer.php'; ?>