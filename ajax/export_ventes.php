<?php
require_once __DIR__ . '/../protection_pages.php';

try {
    // Récupérer les filtres
    $filter_date_from = isset($_POST['filter_date_from']) ? $_POST['filter_date_from'] : date('Y-m-01');
    $filter_date_to = isset($_POST['filter_date_to']) ? $_POST['filter_date_to'] : date('Y-m-d');
    $filter_client = isset($_POST['filter_client']) ? $_POST['filter_client'] : '';
    $filter_vendeur = isset($_POST['filter_vendeur']) ? $_POST['filter_vendeur'] : '';
    $filter_paiement = isset($_POST['filter_paiement']) ? $_POST['filter_paiement'] : '';
    $filter_statut = isset($_POST['filter_statut']) ? $_POST['filter_statut'] : '';
    $search_numero = isset($_POST['search_numero']) ? $_POST['search_numero'] : '';
    
    // Requête avec filtres
    $query = "
        SELECT v.*, 
               c.nom_client,
               u.nom_complet as vendeur
        FROM ventes v
        LEFT JOIN clients c ON v.id_client = c.id_client
        LEFT JOIN utilisateurs u ON v.id_vendeur = u.id_utilisateur
        WHERE 1=1
    ";
    
    if ($filter_date_from) $query .= " AND DATE(v.date_vente) >= '" . date('Y-m-d', strtotime($filter_date_from)) . "'";
    if ($filter_date_to) $query .= " AND DATE(v.date_vente) <= '" . date('Y-m-d', strtotime($filter_date_to)) . "'";
    if ($filter_client) $query .= " AND v.id_client = " . intval($filter_client);
    if ($filter_vendeur) $query .= " AND v.id_vendeur = " . intval($filter_vendeur);
    if ($filter_paiement) $query .= " AND v.mode_paiement = '" . $filter_paiement . "'";
    if ($filter_statut) $query .= " AND v.statut = '" . $filter_statut . "'";
    if ($search_numero) $query .= " AND v.numero_facture LIKE '%". str_replace("'", "''", $search_numero) ."%'";
    
    $query .= " ORDER BY v.date_vente DESC";
    
    $ventes = db_fetch_all($query);
    
    // En-têtes HTTP
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Ventes_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    // Créer le fichier CSV pour maintenant (compatible Excel)
    $output = fopen('php://output', 'w');
    
    // BOM UTF-8 pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // En-têtes
    $headers = ['N° Facture', 'Date', 'Client', 'Montant HT', 'TVA', 'Remise', 'Montant TTC', 'Mode Paiement', 'Statut', 'Vendeur'];
    fputcsv($output, $headers, ';');
    
    // Données
    $mode_labels = ['especes' => 'Espèces', 'carte' => 'Carte', 'mobile_money' => 'Mobile Money', 'cheque' => 'Chèque', 'credit' => 'Crédit'];
    $statut_labels = ['validee' => 'Validée', 'en_cours' => 'En cours', 'annulee' => 'Annulée'];
    
    foreach ($ventes as $vente) {
        $row = [
            $vente['numero_facture'],
            date('d/m/Y H:i', strtotime($vente['date_vente'])),
            $vente['nom_client'] ?: 'Vente comptoir',
            number_format($vente['montant_ht'], 2, ',', ' '),
            number_format($vente['montant_tva'], 2, ',', ' '),
            number_format($vente['montant_remise'], 2, ',', ' '),
            number_format($vente['montant_total'], 2, ',', ' '),
            $mode_labels[$vente['mode_paiement']] ?? $vente['mode_paiement'],
            $statut_labels[$vente['statut']] ?? $vente['statut'],
            $vente['vendeur'] ?: 'N/A'
        ];
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Server Error');
    echo 'Erreur: ' . e($e->getMessage());
    exit;
}
