<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Vérifier config.php</h1><pre>";

// Vérifier quel fichier config existe
if (file_exists('config/config.php')) {
    echo "✓ config/config.php EXISTE\n\n";
    
    // Lire le fichier
    $content = file_get_contents('config/config.php');
    
    // Vérifier les constantes
    preg_match("/define\('DB_HOST',\s*'([^']+)'\)/", $content, $host);
    preg_match("/define\('DB_NAME',\s*'([^']+)'\)/", $content, $name);
    preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $content, $user);
    
    echo "DB_HOST: " . ($host[1] ?? 'NOT FOUND') . "\n";
    echo "DB_NAME: " . ($name[1] ?? 'NOT FOUND') . "\n";
    echo "DB_USER: " . ($user[1] ?? 'NOT FOUND') . "\n";
    echo "BASE_URL: ";
    preg_match("/define\('BASE_URL',\s*'([^']+)'\)/", $content, $url);
    echo ($url[1] ?? 'NOT FOUND') . "\n";
    
} else if (file_exists('config/config.online.php')) {
    echo "✗ config/config.php N'EXISTE PAS\n";
    echo "✓ Mais config/config.online.php EXISTE\n";
    echo "\nACTION: Renommez config/config.online.php → config/config.php sur le serveur!\n";
} else {
    echo "✗ AUCUN config.php NI config.online.php TROUVÉ\n";
}

echo "</pre>";
?>
