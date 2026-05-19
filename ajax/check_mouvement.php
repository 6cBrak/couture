<?php
/**
 * AJAX endpoint - Vérifier si un mouvement vient d'une vente
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$response = ['est_vente' => false, 'message' => ''];

try {
    $rawId = $_POST['id_mouvement'] ?? null;
    if (is_array($rawId)) {
        $rawId = reset($rawId); // prendre le premier élément
    }
    error_log('CHECK raw id_mouvement=' . print_r($rawId, true));

    if (!is_numeric($rawId)) {
        throw new Exception('ID mouvement invalide');
    }

    $id_mouvement = (int) $rawId;
    
    if (empty($id_mouvement)) {
        throw new Exception('ID mouvement requis');
    }
    
    // Récupérer le mouvement
    $mouvement = db_fetch_one("
        SELECT motif FROM mouvements_stock 
        WHERE id_mouvement = ?
    ", [$id_mouvement]);
    
    if (!$mouvement) {
        throw new Exception('Mouvement non trouvé');
    }
    
    // Vérifier si c'est un mouvement de vente
    // Un mouvement de vente contient "Vente" ou "Annulation vente" dans le motif
    $motif = strtolower($mouvement['motif'] ?? '');
    $est_vente = (
        strpos($motif, 'vente') !== false || 
        strpos($motif, 'facture') !== false ||
        strpos($motif, 'annulation') !== false
    );
    
    $response['est_vente'] = $est_vente;
    
    if ($est_vente) {
        $response['message'] = 'Ce mouvement provient d\'une vente et ne peut pas être supprimé';
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
