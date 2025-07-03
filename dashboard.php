<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'config/db.php';

// Récupérer les statistiques
$stats = [];
$stats['classes'] = $pdo->query("SELECT COUNT(*) FROM Classe")->fetchColumn();
$stats['modules'] = $pdo->query("SELECT COUNT(*) FROM Module")->fetchColumn();
$stats['enseignants'] = $pdo->query("SELECT COUNT(*) FROM User WHERE type_user = 'enseignant'")->fetchColumn();
$stats['etudiants'] = $pdo->query("SELECT COUNT(*) FROM User WHERE type_user = 'etudiant'")->fetchColumn();

require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold text-dark mb-3">Bienvenue sur SchoolTime</h1>
        <p class="lead text-muted">Gérez facilement les emplois du temps de votre établissement</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-5">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card stats-card">
                <div class="stats-number"><?= $stats['classes'] ?></div>
                <div class="stats-label">Classes</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card stats-card">
                <div class="stats-number"><?= $stats['modules'] ?></div>
                <div class="stats-label">Matières</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card stats-card">
                <div class="stats-number"><?= $stats['enseignants'] ?></div>
                <div class="stats-label">Professeurs</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card stats-card">
                <div class="stats-number"><?= $stats['etudiants'] ?></div>
                <div class="stats-label">Élèves</div>
            </div>
        </div>
    </div>

    <!-- Feature Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card feature-card h-100">
                <div class="feature-icon">
                    <i class="fas fa-door-open"></i>
                </div>
                <h5 class="feature-title">Gérer les Classes</h5>
                <p class="feature-description">Ajouter, modifier ou supprimer des classes</p>
                <div class="mt-auto">
                    <a href="pages/classes/list.php" class="btn btn-primary btn-sm">Gérer</a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card feature-card h-100">
                <div class="feature-icon">
                    <i class="fas fa-book"></i>
                </div>
                <h5 class="feature-title">Gérer les Matières</h5>
                <p class="feature-description">Organiser les matières et professeurs</p>
                <div class="mt-auto">
                    <a href="pages/modules/list.php" class="btn btn-primary btn-sm">Gérer</a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card feature-card h-100">
                <div class="feature-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h5 class="feature-title">Emplois du Temps</h5>
                <p class="feature-description">Consulter et gérer les plannings</p>
                <div class="mt-auto">
                    <a href="pages/emploi/view.php" class="btn btn-primary btn-sm">Voir</a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card feature-card h-100">
                <div class="feature-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <h5 class="feature-title">Éditer Planning</h5>
                <p class="feature-description">Modifier dynamiquement les horaires</p>
                <div class="mt-auto">
                    <a href="pages/emploi/edit.php" class="btn btn-primary btn-sm">Éditer</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>