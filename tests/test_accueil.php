<?php
/**
 * FICHIER DE TEST - Simule accueil.php pour voir l'erreur 500
 * À UPLOADER: https://storesuite.shop/test_accueil.php
 */

// Désactiver le cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test accueil.php</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        .card { background: #252526; padding: 20px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007acc; }
        .error { background: #3f2f2f; border-left-color: #f44747; color: #f48771; }
        .success { background: #2f3f2f; border-left-color: #4ec9b0; color: #4ec9b0; }
        h1 { color: #4ec9b0; }
        h2 { color: #9cdcfe; }
        pre { background: #1e1e1e; padding: 10px; border: 1px solid #3e3e42; border-radius: 3px; overflow-x: auto; color: #ce9178; }
        code { color: #ce9178; }
    </style>
</head>
<body>
    <h1>🧪 Test d'accueil.php - Diagnostic d'erreur 500</h1>
    <p>Heure: " . date('Y-m-d H:i:s') . "</p>
";

$root = dirname(__FILE__);

// ============================================================================
// TEST 1: INCLURE PROTECTION_PAGES.PHP
// ============================================================================
echo "<div class='card'>";
echo "<h2>1️⃣ Chargement de protection_pages.php</h2>";

ob_start();
$errors = [];

try {
    // Désactiver les erreurs affichées, on va les capturer
    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
        $errors[] = "[$errno] $errstr (dans $errfile:$errline)";
        return true;
    });
    
    require_once $root . '/protection_pages.php';
    
    restore_error_handler();
    echo "<p class='success'>✓ protection_pages.php chargé avec succès</p>";
    
} catch (Exception $e) {
    restore_error_handler();
    ob_end_clean();
    echo "<p class='error'>✗ Exception lors du chargement:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . "</pre>";
    
    if (!empty($errors)) {
        echo "<p class='error'><strong>Erreurs PHP détectées:</strong></p>";
        foreach ($errors as $err) {
            echo "<pre>" . htmlspecialchars($err) . "</pre>";
        }
    }
    die();
}

// Afficher les erreurs PHP si y en a
if (!empty($errors)) {
    echo "<p class='error'><strong>Erreurs détectées:</strong></p>";
    foreach ($errors as $err) {
        echo "<pre>" . htmlspecialchars($err) . "</pre>";
    }
}

// Vérifier les variables globales
echo "<p><strong>Variables de session disponibles:</strong></p>";
echo "<pre>";
echo "user_id: " . (isset($user_id) ? $user_id : 'UNDEFINED') . "\n";
echo "user_name: " . (isset($user_name) ? $user_name : 'UNDEFINED') . "\n";
echo "user_niveau: " . (isset($user_niveau) ? $user_niveau : 'UNDEFINED') . "\n";
echo "is_admin: " . (isset($is_admin) ? ($is_admin ? 'true' : 'false') : 'UNDEFINED') . "\n";
echo "</pre>";

echo "</div>";

// ============================================================================
// TEST 2: INCLURE HEADER.PHP
// ============================================================================
echo "<div class='card'>";
echo "<h2>2️⃣ Chargement de header.php</h2>";

ob_start();
$errors = [];

try {
    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
        $errors[] = "[$errno] $errstr (dans $errfile:$errline)";
        return true;
    });
    
    ob_start(); // Capture le HTML de header.php
    require_once $root . '/header.php';
    $header_content = ob_get_clean();
    
    restore_error_handler();
    echo "<p class='success'>✓ header.php chargé avec succès</p>";
    echo "<p>Taille du header: " . strlen($header_content) . " caractères</p>";
    
} catch (Exception $e) {
    restore_error_handler();
    ob_end_clean();
    echo "<p class='error'>✗ Exception lors du chargement:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

if (!empty($errors)) {
    echo "<p class='error'><strong>Erreurs PHP:</strong></p>";
    foreach ($errors as $err) {
        echo "<pre>" . htmlspecialchars($err) . "</pre>";
    }
}

echo "</div>";

// ============================================================================
// TEST 3: ESSAYER DE CHARGER ACCUEIL.PHP DIRECTEMENT
// ============================================================================
echo "<div class='card'>";
echo "<h2>3️⃣ Chargement de accueil.php (LE TEST PRINCIPAL)</h2>";

ob_start();
$errors = [];

try {
    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
        $errors[] = "[$errno] <strong>$errstr</strong> (dans <code>$errfile:$errline</code>)";
        return true;
    });
    
    // Essayer de charger accueil.php
    include $root . '/accueil.php';
    
    restore_error_handler();
    echo "<p class='success'>✓ accueil.php chargé avec succès!</p>";
    
} catch (Exception $e) {
    restore_error_handler();
    $output = ob_get_clean();
    
    echo "<p class='error'>✗ Exception lors du chargement:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString()) . "</pre>";
    
    if (!empty($output)) {
        echo "<p><strong>Output avant erreur:</strong></p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
}

// SI ERREURS, LES AFFICHER
if (!empty($errors)) {
    echo "<p class='error'><strong style='font-size: 18px; color: #f48771;'>❌ ERREURS DÉTECTÉES:</strong></p>";
    echo "<div style='background: #3f2f2f; padding: 15px; border-radius: 3px;'>";
    foreach ($errors as $err) {
        echo "<div style='margin: 10px 0; line-height: 1.5;'>" . $err . "</div>";
    }
    echo "</div>";
} else {
    ob_end_clean();
}

echo "</div>";

// ============================================================================
// TEST 4: TEST DE PERMISSIONS
// ============================================================================
echo "<div class='card'>";
echo "<h2>4️⃣ Vérification des permissions fichiers</h2>";

$files_to_check = [
    'config/config.php',
    'config/database.php',
    'protection_pages.php',
    'header.php',
    'footer.php',
    'accueil.php',
    'login.php',
];

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #2d2d30;'><td style='padding: 8px; border: 1px solid #3e3e42;'><strong>Fichier</strong></td><td style='padding: 8px; border: 1px solid #3e3e42;'><strong>Lisible</strong></td><td style='padding: 8px; border: 1px solid #3e3e42;'><strong>Writable</strong></td><td style='padding: 8px; border: 1px solid #3e3e42;'><strong>Permissions</strong></td></tr>";

foreach ($files_to_check as $file) {
    $path = $root . '/' . $file;
    $exists = file_exists($path);
    $readable = is_readable($path);
    $writable = is_writable($path);
    $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : '-';
    
    $bg = $exists ? '#1e1e1e' : '#3f2f2f';
    echo "<tr style='background: $bg;'>";
    echo "<td style='padding: 8px; border: 1px solid #3e3e42;'><code>$file</code></td>";
    echo "<td style='padding: 8px; border: 1px solid #3e3e42;'>" . ($readable ? '✓' : '✗') . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #3e3e42;'>" . ($writable ? '✓' : '✗') . "</td>";
    echo "<td style='padding: 8px; border: 1px solid #3e3e42;'><code>$perms</code></td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// ============================================================================
// TEST 5: RÉSUMÉ
// ============================================================================
echo "<div class='card success'>";
echo "<h2>📋 Résumé</h2>";
echo "<p>Si tu vois <span style='color: #f48771;'>❌ ERREURS DÉTECTÉES</span> ci-dessus:</p>";
echo "<ul>";
echo "<li><strong>Parse error:</strong> Mauvaise syntaxe PHP → Montre-moi l'erreur exacte</li>";
echo "<li><strong>Undefined function:</strong> Fonction inexistante → Vérifie que config.php ou protection_pages.php s'est chargé</li>";
echo "<li><strong>Cannot redeclare:</strong> Variable/fonction déclarée deux fois → Problème d'include</li>";
echo "<li><strong>Call to undefined:</strong> Fonction manquante → Fichier config ou database pas chargé</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>
