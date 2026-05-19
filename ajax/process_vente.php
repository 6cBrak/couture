<?php
/**
 * ENDPOINT: Validation et sauvegarde d'une vente
 * POST: cart (JSON array), id_client (optionnel)
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'id_vente' => null];

try {
    // Validation des données
    if (empty($_POST['cart'])) {
        throw new Exception('Panier vide');
    }
    
    $cart = json_decode($_POST['cart'], true);
    if (!is_array($cart) || empty($cart)) {
        throw new Exception('Données panier invalides');
    }
    
    $id_client = !empty($_POST['id_client']) ? intval($_POST['id_client']) : null;
    
    // TVA
    $avec_tva = !empty($_POST['avec_tva']) && $_POST['avec_tva'] === '1';
    $taux_tva_pct = isset($config['taux_tva']) ? (float)$config['taux_tva'] : 0.00;
    $taux_tva_coef = $taux_tva_pct / 100;

    // Récupérer le mode de paiement
    $mode_paiement = !empty($_POST['mode_paiement']) ? $_POST['mode_paiement'] : 'especes';
    $modes_valides = ['especes', 'carte', 'mobile_money', 'cheque', 'credit'];
    if (!in_array($mode_paiement, $modes_valides)) {
        throw new Exception('Mode de paiement invalide');
    }

    // Données spécifiques au crédit
    $acompte       = 0;
    $date_echeance = null;
    if ($mode_paiement === 'credit') {
        $acompte       = max(0, floatval($_POST['acompte'] ?? 0));
        $date_echeance = !empty($_POST['date_echeance']) ? $_POST['date_echeance'] : null;
        // Un client est obligatoire pour une vente à crédit
        if (empty($id_client)) {
            throw new Exception('Un client doit être sélectionné pour une vente à crédit');
        }
    }
    
    // Vérifier le stock pour chaque produit
    $stocks = [];
    foreach ($cart as $item) {
        $produit = db_fetch_one(
            "SELECT id_produit, quantite_stock, type_produit, prix_achat FROM produits WHERE id_produit = ?",
            [$item['id']]
        );

        if (!$produit) {
            throw new Exception("Produit {$item['nom']} non trouvé");
        }

        // Les services n'ont pas de stock physique
        if (($produit['type_produit'] ?? 'standard') === 'standard') {
            if ($produit['quantite_stock'] < $item['quantite']) {
                throw new Exception("Stock insuffisant pour {$item['nom']} (disponible: {$produit['quantite_stock']})");
            }
        }

        $stocks[$item['id']]     = $produit['quantite_stock'];
        $types[$item['id']]      = $produit['type_produit'] ?? 'standard';
        $prix_achats[$item['id']] = (float)($produit['prix_achat'] ?? 0);
    }
    
    // Générer le numéro de facture
    $last_facture = db_fetch_one(
        "SELECT numero_facture FROM ventes ORDER BY id_vente DESC LIMIT 1"
    );
    
    $numero_facture = 'FAC-' . date('Ymd') . '-' . str_pad(
        (int)substr($last_facture['numero_facture'] ?? 'FAC-00000000-0000', -4) + 1, 
        4, 
        '0', 
        STR_PAD_LEFT
    );
    
    // Calculer les montants (prix saisis en HT, TVA ajoutée en option)
    $montant_ht = 0;
    foreach ($cart as $item) {
        $montant_ht += $item['prix'] * $item['quantite'];
    }
    $montant_ht = round($montant_ht, 2);
    $montant_tva = $avec_tva ? round($montant_ht * $taux_tva_coef, 2) : 0;
    $montant_total = round($montant_ht + $montant_tva, 2);
    $montant_remise = 0; // TODO: Ajouter support des remises
    
    // Vérifier que la caisse de l'utilisateur courant est ouverte
    $caisse_check = db_fetch_one("SELECT id_caisse FROM caisse WHERE statut = 'ouverte' AND id_utilisateur = ? ORDER BY date_ouverture DESC LIMIT 1", [$user_id]);
    if (!$caisse_check) {
        throw new Exception('La caisse est fermée. Veuillez ouvrir la caisse avant d\'effectuer une vente.');
    }

    // Commencer la transaction
    db_begin_transaction();
    
    try {
        // Créer la vente
        $id_vente = db_insert('ventes', [
            'numero_facture' => $numero_facture,
            'id_client'      => $id_client,
            'id_vendeur'     => $user_id,
            'montant_ht'     => $montant_ht,
            'montant_tva'    => $montant_tva,
            'montant_remise' => $montant_remise,
            'montant_paye'   => ($mode_paiement === 'credit') ? $acompte : 0,
            'montant_rendu'  => 0,
            'montant_total'  => $montant_total,
            'mode_paiement'  => $mode_paiement,
            'date_echeance'  => $date_echeance,
            'statut'          => 'validee',
            'notes'           => '',
            'is_confection'   => intval($_POST['is_confection'] ?? 0),
            'type_confection' => (intval($_POST['is_confection'] ?? 0) && trim($_POST['type_confection'] ?? '') !== '')
                                  ? trim($_POST['type_confection']) : null,
            'date_vente'      => date('Y-m-d H:i:s'),
        ]);
        
        if (!$id_vente) {
            throw new Exception('Erreur lors de la création de la vente');
        }
        
        // Ajouter les détails de vente et mettre à jour le stock
        foreach ($cart as $item) {
            // Insérer le détail de vente
            $prix_achat_u = $prix_achats[$item['id']] ?? 0;
            $benefice_u   = ($item['prix'] - $prix_achat_u) * $item['quantite'];
            db_insert('details_vente', [
                'id_vente'            => $id_vente,
                'id_produit'          => $item['id'],
                'nom_produit'         => $item['nom'] ?? '',
                'quantite'            => $item['quantite'],
                'prix_unitaire'       => $item['prix'],
                'prix_achat_unitaire' => $prix_achat_u,
                'prix_total'          => $item['quantite'] * $item['prix'],
                'benefice_ligne'      => $benefice_u,
            ]);
            
            // Mettre à jour le stock uniquement pour les produits standard
            if (($types[$item['id']] ?? 'standard') === 'standard') {
                db_execute(
                    "UPDATE produits SET quantite_stock = quantite_stock - ? WHERE id_produit = ?",
                    [$item['quantite'], $item['id']]
                );

                $stock_avant = $stocks[$item['id']];
                $stock_apres = $stock_avant - $item['quantite'];

                db_insert('mouvements_stock', [
                    'id_produit'     => $item['id'],
                    'type_mouvement' => 'sortie',
                    'quantite'       => $item['quantite'],
                    'quantite_avant' => $stock_avant,
                    'quantite_apres' => $stock_apres,
                    'motif'          => 'Vente ' . $numero_facture,
                    'id_utilisateur' => $user_id,
                    'date_mouvement' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        // Enregistrer l'acompte crédit si présent
        if ($mode_paiement === 'credit' && $acompte > 0) {
            db_insert('paiements_credit', [
                'id_vente'       => $id_vente,
                'montant'        => $acompte,
                'mode_paiement'  => 'especes',
                'notes'          => 'Acompte initial',
                'id_utilisateur' => $user_id,
                'date_paiement'  => date('Y-m-d H:i:s'),
            ]);
        }

        // Enregistrer l'entrée dans la caisse si une caisse est ouverte
        $montant_caisse = ($mode_paiement === 'credit') ? $acompte : $montant_total;
        if ($montant_caisse > 0) {
            $caisse_ouverte = db_fetch_one(
                "SELECT id_caisse FROM caisse WHERE statut = 'ouverte' AND id_utilisateur = ? ORDER BY date_ouverture DESC LIMIT 1",
                [$user_id]
            );
            if ($caisse_ouverte) {
                $type_mvt = ($mode_paiement === 'especes' || $mode_paiement === 'credit') ? 'vente_especes' : 'entree_manuelle';
                db_insert('mouvements_caisse', [
                    'id_caisse'      => $caisse_ouverte['id_caisse'],
                    'type_mouvement' => $type_mvt,
                    'sens'           => 'entree',
                    'montant'        => $montant_caisse,
                    'description'    => 'Vente ' . $numero_facture,
                    'reference'      => $numero_facture,
                    'id_utilisateur' => $user_id,
                    'date_mouvement' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Enregistrer l'activité
        log_activity('VENTE', "Nouvelle vente créée: $numero_facture ($montant_total " . $devise . ")", [
            'id_vente' => $id_vente,
            'numero_facture' => $numero_facture,
            'montant' => $montant_total
        ]);
        
        db_commit();
        
        $response = [
            'success' => true,
            'message' => "Vente validée avec succès! N° facture: $numero_facture",
            'id_vente' => $id_vente,
            'numero_facture' => $numero_facture,
            'montant_total' => $montant_total
        ];
        
    } catch (Exception $e) {
        db_rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    $response['message'] = 'Erreur: ' . $e->getMessage();
}

echo json_encode($response);
