<?php
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => 'AccÃ¨s refusÃ©']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {

    // =========================
    // ðŸ”¹ RÃ‰CUPÃ‰RATION + CAST
    // =========================
    $type_mouvement = $_POST['type_mouvement'] ?? '';
    $id_produit = (int)($_POST['id_produit'] ?? 0);
    $id_depot_source = (int)($_POST['id_depot_source'] ?? 0);
    $id_depot_destination = $_POST['id_depot_destination'] ?? null;

    if ($type_mouvement !== 'transfert') {
        $id_depot_destination = null;
    } else {
        $id_depot_destination = (int)$id_depot_destination;
    }

    $quantite_input = (float)($_POST['quantite'] ?? 0);
    $cout_unitaire = $_POST['cout_unitaire'] !== '' ? (float)$_POST['cout_unitaire'] : null;
    $id_fournisseur = $_POST['id_fournisseur'] !== '' ? (int)$_POST['id_fournisseur'] : null;

    $notes = trim($_POST['motif'] ?? $_POST['notes'] ?? '');

    // =========================
    // ðŸ”¹ VALIDATION
    // =========================
    if (!$type_mouvement) throw new Exception('Type requis');
    if (!$id_produit) throw new Exception('Produit requis');
    if (!$id_depot_source) throw new Exception('DÃ©pÃ´t requis');
    if ($quantite_input <= 0) throw new Exception('QuantitÃ© invalide');
    if ($notes === '') throw new Exception('Motif requis');

    if ($type_mouvement === 'transfert') {
        if (!$id_depot_destination) throw new Exception('DÃ©pÃ´t destination requis');
        if ($id_depot_source === $id_depot_destination) {
            throw new Exception('MÃªme dÃ©pÃ´t interdit');
        }
    }

    // =========================
    // ðŸ”¹ STOCK AVANT
    // =========================
    $stock_avant_row = db_fetch_one("
        SELECT quantite FROM stock_par_depot
        WHERE id_produit = ? AND id_depot = ?
    ", [$id_produit, $id_depot_source]);

    $quantite_avant = $stock_avant_row ? (int)$stock_avant_row['quantite'] : 0;

    // =========================
    // ðŸ”¹ CALCUL MOUVEMENT
    // =========================
    $quantite_mouvement = 0;
    $quantite_apres = 0;

    switch ($type_mouvement) {

        case 'inventaire':
            $quantite_apres = $quantite_input;
            $quantite_mouvement = $quantite_apres - $quantite_avant;
            break;

        case 'ajustement':
            $quantite_mouvement = $quantite_input;
            $quantite_apres = $quantite_avant + $quantite_mouvement;
            break;

        case 'sortie':
        case 'perte':
            if ($quantite_input > $quantite_avant) {
                throw new Exception("Stock insuffisant !");
            }
            $quantite_mouvement = -$quantite_input;
            $quantite_apres = $quantite_avant + $quantite_mouvement;
            break;

        case 'transfert':
            if ($quantite_input > $quantite_avant) {
                throw new Exception("Stock insuffisant !");
            }
            $quantite_mouvement = -$quantite_input;
            $quantite_apres = $quantite_avant - $quantite_input;
            break;

        default: // entrÃ©e
            $quantite_mouvement = $quantite_input;
            $quantite_apres = $quantite_avant + $quantite_input;
            break;
    }

    // =========================
    // ðŸ”¹ COÃ›T
    // =========================
    $cout_total = $cout_unitaire !== null
        ? $cout_unitaire * abs($quantite_mouvement)
        : null;

    db_begin_transaction();

    // =========================
    // ðŸ”¹ INSERT MOUVEMENT
    // =========================
    db_query("
        INSERT INTO mouvements_stock
        (id_produit, type_mouvement, quantite, quantite_avant, quantite_apres,
         id_depot_source, id_depot_destination, id_fournisseur,
         cout_unitaire, cout_total, date_mouvement, id_utilisateur, motif)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
    ", [
        $id_produit,
        $type_mouvement,
        $quantite_mouvement,
        $quantite_avant,
        $quantite_apres,
        $id_depot_source,
        $id_depot_destination,
        $id_fournisseur,
        $cout_unitaire,
        $cout_total,
        $user_id,
        $notes
    ]);

    $id_mouvement = $pdo->lastInsertId();

    // =========================
    // ðŸ”¹ UPDATE STOCK
    // =========================
    if ($type_mouvement === 'transfert') {

        // source
        db_query("
            UPDATE stock_par_depot
            SET quantite = quantite - ?
            WHERE id_produit = ? AND id_depot = ?
        ", [$quantite_input, $id_produit, $id_depot_source]);

        // destination
        $exists = db_fetch_one("
            SELECT 1 FROM stock_par_depot
            WHERE id_produit = ? AND id_depot = ?
        ", [$id_produit, $id_depot_destination]);

        if ($exists) {
            db_query("
                UPDATE stock_par_depot
                SET quantite = quantite + ?
                WHERE id_produit = ? AND id_depot = ?
            ", [$quantite_input, $id_produit, $id_depot_destination]);
        } else {
            db_insert('stock_par_depot', [
                'id_produit' => $id_produit,
                'id_depot' => $id_depot_destination,
                'quantite' => $quantite_input
            ]);
        }

    } else {

        db_query("
            INSERT INTO stock_par_depot (id_produit, id_depot, quantite)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantite = quantite + VALUES(quantite)
        ", [$id_produit, $id_depot_source, $quantite_mouvement]);
    }

    db_commit();

    $response = [
        'success' => true,
        'message' => 'Mouvement enregistrÃ© avec succÃ¨s',
        'id_mouvement' => $id_mouvement
    ];

} catch (Exception $e) {

    if (db_in_transaction()) {
        db_rollback();
    }

    $response['message'] = $e->getMessage();
}

echo json_encode($response);