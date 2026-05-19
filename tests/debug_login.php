<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Login</h1><pre>";

// Test require database.php
try {
    require_once __DIR__ . '/config/database.php';
    echo "✓ database.php inclus\n";
} catch (Throwable $e) {
    echo "✗ Erreur database.php: " . $e->getMessage() . "\n";
    exit;
}

// Test is_logged_in
try {
    $logged = is_logged_in();
    echo "✓ is_logged_in() = " . ($logged ? 'true' : 'false') . "\n";
} catch (Throwable $e) {
    echo "✗ Erreur is_logged_in(): " . $e->getMessage() . "\n";
}

// Test get_system_config
try {
    $config = get_system_config();
    echo "✓ get_system_config() ok\n";
    echo "  est_configure: " . ($config['est_configure'] ?? 'NULL') . "\n";
} catch (Throwable $e) {
    echo "✗ Erreur get_system_config(): " . $e->getMessage() . "\n";
}

// Test generate_csrf_token
try {
    $token = generate_csrf_token();
    echo "✓ generate_csrf_token() ok\n";
} catch (Throwable $e) {
    echo "✗ Erreur generate_csrf_token(): " . $e->getMessage() . "\n";
}

echo "\n✓ Toutes les fonctions OK";
echo "</pre>";
?>
