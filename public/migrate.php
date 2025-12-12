<?php
/**
 * Database Migration Script
 * Run this to add property management tables to existing database
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/install-check.php';
require_once __DIR__ . '/../includes/auth.php';

requireInstallation();
requireLogin();

// Only allow admins or property owners to run migration
$userRoles = getUserRoles($_SESSION['user_id']);
$isAdmin = false;
$isOwner = false;
foreach ($userRoles as $role) {
    if ($role['slug'] === 'admin') $isAdmin = true;
    if ($role['slug'] === 'property_owner') $isOwner = true;
}

if (!$isAdmin && !$isOwner) {
    die('Access denied. Only admins and property owners can run migrations.');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    try {
        $migrationFile = __DIR__ . '/../database/migration_add_properties.sql';
        
        if (!file_exists($migrationFile)) {
            throw new Exception('Migration file not found');
        }
        
        $db = getDBConnection();
        if (!$db) {
            throw new Exception('Database connection failed');
        }
        
        $sql = file_get_contents($migrationFile);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $db->exec($statement);
                } catch (PDOException $e) {
                    // Ignore "table already exists" errors
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            }
        }
        
        $message = 'Migration completed successfully! All property management tables have been created.';
    } catch (Exception $e) {
        $error = 'Migration failed: ' . $e->getMessage();
    }
}

// Check which tables exist
$existingTables = [];
$requiredTables = ['property_types', 'properties', 'property_details', 'floors', 'rooms', 'renters', 'notifications'];

try {
    $db = getDBConnection();
    if ($db) {
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $existingTables = $tables;
    }
} catch (Exception $e) {
    $error = 'Failed to check tables: ' . $e->getMessage();
}

$missingTables = array_diff($requiredTables, $existingTables);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-database"></i> Database Migration</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h5>Table Status</h5>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requiredTables as $table): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($table); ?></code></td>
                                        <td>
                                            <?php if (in_array($table, $existingTables)): ?>
                                                <span class="badge bg-success"><i class="bi bi-check"></i> Exists</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="bi bi-x"></i> Missing</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if (!empty($missingTables)): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-info-circle"></i> 
                                <strong>Missing Tables:</strong> The following tables need to be created: 
                                <?php echo implode(', ', $missingTables); ?>
                            </div>
                            
                            <form method="POST">
                                <button type="submit" name="run_migration" class="btn btn-primary">
                                    <i class="bi bi-play-circle"></i> Run Migration
                                </button>
                                <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-secondary">Cancel</a>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> All required tables exist. No migration needed.
                            </div>
                            <a href="<?php echo BASE_URL; ?>dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

