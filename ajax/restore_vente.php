<?php
/**
 * AJAX - RESTAURER UNE VENTE ANNULÉE
 * Remet une vente annulée en statut 'validee'
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $id_vente = intval($_POST['id_vente'] ?? 0);
    if (!$id_vente) {
        throw new Exception('ID vente manquant');
    }
    
    // Vérifier que l'utilisateur est admin ou propriétaire de la vente
    $vente = db_fetch_one("SELECT * FROM ventes WHERE id_vente = ? AND statut = 'annulee'", [$id_vente]);
    if (!$vente) {
        throw new Exception('Vente non trouvée ou non annulée');
    }
    
    // Vérifier permission : admin ou vendeur propriétaire
    if (!$is_admin && $vente['id_vendeur'] != $user_id) {
        throw new Exception('Vous n\'avez pas la permission de restaurer cette vente');
    }
    
    // Vérifier que le stock actuel permet la restauration
    $details = db_fetch_all("SELECT * FROM details_vente WHERE id_vente = ?", [$id_vente]);
    
    db_begin_transaction();
    
    // Déduire le stock des produits (inverse de l'annulation)
    foreach ($details as $detail) {
        $produit = db_fetch_one("SELECT quantite_stock FROM produits WHERE id_produit = ?", [$detail['id_produit']]);
        
        // Vérifier si on a assez de stock pour soustraire
        if ($produit['quantite_stock'] < $detail['quantite']) {
            throw new Exception("Stock insuffisant pour restaurer la vente (produit : {$detail['id_produit']})");
        }
        
        // Mettre à jour le stock
        db_execute("UPDATE produits SET quantite_stock = quantite_stock - ? WHERE id_produit = ?", [
            $detail['quantite'],
            $detail['id_produit']
        ]);
        
        // Enregistrer le mouvement de stock
        $quantite_avant = $produit['quantite_stock'];
        $quantite_apres = $quantite_avant - $detail['quantite'];
        
        db_execute("INSERT INTO mouvements_stock (
            id_produit,
            type_mouvement,
            quantite,
            quantite_avant,
            quantite_apres,
            id_utilisateur,
            motif,
            date_mouvement
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())", [
            $detail['id_produit'],
            'sortie',
            $detail['quantite'],
            $quantite_avant,
            $quantite_apres,
            $user_id,
            "Restauration vente - Facture " . $vente['numero_facture']
        ]);
    }
    
    // Restaurer la vente au statut validée
    db_execute("UPDATE ventes SET statut = 'validee' WHERE id_vente = ?", [$id_vente]);
    
    // Logger l'activité
    log_activity('vente_restauree', "Restauration de la vente {$vente['numero_facture']}", [
        'id_vente' => $id_vente,
        'numero_facture' => $vente['numero_facture']
    ]);
    
    db_commit();
    
    $response['success'] = true;
    $response['message'] = 'Vente restaurée avec succès. Le stock a été rétabli.';
    
} catch (Exception $e) {
    if (db_in_transaction()) db_rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
