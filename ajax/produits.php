<?php
/**
 * AJAX - GESTION DES PRODUITS
 * Ajouter, modifier, supprimer des produits
 */
require_once '../protection_pages.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'add_product':
            $nom            = trim($_POST['product_name'] ?? $_POST['nom_produit'] ?? '');
            $id_categorie   = $_POST['product_category'] ?? $_POST['id_categorie'] ?? null;
            $id_fournisseur = $_POST['product_fournisseur'] ?? null;
            $id_depot       = $_POST['product_depot'] ?? null;
            $prix_achat     = floatval($_POST['product_purchase_price'] ?? $_POST['prix_achat'] ?? 0);
            $prix_vente     = floatval($_POST['product_sale_price'] ?? $_POST['prix_vente'] ?? 0);
            $quantite_stock = intval($_POST['product_stock'] ?? $_POST['quantite_stock'] ?? 0);
            $seuil_alerte   = intval($_POST['product_min_stock'] ?? $_POST['seuil_alerte'] ?? 10);
            $seuil_critique = intval($seuil_alerte / 2);
            $description    = trim($_POST['product_description'] ?? $_POST['description'] ?? '');
            $code_produit   = trim($_POST['product_barcode'] ?? '');   // ✅ code_produit dans la BDD
            $unite_mesure   = trim($_POST['product_unit'] ?? 'pièce');
            $type_produit   = in_array($_POST['product_type'] ?? '', ['standard','service']) ? $_POST['product_type'] : 'standard';

            if (empty($nom)) {
                throw new Exception('Le nom du produit est obligatoire');
            }
            if ($prix_vente <= 0) {
                throw new Exception('Le prix de vente doit être supérieur à 0');
            }
            if ($type_produit === 'standard' && empty($id_fournisseur)) {
                throw new Exception('Le fournisseur est obligatoire pour un produit standard');
            }
            if ($type_produit === 'standard' && empty($id_depot)) {
                throw new Exception('Le dépôt initial est obligatoire pour un produit standard');
            }

            // Normaliser les nulls
            $id_categorie   = ($id_categorie   && $id_categorie   !== '0') ? intval($id_categorie)   : null;
            $id_fournisseur = ($id_fournisseur  && $id_fournisseur !== '0') ? intval($id_fournisseur) : null;
            $id_depot       = ($id_depot        && $id_depot       !== '0') ? intval($id_depot)       : null;
            $code_produit   = $code_produit !== '' ? $code_produit : null;

            db_begin_transaction();
            try {
                // Services: no physical stock
                if ($type_produit === 'service') {
                    $quantite_stock = 0;
                    $seuil_alerte   = 0;
                    $seuil_critique = 0;
                    $id_depot       = null;
                }

                // ✅ Colonnes exactes de la table produits (DESCRIBE produits)
                $sql = "INSERT INTO produits
                            (nom_produit, description, id_categorie, id_fournisseur_principal,
                             prix_achat, prix_vente, quantite_stock,
                             seuil_alerte, seuil_critique,
                             code_produit, unite_mesure, type_produit, est_actif)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
                db_execute($sql, [
                    $nom, $description, $id_categorie, $id_fournisseur,
                    $prix_achat, $prix_vente, $quantite_stock,
                    $seuil_alerte, $seuil_critique,
                    $code_produit, $unite_mesure, $type_produit
                ]);

                $id_produit = $pdo->lastInsertId();

                // Créer l'entrée stock_par_depot (uniquement pour les produits standard)
                if ($type_produit === 'standard' && $id_depot) {
                    $sql_stock = "INSERT INTO stock_par_depot (id_produit, id_depot, quantite, seuil_alerte)
                                  VALUES (?, ?, ?, ?)";
                    db_execute($sql_stock, [$id_produit, $id_depot, $quantite_stock, $seuil_alerte]);
                }

                // Enregistrer le mouvement de stock initial si quantité > 0
                if ($type_produit === 'standard' && $quantite_stock > 0) {
                    $sql_mvt = "INSERT INTO mouvements_stock
                                    (id_produit, type_mouvement, quantite,
                                     quantite_avant, quantite_apres,
                                     id_depot_source, id_fournisseur,
                                     id_utilisateur, motif, date_mouvement)
                                VALUES (?, 'entree', ?, 0, ?, ?, ?, ?, 'Stock initial lors de la création du produit', NOW())";
                    db_execute($sql_mvt, [
                        $id_produit, $quantite_stock, $quantite_stock,
                        $id_depot, $id_fournisseur, $user_id
                    ]);
                }

                db_commit();

                $response['success']    = true;
                $response['message']    = 'Produit ajouté avec succès';
                $response['id_produit'] = $id_produit;

            } catch (Exception $e) {
                db_rollback();
                throw $e;
            }
            break;

        case 'update_product':
            $id_produit     = intval($_POST['id_produit'] ?? $_POST['product_id'] ?? 0);
            $nom            = trim($_POST['product_name'] ?? $_POST['nom_produit'] ?? '');
            $id_categorie   = $_POST['product_category'] ?? $_POST['id_categorie'] ?? null;
            $id_fournisseur = $_POST['product_fournisseur'] ?? null;
            $prix_achat     = floatval($_POST['product_purchase_price'] ?? $_POST['prix_achat'] ?? 0);
            $prix_vente     = floatval($_POST['product_sale_price'] ?? $_POST['prix_vente'] ?? 0);
            $seuil_alerte   = intval($_POST['product_min_stock'] ?? $_POST['seuil_alerte'] ?? 10);
            $seuil_critique = intval($seuil_alerte / 2);
            $description    = trim($_POST['product_description'] ?? $_POST['description'] ?? '');
            $code_produit   = trim($_POST['product_barcode'] ?? '');
            $unite_mesure   = trim($_POST['product_unit'] ?? 'pièce');
            $type_produit   = in_array($_POST['product_type'] ?? '', ['standard','service']) ? $_POST['product_type'] : 'standard';

            if (!$id_produit) {
                throw new Exception('ID produit manquant');
            }
            if (empty($nom)) {
                throw new Exception('Le nom du produit est obligatoire');
            }

            // Normaliser les nulls
            $id_categorie   = ($id_categorie   && $id_categorie   !== '0') ? intval($id_categorie)   : null;
            $id_fournisseur = ($id_fournisseur  && $id_fournisseur !== '0') ? intval($id_fournisseur) : null;
            $code_produit   = $code_produit !== '' ? $code_produit : null;

            // Services: reset stock thresholds
            if ($type_produit === 'service') {
                $seuil_alerte   = 0;
                $seuil_critique = 0;
            }

            // ✅ Colonnes exactes de la table produits
            $sql = "UPDATE produits
                    SET nom_produit              = ?,
                        description              = ?,
                        id_categorie             = ?,
                        id_fournisseur_principal = ?,
                        prix_achat               = ?,
                        prix_vente               = ?,
                        seuil_alerte             = ?,
                        seuil_critique           = ?,
                        code_produit             = ?,
                        unite_mesure             = ?,
                        type_produit             = ?
                    WHERE id_produit = ?";
            db_execute($sql, [
                $nom, $description, $id_categorie, $id_fournisseur,
                $prix_achat, $prix_vente,
                $seuil_alerte, $seuil_critique,
                $code_produit, $unite_mesure, $type_produit,
                $id_produit
            ]);

            $response['success'] = true;
            $response['message'] = 'Produit modifié avec succès';
            break;

        case 'delete':
        case 'delete_product':
            $id_produit = intval($_POST['id'] ?? $_POST['id_produit'] ?? 0);

            if (!$id_produit) {
                throw new Exception('ID produit manquant');
            }

            // Vérifier le stock total dans tous les dépôts
            $stock_total = db_fetch_one("
                SELECT COALESCE(SUM(quantite), 0) as total
                FROM stock_par_depot
                WHERE id_produit = ?
            ", [$id_produit]);

            if (($stock_total['total'] ?? 0) > 0) {
                throw new Exception("Impossible de supprimer un produit qui a du stock. Videz d'abord tous les dépôts.");
            }

            // Vérifier s'il y a des ventes associées
            $ventes = db_fetch_one("SELECT COUNT(*) as nb FROM details_vente WHERE id_produit = ?", [$id_produit]);

            db_begin_transaction();
            try {
                if (($ventes['nb'] ?? 0) > 0) {
                    db_execute("UPDATE produits SET est_actif = 0 WHERE id_produit = ?", [$id_produit]);
                    $response['message'] = 'Produit désactivé (a des ventes historiques)';
                } else {
                    db_execute("DELETE FROM stock_par_depot WHERE id_produit = ?", [$id_produit]);
                    db_execute("DELETE FROM mouvements_stock WHERE id_produit = ?", [$id_produit]);
                    db_execute("DELETE FROM produits WHERE id_produit = ?", [$id_produit]);
                    $response['message'] = 'Produit supprimé avec succès';
                }
                db_commit();
                $response['success'] = true;
            } catch (Exception $e) {
                db_rollback();
                throw $e;
            }
            break;

        case 'adjust_stock':
            $id_produit     = intval($_POST['stock_product_id'] ?? $_POST['id_produit'] ?? 0);
            $type_mouvement = $_POST['stock_type'] ?? $_POST['type_mouvement'] ?? 'entree';
            $quantite       = intval($_POST['stock_quantity'] ?? $_POST['quantite'] ?? 0);
            $motif          = trim($_POST['stock_reason'] ?? $_POST['motif'] ?? '');

            if (!$id_produit || $quantite <= 0) {
                throw new Exception('Données invalides');
            }

            $produit = db_fetch_one("SELECT quantite_stock FROM produits WHERE id_produit = ?", [$id_produit]);
            if (!$produit) {
                throw new Exception('Produit introuvable');
            }

            $stock_avant   = $produit['quantite_stock'];
            $nouveau_stock = ($type_mouvement === 'entree')
                ? $stock_avant + $quantite
                : $stock_avant - $quantite;

            if ($nouveau_stock < 0) {
                throw new Exception('Stock insuffisant');
            }

            db_begin_transaction();
            try {
                db_execute("UPDATE produits SET quantite_stock = ? WHERE id_produit = ?", [$nouveau_stock, $id_produit]);

                // ✅ Table mouvements_stock avec les bonnes colonnes
                $sql = "INSERT INTO mouvements_stock
                            (id_produit, type_mouvement, quantite,
                             quantite_avant, quantite_apres,
                             id_utilisateur, motif, date_mouvement)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                db_execute($sql, [
                    $id_produit, $type_mouvement, $quantite,
                    $stock_avant, $nouveau_stock,
                    $user_id, $motif
                ]);

                db_commit();

                $response['success']       = true;
                $response['message']       = 'Stock ajusté avec succès';
                $response['nouveau_stock'] = $nouveau_stock;

            } catch (Exception $e) {
                db_rollback();
                throw $e;
            }
            break;

        case 'get_product':
            $id_produit = intval($_POST['id_produit'] ?? 0);
            if (!$id_produit) throw new Exception('ID produit manquant');

            $produit = db_fetch_one("
                SELECT p.*, c.nom_categorie
                FROM produits p
                LEFT JOIN categories c ON p.id_categorie = c.id_categorie
                WHERE p.id_produit = ?
            ", [$id_produit]);

            if (!$produit) throw new Exception('Produit introuvable');

            $response['success'] = true;
            $response['produit'] = $produit;
            break;

        default:
            throw new Exception('Action non reconnue : ' . htmlspecialchars($action));
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>