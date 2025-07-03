<?php
session_start();
$level = 2;
require_once '../../config/db.php';

if ($_SESSION['user_type'] != 'admin') {
    header('Location: ../../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE User SET email = ?, password_hashed = ? WHERE id = ?");
        $stmt->execute([$_POST['email'], $password, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE User SET email = ? WHERE id = ?");
        $stmt->execute([$_POST['email'], $_SESSION['user_id']]);
    }
    $success = "Profil mis à jour";
}

$stmt = $pdo->prepare("SELECT * FROM User WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

require_once '../../includes/header.php';
?>
<div class="container mt-4">
    <h2>Mon Profil</h2>
    <?php if(isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
            <input type="password" name="password" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
        <a href="../../dashboard.php" class="btn btn-secondary">Retour</a>
    </form>
</div>
<?php require_once '../../includes/footer.php'; ?>