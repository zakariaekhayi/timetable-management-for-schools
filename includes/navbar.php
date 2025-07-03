<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= isset($level) ? str_repeat('../', $level) : '' ?>dashboard.php">
            <span class="logo">ST</span>SchoolTime
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= isset($level) ? str_repeat('../', $level) : '' ?>dashboard.php">
                        <i class="fas fa-home me-1"></i>Accueil
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-cogs me-1"></i>Gestion
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= isset($level) ? str_repeat('../', $level) : '' ?>pages/classes/list.php">
                            <i class="fas fa-door-open me-2"></i>Classes
                        </a></li>
                        <li><a class="dropdown-item" href="<?= isset($level) ? str_repeat('../', $level) : '' ?>pages/modules/list.php">
                            <i class="fas fa-book me-2"></i>Matières
                        </a></li>
                        <li><a class="dropdown-item" href="<?= isset($level) ? str_repeat('../', $level) : '' ?>pages/enseignants/list.php">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Professeurs
                        </a></li>
                        <li><a class="dropdown-item" href="<?= isset($level) ? str_repeat('../', $level) : '' ?>pages/etudiants/list.php">
                            <i class="fas fa-user-graduate me-2"></i>Étudiants
                        </a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?= isset($level) ? str_repeat('../', $level) : '' ?>pages/emploi/view.php">
                        <i class="fas fa-calendar-alt me-1"></i>Emploi du temps
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?= isset($level) ? str_repeat('../', $level) : '' ?>pages/emploi/edit.php">
                        <i class="fas fa-edit me-1"></i>Édition
                    </a>
                </li>
            </ul>
            
            <div class="navbar-nav">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?= $_SESSION['user_name'] ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-user-cog me-2"></i>Profil
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= isset($level) ? str_repeat('../', $level) : '' ?>logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>