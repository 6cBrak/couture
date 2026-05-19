<?php
/**
 * ENDPOINT: Supprimer définitivement une vente (ADMIN ONLY)
 * Supprime la vente et ses détails de la base de données
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Vérifier que l'utilisateur est admin
    if (!$is_admin) {
        throw new Exception('Accès refusé. Réservé aux administrateurs.');
    }
    
    if (empty($_POST['id_vente'])) {
        throw new Exception('ID vente manquant');
    }
    
    $id_vente = intval($_POST['id_vente']);
    
    // Vérifier que la vente existe
    $vente = db_fetch_one("
        SELECT * FROM ventes WHERE id_vente = ?
    ", [$id_vente]);
    
    if (!$vente) {
        throw new Exception('Vente non trouvée');
    }
    
    // Vérifier que la vente est bien annulée
    if ($vente['statut'] != 'annulee') {
        throw new Exception('Seules les ventes annulées peuvent être supprimées. Annulez d\'abord la vente.');
    }
    
    // Commencer la transaction
    db_begin_transaction();
    
    try {
        // Supprimer les mouvements de stock liés
        db_execute(
            "DELETE FROM mouvements_stock WHERE motif LIKE ?",
            ['%' . $vente['numero_facture'] . '%']
        );
        
        // Supprimer les détails de la vente
        db_execute(
            "DELETE FROM details_vente WHERE id_vente = ?",
            [$id_vente]
        );
        
        // Supprimer la vente
        db_execute(
            "DELETE FROM ventes WHERE id_vente = ?",
            [$id_vente]
        );
        
        // Log de l'activité
        log_activity('VENTE_SUPPRIMEE', "Vente supprimée définitivement: {$vente['numero_facture']}", [
            'id_vente' => $id_vente,
            'numero_facture' => $vente['numero_facture'],
            'montant' => $vente['montant_total']
        ]);
        
        db_commit();
        
        $response = [
            'success' => true,
            'message' => "Vente {$vente['numero_facture']} supprimée définitivement."
        ];
        
    } catch (Exception $e) {
        db_rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
