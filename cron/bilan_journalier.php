<?php
/**
 * BILAN JOURNALIER — Planificateur de tâches Windows, 20h00
 * Commande : C:\xampp\php\php.exe C:\xampp\htdocs\couture\cron\bilan_journalier.php
 */
define('CLI_MODE', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/BilanMailer.php';

try {
    $pdo    = get_pdo();
    $config = $pdo->query("SELECT * FROM configuration WHERE id_config=1")->fetch(PDO::FETCH_ASSOC);

    if (empty($config['bilan_actif']))    exit("Bilan désactivé.\n");
    if (empty($config['email_bilan']))    exit("Email destinataire non configuré.\n");
    if (empty($config['smtp_host']))      exit("SMTP non configuré.\n");

    $result = envoyer_bilan($pdo, $config);
    echo "[OK] " . $result['message'] . "\n";
} catch (Exception $e) {
    echo "[ERREUR] " . $e->getMessage() . "\n";
    exit(1);
}
