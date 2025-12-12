<?php
/**
 * Homepage / Landing Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/install-check.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if installed
requireInstallation();

$isLoggedIn = isLoggedIn();
$currentUser = null;
$userRoles = [];

if ($isLoggedIn) {
    $currentUser = getCurrentUser();
    $userRoles = getUserRoles($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .hero-section {
            padding: 100px 0;
            color: white;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>">
                <i class="bi bi-building"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($currentUser['first_name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>dashboard.php">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="logout()">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (!$isLoggedIn): ?>
    <div class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Property Management Made Easy</h1>
            <p class="lead mb-5">Manage your properties, tenants, and more with our comprehensive platform</p>
            <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-primary btn-lg me-2">
                <i class="bi bi-person-plus"></i> Get Started
            </a>
            <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-outline-light btn-lg">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </a>
        </div>
    </div>

    <div class="container py-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 p-4 text-center">
                    <i class="bi bi-building text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Property Owners</h4>
                    <p class="text-muted">Manage your properties efficiently</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-4 text-center">
                    <i class="bi bi-people text-success" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Tenants</h4>
                    <p class="text-muted">Easy access to your rental information</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-4 text-center">
                    <i class="bi bi-gear text-info" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Property Managers</h4>
                    <p class="text-muted">Streamline your management tasks</p>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card p-4">
                    <h3 class="mb-4">Welcome, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>!</h3>
                    <p class="text-muted">Email: <?php echo htmlspecialchars($currentUser['email']); ?></p>
                    <div class="mb-3">
                        <strong>Your Roles:</strong>
                        <div class="mt-2">
                            <?php foreach ($userRoles as $role): ?>
                                <span class="badge bg-primary me-2"><?php echo htmlspecialchars($role['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-primary">
                        <i class="bi bi-speedometer2"></i> Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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

