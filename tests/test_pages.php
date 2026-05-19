<?php
/**
 * TEST DES PAGES - Trouve quelle page cause l'erreur 500
 * Accédez: https://shop.fosip-drc.org/test_pages.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test des pages</h1><hr>";

$pages = ['index.php', 'login.php', 'accueil.php'];

foreach ($pages as $page) {
    echo "<h2>Test: $page</h2><pre>";
    
    if (!file_exists($page)) {
        echo "✗ Fichier n'existe pas\n";
        echo "</pre>";
        continue;
    }
    
    ob_start();
    $error = null;
    
    try {
        include $page;
        $output = ob_get_clean();
        echo "✓ OK - Page chargée\n";
        echo "Output length: " . strlen($output) . " caractères\n";
    } catch (Throwable $e) {
        ob_get_clean();
        echo "✗ ERREUR FATALE:\n";
        echo "  Type: " . get_class($e) . "\n";
        echo "  Message: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . "\n";
        echo "  Line: " . $e->getLine() . "\n";
    }
    
    echo "</pre>\n";
}

echo "<hr>";
echo "<p>L'erreur 500 vient d'une de ces pages.</p>";
?>
