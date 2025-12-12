<?php
/**
 * Installation Wizard
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/install_errors.log');

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0755, true);
}

// Set error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $logFile = __DIR__ . '/../logs/install_errors.log';
    $message = "[$errno] $errstr in $errfile on line $errline\n";
    @file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message, FILE_APPEND);
    
    // Display error if display_errors is on
    if (ini_get('display_errors')) {
        echo "<div style='background: #fee; border: 1px solid #fcc; padding: 10px; margin: 10px;'>";
        echo "<strong>Error:</strong> $errstr<br>";
        echo "<small>File: $errfile, Line: $errline</small>";
        echo "</div>";
    }
    return false; // Let PHP handle it normally too
});

// Set exception handler
set_exception_handler(function($exception) {
    $logFile = __DIR__ . '/../logs/install_errors.log';
    $message = "Uncaught exception: " . $exception->getMessage() . "\n";
    $message .= "Stack trace:\n" . $exception->getTraceAsString() . "\n";
    @file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message, FILE_APPEND);
    
    echo "<!DOCTYPE html><html><head><title>Installation Error</title></head><body>";
    echo "<div style='max-width: 800px; margin: 50px auto; padding: 20px; background: #fee; border: 2px solid #f00;'>";
    echo "<h2>Installation Error</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
    echo "<details><summary>Stack Trace</summary><pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre></details>";
    echo "<p><small>Check <code>" . htmlspecialchars($logFile) . "</code> for detailed logs.</small></p>";
    echo "</div></body></html>";
    exit;
});

// Log function
function logInstall($message, $data = null) {
    $logFile = __DIR__ . '/../logs/install.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= " | Data: " . json_encode($data);
    }
    $logMessage .= "\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Start session FIRST - must be before any $_SESSION usage
try {
    session_start();
    logInstall('Session started');
} catch (Exception $e) {
    die('Session error: ' . $e->getMessage());
}

// Get base URL helper function (only declare if not already declared)
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($scriptName, '/pm/') !== false || strpos($requestUri, '/pm/') === 0) {
            return '/pm/';
        }
        return '/';
    }
}

$baseUrl = getBaseUrl();

// Check if already installed - but allow running installer to update schema
require_once __DIR__ . '/../includes/install-check.php';

// Only redirect if fully installed AND not trying to update schema
if (isInstalled() && !isset($_GET['update'])) {
    // Check if we should allow schema update
    $allowUpdate = false;
    if (file_exists(__DIR__ . '/../config/database.php')) {
        require_once __DIR__ . '/../config/database.php';
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            require_once __DIR__ . '/../includes/mongodb.php';
            $db = getDBConnection();
            if ($db) {
                // Check if property_types collection exists (newest addition)
                $count = MongoDBHelper::count('property_types');
                if ($count === 0) {
                    $allowUpdate = true; // Missing new collections, allow update
                }
            }
        } catch (Exception $e) {
            // Error checking, allow update
            $allowUpdate = true;
        }
    }
    
    if (!$allowUpdate) {
        header('Location: ' . $baseUrl);
        exit;
    }
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Check for success message from redirect
if ($step === 3 && isset($_SESSION['install_message'])) {
    $success = $_SESSION['install_message'];
    unset($_SESSION['install_message']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        // MongoDB connection test
        $mongoUri = $_POST['mongo_uri'] ?? 'mongodb://localhost:27017';
        $dbName = $_POST['db_name'] ?? 'property_management';
        
        // Validate inputs
        if (empty($mongoUri) || empty($dbName)) {
            $error = 'Please fill in all required fields.';
            $step = 1; // Stay on step 1
        } else {
            try {
                logInstall('Step 1: Testing MongoDB connection', ['uri' => $mongoUri, 'db' => $dbName]);
                
                // Check if MongoDB extension is installed
                if (!extension_loaded('mongodb')) {
                    $phpVersion = PHP_VERSION;
                    $zts = ZEND_THREAD_SAFE ? 'ZTS' : 'NTS';
                    $arch = (PHP_INT_SIZE * 8) . '-bit';
                    $downloadUrl = 'https://windows.php.net/downloads/pecl/releases/mongodb/';
                    
                    $errorMsg = "MongoDB PHP extension is not installed or not loading.\n\n";
                    $errorMsg .= "Your PHP Configuration:\n";
                    $errorMsg .= "- Version: $phpVersion\n";
                    $errorMsg .= "- Build: $zts\n";
                    $errorMsg .= "- Architecture: $arch\n\n";
                    
                    // Check for common issues
                    $dllPath = 'C:\\xampp\\php\\ext\\php_mongodb.dll';
                    $dllExists = file_exists($dllPath);
                    $libMongocExists = file_exists('C:\\xampp\\php\\ext\\libmongoc.dll');
                    $libBsonExists = file_exists('C:\\xampp\\php\\ext\\libbson.dll');
                    
                    if ($dllExists) {
                        $errorMsg .= "Status:\n";
                        $errorMsg .= "- php_mongodb.dll: " . ($dllExists ? "Found ✓" : "Missing ✗") . "\n";
                        $errorMsg .= "- libmongoc.dll: " . ($libMongocExists ? "Found ✓" : "Missing ✗") . "\n";
                        $errorMsg .= "- libbson.dll: " . ($libBsonExists ? "Found ✓" : "Missing ✗") . "\n\n";
                        
                        if (!$libMongocExists || !$libBsonExists) {
                            $errorMsg .= "⚠ CRITICAL: Missing dependencies!\n";
                            $errorMsg .= "The MongoDB extension requires libmongoc.dll and libbson.dll.\n";
                            $errorMsg .= "These must be in the same directory as php_mongodb.dll.\n\n";
                            $errorMsg .= "Solution:\n";
                            $errorMsg .= "1. Extract ALL DLLs from the zip file (not just php_mongodb.dll)\n";
                            $errorMsg .= "2. Copy ALL DLLs to: C:\\xampp\\php\\ext\\\n";
                            $errorMsg .= "3. Restart Apache\n\n";
                        } else {
                            $errorMsg .= "If DLLs are present but extension still not loading:\n";
                            $errorMsg .= "- Wrong DLL version (NTS vs ZTS mismatch)\n";
                            $errorMsg .= "- Wrong architecture (x86 vs x64)\n";
                            $errorMsg .= "- Corrupted DLL file\n\n";
                        }
                    }
                    
                    $errorMsg .= "Installation Steps:\n";
                    $errorMsg .= "1. Download MongoDB extension from: $downloadUrl\n";
                    $errorMsg .= "2. Look for: php_mongodb-*-8.2-ts-vs16-x64.zip (MUST be ZTS, not NTS)\n";
                    $errorMsg .= "3. Extract ALL DLLs from zip to: C:\\xampp\\php\\ext\\\n";
                    $errorMsg .= "   Required files: php_mongodb.dll, libmongoc.dll, libbson.dll\n";
                    $errorMsg .= "4. Edit C:\\xampp\\php\\php.ini and add: extension=mongodb\n";
                    $errorMsg .= "5. Restart Apache\n\n";
                    $errorMsg .= "Diagnostic: Run diagnose_mongodb.ps1 or check_mongodb.php for detailed analysis.";
                    
                    throw new Exception($errorMsg);
                }
                
                // Check if composer autoload exists
                $vendorPath = __DIR__ . '/../vendor/autoload.php';
                if (!file_exists($vendorPath)) {
                    throw new Exception('Composer dependencies not installed. Please run: composer install');
                }
                
                require_once $vendorPath;
                
                // Test MongoDB connection
                $client = new MongoDB\Client($mongoUri);
                logInstall('MongoDB client created');
                
                // Test connection by pinging
                $admin = $client->selectDatabase('admin');
                $result = $admin->command(['ping' => 1]);
                logInstall('MongoDB connection successful', ['result' => 'pong']);
                
                // Select database (MongoDB creates it automatically on first use)
                $db = $client->selectDatabase($dbName);
                logInstall('Database selected', ['name' => $dbName]);
                
                // Check if config directory is writable
                $configDir = __DIR__ . '/../config';
                logInstall('Checking config directory', ['path' => $configDir, 'writable' => is_writable($configDir)]);
                if (!is_writable($configDir)) {
                    throw new Exception('Config directory is not writable. Please check file permissions.');
                }
                
                // Save configuration
                logInstall('Saving database configuration');
                $configContent = "<?php
/**
 * Database Configuration (MongoDB)
 * This file is auto-generated by the installer
 */

// MongoDB configuration
define('MONGODB_URI', '" . addslashes($dbHost) . "');
define('MONGODB_DB', '" . addslashes($dbName) . "');

// Database connection
function getDBConnection() {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        \$client = new MongoDB\\Client(MONGODB_URI);
        \$db = \$client->selectDatabase(MONGODB_DB);
        
        return \$db;
    } catch (Exception \$e) {
        error_log(\"MongoDB connection failed: \" . \$e->getMessage());
        return null;
    }
}
";
                
                $configFile = __DIR__ . '/../config/database.php';
                $writeResult = @file_put_contents($configFile, $configContent);
                
                if ($writeResult === false) {
                    throw new Exception('Failed to write configuration file. Please check file permissions for the config directory.');
                }
                
                // Verify the file was written correctly
                if (!file_exists($configFile)) {
                    throw new Exception('Configuration file was not created. Please check file permissions.');
                }
                logInstall('Configuration file created successfully');
                
                // Store in session for next step
                $_SESSION['install_db_config'] = [
                    'uri' => $mongoUri,
                    'name' => $dbName
                ];
                logInstall('Step 1 completed, redirecting to step 2');
                
                // Only redirect if everything succeeded
                header('Location: ?step=2');
                exit;
            } catch (PDOException $e) {
                $errorMsg = 'Database connection failed: ' . $e->getMessage();
                logInstall('Step 1 failed (PDOException)', ['error' => $errorMsg]);
                $error = $errorMsg;
                $step = 1; // Stay on step 1 on error
            } catch (Exception $e) {
                $errorMsg = $e->getMessage();
                logInstall('Step 1 failed (Exception)', ['error' => $errorMsg]);
                $error = $errorMsg;
                $step = 1; // Stay on step 1 on error
            }
        }
    } elseif ($step === 2) {
        // Install database schema
        logInstall('Step 2: Starting schema installation');
        
        if (isset($_SESSION['install_db_config'])) {
            $config = $_SESSION['install_db_config'];
            logInstall('Database config found in session', ['db' => $config['name']]);
            
            try {
                logInstall('Connecting to MongoDB');
                require_once __DIR__ . '/../vendor/autoload.php';
                
                $client = new MongoDB\Client($config['uri']);
                $db = $client->selectDatabase($config['name']);
                logInstall('MongoDB connection established');
                
                // Load and run MongoDB schema setup
                $schemaFile = __DIR__ . '/../database/schema_mongodb.php';
                if (!file_exists($schemaFile)) {
                    throw new Exception('MongoDB schema file not found: ' . $schemaFile);
                }
                
                logInstall('Loading MongoDB schema setup');
                require_once $schemaFile;
                
                logInstall('Running MongoDB schema setup');
                $result = setupMongoDBSchema();
                
                if (!$result['success']) {
                    throw new Exception($result['message']);
                }
                
                logInstall('MongoDB schema setup completed', ['details' => $result['details']]);
                
                $fullMessage = 'MongoDB schema installed successfully! ' . implode('. ', $result['details']);
                $_SESSION['install_message'] = $fullMessage;
                
                header('Location: ?step=3');
                exit;
            } catch (Exception $e) {
                $errorMsg = 'Schema installation failed: ' . $e->getMessage();
                logInstall('Installation failed (Exception)', ['error' => $errorMsg, 'trace' => $e->getTraceAsString()]);
                $error = $errorMsg;
                $step = 2; // Stay on step 2 on error
            }
        } else {
            // Missing session data, go back to step 1
            logInstall('Step 2 failed: Missing database config in session');
            $error = 'Database configuration not found. Please start from step 1.';
            $step = 1;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Property Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        .install-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
        }
        .step.active .step-number {
            background: #667eea;
            color: white;
        }
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        .step-line {
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e9ecef;
            z-index: -1;
        }
        .step:last-child .step-line {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <h2 class="text-center mb-4">
                <i class="bi bi-gear-fill"></i> Installation Wizard
            </h2>
            
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                    <div class="step-number">1</div>
                    <small>Database</small>
                </div>
                <div class="step-line"></div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                    <div class="step-number">2</div>
                    <small>Schema</small>
                </div>
                <div class="step-line"></div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                    <div class="step-number">3</div>
                    <small>Complete</small>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                    <br><small class="text-muted">Check the logs directory for detailed error information.</small>
                </div>
                
                <!-- Debug: Show recent log entries -->
                <?php
                $logFile = __DIR__ . '/../logs/install.log';
                if (file_exists($logFile)) {
                    $logs = file($logFile);
                    $recentLogs = array_slice($logs, -10); // Last 10 lines
                    if (!empty($recentLogs)) {
                        echo '<div class="alert alert-info mt-3">';
                        echo '<strong><i class="bi bi-info-circle"></i> Recent Log Entries:</strong>';
                        echo '<pre class="small bg-light p-2 mt-2" style="max-height: 200px; overflow-y: auto;">';
                        echo htmlspecialchars(implode('', $recentLogs));
                        echo '</pre>';
                        echo '</div>';
                    }
                }
                ?>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- Debug Panel (only show if there are errors or in development) -->
            <?php if ($error || (isset($_GET['debug']) && $_GET['debug'] === '1')): ?>
                <div class="alert alert-secondary mt-3">
                    <strong><i class="bi bi-bug"></i> Debug Information:</strong>
                    <ul class="small mb-0 mt-2">
                        <li>Current Step: <?php echo $step; ?></li>
                        <li>PHP Version: <?php echo PHP_VERSION; ?></li>
                        <li>Session Status: <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?></li>
                        <li>Log File: <code><?php echo __DIR__ . '/../logs/install.log'; ?></code></li>
                        <?php if (file_exists(__DIR__ . '/../logs/install.log')): ?>
                            <li>Log Size: <?php echo filesize(__DIR__ . '/../logs/install.log'); ?> bytes</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
                <form method="POST">
                    <h4 class="mb-3">MongoDB Configuration</h4>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Make sure MongoDB is running and the PHP MongoDB extension is installed.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">MongoDB Connection URI *</label>
                        <input type="text" class="form-control" name="mongo_uri" value="mongodb://localhost:27017" required placeholder="mongodb://localhost:27017 or mongodb+srv://user:pass@cluster.mongodb.net/">
                        <small class="text-muted">
                            <strong>Local:</strong> mongodb://localhost:27017<br>
                            <strong>Atlas:</strong> mongodb+srv://username:password@cluster.mongodb.net/
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Name *</label>
                        <input type="text" class="form-control" name="db_name" value="property_management" required>
                        <small class="text-muted">MongoDB will create this database automatically on first use</small>
                    </div>
                    <div class="alert alert-warning">
                        <strong>Note:</strong> Make sure you have:
                        <ul class="mb-0 small">
                            <li>MongoDB server running</li>
                            <li>PHP MongoDB extension installed (run <code>check_mongodb.php</code> to check)</li>
                            <li>Composer dependencies installed (<code>composer install</code>)</li>
                        </ul>
                        <p class="mb-0 mt-2 small">
                            <strong>Don't have MongoDB extension?</strong> 
                            <a href="<?php echo $baseUrl; ?>MONGODB_INSTALLATION.md" target="_blank">See installation guide</a> or 
                            run <code>check_mongodb.php</code> for detailed instructions.
                        </p>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-arrow-right"></i> Continue
                    </button>
                </form>
            <?php elseif ($step === 2): ?>
                <form method="POST">
                    <h4 class="mb-3">Install/Update Database Schema</h4>
                    <p>Click the button below to install or update the database schema. This will:</p>
                    <ul class="text-start">
                        <li>Create any missing collections</li>
                        <li>Add default data (roles, property types, etc.)</li>
                        <li>Create indexes for better performance</li>
                        <li>Skip collections and data that already exist</li>
                    </ul>
                    <p class="text-muted small">Safe to run multiple times - it will only create what's missing.</p>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-database"></i> Install/Update Schema
                    </button>
                </form>
            <?php elseif ($step === 3): ?>
                <div class="text-center">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="mb-3">Installation Complete!</h4>
                    <p class="text-muted mb-4">Your Property Management System has been successfully installed.</p>
                    <div class="alert alert-info text-start">
                        <h5 class="alert-heading"><i class="bi bi-info-circle"></i> Next Steps:</h5>
                        <ol class="mb-0">
                            <li>Go to the <strong>Register</strong> page to create your first user account</li>
                            <li>Select your roles (Property Owner, Tenant, Property Manager, or Admin)</li>
                            <li>Log in with your new account</li>
                            <li>Start managing your properties!</li>
                        </ol>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="<?php echo $baseUrl; ?>register.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus"></i> Register Your Account
                        </a>
                        <a href="<?php echo $baseUrl; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-house"></i> Go to Homepage
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

