<?php
/**
 * AJAX endpoint - Get list of depots for dropdown selection
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'depots' => []];

try {
    // Get all active depots
    $depots = db_fetch_all("
        SELECT 
            id_depot,
            nom_depot,
            description,
            est_principal
        FROM depots
        WHERE est_actif = 1
        ORDER BY est_principal DESC, nom_depot ASC
    ");
    
    if (!$depots) {
        throw new Exception('Aucun dépôt trouvé');
    }
    
    $response['success'] = true;
    $response['depots'] = $depots;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
