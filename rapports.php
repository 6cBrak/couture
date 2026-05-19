<?php
/**
 * PAGE RAPPORTS - STORE SUITE
 * Rapports et statistiques de vente
 */
require_once 'protection_pages.php';
$page_title = 'Rapports et Statistiques';

// Périodes prédéfinies
$periode = $_GET['periode'] ?? 'today';
$date_debut = $_GET['date_debut'] ?? date('Y-m-d');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');

// Calcul des dates selon la période
switch ($periode) {
    case 'today':
        $date_debut = $date_fin = date('Y-m-d');
        break;
    case 'week':
        $date_debut = date('Y-m-d', strtotime('monday this week'));
        $date_fin = date('Y-m-d');
        break;
    case 'month':
        $date_debut = date('Y-m-01');
        $date_fin = date('Y-m-d');
        break;
    case 'year':
        $date_debut = date('Y-01-01');
        $date_fin = date('Y-m-d');
        break;
}

// ── Filtre dépôt ─────────────────────────────────────────────────────────────
// Chaque vendeur ne voit que les stats de son dépôt ; l'admin voit tout
$id_depot_rapports = null;
$depot_cond_v   = '';  // pour queries avec alias v. sur ventes
$depot_cond_raw = '';  // pour queries sans alias sur ventes
$depot_p        = [];  // paramètre à ajouter en fin de liste

if (!$is_admin) {
    $uinfo = db_fetch_one("SELECT id_depot FROM utilisateurs WHERE id_utilisateur = ?", [$user_id]);
    $id_depot_rapports = $uinfo['id_depot'] ?? null;
    if ($id_depot_rapports) {
        $sub = "SELECT id_utilisateur FROM utilisateurs WHERE id_depot = ?";
        $depot_cond_v   = "AND v.id_vendeur IN ($sub)";
        $depot_cond_raw = "AND id_vendeur IN ($sub)";
        $depot_p        = [$id_depot_rapports];
    }
}

// Statistiques globales
$stats = db_fetch_one("
    SELECT
        COUNT(*) as total_ventes,
        COALESCE(SUM(montant_total), 0) as chiffre_affaires,
        COALESCE(AVG(montant_total), 0) as panier_moyen
    FROM ventes
    WHERE DATE(date_vente) BETWEEN ? AND ?
    AND statut = 'validee' $depot_cond_raw
", array_merge([$date_debut, $date_fin], $depot_p));

// Top 10 produits (basé sur les ventes)
$top_produits = db_fetch_all("
    SELECT
        p.nom_produit,
        p.prix_vente,
        SUM(dv.quantite) as quantite_vendue,
        SUM(dv.prix_total) as montant_total
    FROM details_vente dv
    INNER JOIN produits p ON dv.id_produit = p.id_produit
    INNER JOIN ventes v ON dv.id_vente = v.id_vente
    WHERE DATE(v.date_vente) BETWEEN ? AND ?
    AND v.statut = 'validee' $depot_cond_v
    GROUP BY p.id_produit
    ORDER BY quantite_vendue DESC
    LIMIT 10
", array_merge([$date_debut, $date_fin], $depot_p));

// Ventes par jour
$ventes_jour = db_fetch_all("
    SELECT
        DATE(date_vente) as jour,
        COUNT(*) as nombre_ventes,
        SUM(montant_total) as montant
    FROM ventes
    WHERE DATE(date_vente) BETWEEN ? AND ?
    AND statut = 'validee' $depot_cond_raw
    GROUP BY DATE(date_vente)
    ORDER BY jour ASC
", array_merge([$date_debut, $date_fin], $depot_p));

// Ventes par statut
$ventes_statut = db_fetch_all("
    SELECT
        statut,
        COUNT(*) as nombre,
        SUM(montant_total) as montant
    FROM ventes
    WHERE DATE(date_vente) BETWEEN ? AND ?
    $depot_cond_raw
    GROUP BY statut
    ORDER BY statut ASC
", array_merge([$date_debut, $date_fin], $depot_p));

// Top clients
$top_clients = db_fetch_all("
    SELECT
        c.nom_client,
        COUNT(v.id_vente) as nombre_achats,
        SUM(v.montant_total) as total_achete,
        MAX(v.date_vente) as derniere_vente
    FROM ventes v
    INNER JOIN clients c ON v.id_client = c.id_client
    WHERE DATE(v.date_vente) BETWEEN ? AND ?
    AND v.statut = 'validee' $depot_cond_v
    GROUP BY v.id_client
    ORDER BY total_achete DESC
    LIMIT 10
", array_merge([$date_debut, $date_fin], $depot_p));

// Statistiques crédit pour la période
$stats_credit = db_fetch_one("
    SELECT
        COUNT(*) as nb_credits,
        COALESCE(SUM(montant_total), 0) as total_credit,
        COALESCE(SUM(COALESCE(montant_paye, 0)), 0) as total_paye,
        COALESCE(SUM(montant_total) - SUM(COALESCE(montant_paye, 0)), 0) as solde_restant,
        SUM(CASE WHEN (montant_total - COALESCE(montant_paye, 0)) <= 0.01 THEN 1 ELSE 0 END) as nb_soldes
    FROM ventes
    WHERE mode_paiement = 'credit'
    AND statut = 'validee'
    AND DATE(date_vente) BETWEEN ? AND ?
    $depot_cond_raw
", array_merge([$date_debut, $date_fin], $depot_p));

// Ventes par catégorie
$ventes_categorie = db_fetch_all("
    SELECT
        cat.nom_categorie,
        COUNT(DISTINCT dv.id_vente) as nombre_ventes,
        SUM(dv.quantite) as quantite_vendue,
        SUM(dv.prix_total) as montant_total,
        COUNT(DISTINCT p.id_produit) as nombre_produits
    FROM details_vente dv
    INNER JOIN produits p ON dv.id_produit = p.id_produit
    INNER JOIN categories cat ON p.id_categorie = cat.id_categorie
    INNER JOIN ventes v ON dv.id_vente = v.id_vente
    WHERE DATE(v.date_vente) BETWEEN ? AND ?
    AND v.statut = 'validee' $depot_cond_v
    GROUP BY cat.id_categorie
    ORDER BY montant_total DESC
", array_merge([$date_debut, $date_fin], $depot_p));

// Produits par catégorie (pour detail)
$produits_categorie = db_fetch_all("
    SELECT
        cat.nom_categorie,
        p.nom_produit,
        SUM(dv.quantite) as quantite_vendue,
        SUM(dv.prix_total) as montant_total,
        p.prix_vente
    FROM details_vente dv
    INNER JOIN produits p ON dv.id_produit = p.id_produit
    INNER JOIN categories cat ON p.id_categorie = cat.id_categorie
    INNER JOIN ventes v ON dv.id_vente = v.id_vente
    WHERE DATE(v.date_vente) BETWEEN ? AND ?
    AND v.statut = 'validee' $depot_cond_v
    GROUP BY p.id_produit
    ORDER BY cat.nom_categorie ASC, montant_total DESC
", array_merge([$date_debut, $date_fin], $depot_p));

// Liste complète des produits (sans prix d'achat)
$liste_produits = db_fetch_all("
    SELECT 
        p.id_produit,
        p.code_produit,
        p.nom_produit,
        p.prix_vente,
        p.prix_vente_min,
        p.quantite_stock,
        p.seuil_alerte,
        p.seuil_critique,
        p.unite_mesure,
        p.emplacement,
        p.code_barre,
        p.est_actif,
        p.date_modification,
        p.date_creation,
        cat.nom_categorie
    FROM produits p
    LEFT JOIN categories cat ON p.id_categorie = cat.id_categorie
    ORDER BY p.nom_produit ASC
", []);

// Liste des catégories (catalogue complet)
$liste_categories = db_fetch_all("
    SELECT id_categorie, nom_categorie, description, est_actif, date_modification
    FROM categories
    ORDER BY nom_categorie ASC
", []);

// Stats par dépôt (admin : tous ; vendeur : son dépôt uniquement)
$stats_depots = [];
try {
    $depot_filter_sql = $id_depot_rapports ? "AND d.id_depot = ?" : "";
    $depot_filter_par = $id_depot_rapports ? [$id_depot_rapports] : [];
    $stats_depots = db_fetch_all("
        SELECT
            d.id_depot,
            d.nom_depot,
            d.type_depot,
            COUNT(DISTINCT v.id_vente)  AS nb_ventes,
            COALESCE(SUM(v.montant_total), 0) AS ca_total,
            COUNT(DISTINCT u.id_utilisateur) AS nb_caissiers
        FROM depots d
        LEFT JOIN utilisateurs u ON u.id_depot = d.id_depot AND u.est_actif = 1
        LEFT JOIN ventes v ON v.id_vendeur = u.id_utilisateur
            AND DATE(v.date_vente) BETWEEN ? AND ?
            AND v.statut = 'validee'
        WHERE d.est_actif = 1 $depot_filter_sql
        GROUP BY d.id_depot
        ORDER BY ca_total DESC
    ", array_merge([$date_debut, $date_fin], $depot_filter_par));
} catch (Exception $e) { /* migration pas encore exécutée */ }

include 'header.php';
?>

<style>
.report-card-hover {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.report-card-hover:hover {
    transform: translateY(-5px);
    border-color: <?php echo $couleur_primaire; ?>;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.stat-card {
    border-left: 4px solid <?php echo $couleur_primaire; ?>;
}

.badge-gradient {
    background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>);
    color: white;
}

/* Card sizing for better layout */
.card-body {
    padding: 0.75rem !important;
}

.card-title {
    font-size: 14px !important;
    font-weight: 600;
}

.small {
    font-size: 11px !important;
}

.btn-sm {
    font-size: 11px !important;
    padding: 0.35rem 0.5rem !important;
}

.icon-small {
    width: 28px !important;
    height: 28px !important;
}

.card-header {
    padding: 0.75rem 1.25rem !important;
}

/* Rapport cards uniform sizing */
.report-card {
    min-height: 165px;
    border-left: 3px solid #0d6efd !important;
    border-radius: 6px !important;
    border: 1px solid #e9ecef !important;
    border-left: 3px solid #0d6efd !important;
}

.report-card:hover {
    border-left-color: #0a58ca !important;
}
</style>

<script>
// Prevent page reload on file downloads
document.addEventListener('DOMContentLoaded', function() {
    // Handle download links - use iframe for Excel only
    document.querySelectorAll('a[href*="ajax/export_"]').forEach(link => {
        link.addEventListener('click', function(e) {
            // Only prevent default for Excel
            const url = this.href;
            if (url.includes('export_excel')) {
                e.preventDefault();
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = url;
                document.body.appendChild(iframe);
                setTimeout(() => document.body.removeChild(iframe), 1000);
            }
            // For PDF, let browser handle it normally
        });
    });
    
    // Handle "Voir" buttons - display data in modal
    document.querySelectorAll('.btn-voir-rapport').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const reportType = this.getAttribute('data-type');
            if (reportType) {
                // If inventaire_depot, show depot selection modal first
                if (reportType === 'inventaire_depot') {
                    showDepotSelectionModal(reportType);
                } else {
                    showReportModal(reportType);
                }
            }
        });
    });
});

function showDepotSelectionModal(reportType) {
    // Create depot selection modal
    let depotModal = document.getElementById('depotSelectionModal');
    
    if (!depotModal) {
        const modalHtml = `
            <div class="modal fade" id="depotSelectionModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Sélectionner un Dépôt</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Dépôt</label>
                            <select class="form-select" id="depotSelect">
                                <option value="all">Tous les dépôts</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary" id="btnViewDepotReport">Voir le rapport</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        depotModal = document.getElementById('depotSelectionModal');
        
        // Load depots list
        fetch('ajax/get_depots.php')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('depotSelect');
                    data.depots.forEach(depot => {
                        const option = document.createElement('option');
                        option.value = depot.id_depot;
                        option.textContent = depot.nom_depot + (depot.description ? ' - ' + depot.description : '');
                        select.appendChild(option);
                    });
                }
            });
    }
    
    // Show modal
    const bsModal = new bootstrap.Modal(depotModal);
    bsModal.show();
    
    // Handle confirm button
    document.getElementById('btnViewDepotReport').onclick = function() {
        const depotId = document.getElementById('depotSelect').value;
        bsModal.hide();
        showReportModal(reportType, {id_depot: depotId});
    };
}

function showReportModal(type, extraParams = {}) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('reportModal');
    
    if (!modal) {
        const modalHtml = `
            <div class="modal fade" id="reportModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="reportTitle">Rapport</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="reportContent" style="max-height: 600px; overflow-y: auto;">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        modal = document.getElementById('reportModal');
    }
    
    // Load content
    const contentDiv = document.getElementById('reportContent');
    contentDiv.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
    
    // Get the data based on type
    const params = new URLSearchParams(window.location.search);
    const dateDebut = params.get('date_debut') || new Date().toISOString().split('T')[0];
    const dateFin = params.get('date_fin') || new Date().toISOString().split('T')[0];
    
    // Fetch the report content
    const formData = new URLSearchParams({
        type: type,
        date_debut: dateDebut,
        date_fin: dateFin,
        ...extraParams
    });
    
    fetch('ajax/get_report.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('reportTitle').textContent = data.title;
            contentDiv.innerHTML = data.html;
        } else {
            contentDiv.innerHTML = '<div class="alert alert-danger">Erreur: ' + data.message + '</div>';
        }
    })
    .catch(err => {
        contentDiv.innerHTML = '<div class="alert alert-danger">Erreur de chargement: ' + err.message + '</div>';
    });
    
    // Show modal
    new bootstrap.Modal(modal).show();
}
</script>

<div class="container-xl">
    <!-- Page Header -->
    <div class="page-header d-print-none mb-2">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-3" width="32" height="32" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <rect x="4" y="4" width="16" height="16" rx="2"/>
                        <line x1="4" y1="10" x2="20" y2="10"/>
                        <line x1="10" y1="4" x2="10" y2="20"/>
                    </svg>
                    Rapports et Statistiques
                </h1>
                <p class="text-muted mt-1">Analysez les performances de votre boutique</p>
            </div>
            <div class="col-auto">
                <a href="accueil.php" class="btn btn-outline-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <polyline points="5 12 19 12"/>
                        <polyline points="5 12 11 18"/>
                        <polyline points="5 12 11 6"/>
                    </svg>
                    Retour
                </a>
            </div>
        </div>
    </div>

    <!-- Filtres et Périodes -->
    <div class="card mb-2 border-0 shadow-sm">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-600">Période</label>
                    <select name="periode" class="form-select" onchange="toggleCustomDates(this.value)">
                        <option value="today" <?php echo $periode === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                        <option value="week" <?php echo $periode === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                        <option value="month" <?php echo $periode === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                        <option value="year" <?php echo $periode === 'year' ? 'selected' : ''; ?>>Cette année</option>
                        <option value="custom" <?php echo $periode === 'custom' ? 'selected' : ''; ?>>Personnalisée</option>
                    </select>
                </div>
                <div class="col-md-2" id="date_debut_col" style="display: <?php echo $periode === 'custom' ? 'block' : 'none'; ?>;">
                    <label class="form-label fw-600">Du</label>
                    <input type="date" name="date_debut" class="form-control" value="<?php echo $date_debut; ?>">
                </div>
                <div class="col-md-2" id="date_fin_col" style="display: <?php echo $periode === 'custom' ? 'block' : 'none'; ?>;">
                    <label class="form-label fw-600">Au</label>
                    <input type="date" name="date_fin" class="form-control" value="<?php echo $date_fin; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><line x1="21" y1="21" x2="15" y2="15"/></svg>
                        Appliquer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs Principaux -->
    <div class="row mb-2 g-2">
        <div class="col-md-6">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <div class="text-muted small fw-600 mb-2">NOMBRE DE VENTES</div>
                            <h3 class="mb-0"><?php echo number_format($stats['total_ventes'], 0, ',', ' '); ?></h3>
                            <small class="text-muted">ventes validées</small>
                        </div>
                        <div class="badge badge-gradient p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="6" cy="19" r="2"/>
                                <circle cx="17" cy="19" r="2"/>
                                <path d="M17 17h-11v-14h-2"/>
                                <path d="M6 5l14 1l-1 7h-13"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <div class="text-muted small fw-600 mb-2">PANIER MOYEN</div>
                            <h3 class="mb-0 text-info"><?php echo format_montant($stats['panier_moyen']); ?></h3>
                            <small class="text-muted">montant par vente</small>
                        </div>
                        <div class="badge bg-info p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 17.5c2.664 0 4.5 -1.679 4.5 -3.5c0 -1 -.516 -1.911 -1.5 -2.5c1.579 -1.151 2.517 -2 2.517 -3.5c0 -1.861 -1.823 -3 -4.017 -3c-1.498 0 -2.783 .856 -3.5 1.659c-.718 -.803 -2.002 -1.659 -3.5 -1.659c-2.194 0 -4.017 1.139 -4.017 3c0 1.5 .938 2.349 2.517 3.5c-.984 .589 -1.5 1.5 -1.5 2.5c0 1.821 1.836 3.5 4.5 3.5z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Statistiques Crédit -->
    <?php if ($stats_credit['nb_credits'] > 0): ?>
    <div class="card mb-2 border-0 shadow-sm" style="border-left: 3px solid #ef4444 !important;">
        <div class="card-header bg-light border-0 py-2 d-flex align-items-center justify-content-between">
            <h3 class="card-title d-flex align-items-center mb-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/></svg>
                Ventes à crédit
            </h3>
            <a href="listes.php?page=credits" class="btn btn-sm btn-outline-danger">Gérer les crédits</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="text-muted small fw-600 mb-1">NOMBRE</div>
                    <h4 class="mb-0 text-danger"><?php echo number_format($stats_credit['nb_credits']); ?></h4>
                    <small class="text-muted">ventes à crédit</small>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small fw-600 mb-1">TOTAL ACCORDÉ</div>
                    <h4 class="mb-0"><?php echo format_montant($stats_credit['total_credit']); ?></h4>
                    <small class="text-muted">montant total</small>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small fw-600 mb-1">DÉJÀ RECOUVRÉ</div>
                    <h4 class="mb-0 text-success"><?php echo format_montant($stats_credit['total_paye']); ?></h4>
                    <small class="text-muted"><?php echo $stats_credit['nb_soldes']; ?> soldé(s)</small>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small fw-600 mb-1">SOLDE RESTANT</div>
                    <h4 class="mb-0 <?php echo $stats_credit['solde_restant'] > 0 ? 'text-warning' : 'text-success'; ?>">
                        <?php echo format_montant($stats_credit['solde_restant']); ?>
                    </h4>
                    <small class="text-muted">à recouvrer</small>
                </div>
            </div>
            <?php if ($stats_credit['total_credit'] > 0): ?>
            <div class="mt-3">
                <div class="d-flex justify-content-between mb-1">
                    <small class="text-muted">Taux de recouvrement</small>
                    <small class="fw-bold"><?php echo number_format($stats_credit['total_paye'] / $stats_credit['total_credit'] * 100, 1); ?>%</small>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" style="width: <?php echo min(100, round($stats_credit['total_paye'] / $stats_credit['total_credit'] * 100)); ?>%"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Résumé par Statut -->
    <div class="card mb-2 border-0 shadow-sm">
        <div class="card-header bg-light border-0 py-3">
            <h3 class="card-title d-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="4" y="4" width="16" height="16" rx="2"/><line x1="4" y1="10" x2="20" y2="10"/><line x1="10" y1="4" x2="10" y2="20"/>
                </svg>
                Récapitulatif par statut
            </h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead class="bg-light">
                    <tr>
                        <th>État</th>
                        <th class="text-center">Nombre de ventes</th>
                        <th class="text-end">Montant total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($ventes_statut as $stat):
                        $color = match($stat['statut']) {
                            'validee' => 'bg-success-lt',
                            'brouillon' => 'bg-warning-lt',
                            'annulea' => 'bg-danger-lt',
                            'remboursee' => 'bg-secondary-lt',
                            default => 'bg-light'
                        };
                        $label = match($stat['statut']) {
                            'validee' => 'Validée',
                            'brouillon' => 'Brouillon',
                            'annulea' => 'Annulée',
                            'remboursee' => 'Remboursée',
                            default => ucfirst($stat['statut'])
                        };
                    ?>
                    <tr>
                        <td>
                            <span class="badge <?php echo $color; ?>">
                                <?php echo e($label); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <strong><?php echo $stat['nombre']; ?></strong>
                        </td>
                        <td class="text-end">
                            <strong><?php echo format_montant($stat['montant'] ?? 0); ?></strong>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ventes_statut)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">Aucune vente enregistrée pour cette période</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Types de Rapports -->
    <div class="card mb-2 border-0 shadow-sm">
        <div class="card-header bg-light border-0 py-2">
            <h3 class="card-title d-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                </svg>
                Rapports téléchargeables
            </h3>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <!-- Rapport Ventes -->
                <div class="col-md-6 col-lg-3">
                    <div class="card report-card shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <h5 class="card-title mb-1">
                                    Rapport des ventes
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-muted ms-1" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Liste détaillée de toutes les ventes validées avec montants, clients et produits vendus" style="cursor: help;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <circle cx="12" cy="12" r="9"/>
                                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                                        <polyline points="11 12 12 12 12 16 13 16"/>
                                    </svg>
                                </h5>
                                <p class="small mb-0 text-muted">Tous les détails</p>
                            </div>
                            <div class="mt-auto pt-1 d-flex gap-1">
                                <a href="ajax/export_excel.php?type=ventes&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" class="btn btn-success btn-sm flex-fill" title="Rapport Excel">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                    </svg>
                                    Excel
                                </a>
                                <a href="rapport_affichage.php?type=ventes&periode=<?php echo $periode; ?>&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" target="_blank" class="btn btn-danger btn-sm flex-fill" title="Rapport PDF">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                    </svg>
                                    PDF
                                </a>
                                <a href="#" class="btn btn-info btn-sm flex-fill btn-voir-rapport" data-type="ventes" title="Visualiser">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2"/><path d="M22 12c-2.667 4.667 -6.667 7 -11 7s-8.333 -2.333 -11 -7c2.667 -4.667 6.667 -7 11 -7s8.333 2.333 11 7"/>
                                    </svg>
                                    Voir
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rapport Produits -->
                <div class="col-md-6 col-lg-3">
                    <div class="card report-card shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <h5 class="card-title mb-1">
                                    Inventaire produits
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-muted ms-1" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Liste complète de tous les produits avec stocks, prix de vente et informations" style="cursor: help;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <circle cx="12" cy="12" r="9"/>
                                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                                        <polyline points="11 12 12 12 12 16 13 16"/>
                                    </svg>
                                </h5>
                                <p class="small mb-0 text-muted">Catalogue complet</p>
                            </div>
                            <div class="mt-auto pt-1 d-flex gap-1">
                                <a href="ajax/export_excel.php?type=produits" class="btn btn-success btn-sm flex-fill" title="Rapport Excel">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                    </svg>
                                    Excel
                                </a>
                                <a href="rapport_affichage.php?type=produits&periode=<?php echo $periode; ?>&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" target="_blank" class="btn btn-danger btn-sm flex-fill" title="Rapport PDF">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                    </svg>
                                    PDF
                                </a>
                                <a href="#" class="btn btn-info btn-sm flex-fill btn-voir-rapport" data-type="produits" title="Visualiser">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2"/><path d="M22 12c-2.667 4.667 -6.667 7 -11 7s-8.333 -2.333 -11 -7c2.667 -4.667 6.667 -7 11 -7s8.333 2.333 11 7"/>
                                    </svg>
                                    Voir
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rapport Bénéfices (Admin seulement) -->
                <?php if ($is_admin): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card report-card shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <h5 class="card-title mb-1">Analyse profitabilité</h5>
                                <p class="small mb-0 text-muted">Marges et rendement</p>
                            </div>
                            <div class="mt-auto pt-1 d-flex gap-1">
                                <a href="ajax/export_excel.php?type=benefices&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" class="btn btn-success btn-sm flex-fill" title="Rapport Excel">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                    </svg>
                                    Excel
                                </a>
                                <a href="rapport_affichage.php?type=benefices&periode=<?php echo $periode; ?>&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" target="_blank" class="btn btn-danger btn-sm flex-fill" title="Rapport PDF">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                    </svg>
                                    PDF
                                </a>
                                <a href="#" class="btn btn-info btn-sm flex-fill btn-voir-rapport" data-type="benefices" title="Visualiser">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2"/><path d="M22 12c-2.667 4.667 -6.667 7 -11 7s-8.333 -2.333 -11 -7c2.667 -4.667 6.667 -7 11 -7s8.333 2.333 11 7"/>
                                    </svg>
                                    Voir
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Rapport Catégories -->
                <div class="col-md-6 col-lg-3">
                    <div class="card report-card shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <h5 class="card-title mb-1">Performance catégories</h5>
                                <p class="small mb-0 text-muted">Par segment</p>
                            </div>
                            <div class="mt-auto pt-1 d-flex gap-1">
                                <a href="ajax/export_excel.php?type=categories&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" class="btn btn-success btn-sm flex-fill" title="Rapport Excel">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                    </svg>
                                    Excel
                                </a>
                                <a href="rapport_affichage.php?type=categories&periode=<?php echo $periode; ?>&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" target="_blank" class="btn btn-danger btn-sm flex-fill" title="Rapport PDF">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                    </svg>
                                    PDF
                                </a>
                                <a href="#" class="btn btn-info btn-sm flex-fill btn-voir-rapport" data-type="categories" title="Visualiser">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2"/><path d="M22 12c-2.667 4.667 -6.667 7 -11 7s-8.333 -2.333 -11 -7c2.667 -4.667 6.667 -7 11 -7s8.333 2.333 11 7"/>
                                    </svg>
                                    Voir
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- SECTION : RAPPORTS DE GESTION DE STOCK -->
            <!-- ============================================================ -->
            <div class="mt-4">
                <h5 class="text-muted mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M3 21l18 0"/>
                        <path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4"/>
                        <path d="M5 21l0 -10.15"/>
                        <path d="M19 21l0 -10.15"/>
                        <path d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4"/>
                    </svg>
                    Rapports de Gestion de Stock
                </h5>
                <div class="row g-2">
                    <!-- Rapport Inventaire par Dépôt -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card report-card shadow-sm h-100" style="border-left-color: #17a2b8 !important;">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <h5 class="card-title mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <polyline points="12 3 20 7.5 20 16.5 12 21 4 16.5 4 7.5 12 3"/>
                                            <line x1="12" y1="12" x2="20" y2="7.5"/>
                                            <line x1="12" y1="12" x2="12" y2="21"/>
                                            <line x1="12" y1="12" x2="4" y2="7.5"/>
                                        </svg>
                                        Inventaire par dépôt
                                    </h5>
                                    <p class="small mb-0 text-muted">Localisation des produits</p>
                                </div>
                                <div class="mt-auto pt-1 d-flex gap-1">
                                    <a href="ajax/export_excel.php?type=inventaire_depot" class="btn btn-success btn-sm flex-fill" title="Rapport Excel">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                        </svg>
                                        Excel
                                    </a>
                                    <a href="rapport_affichage.php?type=inventaire_depot" target="_blank" class="btn btn-danger btn-sm flex-fill" title="Rapport PDF">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                        </svg>
                                        PDF
                                    </a>
                                    <a href="#" class="btn btn-info btn-sm flex-fill btn-voir-rapport" data-type="inventaire_depot" title="Visualiser">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2"/><path d="M22 12c-2.667 4.667 -6.667 7 -11 7s-8.333 -2.333 -11 -7c2.667 -4.667 6.667 -7 11 -7s8.333 2.333 11 7"/>
                                        </svg>
                                        Voir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rapport Mouvements de Stock -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card report-card shadow-sm h-100" style="border-left-color: #6f42c1 !important;">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <h5 class="card-title mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <polyline points="7 10 12 15 17 10"/>
                                            <polyline points="7 14 12 19 17 14"/>
                                        </svg>
                                        Mouvements de stock
                                    </h5>
                                    <p class="small mb-0 text-muted">Historique complet</p>
                                </div>
                                <div class="mt-auto pt-1 d-flex gap-1">
                                    <a href="ajax/export_excel.php?type=mouvements_stock&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" class="btn btn-success btn-sm flex-fill" title="Rapport Excel">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                        </svg>
                                        Excel
                                    </a>
                                    <a href="rapport_affichage.php?type=mouvements_stock&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" target="_blank" class="btn btn-danger btn-sm flex-fill" title="Rapport PDF">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                        </svg>
                                        PDF
                                    </a>
                                    <a href="#" class="btn btn-info btn-sm flex-fill btn-voir-rapport" data-type="mouvements_stock" title="Visualiser">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2"/><path d="M22 12c-2.667 4.667 -6.667 7 -11 7s-8.333 -2.333 -11 -7c2.667 -4.667 6.667 -7 11 -7s8.333 2.333 11 7"/>
                                        </svg>
                                        Voir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rapport Valeur du Stock (Admin seulement) -->
                    <?php if ($is_admin): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card report-card shadow-sm h-100" style="border-left-color: #28a745 !important;">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <h5 class="card-title mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2"/>
                                            <path d="M12 3v3m0 12v3"/>
                                        </svg>
                                        Valeur du stock
                                    </h5>
                                    <p class="small mb-0 text-muted">Valorisation complète</p>
                                    <span class="badge bg-warning-lt mt-1" style="font-size: 0.65rem;">Admin</span>
                                </div>
                                <div class="mt-auto pt-1 d-flex gap-1">
                                    <a href="ajax/export_excel.php?type=valeur_stock" class="btn btn-success btn-sm flex-fill" title="Rapport Excel">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                        </svg>
                                        Excel
                                    </a>
                                    <a href="rapport_affichage.php?type=valeur_stock" target="_blank" class="btn btn-danger btn-sm flex-fill" title="Rapport PDF">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                        </svg>
                                        PDF
                                    </a>
                                    <a href="#" class="btn btn-info btn-sm flex-fill btn-voir-rapport" data-type="valeur_stock" title="Visualiser">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2"/><path d="M22 12c-2.667 4.667 -6.667 7 -11 7s-8.333 -2.333 -11 -7c2.667 -4.667 6.667 -7 11 -7s8.333 2.333 11 7"/>
                                        </svg>
                                        Voir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Rapport Alertes Stock -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card report-card shadow-sm h-100" style="border-left-color: #dc3545 !important;">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <h5 class="card-title mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M12 9v2m0 4v.01"/>
                                            <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                                        </svg>
                                        Alertes & Ruptures
                                    </h5>
                                    <p class="small mb-0 text-muted">Stock faible/critique</p>
                                </div>
                                <div class="mt-auto pt-1 d-flex gap-1">
                                    <a href="ajax/export_excel.php?type=alertes_stock" class="btn btn-success btn-sm flex-fill" title="Rapport Excel">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                        </svg>
                                        Excel
                                    </a>
                                    <a href="rapport_affichage.php?type=alertes_stock" target="_blank" class="btn btn-danger btn-sm flex-fill" title="Rapport PDF">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                        </svg>
                                        PDF
                                    </a>
                                    <a href="#" class="btn btn-info btn-sm flex-fill btn-voir-rapport" data-type="alertes_stock" title="Visualiser">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2"/><path d="M22 12c-2.667 4.667 -6.667 7 -11 7s-8.333 -2.333 -11 -7c2.667 -4.667 6.667 -7 11 -7s8.333 2.333 11 7"/>
                                        </svg>
                                        Voir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Fin Section Gestion de Stock -->
        </div>
    </div>

    <!-- Section Inventaire Détaillé -->

    <!-- Catalogue Produits & Catégories -->
    <div class="row mb-2 g-2">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0 py-2 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <h3 class="card-title d-flex align-items-center mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7h16"/><path d="M10 11h6"/><path d="M8 15h8"/><path d="M12 3v2"/><path d="M6 3v2"/><path d="M18 3v2"/><rect x="4" y="5" width="16" height="14" rx="2"/>
                            </svg>
                            Liste complète des produits
                        </h3>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="ajax/export_excel.php?type=produits" class="btn btn-success btn-sm" title="Exporter en Excel">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                            </svg>
                            Export Excel
                        </a>
                        <a href="rapport_affichage.php?type=produits&periode=<?php echo $periode; ?>&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" target="_blank" class="btn btn-danger btn-sm" title="Imprimer / PDF">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:4px;">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                            </svg>
                            Imprimer
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-6">
                            <input type="text" id="filterProduitsList" class="form-control form-control-sm" placeholder="Rechercher un produit (nom, code, catégorie)...">
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">Total : <?php echo count($liste_produits); ?> produit<?php echo count($liste_produits) > 1 ? 's' : ''; ?></small>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-vcenter table-hover card-table" id="tableProduitsList">
                            <thead class="bg-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Produit</th>
                                    <th>Catégorie</th>
                                    <th class="text-end">Prix vente</th>
                                    <th class="text-end">Prix min.</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Seuils</th>
                                    <th>Unité</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($liste_produits)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Aucun produit enregistré</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($liste_produits as $prod): 
                                    $search = strtolower(($prod['nom_produit'] ?? '') . ' ' . ($prod['code_produit'] ?? '') . ' ' . ($prod['nom_categorie'] ?? '') . ' ' . ($prod['code_barre'] ?? ''));
                                    $statut_label = $prod['est_actif'] ? 'Actif' : 'Inactif';
                                    $statut_class = $prod['est_actif'] ? 'bg-success-lt' : 'bg-secondary-lt';
                                ?>
                                <tr class="produit-full-row" data-search="<?php echo e($search); ?>">
                                    <td><strong><?php echo e($prod['code_produit'] ?? '—'); ?></strong></td>
                                    <td>
                                        <div class="fw-600"><?php echo e($prod['nom_produit']); ?></div>
                                        <small class="text-muted">#<?php echo $prod['id_produit']; ?></small>
                                    </td>
                                    <td><span class="badge bg-purple-lt"><?php echo e($prod['nom_categorie'] ?? 'Non classé'); ?></span></td>
                                    <td class="text-end text-success text-nowrap"><?php echo format_montant($prod['prix_vente']); ?></td>
                                    <td class="text-end text-muted text-nowrap"><?php echo $prod['prix_vente_min'] ? format_montant($prod['prix_vente_min']) : '—'; ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo ($prod['quantite_stock'] <= $prod['seuil_critique']) ? 'bg-danger' : (($prod['quantite_stock'] <= $prod['seuil_alerte']) ? 'bg-warning' : 'bg-success'); ?>">
                                            <?php echo $prod['quantite_stock']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center text-muted small">
                                        <?php echo $prod['seuil_alerte']; ?>/<?php echo $prod['seuil_critique']; ?>
                                    </td>
                                    <td><?php echo e($prod['unite_mesure'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge <?php echo $statut_class; ?> text-dark fw-600"><?php echo $statut_label; ?></span>
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
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0 py-2 d-flex align-items-center justify-content-between">
                    <h3 class="card-title d-flex align-items-center mb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7h16"/><path d="M10 11h6"/><path d="M8 15h8"/><path d="M4 5v14"/><path d="M20 5v14"/>
                        </svg>
                        Catégories du catalogue
                    </h3>
                    <input type="text" id="filterCategoriesList" class="form-control form-control-sm" placeholder="Filtrer" style="width: 180px;">
                </div>
                <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-vcenter table-hover card-table">
                        <thead class="bg-light">
                            <tr>
                                <th>Catégorie</th>
                                <th>Statut</th>
                                <th class="text-end">Modifié</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($liste_categories)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Aucune catégorie</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($liste_categories as $cat): 
                                $cat_search = strtolower(($cat['nom_categorie'] ?? '') . ' ' . ($cat['description'] ?? ''));
                                $cat_label = $cat['est_actif'] ? 'Active' : 'Inactive';
                                $cat_class = $cat['est_actif'] ? 'bg-success-lt' : 'bg-secondary-lt';
                            ?>
                            <tr class="categorie-full-row" data-search="<?php echo e($cat_search); ?>">
                                <td>
                                    <strong><?php echo e($cat['nom_categorie']); ?></strong>
                                    <?php if (!empty($cat['description'])): ?>
                                    <div class="text-muted small"><?php echo e($cat['description']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo $cat_class; ?> text-dark fw-600"><?php echo $cat_label; ?></span></td>
                                <td class="text-end text-muted small">
                                    <?php echo $cat['date_modification'] ? date('d/m/Y', strtotime($cat['date_modification'])) : '—'; ?>
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

    <!-- Graphiques -->
    <div class="row mb-2 g-2">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-2">
                    <h3 class="card-title d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="4 19 8 13 12 15 16 10 20 14 20 19 4 19"/>
                        </svg>
                        Évolution des ventes
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-2" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" data-bs-placement="top" title="Graphique montrant l'évolution des ventes sur la période sélectionnée. La courbe représente le montant total des ventes (TTC) jour par jour. Permet de visualiser les tendances et les variations du chiffre d'affaires." style="cursor: help; opacity: 0.7;">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/>
                        </svg>
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-3">
                    <h3 class="card-title d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="12 3 20 7.5 20 16.5 12 21 4 16.5 4 7.5 12 3"/><polyline points="12 12 20 7.5"/>
                        </svg>
                        Top 10 Produits
                    </h3>
                </div>
                <div class="card-body p-0" style="max-height: 450px; overflow-y: auto;">
                    <div class="list-group list-group-flush">
                        <?php if (empty($top_produits)): ?>
                        <div class="list-group-item text-center text-muted py-4">
                            Aucune vente
                        </div>
                        <?php else: ?>
                        <?php foreach ($top_produits as $index => $produit): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-gradient me-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            <?php echo $index + 1; ?>
                                        </span>
                                        <div>
                                            <strong class="d-block small"><?php echo e($produit['nom_produit']); ?></strong>
                                            <small class="text-muted"><?php echo $produit['quantite_vendue']; ?> unit<?php echo $produit['quantite_vendue'] > 1 ? 'és' : 'é'; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end ms-2">
                                    <strong class="d-block text-success small"><?php echo format_montant($produit['montant_total']); ?></strong>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ventes par Catégorie -->
    <div class="row mb-2 g-2">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="card-title d-flex align-items-center mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="5" y="3" width="4" height="4"/><rect x="15" y="3" width="4" height="4"/><rect x="5" y="13" width="4" height="4"/><rect x="15" y="13" width="4" height="4"/>
                            </svg>
                            Ventes par catégorie
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-2" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" data-bs-placement="top" title="Récapitulatif du chiffre d'affaires par catégorie de produits. Affiche le nombre de produits différents vendus, la quantité totale et le montant total TTC pour chaque catégorie." style="cursor: help; opacity: 0.7;">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/>
                            </svg>
                        </h3>
                        <input type="text" id="filterCategorie" class="form-control form-control-sm" placeholder="Filtrer..." style="width: 150px;">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table" id="tableCategorie">
                        <thead class="bg-light">
                            <tr>
                                <th>Catégorie</th>
                                <th class="text-center">Produits</th>
                                <th class="text-center">Quantité</th>
                                <th class="text-end">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ventes_categorie)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Aucune vente</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($ventes_categorie as $cat): ?>
                            <tr class="categorie-row" data-categorie="<?php echo strtolower(e($cat['nom_categorie'])); ?>">
                                <td>
                                    <strong><?php echo e($cat['nom_categorie']); ?></strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-blue"><?php echo $cat['nombre_produits']; ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-gradient"><?php echo $cat['quantite_vendue']; ?></span>
                                </td>
                                <td class="text-end">
                                    <strong class="text-success"><?php echo format_montant($cat['montant_total']); ?></strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="card-title d-flex align-items-center mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><circle cx="15" cy="11" r="2"/><path d="M21 20v-2a2 2 0 0 0 -2 -2"/>
                            </svg>
                            Top 10 Clients
                        </h3>
                        <input type="text" id="filterClients" class="form-control form-control-sm" placeholder="Filtrer..." style="width: 150px;">
                    </div>
                </div>
                <div class="card-body p-0" style="max-height: 520px; overflow-y: auto;">
                    <div class="list-group list-group-flush">
                        <?php if (empty($top_clients)): ?>
                        <div class="list-group-item text-center text-muted py-4">
                            Aucune vente
                        </div>
                        <?php else: ?>
                        <?php foreach ($top_clients as $index => $client): ?>
                        <div class="list-group-item client-row" data-client="<?php echo strtolower(e($client['nom_client'])); ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="badge badge-gradient me-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            <?php echo $index + 1; ?>
                                        </span>
                                        <div>
                                            <strong class="d-block"><?php echo e($client['nom_client']); ?></strong>
                                            <small class="text-muted"><?php echo $client['nombre_achats']; ?> achat<?php echo $client['nombre_achats'] > 1 ? 's' : ''; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <strong class="d-block text-success"><?php echo format_montant($client['total_achete']); ?></strong>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($client['derniere_vente'])); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Détail Produits par Catégorie -->
    <div class="card mb-2 border-0 shadow-sm">
        <div class="card-header bg-light border-0 py-2">
            <div class="d-flex align-items-center justify-content-between">
                <h3 class="card-title d-flex align-items-center mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="5" r="4"/><path d="M3 19c0 -2.21 4.03 -4 9 -4s9 1.79 9 4"/>
                    </svg>
                    Détail produits par catégorie
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-2" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" data-bs-placement="top" title="Liste détaillée de chaque produit vendu, organisée par catégorie. Choisissez une catégorie dans le menu pour filtrer les résultats. Affiche la quantité vendue, le prix unitaire et le montant total HT pour chaque produit." style="cursor: help; opacity: 0.7;">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/>
                    </svg>
                </h3>
                <select id="filterProduitCategorie" class="form-select form-select-sm" style="width: 200px;">
                    <option value="">-- Toutes les catégories --</option>
                    <?php foreach ($ventes_categorie as $cat): ?>
                    <option value="<?php echo strtolower(e($cat['nom_categorie'])); ?>"><?php echo e($cat['nom_categorie']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead class="bg-light">
                    <tr>
                        <th>Catégorie</th>
                        <th>Produit</th>
                        <th class="text-center">Quantité vendue</th>
                        <th class="text-end">Prix unitaire</th>
                        <th class="text-end">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $current_cat = '';
                    if (empty($produits_categorie)): 
                    ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Aucune vente</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($produits_categorie as $prod): 
                        $show_cat = $current_cat !== $prod['nom_categorie'];
                        if ($show_cat) $current_cat = $prod['nom_categorie'];
                    ?>
                    <tr class="produit-row" data-categorie="<?php echo strtolower(e($prod['nom_categorie'])); ?>">
                        <td <?php if ($show_cat) echo 'class="fw-600"'; ?>>
                            <?php if ($show_cat): ?>
                            <span class="badge bg-purple"><?php echo e($prod['nom_categorie']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="text-muted"><?php echo e($prod['nom_produit']); ?></span>
                        </td>
                        <td class="text-center">
                            <strong><?php echo $prod['quantite_vendue']; ?></strong>
                        </td>
                        <td class="text-end text-muted">
                            <?php echo format_montant($prod['prix_vente']); ?>
                        </td>
                        <td class="text-end">
                            <strong><?php echo format_montant($prod['montant_total']); ?></strong>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($stats_depots)): ?>
<div class="container-xl mt-4">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-primary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M3 21l18 0"/><path d="M3 10l18 0"/><path d="M5 6l7 -3l7 3"/>
                    <path d="M4 10l0 11"/><path d="M20 10l0 11"/><path d="M8 14l0 3"/><path d="M12 14l0 3"/><path d="M16 14l0 3"/>
                </svg>
                Performance par Dépôt
                <span class="text-muted ms-2 fs-5">(<?php echo e($date_debut); ?> → <?php echo e($date_fin); ?>)</span>
            </h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                    <tr>
                        <th>Dépôt</th>
                        <th>Type</th>
                        <th class="text-center">Caissiers actifs</th>
                        <th class="text-center">Nb ventes</th>
                        <th class="text-end">Chiffre d'affaires</th>
                        <th class="text-end">CA moyen / vente</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $ca_total_all = array_sum(array_column($stats_depots, 'ca_total'));
                foreach ($stats_depots as $depot):
                    $pct = $ca_total_all > 0 ? round($depot['ca_total'] / $ca_total_all * 100, 1) : 0;
                    $ca_moy = $depot['nb_ventes'] > 0 ? $depot['ca_total'] / $depot['nb_ventes'] : 0;
                    $type_label = match($depot['type_depot'] ?? '') {
                        'boutique'   => ['Boutique',    'bg-blue'],
                        'entrepot'   => ['Entrepôt',    'bg-purple'],
                        'kiosque'    => ['Kiosque',     'bg-orange'],
                        'franchise'  => ['Franchise',   'bg-green'],
                        default      => ['Principal',   'bg-cyan'],
                    };
                ?>
                    <tr>
                        <td>
                            <strong><?php echo e($depot['nom_depot']); ?></strong>
                            <div class="progress mt-1" style="height:4px;">
                                <div class="progress-bar bg-primary" style="width:<?php echo $pct; ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo $pct; ?>% du CA total</small>
                        </td>
                        <td>
                            <span class="badge <?php echo $type_label[1]; ?>"><?php echo $type_label[0]; ?></span>
                        </td>
                        <td class="text-center">
                            <?php if ($depot['nb_caissiers'] > 0): ?>
                                <span class="badge bg-success-lt"><?php echo $depot['nb_caissiers']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($depot['nb_ventes'] > 0): ?>
                                <strong><?php echo number_format($depot['nb_ventes']); ?></strong>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($depot['ca_total'] > 0): ?>
                                <strong class="text-success"><?php echo format_montant($depot['ca_total']); ?></strong>
                            <?php else: ?>
                                <span class="text-muted"><?php echo format_montant(0); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-muted">
                            <?php echo $ca_moy > 0 ? format_montant($ca_moy) : '—'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <?php if (count($stats_depots) > 1 && $ca_total_all > 0): ?>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="3">Total</td>
                        <td class="text-center"><?php echo number_format(array_sum(array_column($stats_depots, 'nb_ventes'))); ?></td>
                        <td class="text-end text-success"><?php echo format_montant($ca_total_all); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Toggle custom dates
function toggleCustomDates(periode) {
    const dateDebutCol = document.getElementById('date_debut_col');
    const dateFinCol = document.getElementById('date_fin_col');
    if (periode === 'custom') {
        dateDebutCol.style.display = 'block';
        dateFinCol.style.display = 'block';
    } else {
        dateDebutCol.style.display = 'none';
        dateFinCol.style.display = 'none';
        document.querySelector('form').submit();
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Sales chart
const salesData = <?php echo json_encode($ventes_jour); ?>;
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: salesData.map(d => new Date(d.jour).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })),
        datasets: [{
            label: 'Ventes (<?php echo $devise; ?>)',
            data: salesData.map(d => d.montant),
            borderColor: '<?php echo $couleur_primaire; ?>',
            backgroundColor: '<?php echo $couleur_primaire; ?>20',
            tension: 0.4,
            fill: true,
            pointRadius: 5,
            pointBackgroundColor: '<?php echo $couleur_primaire; ?>',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => value.toLocaleString() + ' <?php echo $devise; ?>'
                }
            }
        }
    }
});

// Filtrage - Catégories
document.getElementById('filterCategorie').addEventListener('keyup', function(e) {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('.categorie-row').forEach(row => {
        const categorie = row.getAttribute('data-categorie');
        row.style.display = categorie.includes(filter) ? '' : 'none';
    });
});

// Filtrage - Clients
document.getElementById('filterClients').addEventListener('keyup', function(e) {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('.client-row').forEach(row => {
        const client = row.getAttribute('data-client');
        row.style.display = client.includes(filter) ? '' : 'none';
    });
});

// Filtrage - Produits par Catégorie (dropdown)
document.getElementById('filterProduitCategorie').addEventListener('change', function(e) {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('.produit-row').forEach(row => {
        const categorie = row.getAttribute('data-categorie');
        row.style.display = (filter === '' || categorie === filter) ? '' : 'none';
    });
});

// Filtrage - Liste complète des produits
const filterProduitsList = document.getElementById('filterProduitsList');
if (filterProduitsList) {
    filterProduitsList.addEventListener('keyup', function() {
        const needle = this.value.toLowerCase();
        document.querySelectorAll('.produit-full-row').forEach(row => {
            const haystack = row.getAttribute('data-search');
            row.style.display = haystack.includes(needle) ? '' : 'none';
        });
    });
}

// Filtrage - Liste des catégories
const filterCategoriesList = document.getElementById('filterCategoriesList');
if (filterCategoriesList) {
    filterCategoriesList.addEventListener('keyup', function() {
        const needle = this.value.toLowerCase();
        document.querySelectorAll('.categorie-full-row').forEach(row => {
            const haystack = row.getAttribute('data-search');
            row.style.display = haystack.includes(needle) ? '' : 'none';
        });
    });
}

// Initialiser les tooltips Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'footer.php'; ?>
