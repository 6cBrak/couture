<?php
/**
 * AJAX endpoint - Supprimer un mouvement de stock
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

// Seuls les admins peuvent supprimer
if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    $rawId = $_POST['id_mouvement'] ?? null;
    if (is_array($rawId)) {
        $rawId = reset($rawId); // prendre le premier élément si tableau
    }
    error_log('DELETE raw id_mouvement=' . print_r($rawId, true));

    if (!is_numeric($rawId)) {
        throw new Exception('ID mouvement invalide');
    }

    $id_mouvement = (int) $rawId;
    
    if (empty($id_mouvement)) {
        throw new Exception('ID mouvement manquant');
    }
    
    // Récupérer le mouvement avant suppression pour recalculer le stock
    $mouvement = db_fetch_one("
        SELECT * FROM mouvements_stock WHERE id_mouvement = ?
    ", [$id_mouvement]);
    
    if (!$mouvement) {
        throw new Exception('Mouvement introuvable');
    }
    
    db_begin_transaction();
    
    // Supprimer le mouvement (WHERE paramétré)
    $deleted = db_delete('mouvements_stock', 'id_mouvement = ?', [$id_mouvement]);
    
    if (!$deleted) {
        throw new Exception('Erreur lors de la suppression');
    }
    
    // Inverser l'impact sur le stock
    if ($mouvement['type_mouvement'] === 'transfert') {
        // Pour un transfert, inverser les deux dépôts
        db_query("
            UPDATE stock_par_depot 
            SET quantite = quantite + ? 
            WHERE id_produit = ? AND id_depot = ?
        ", [abs($mouvement['quantite']), $mouvement['id_produit'], $mouvement['id_depot_source']]);
        
        db_query("
            UPDATE stock_par_depot 
            SET quantite = quantite - ? 
            WHERE id_produit = ? AND id_depot = ?
        ", [abs($mouvement['quantite']), $mouvement['id_produit'], $mouvement['id_depot_destination']]);
    } else {
        // Pour les autres types, inverser simplement la quantité
        $depot = $mouvement['id_depot_source'] ?: $mouvement['id_depot_destination'];
        if ($depot) {
            db_query("
                UPDATE stock_par_depot 
                SET quantite = quantite - ? 
                WHERE id_produit = ? AND id_depot = ?
            ", [$mouvement['quantite'], $mouvement['id_produit'], $depot]);
        }
    }
    
    db_commit();
    
    $response = [
        'success' => true,
        'message' => 'Mouvement supprimé avec succès'
    ];
    
} catch (Exception $e) {
    if (db_in_transaction()) {
        db_rollback();
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
