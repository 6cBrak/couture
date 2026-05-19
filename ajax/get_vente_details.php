<?php
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'html' => ''];

try {
    if (empty($_GET['id'])) throw new Exception('ID vente requis');
    
    $id_vente = intval($_GET['id']);
    
    // Récupérer les infos vente
    $vente = db_fetch_one("
        SELECT v.*, 
               c.nom_client, c.email, c.telephone,
               u.nom_complet as vendeur
        FROM ventes v
        LEFT JOIN clients c ON v.id_client = c.id_client
        LEFT JOIN utilisateurs u ON v.id_vendeur = u.id_utilisateur
        WHERE v.id_vente = ?
    ", [$id_vente]);
    
    if (!$vente) throw new Exception('Vente non trouvée');
    
    // Récupérer les détails (articles)
    $details = db_fetch_all("
        SELECT d.*, p.nom_produit, p.prix_vente
        FROM details_vente d
        JOIN produits p ON d.id_produit = p.id_produit
        WHERE d.id_vente = ?
        ORDER BY d.id_detail
    ", [$id_vente]);
    
    // Construire le HTML
    $html = '<div class="row mb-3">';
    $html .= '<div class="col-md-6">';
    $html .= '<h6 class="text-muted">Informations Vente</h6>';
    $html .= '<dl class="row small">';
    $html .= '<dt class="col-sm-5">N° Facture:</dt><dd class="col-sm-7"><strong>' . e($vente['numero_facture']) . '</strong></dd>';
    $html .= '<dt class="col-sm-5">Date:</dt><dd class="col-sm-7">' . date('d/m/Y H:i', strtotime($vente['date_vente'])) . '</dd>';
    $html .= '<dt class="col-sm-5">Vendeur:</dt><dd class="col-sm-7">' . e($vente['vendeur'] ?: 'N/A') . '</dd>';
    $html .= '<dt class="col-sm-5">Statut:</dt><dd class="col-sm-7">';
    $statut_badges = ['validee' => 'success', 'en_cours' => 'warning', 'annulee' => 'danger'];
    $statut_labels = ['validee' => 'Validée', 'en_cours' => 'En cours', 'annulee' => 'Annulée'];
    $badge_class = $statut_badges[$vente['statut']] ?? 'secondary';
    $statut_label = $statut_labels[$vente['statut']] ?? $vente['statut'];
    $html .= '<span class="badge bg-' . $badge_class . '">' . $statut_label . '</span></dd>';
    $html .= '</dl>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-6">';
    $html .= '<h6 class="text-muted">Informations Client</h6>';
    if ($vente['nom_client']) {
        $html .= '<dl class="row small">';
        $html .= '<dt class="col-sm-5">Nom:</dt><dd class="col-sm-7">' . e($vente['nom_client']) . '</dd>';
        if ($vente['telephone']) {
            $html .= '<dt class="col-sm-5">Téléphone:</dt><dd class="col-sm-7"><a href="tel:' . e($vente['telephone']) . '">' . e($vente['telephone']) . '</a></dd>';
        }
        if ($vente['email']) {
            $html .= '<dt class="col-sm-5">Email:</dt><dd class="col-sm-7"><a href="mailto:' . e($vente['email']) . '">' . e($vente['email']) . '</a></dd>';
        }
        $html .= '</dl>';
    } else {
        $html .= '<p class="text-muted small">Vente comptoir (pas de client attribué)</p>';
    }
    $html .= '</div>';
    $html .= '</div>';
    
    // Articles
    $html .= '<hr>';
    $html .= '<h6 class="text-muted">Articles Vendus</h6>';
    $html .= '<div class="table-responsive">';
    $html .= '<table class="table table-sm table-hover">';
    $html .= '<thead class="table-light"><tr><th>Produit</th><th class="text-end">Qté</th><th class="text-end">PU</th><th class="text-end">Total</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($details as $detail) {
        $total_ligne = $detail['quantite'] * $detail['prix_vente'];
        $html .= '<tr>';
        $html .= '<td>' . e($detail['nom_produit']) . '</td>';
        $html .= '<td class="text-end">' . $detail['quantite'] . '</td>';
        $html .= '<td class="text-end">' . format_montant($detail['prix_vente'], $GLOBALS['devise']) . '</td>';
        $html .= '<td class="text-end"><strong>' . format_montant($total_ligne, $GLOBALS['devise']) . '</strong></td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    // Résumé financier
    $html .= '<hr>';
    $html .= '<div class="row small">';
    $html .= '<div class="col-md-6 offset-md-6">';
    $html .= '<dl class="row">';
    $html .= '<dt class="col-sm-6">Montant HT:</dt><dd class="col-sm-6 text-end">' . format_montant($vente['montant_ht'], $GLOBALS['devise']) . '</dd>';
    $tva_label = ($GLOBALS['taux_tva'] > 0) ? 'TVA (' . (float)$GLOBALS['taux_tva'] . '%)' : 'TVA';
    $html .= '<dt class="col-sm-6">' . $tva_label . ':</dt><dd class="col-sm-6 text-end">' . format_montant($vente['montant_tva'], $GLOBALS['devise']) . '</dd>';
    if ($vente['montant_remise'] > 0) {
        $html .= '<dt class="col-sm-6">Remise:</dt><dd class="col-sm-6 text-end">-' . format_montant($vente['montant_remise'], $GLOBALS['devise']) . '</dd>';
    }
    $html .= '<dt class="col-sm-6"><strong>Montant Total:</strong></dt><dd class="col-sm-6 text-end"><strong>' . format_montant($vente['montant_total'], $GLOBALS['devise']) . '</strong></dd>';
    $html .= '<dt class="col-sm-6">Mode paiement:</dt><dd class="col-sm-6 text-end">';
    
    $mode_labels = ['especes' => 'Espèces', 'carte' => 'Carte', 'mobile_money' => 'Mobile Money', 'cheque' => 'Chèque', 'credit' => 'Crédit'];
    $html .= $mode_labels[$vente['mode_paiement']] ?? $vente['mode_paiement'];
    $html .= '</dd>';
    
    if ($vente['notes']) {
        $html .= '<dt class="col-sm-6">Notes:</dt><dd class="col-sm-6 text-end"><em>' . e($vente['notes']) . '</em></dd>';
    }
    
    $html .= '</dl>';
    $html .= '</div>';
    $html .= '</div>';
    
    $response = ['success' => true, 'html' => $html];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
