<?php
/**
 * ENDPOINT: Annuler une vente
 * Restaure le stock et change le statut
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if (empty($_POST['id_vente'])) {
        throw new Exception('ID vente manquant');
    }
    
    $id_vente = intval($_POST['id_vente']);
    
    // Vérifier que la vente existe et appartient au vendeur
    $vente = db_fetch_one("
        SELECT * FROM ventes 
        WHERE id_vente = ? AND id_vendeur = ?
    ", [$id_vente, $user_id]);
    
    if (!$vente) {
        throw new Exception('Vente non trouvée ou accès refusé');
    }
    
    if ($vente['statut'] == 'annulee') {
        throw new Exception('Cette vente est déjà annulée');
    }
    
    // Récupérer les détails pour restaurer le stock
    $details = db_fetch_all("
        SELECT * FROM details_vente WHERE id_vente = ?
    ", [$id_vente]);
    
    // Commencer la transaction
    db_begin_transaction();
    
    try {
        // Restaurer le stock pour chaque produit
        foreach ($details as $detail) {
            // Récupérer le stock actuel avant modification
            $produit_current = db_fetch_one(
                "SELECT quantite_stock FROM produits WHERE id_produit = ?",
                [$detail['id_produit']]
            );
            $stock_avant = $produit_current['quantite_stock'];
            
            // Mettre à jour le stock
            db_execute(
                "UPDATE produits SET quantite_stock = quantite_stock + ? WHERE id_produit = ?",
                [$detail['quantite'], $detail['id_produit']]
            );
            
            $stock_apres = $stock_avant + $detail['quantite'];
            
            // Enregistrer le mouvement de stock
            db_insert('mouvements_stock', [
                'id_produit' => $detail['id_produit'],
                'type_mouvement' => 'entree',
                'quantite' => $detail['quantite'],
                'quantite_avant' => $stock_avant,
                'quantite_apres' => $stock_apres,
                'motif' => 'Annulation vente ' . $vente['numero_facture'],
                'id_utilisateur' => $user_id,
                'date_mouvement' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Marquer la vente comme annulée
        db_execute(
            "UPDATE ventes SET statut = ? WHERE id_vente = ?",
            ['annulee', $id_vente]
        );
        
        // Log de l'activité
        log_activity('VENTE_ANNULEE', "Vente annulée: {$vente['numero_facture']}", [
            'id_vente' => $id_vente,
            'numero_facture' => $vente['numero_facture'],
            'montant' => $vente['montant_total']
        ]);
        
        db_commit();
        
        $response = [
            'success' => true,
            'message' => "Vente {$vente['numero_facture']} annulée avec succès. Stock restauré."
        ];
        
    } catch (Exception $e) {
        db_rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
