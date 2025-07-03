<?php
session_start();
$level = 2;
require_once '../../config/db.php';
require_once '../../includes/header.php';

$stmt = $pdo->query("SELECT * FROM Module ORDER BY nom");
$modules = $stmt->fetchAll();
?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark fw-bold mb-1">Gestion des Matières</h2>
            <p class="text-muted mb-0">Organisez les matières et assignez les professeurs</p>
        </div>
    </div>

    <div class="row">
        <!-- Section Matières -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Matières</h5>
                        <button class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-plus me-1"></i> Nouvelle Matière
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 fw-semibold">Matière</th>
                                    <th class="border-0 fw-semibold">Code</th>
                                    <th class="border-0 fw-semibold">Couleur</th>
                                    <th class="border-0 fw-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                                            Mathématiques
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark">MATH</span></td>
                                    <td><div class="bg-primary rounded-circle" style="width: 20px; height: 20px;"></div></td>
                                    <td>
                                        <button class="btn btn-outline-primary btn-sm me-1">Modifier</button>
                                        <button class="btn btn-outline-danger btn-sm">Supprimer</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                                            Français
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark">FR</span></td>
                                    <td><div class="bg-danger rounded-circle" style="width: 20px; height: 20px;"></div></td>
                                    <td>
                                        <button class="btn btn-outline-primary btn-sm me-1">Modifier</button>
                                        <button class="btn btn-outline-danger btn-sm">Supprimer</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                                            Anglais
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark">ANG</span></td>
                                    <td><div class="bg-success rounded-circle" style="width: 20px; height: 20px;"></div></td>
                                    <td>
                                        <button class="btn btn-outline-primary btn-sm me-1">Modifier</button>
                                        <button class="btn btn-outline-danger btn-sm">Supprimer</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Professeurs -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Professeurs</h5>
                        <button class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-plus me-1"></i> Nouveau Professeur
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 fw-semibold">Nom</th>
                                    <th class="border-0 fw-semibold">Matières</th>
                                    <th class="border-0 fw-semibold">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                <span class="text-white fw-bold small">MD</span>
                                            </div>
                                            Mme Dupont
                                        </div>
                                    </td>
                                    <td>Mathématiques</td>
                                    <td><span class="badge bg-success">ACTIF</span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                <span class="text-white fw-bold small">MM</span>
                                            </div>
                                            M. Martin
                                        </div>
                                    </td>
                                    <td>Français</td>
                                    <td><span class="badge bg-success">ACTIF</span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                <span class="text-white fw-bold small">ML</span>
                                            </div>
                                            Mme Leroy
                                        </div>
                                    </td>
                                    <td>Anglais, Espagnol</td>
                                    <td><span class="badge bg-success">ACTIF</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>