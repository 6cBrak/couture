<?php
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$response = ['success' => false, 'data' => null];

try {
    $id_produit = $_POST['id_produit'] ?? null;
    
    if (!$id_produit) {
        throw new Exception('Produit non spécifié');
    }
    
    // Récupérer les infos du produit
    $produit = db_fetch_one("
        SELECT 
            p.*,
            c.nom_categorie,
            f.nom_fournisseur
        FROM produits p
        LEFT JOIN categories c ON p.id_categorie = c.id_categorie
        LEFT JOIN fournisseurs f ON p.id_fournisseur_principal = f.id_fournisseur
        WHERE p.id_produit = ?
        AND p.est_actif = 1
    ", [$id_produit]);
    
    if (!$produit) {
        throw new Exception('Produit non trouvé');
    }
    
    // Récupérer le stock par dépôt
    $stock_par_depot = db_fetch_all("
        SELECT 
            d.id_depot,
            d.nom_depot,
            COALESCE(spd.quantite, 0) as quantite
        FROM depots d
        LEFT JOIN stock_par_depot spd ON d.id_depot = spd.id_depot AND spd.id_produit = ?
        WHERE d.est_actif = 1
        ORDER BY d.est_principal DESC, d.nom_depot ASC
    ", [$id_produit]);
    
    // Calculer le stock total
    $stock_total = array_sum(array_column($stock_par_depot, 'quantite'));
    
    // Vérifier les seuils d'alerte
    $alerte = '';
    if ($stock_total <= $produit['seuil_critique']) {
        $alerte = 'critique';
    } elseif ($stock_total <= $produit['seuil_alerte']) {
        $alerte = 'alerte';
    }
    
    $response = [
        'success' => true,
        'data' => [
            'id_produit' => $produit['id_produit'],
            'nom_produit' => $produit['nom_produit'],
            'code_produit' => $produit['code_produit'],
            'prix_vente' => $produit['prix_vente'],
            'prix_vente_min' => $produit['prix_vente_min'],
            'prix_achat' => $produit['prix_achat'] ?? 0,
            'quantite_stock' => $produit['quantite_stock'],
            'stock_total' => $stock_total,
            'seuil_alerte' => $produit['seuil_alerte'],
            'seuil_critique' => $produit['seuil_critique'],
            'categorie' => $produit['nom_categorie'] ?? '-',
            'fournisseur' => $produit['nom_fournisseur'] ?? '-',
            'unite_mesure' => $produit['unite_mesure'],
            'alerte_niveau' => $alerte,
            'stock_par_depot' => $stock_par_depot
        ]
    ];
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
