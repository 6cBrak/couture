<?php
/**
 * DIAGNOSTIC SIMPLE - Erreur 500
 * Accédez: https://shop.fosip-drc.org/diagnostic_500.php
 */
error_reporting(0);
ini_set('display_errors', 0);

echo "<h1>Diagnostic Erreur 500</h1><hr>";

// TEST 1: config.php exists
echo "<h2>1. config/config.php</h2><pre>";
$config_exists = file_exists('config/config.php');
echo $config_exists ? "✓ EXISTE\n" : "✗ MANQUANT\n";

if ($config_exists) {
    include 'config/config.php';
    echo "✓ CHARGE\n";
    echo "  DB_HOST: " . DB_HOST . "\n";
    echo "  DB_NAME: " . DB_NAME . "\n";
} else {
    echo "⚠ ACTION: Uploadez config/config.php\n";
}
echo "</pre>";

// TEST 2: database.php exists
echo "<h2>2. config/database.php</h2><pre>";
$db_exists = file_exists('config/database.php');
echo $db_exists ? "✓ EXISTE\n" : "✗ MANQUANT\n";
echo "</pre>";

// TEST 3: Test uploads directory
echo "<h2>3. Dossier uploads/</h2><pre>";
$uploads_ok = is_dir('uploads/');
echo $uploads_ok ? "✓ EXISTE\n" : "✗ MANQUANT\n";

if ($uploads_ok) {
    foreach (['logos', 'produits', 'utilisateurs'] as $d) {
        $p = is_dir('uploads/' . $d) ? "✓" : "✗";
        echo "$p uploads/$d/\n";
    }
}
echo "</pre>";

// TEST 4: Critical files
echo "<h2>4. Fichiers critiques</h2><pre>";
$critical = ['header.php', 'footer.php', 'login.php', 'protection_pages.php', 'accueil.php'];
foreach ($critical as $f) {
    $p = file_exists($f) ? "✓" : "✗";
    echo "$p $f\n";
}
echo "</pre>";

// TEST 5: Database connection
if (defined('DB_HOST')) {
    echo "<h2>5. Connexion MySQL</h2><pre>";
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS
        );
        echo "✓ CONNEXION OK\n";
        
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "✓ " . count($tables) . " TABLES\n";
        foreach ($tables as $t) {
            echo "  - $t\n";
        }
    } catch (Exception $e) {
        echo "✗ ERREUR: " . $e->getMessage() . "\n";
    }
    echo "</pre>";
}

echo "<hr>";
echo "<p>Si tout est ✓, essayez: <a href='login.php'>login.php</a></p>";
?>
