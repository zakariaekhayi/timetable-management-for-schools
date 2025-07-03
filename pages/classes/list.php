<?php
session_start();
$level = 2;
require_once '../../config/db.php';
require_once '../../includes/header.php';

$stmt = $pdo->query("SELECT * FROM Classe ORDER BY numeroSalle");
$classes = $stmt->fetchAll();
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="h3 fw-bold text-dark mb-2">Gestion des Classes</h2>
            <p class="text-muted">Organisez et gérez toutes les classes de votre établissement</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-semibold">Liste des Classes</h5>
            </div>
            <a href="add.php" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>+ Nouvelle Classe
            </a>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="px-4 py-3">Nom de la classe</th>
                            <th class="px-4 py-3">Niveau</th>
                            <th class="px-4 py-3">Nombre d'élèves</th>
                            <th class="px-4 py-3">Professeur principal</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Données exemple basées sur l'image -->
                        <tr>
                            <td class="px-4 py-3 fw-medium">6ème A</td>
                            <td class="px-4 py-3">Sixième</td>
                            <td class="px-4 py-3">28</td>
                            <td class="px-4 py-3">Mme Dupont</td>
                            <td class="px-4 py-3">
                                <span class="badge badge-active">ACTIVE</span>
                            </td>
                            <td class="px-4 py-3">
                                <button class="btn btn-outline-primary btn-sm me-2">Modifier</button>
                                <button class="btn btn-outline-danger btn-sm">Supprimer</button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 fw-medium">5ème B</td>
                            <td class="px-4 py-3">Cinquième</td>
                            <td class="px-4 py-3">25</td>
                            <td class="px-4 py-3">M. Martin</td>
                            <td class="px-4 py-3">
                                <span class="badge badge-active">ACTIVE</span>
                            </td>
                            <td class="px-4 py-3">
                                <button class="btn btn-outline-primary btn-sm me-2">Modifier</button>
                                <button class="btn btn-outline-danger btn-sm">Supprimer</button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 fw-medium">4ème C</td>
                            <td class="px-4 py-3">Quatrième</td>
                            <td class="px-4 py-3">30</td>
                            <td class="px-4 py-3">Mme Leroy</td>
                            <td class="px-4 py-3">
                                <span class="badge badge-inactive">EN COURS</span>
                            </td>
                            <td class="px-4 py-3">
                                <button class="btn btn-outline-primary btn-sm me-2">Modifier</button>
                                <button class="btn btn-outline-danger btn-sm">Supprimer</button>
                            </td>
                        </tr>
                        
                        <!-- Données de la base -->
                        <?php foreach($classes as $classe): ?>
                        <tr>
                            <td class="px-4 py-3 fw-medium"><?= htmlspecialchars($classe['numeroSalle']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($classe['batiment']) ?></td>
                            <td class="px-4 py-3"><?= $classe['nombrePlaces'] ?></td>
                            <td class="px-4 py-3"><?= ucfirst($classe['type_classe']) ?></td>
                            <td class="px-4 py-3">
                                <span class="badge badge-active">ACTIVE</span>
                            </td>
                            <td class="px-4 py-3">
                                <button class="btn btn-outline-primary btn-sm me-2">Modifier</button>
                                <button class="btn btn-outline-danger btn-sm">Supprimer</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>