<?php
// Test rapide de connexion BD (à supprimer après diagnostic)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';

echo "<pre>Connexion BD réussie ✔\n";
// Tester une requête simple
try {
    $stmt = $pdo->query('SELECT NOW() as now');
    $row = $stmt->fetch();
    echo "Ping SQL OK : " . ($row['now'] ?? 'n/a') . "\n";
} catch (Exception $e) {
    echo "Requête test échouée : " . htmlspecialchars($e->getMessage()) . "\n";
}

echo "Utilisateur BD : " . htmlspecialchars(DB_USER) . "\n";
echo "Base BD       : " . htmlspecialchars(DB_NAME) . "\n";
echo "Hôte          : " . htmlspecialchars(DB_HOST) . "\n";
?>
