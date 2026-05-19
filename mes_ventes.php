<?php
/**
 * MES VENTES - Interface vendeur pour gérer ses propres ventes
 * Permet de consulter, annuler et réimprimer les factures
 */
require_once 'protection_pages.php';
$page_title = $is_admin ? 'Toutes les Ventes' : 'Mes Ventes';

// Filtres
$filter_date_debut = $_GET['date_debut'] ?? date('Y-m-d', strtotime('-30 days'));
$filter_date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$filter_client = $_GET['client'] ?? '';
$filter_statut = $_GET['statut'] ?? '';
$filter_search = $_GET['search'] ?? '';
$filter_vendeur = $is_admin ? (intval($_GET['vendeur'] ?? 0)) : 0;
$filter_depot   = $is_admin ? (intval($_GET['depot'   ] ?? 0)) : 0;

// Requête de base
$query = "
    SELECT v.*,
           c.nom_client,
           u.nom_complet as nom_vendeur,
           u.id_depot,
           d.nom_depot,
           COUNT(dv.id_detail) as nb_articles
    FROM ventes v
    LEFT JOIN clients c ON v.id_client = c.id_client
    LEFT JOIN utilisateurs u ON v.id_vendeur = u.id_utilisateur
    LEFT JOIN depots d ON u.id_depot = d.id_depot
    LEFT JOIN details_vente dv ON v.id_vente = dv.id_vente
    WHERE 1=1
";
$params = [];

// Admin voit tout, caissier voit seulement ses ventes
if (!$is_admin) {
    $query .= " AND v.id_vendeur = ?";
    $params[] = $user_id;
} elseif ($filter_vendeur) {
    $query .= " AND v.id_vendeur = ?";
    $params[] = $filter_vendeur;
}

if ($is_admin && $filter_depot) {
    $query .= " AND u.id_depot = ?";
    $params[] = $filter_depot;
}

// Appliquer les filtres
if ($filter_date_debut) {
    $query .= " AND DATE(v.date_vente) >= ?";
    $params[] = $filter_date_debut;
}
if ($filter_date_fin) {
    $query .= " AND DATE(v.date_vente) <= ?";
    $params[] = $filter_date_fin;
}
if ($filter_client) {
    $query .= " AND v.id_client = ?";
    $params[] = $filter_client;
}
if ($filter_statut) {
    $query .= " AND v.statut = ?";
    $params[] = $filter_statut;
}
if ($filter_search) {
    $query .= " AND (v.numero_facture LIKE ? OR c.nom_client LIKE ?)";
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
}

$query .= " GROUP BY v.id_vente ORDER BY v.date_vente DESC";

$ventes = db_fetch_all($query, $params);

// Clients pour le filtre
if ($is_admin) {
    $clients = db_fetch_all("SELECT DISTINCT c.* FROM clients c JOIN ventes v ON c.id_client = v.id_client ORDER BY c.nom_client");
} else {
    $clients = db_fetch_all("
        SELECT DISTINCT c.*
        FROM clients c
        JOIN ventes v ON c.id_client = v.id_client
        WHERE v.id_vendeur = ?
        ORDER BY c.nom_client
    ", [$user_id]);
}

// Vendeurs pour le filtre admin
$vendeurs = $is_admin ? db_fetch_all("SELECT id_utilisateur, nom_complet FROM utilisateurs WHERE est_actif = 1 ORDER BY nom_complet") : [];

// Dépôts pour le filtre admin + stats
$depots_liste = $is_admin ? db_fetch_all("SELECT id_depot, nom_depot FROM depots WHERE est_actif = 1 ORDER BY nom_depot") : [];

// Stats par dépôt (admin seulement)
$stats_par_depot = [];
if ($is_admin) {
    try {
        $stats_par_depot = db_fetch_all("
            SELECT d.id_depot, d.nom_depot,
                   COUNT(DISTINCT v.id_vente) AS nb_ventes,
                   COALESCE(SUM(CASE WHEN v.statut='validee' THEN v.montant_total ELSE 0 END), 0) AS ca_total,
                   COALESCE(SUM(CASE WHEN v.statut='validee' AND v.mode_paiement='credit'
                                      AND (v.montant_total - COALESCE(v.montant_paye,0)) > 0.01
                                      THEN (v.montant_total - COALESCE(v.montant_paye,0)) ELSE 0 END), 0) AS credits_restants
            FROM depots d
            LEFT JOIN utilisateurs u ON u.id_depot = d.id_depot AND u.est_actif = 1
            LEFT JOIN ventes v ON v.id_vendeur = u.id_utilisateur
                AND DATE(v.date_vente) BETWEEN ? AND ?
            WHERE d.est_actif = 1
            GROUP BY d.id_depot ORDER BY ca_total DESC
        ", [$filter_date_debut, $filter_date_fin]);
    } catch (Exception $e) { /* migration pas encore exécutée */ }
}

// Statistiques
$stats_where  = $is_admin ? "1=1" : "v.id_vendeur = ?";
$stats_params = $is_admin ? [] : [$user_id];
$stats = db_fetch_one("
    SELECT
        COUNT(DISTINCT v.id_vente) as total_ventes,
        COALESCE(SUM(CASE WHEN DATE(v.date_vente) = CURDATE() THEN v.montant_total ELSE 0 END), 0) as ca_today,
        COALESCE(SUM(v.montant_total), 0) as ca_total
    FROM ventes v
    WHERE $stats_where
", $stats_params);

// Statistiques crédit
$credit_where  = $is_admin ? "1=1" : "id_vendeur = ?";
$credit_params = $is_admin ? [] : [$user_id];
$stats_credit = db_fetch_one("
    SELECT
        COUNT(*) as nb_credits,
        COALESCE(SUM(montant_total) - SUM(COALESCE(montant_paye, 0)), 0) as solde_restant
    FROM ventes
    WHERE $credit_where
    AND mode_paiement = 'credit'
    AND statut = 'validee'
    AND (montant_total - COALESCE(montant_paye, 0)) > 0.01
", $credit_params) ?? ['nb_credits' => 0, 'solde_restant' => 0];

include 'header.php';
?>

<style>
.stat-card {
    border-left: 4px solid <?php echo $couleur_primaire; ?>;
    transition: all 0.3s;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}
</style>

<div class="container-xl">
    <!-- Header -->
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    Mes Ventes
                </h2>
                <div class="text-muted mt-1">Gérez vos ventes, annulez et réimprimez vos factures</div>
            </div>
            <div class="col-auto">
                <a href="vente.php" class="btn btn-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nouvelle Vente
                </a>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="<?php echo $couleur_primaire; ?>" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small">Total Ventes</div>
                            <div class="h4 mb-0"><?php echo $stats['total_ventes']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="<?php echo $couleur_secondaire; ?>" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small">CA Aujourd'hui</div>
                            <div class="h4 mb-0"><?php echo format_montant($stats['ca_today'], $devise); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small">CA Total</div>
                            <div class="h4 mb-0"><?php echo format_montant($stats['ca_total'], $devise); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($stats_credit['nb_credits'] > 0): ?>
        <div class="col-md-3">
            <div class="card stat-card" style="border-left-color: #ef4444;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                                <line x1="7" y1="15" x2="7.01" y2="15"></line>
                                <line x1="11" y1="15" x2="13" y2="15"></line>
                            </svg>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small">Crédits en cours (<?php echo $stats_credit['nb_credits']; ?>)</div>
                            <div class="h4 mb-0 text-danger"><?php echo format_montant($stats_credit['solde_restant'], $devise); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date début</label>
                    <input type="date" class="form-control" name="date_debut" value="<?php echo e($filter_date_debut); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date fin</label>
                    <input type="date" class="form-control" name="date_fin" value="<?php echo e($filter_date_fin); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Client</label>
                    <select class="form-select" name="client">
                        <option value="">Tous</option>
                        <?php foreach($clients as $c): ?>
                        <option value="<?php echo $c['id_client']; ?>" <?php echo $filter_client == $c['id_client'] ? 'selected' : ''; ?>>
                            <?php echo e($c['nom_client']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select class="form-select" name="statut">
                        <option value="">Tous</option>
                        <option value="validee" <?php echo $filter_statut == 'validee' ? 'selected' : ''; ?>>Validée</option>
                        <option value="annulee" <?php echo $filter_statut == 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                    </select>
                </div>
                <?php if ($is_admin && !empty($depots_liste)): ?>
                <div class="col-md-2">
                    <label class="form-label">Dépôt</label>
                    <select class="form-select" name="depot">
                        <option value="">Tous</option>
                        <?php foreach($depots_liste as $dep): ?>
                        <option value="<?php echo $dep['id_depot']; ?>" <?php echo $filter_depot == $dep['id_depot'] ? 'selected' : ''; ?>>
                            <?php echo e($dep['nom_depot']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <?php if ($is_admin && !empty($vendeurs)): ?>
                <div class="col-md-2">
                    <label class="form-label">Vendeur</label>
                    <select class="form-select" name="vendeur">
                        <option value="">Tous</option>
                        <?php foreach($vendeurs as $v): ?>
                        <option value="<?php echo $v['id_utilisateur']; ?>" <?php echo $filter_vendeur == $v['id_utilisateur'] ? 'selected' : ''; ?>>
                            <?php echo e($v['nom_complet']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-2">
                    <label class="form-label">Recherche</label>
                    <input type="text" class="form-control" name="search" placeholder="N° facture..." value="<?php echo e($filter_search); ?>">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="mes_ventes.php" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($is_admin && !empty($stats_par_depot)): ?>
    <!-- Stats par dépôt -->
    <div class="row mb-4 g-3">
        <?php foreach($stats_par_depot as $sdep): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <span class="avatar avatar-sm bg-primary-lt me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path d="M5 21v-14l8 -4v18"/><path d="M19 21v-10l-6 -4"/></svg>
                        </span>
                        <strong><?php echo e($sdep['nom_depot']); ?></strong>
                    </div>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="text-muted small">Ventes</div>
                            <div class="fw-bold"><?php echo $sdep['nb_ventes']; ?></div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">CA</div>
                            <div class="fw-bold text-success"><?php echo format_montant($sdep['ca_total'], $devise); ?></div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Crédits</div>
                            <div class="fw-bold <?php echo $sdep['credits_restants'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                <?php echo format_montant($sdep['credits_restants'], $devise); ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($depots_liste)): ?>
                    <div class="mt-2 text-end">
                        <a href="?depot=<?php echo $sdep['id_depot']; ?>&date_debut=<?php echo $filter_date_debut; ?>&date_fin=<?php echo $filter_date_fin; ?>" class="btn btn-sm btn-outline-primary">Voir ventes</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Liste des ventes -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📋 Liste de vos ventes (<?php echo count($ventes); ?>)</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Date</th>
                            <th>Client</th>
                            <?php if ($is_admin): ?><th>Vendeur</th><?php endif; ?>
                            <th>Dépôt</th>
                            <th>Articles</th>
                            <th>Montant TTC</th>
                            <th>Paiement</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ventes)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">Aucune vente trouvée</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($ventes as $vente): ?>
                        <tr>
                            <td><strong><?php echo e($vente['numero_facture']); ?></strong></td>
                            <td><small><?php echo date('d/m/Y H:i', strtotime($vente['date_vente'])); ?></small></td>
                            <td><?php echo e($vente['nom_client'] ?: 'Vente comptoir'); ?></td>
                            <?php if ($is_admin): ?>
                            <td><small><?php echo e($vente['nom_vendeur'] ?? '—'); ?></small></td>
                            <?php endif; ?>
                            <td><small class="text-muted"><?php echo e($vente['nom_depot'] ?? '—'); ?></small></td>
                            <td><span class="badge bg-info"><?php echo $vente['nb_articles']; ?></span></td>
                            <td><strong><?php echo format_montant($vente['montant_total'], $devise); ?></strong></td>
                            <td>
                                <?php
                                $mode_badges = ['especes' => 'success', 'carte' => 'primary', 'mobile_money' => 'warning', 'cheque' => 'info', 'credit' => 'secondary'];
                                $mode_labels = ['especes' => 'Espèces', 'carte' => 'Carte', 'mobile_money' => 'Mobile Money', 'cheque' => 'Chèque', 'credit' => 'Crédit'];
                                $badge_class = $mode_badges[$vente['mode_paiement']] ?? 'secondary';
                                $mode_label = $mode_labels[$vente['mode_paiement']] ?? $vente['mode_paiement'];
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $mode_label; ?></span>
                            </td>
                            <td>
                                <?php
                                $statut_badges = ['validee' => 'success', 'annulee' => 'danger'];
                                $statut_labels = ['validee' => 'Validée', 'annulee' => 'Annulée'];
                                $badge_class = $statut_badges[$vente['statut']] ?? 'secondary';
                                $statut_label = $statut_labels[$vente['statut']] ?? $vente['statut'];
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $statut_label; ?></span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="facture_impression.php?id=<?php echo $vente['id_vente']; ?>" 
                                       target="_blank"
                                       class="btn btn-outline-primary"
                                       data-bs-toggle="tooltip" title="Imprimer facture">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                            <rect x="6" y="14" width="12" height="8"></rect>
                                        </svg>
                                    </a>
                                    <?php
                                    $solde_credit = round(($vente['montant_total'] ?? 0) - ($vente['montant_paye'] ?? 0), 2);
                                    if ($vente['mode_paiement'] === 'credit' && $vente['statut'] === 'validee' && $solde_credit > 0.01):
                                    ?>
                                    <button type="button" class="btn btn-outline-success btn-pay-credit"
                                            data-id="<?php echo $vente['id_vente']; ?>"
                                            data-numero="<?php echo e($vente['numero_facture']); ?>"
                                            data-solde="<?php echo $solde_credit; ?>"
                                            data-bs-toggle="tooltip" title="Payer crédit (<?php echo format_montant($solde_credit, $devise); ?> restant)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($vente['statut'] == 'validee'): ?>
                                    <button type="button" class="btn btn-outline-danger btn-cancel-vente"
                                            data-id="<?php echo $vente['id_vente']; ?>"
                                            data-numero="<?php echo e($vente['numero_facture']); ?>"
                                            data-bs-toggle="tooltip" title="Annuler la vente">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($vente['statut'] == 'annulee'): ?>
                                    <button type="button" class="btn btn-outline-success btn-restore-vente" 
                                            data-id="<?php echo $vente['id_vente']; ?>"
                                            data-numero="<?php echo e($vente['numero_facture']); ?>"
                                            data-bs-toggle="tooltip" title="Restaurer la vente">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="23 4 23 10 17 10"></polyline>
                                            <path d="M20.49 15a9 9 0 1 1 .12-4.36"></path>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($vente['statut'] == 'annulee' && $is_admin): ?>
                                    <button type="button" class="btn btn-outline-danger btn-delete-vente" 
                                            data-id="<?php echo $vente['id_vente']; ?>"
                                            data-numero="<?php echo e($vente['numero_facture']); ?>"
                                            data-bs-toggle="tooltip" title="Supprimer définitivement (Admin)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Paiement Crédit -->
<div class="modal fade" id="modalPayCredit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Paiement crédit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2 text-muted small">Facture : <strong id="pcNumero"></strong></p>
                <p class="mb-3">Solde restant : <strong id="pcSolde" class="text-danger"></strong></p>
                <div class="mb-3">
                    <label class="form-label">Montant à payer <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="pcMontant" min="0.01" step="0.01">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mode de paiement</label>
                    <select class="form-select" id="pcMode">
                        <option value="especes">Espèces</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="carte">Carte</option>
                        <option value="cheque">Chèque</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success btn-sm" id="btnConfirmPayCredit">Enregistrer</button>
            </div>
            <input type="hidden" id="pcIdVente">
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Annulation de vente
    document.querySelectorAll('.btn-cancel-vente').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const numero = this.dataset.numero;
            
            console.log('❌ Bouton annuler cliqué:', {id, numero});
            
            if (typeof showConfirmModal === 'function') {
                showConfirmModal({
                    title: 'Confirmer l\'annulation',
                    message: `Annuler la vente ${numero} ? Le stock sera restauré.`,
                    type: 'danger',
                    confirmText: 'Annuler la vente',
                    cancelText: 'Revenir'
                }).then(confirmed => {
                    if (confirmed) cancelVente(id);
                });
            } else {
                const confirmed = confirm(`Annuler la vente ${numero} ?\n\nLe stock sera restauré.`);
                if (confirmed) cancelVente(id);
            }
        });
    });
    
    // Restauration de vente (pour les ventes annulées)
    document.querySelectorAll('.btn-restore-vente').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const numero = this.dataset.numero;
            
            console.log('♻️ Bouton restaurer cliqué:', {id, numero});
            
            if (typeof showConfirmModal === 'function') {
                showConfirmModal({
                    title: 'Restaurer la vente',
                    message: `Restaurer la vente ${numero} en statut validé ? Le stock sera réduit.`,
                    type: 'info',
                    confirmText: 'Restaurer',
                    cancelText: 'Annuler'
                }).then(confirmed => {
                    if (confirmed) restoreVente(id);
                });
            } else {
                const confirmed = confirm(`Restaurer la vente ${numero} ? Le stock sera réduit.`);
                if (confirmed) restoreVente(id);
            }
        });
    });
    
    // Paiement crédit
    document.querySelectorAll('.btn-pay-credit').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('pcIdVente').value  = this.dataset.id;
            document.getElementById('pcNumero').textContent = this.dataset.numero;
            const solde = parseFloat(this.dataset.solde);
            document.getElementById('pcSolde').textContent = solde.toLocaleString('fr-FR', {minimumFractionDigits:2}) + ' <?php echo $devise; ?>';
            document.getElementById('pcMontant').value = solde.toFixed(2);
            document.getElementById('pcMontant').max   = solde;
            document.getElementById('pcMode').value    = 'especes';
            new bootstrap.Modal(document.getElementById('modalPayCredit')).show();
        });
    });

    document.getElementById('btnConfirmPayCredit').addEventListener('click', function() {
        const id      = document.getElementById('pcIdVente').value;
        const montant = parseFloat(document.getElementById('pcMontant').value);
        const mode    = document.getElementById('pcMode').value;
        if (!montant || montant <= 0) { alert('Montant invalide'); return; }
        this.disabled = true;
        fetch('ajax/credits.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'add_payment', id_vente: id, montant, mode_paiement: mode})
        })
        .then(r => r.json())
        .then(data => {
            this.disabled = false;
            bootstrap.Modal.getInstance(document.getElementById('modalPayCredit')).hide();
            if (data.success) {
                showAlertModal ? showAlertModal({title:'Succès', message: data.message, type:'success'}) : alert(data.message);
                setTimeout(() => location.reload(), 900);
            } else {
                showAlertModal ? showAlertModal({title:'Erreur', message: data.message, type:'danger'}) : alert(data.message);
            }
        })
        .catch(() => { this.disabled = false; alert('Erreur de connexion'); });
    });

    // Suppression définitive (admin)
    document.querySelectorAll('.btn-delete-vente').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const numero = this.dataset.numero;
            
            console.log('🗑️ Bouton supprimer cliqué:', {id, numero});
            
            if (typeof showConfirmModal === 'function') {
                showConfirmModal({
                    title: 'Supprimer définitivement',
                    message: `Supprimer la vente ${numero} ? Cette action est irréversible.`,
                    type: 'danger',
                    confirmText: 'Supprimer définitivement',
                    cancelText: 'Revenir'
                }).then(confirmed => {
                    if (confirmed) deleteVente(id);
                });
            } else {
                const confirmed = confirm(`Supprimer la vente ${numero} ? Cette action est irréversible.`);
                if (confirmed) deleteVente(id);
            }
        });
    });
});

function cancelVente(id) {
        console.log('🎯 cancelVente appelée avec id:', id);
        
        fetch('ajax/cancel_vente.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({id_vente: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Annulation réussie');
                if (typeof showAlertModal === 'function') {
                    showAlertModal({
                        title: 'Succès',
                        message: data.message,
                        type: 'success'
                    });
                } else {
                    alert(data.message);
                }
                // Recharger après 1 seconde
                setTimeout(() => {
                    console.log('🔄 Rechargement page...');
                    location.reload();
                }, 1000);
            } else {
                console.error('❌ Erreur annulation:', data.message);
                if (typeof showAlertModal === 'function') {
                    showAlertModal({
                        title: 'Erreur',
                        message: data.message,
                        type: 'error'
                    });
                } else {
                    alert('Erreur: ' + data.message);
                }
            }
        })
        .catch(e => {
            console.error('❌ Erreur fetch:', e);
            if (typeof showAlertModal === 'function') {
                showAlertModal({
                    title: 'Erreur',
                    message: 'Erreur de connexion: ' + e.message,
                    type: 'error'
                });
            } else {
                alert('Erreur de connexion: ' + e.message);
            }
        });
    }
    
    function restoreVente(id) {
        console.log('♻️ restoreVente appelée avec id:', id);
        
        fetch('ajax/restore_vente.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({id_vente: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Restauration réussie');
                if (typeof showAlertModal === 'function') {
                    showAlertModal({
                        title: 'Succès',
                        message: data.message,
                        type: 'success'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    alert(data.message);
                    location.reload();
                }
            } else {
                console.error('❌ Erreur restauration:', data.message);
                if (typeof showAlertModal === 'function') {
                    showAlertModal({
                        title: 'Erreur',
                        message: data.message,
                        type: 'error'
                    });
                } else {
                    alert('Erreur: ' + data.message);
                }
            }
        })
        .catch(e => {
            console.error('❌ Erreur fetch:', e);
            if (typeof showAlertModal === 'function') {
                showAlertModal({
                    title: 'Erreur',
                    message: 'Erreur de connexion: ' + e.message,
                    type: 'error'
                });
            } else {
                alert('Erreur de connexion: ' + e.message);
            }
        });
    }
    
    function deleteVente(id) {
        console.log('🗑️ deleteVente appelée avec id:', id);
        
        fetch('ajax/delete_vente.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({id_vente: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Suppression réussie');
                if (typeof showAlertModal === 'function') {
                    showAlertModal({
                        title: 'Succès',
                        message: data.message,
                        type: 'success'
                    });
                } else {
                    alert(data.message);
                }
                // Recharger après 1 seconde
                setTimeout(() => {
                    console.log('🔄 Rechargement page...');
                    location.reload();
                }, 1000);
            } else {
                console.error('❌ Erreur suppression:', data.message);
                if (typeof showAlertModal === 'function') {
                    showAlertModal({
                        title: 'Erreur',
                        message: data.message,
                        type: 'error'
                    });
                } else {
                    alert('Erreur: ' + data.message);
                }
            }
        })
        .catch(e => {
            console.error('❌ Erreur fetch:', e);
            if (typeof showAlertModal === 'function') {
                showAlertModal({
                    title: 'Erreur',
                    message: 'Erreur de connexion: ' + e.message,
                    type: 'error'
                });
            } else {
                alert('Erreur de connexion: ' + e.message);
            }
        });
    }
</script>

<?php include 'footer.php'; ?>
