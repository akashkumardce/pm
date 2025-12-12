<?php
/**
 * Dashboard Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/install-check.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if installed
requireInstallation();

// Require login
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$currentUser = getCurrentUser();
$userRoles = getUserRoles($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>">
                <i class="bi bi-building"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>properties.php">Properties</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>renters.php">Renters</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($currentUser['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="logout()">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card p-4 mb-4">
                    <h3 class="mb-4">Welcome, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>!</h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($currentUser['email']); ?></p>
                            <?php if ($currentUser['phone']): ?>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($currentUser['phone']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Your Roles:</strong></p>
                            <div>
                                <?php foreach ($userRoles as $role): ?>
                                    <span class="badge bg-primary me-2 mb-2"><?php echo htmlspecialchars($role['name']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card p-4 h-100">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                    <i class="bi bi-building text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">Properties</h5>
                                    <p class="text-muted small mb-0">Manage your properties</p>
                                </div>
                            </div>
                            <a href="<?php echo BASE_URL; ?>properties.php" class="btn btn-primary w-100">
                                <i class="bi bi-arrow-right"></i> View Properties
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card p-4 h-100">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                                    <i class="bi bi-people text-success" style="font-size: 2rem;"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">Renters</h5>
                                    <p class="text-muted small mb-0">Manage renters & tenants</p>
                                </div>
                            </div>
                            <a href="<?php echo BASE_URL; ?>renters.php" class="btn btn-success w-100">
                                <i class="bi bi-arrow-right"></i> View Renters
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function logout() {
            try {
                const response = await fetch('<?php echo BASE_URL; ?>api/auth/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.href = '<?php echo BASE_URL; ?>';
                }
            } catch (error) {
                console.error('Logout error:', error);
            }
        }
    </script>
</body>
</html>

