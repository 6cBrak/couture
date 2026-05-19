<?php
require_once __DIR__ . '/../protection_pages.php';
require_admin();
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../cron/BilanMailer.php';

    if (empty($config['smtp_host'])) throw new Exception('Serveur SMTP non configuré dans les paramètres.');
    if (empty($config['email_bilan'])) throw new Exception('Email destinataire non configuré dans les paramètres.');

    $result = envoyer_bilan($pdo, $config);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
