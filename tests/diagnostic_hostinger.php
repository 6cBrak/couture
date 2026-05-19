<?php
/**
 * FICHIER DE DIAGNOSTIC HOSTINGER
 * À UPLOADER TEMPORAIREMENT POUR TESTER
 * Accès: https://storesuite.shop/diagnostic_hostinger.php
 */

// Désactiver le cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Diagnostic Hostinger - StoreSuite</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        td:first-child { font-weight: bold; width: 30%; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic Hostinger - StoreSuite</h1>
    <p>Date: " . date('Y-m-d H:i:s') . "</p>
";

// ============================================================================
// 1. TEST FICHIERS
// ============================================================================
echo "<div class='card'>
    <h2>1️⃣ Vérification des fichiers</h2>
    <table>";

$files = [
    'config/config.php' => 'Configuration principale',
    'config/database.php' => 'Connexion base de données',
    'protection_pages.php' => 'Middleware d\'authentification',
    'header.php' => 'Header partagé',
    'footer.php' => 'Footer partagé',
    'login.php' => 'Page de connexion',
    'accueil.php' => 'Tableau de bord',
];

$root = dirname(__FILE__);

foreach ($files as $file => $desc) {
    $path = $root . '/' . $file;
    $exists = file_exists($path);
    $status = $exists ? '<span class="ok">✓ OK</span>' : '<span class="error">✗ MANQUANT</span>';
    echo "<tr><td>$file</td><td>$desc</td><td>$status</td></tr>";
}

echo "</table></div>";

// ============================================================================
// 2. TEST CONFIG.PHP
// ============================================================================
echo "<div class='card'>
    <h2>2️⃣ Vérification de config.php</h2>";

if (file_exists($root . '/config/config.php')) {
    echo "<p><span class='ok'>✓ config.php existe</span></p>";
    
    // Essayer de charger config.php
    ob_start();
    $config_error = null;
    try {
        require_once $root . '/config/config.php';
        echo "<p><span class='ok'>✓ config.php s'est chargé sans erreur</span></p>";
        
        // Vérifier les constantes
        echo "<table>";
        $constants = [
            'DB_HOST' => 'Serveur DB',
            'DB_NAME' => 'Nom BD',
            'DB_USER' => 'Utilisateur DB',
            'APP_NAME' => 'Nom app',
            'BASE_URL' => 'URL base',
        ];
        
        foreach ($constants as $const => $desc) {
            $value = defined($const) ? constant($const) : 'UNDEFINED';
            if ($const === 'DB_PASS') {
                $value = '***MASQUÉ***';
            }
            echo "<tr><td>$const</td><td>$desc</td><td><code>$value</code></td></tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p><span class='error'>✗ Erreur au chargement de config.php:</span></p>";
        echo "<pre>" . e($e->getMessage()) . "</pre>";
    }
    ob_end_clean();
} else {
    echo "<p><span class='error'>✗ config.php n'existe pas!</span></p>";
    echo "<p style='color: red;'><strong>ACTION REQUISE:</strong> Upload config.php dans le dossier config/</p>";
}

echo "</div>";

// ============================================================================
// 3. TEST DATABASE.PHP
// ============================================================================
echo "<div class='card'>
    <h2>3️⃣ Vérification de database.php</h2>";

if (file_exists($root . '/config/database.php')) {
    echo "<p><span class='ok'>✓ database.php existe</span></p>";
    
    if (defined('DB_HOST')) {
        try {
            require_once $root . '/config/database.php';
            echo "<p><span class='ok'>✓ database.php s'est chargé</span></p>";
            
            // Essayer une requête simple
            if (isset($pdo)) {
                echo "<p><span class='ok'>✓ \$pdo est initialisé</span></p>";
                
                try {
                    $stmt = $pdo->query('SELECT 1');
                    echo "<p><span class='ok'>✓ Connexion BD OK - requête simple fonctionne</span></p>";
                } catch (Exception $e) {
                    echo "<p><span class='error'>✗ Erreur requête BD:</span></p>";
                    echo "<pre>" . e($e->getMessage()) . "</pre>";
                }
            } else {
                echo "<p><span class='warning'>⚠ \$pdo n'est pas initialisé</span></p>";
            }
        } catch (Exception $e) {
            echo "<p><span class='error'>✗ Erreur au chargement de database.php:</span></p>";
            echo "<pre>" . e($e->getMessage()) . "</pre>";
        }
    } else {
        echo "<p><span class='error'>✗ config.php n'a pas été chargé - impossible de tester</span></p>";
    }
} else {
    echo "<p><span class='error'>✗ database.php n'existe pas!</span></p>";
}

echo "</div>";

// ============================================================================
// 4. TEST PROTECTION_PAGES.PHP
// ============================================================================
echo "<div class='card'>
    <h2>4️⃣ Vérification de protection_pages.php</h2>";

if (file_exists($root . '/protection_pages.php')) {
    echo "<p><span class='ok'>✓ protection_pages.php existe</span></p>";
} else {
    echo "<p><span class='error'>✗ protection_pages.php n'existe pas!</span></p>";
    echo "<p style='color: red;'><strong>ACTION REQUISE:</strong> Upload protection_pages.php à la racine</p>";
}

echo "</div>";

// ============================================================================
// 5. TEST SESSION
// ============================================================================
echo "<div class='card'>
    <h2>5️⃣ Vérification Session PHP</h2>
    <table>
        <tr><td>session_status()</td><td><code>" . session_status() . " (" . ['disabled', 'none', 'active'][session_status()] . ")</code></td></tr>
        <tr><td>session_id()</td><td><code>" . (session_id() ?: 'PAS INITIALISÉE') . "</code></td></tr>
        <tr><td>PHP Version</td><td><code>" . phpversion() . "</code></td></tr>
    </table>
</div>";

// ============================================================================
// 6. RÉSUMÉ
// ============================================================================
echo "<div class='card' style='background: #ffe5e5;'>
    <h2>📋 Résumé et Actions</h2>
    <p><strong>Si tu vois des ✗ rouge ci-dessus:</strong></p>
    <ul>
        <li>✗ Fichier MANQUANT → <strong>Upload le fichier sur Hostinger</strong></li>
        <li>✗ Erreur dans config.php → <strong>Copie-moi l'erreur exacte pour la corriger</strong></li>
        <li>✗ BD non connectée → <strong>Vérifie credentials et que la BD existe</strong></li>
    </ul>
    <p><strong>Si tout est ✓ vert:</strong></p>
    <ul>
        <li>Essaie de te connecter sur https://storesuite.shop/login.php</li>
        <li>Si ça marche: accueil.php devrait marcher aussi</li>
    </ul>
</div>";

echo "</body></html>";

/**
 * Fonction de sécurité (au cas où e() n'existe pas)
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
