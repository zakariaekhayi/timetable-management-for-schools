<?php
session_start();
$level = 2;
require_once '../../config/db.php';

if ($_SESSION['user_type'] != 'admin') {
    header('Location: ../../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO User (nom, prenom, email, password_hashed, type_user) VALUES (?, ?, ?, ?, 'admin')");
    $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['email'], $password]);
    $success = "Admin ajouté avec succès";
}
require_once '../../includes/header.php';
?>
<div class="container mt-4">
    <h2>Ajouter un Administrateur</h2>
    <?php if(isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
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
        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="../../dashboard.php" class="btn btn-secondary">Retour</a>
    </form>
</div>
<?php require_once '../../includes/footer.php'; ?>