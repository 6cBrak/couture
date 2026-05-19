<?php
/**
 * TEST SIMPLE - Vérifie protection_pages.php exactement
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST PROTECTION_PAGES.PHP ===\n";
echo "Heure: " . date('Y-m-d H:i:s') . "\n\n";

$root = dirname(__FILE__);

// ÉTAPE 1: Vérifier que le fichier existe
echo "1. Vérification fichier:\n";
$file = $root . '/protection_pages.php';
echo "   Chemin: $file\n";
echo "   Existe: " . (file_exists($file) ? "OUI\n" : "NON\n");
echo "   Lisible: " . (is_readable($file) ? "OUI\n" : "NON\n");

if (!file_exists($file)) {
    echo "\nERREUR: protection_pages.php n'existe pas!\n";
    die();
}

// ÉTAPE 2: Afficher le début du fichier
echo "\n2. Contenu (premières 500 caractères):\n";
$content = file_get_contents($file);
echo substr($content, 0, 500) . "\n";

// ÉTAPE 3: Essayer de l'inclure
echo "\n3. Tentative d'inclusion:\n";

// Active le rapport d'erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

try {
    echo "   Appel require_once...\n";
    require_once $file;
    echo "   ✓ Succès!\n";
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n";
    echo "   Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

$output = ob_get_clean();
if (!empty($output)) {
    echo "\n4. Output généré:\n" . $output . "\n";
}

// ÉTAPE 4: Vérifier ce qui s'est chargé
echo "\n5. Vérification variables globales:\n";
echo "   \$user_id: " . (isset($user_id) ? $user_id : "non défini") . "\n";
echo "   \$is_admin: " . (isset($is_admin) ? ($is_admin ? "true" : "false") : "non défini") . "\n";
echo "   session_status: " . session_status() . "\n";

echo "\n=== FIN TEST ===\n";
?>
