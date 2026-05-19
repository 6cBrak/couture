<?php
/**
 * PAGE : MOUVEMENTS DE STOCK
 * Historique complet des mouvements de stock avec filtres avancés
 */

require_once('protection_pages.php');

$page_title = 'Mouvements de Stock';

// Récupération des filtres
$filtre_type = $_GET['type'] ?? '';
$filtre_produit = $_GET['produit'] ?? '';
$filtre_depot = $_GET['depot'] ?? '';
$filtre_date_debut = $_GET['date_debut'] ?? '';
$filtre_date_fin = $_GET['date_fin'] ?? '';

// Construction de la requête avec filtres
$where_clauses = [];
$params = [];

if ($filtre_type) {
    $where_clauses[] = "m.type_mouvement = ?";
    $params[] = $filtre_type;
}

if ($filtre_produit) {
    $where_clauses[] = "(p.nom_produit LIKE ? OR p.code_produit LIKE ?)";
    $params[] = "%$filtre_produit%";
    $params[] = "%$filtre_produit%";
}

if ($filtre_depot) {
    $where_clauses[] = "(m.id_depot_source = ? OR m.id_depot_destination = ?)";
    $params[] = $filtre_depot;
    $params[] = $filtre_depot;
}

if ($filtre_date_debut && $filtre_date_fin) {
    $where_clauses[] = "DATE(m.date_mouvement) BETWEEN ? AND ?";
    $params[] = $filtre_date_debut;
    $params[] = $filtre_date_fin;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Statistiques
$stats = db_fetch_one("
    SELECT 
        COUNT(*) as total_mouvements,
        COUNT(CASE WHEN m.type_mouvement = 'entree' THEN 1 END) as total_entrees,
        COUNT(CASE WHEN m.type_mouvement = 'sortie' THEN 1 END) as total_sorties,
        COUNT(CASE WHEN m.type_mouvement = 'transfert' THEN 1 END) as total_transferts
    FROM mouvements_stock m
    $where_sql
", $params);

// Récupération des mouvements
$mouvements = db_fetch_all("
    SELECT 
        m.*,
        p.nom_produit,
        p.code_produit,
        ds.nom_depot as depot_source_nom,
        dd.nom_depot as depot_destination_nom,
        f.nom_fournisseur,
        u.nom_complet as utilisateur_nom
    FROM mouvements_stock m
    INNER JOIN produits p ON m.id_produit = p.id_produit
    LEFT JOIN depots ds ON m.id_depot_source = ds.id_depot
    LEFT JOIN depots dd ON m.id_depot_destination = dd.id_depot
    LEFT JOIN fournisseurs f ON m.id_fournisseur = f.id_fournisseur
    INNER JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur
    $where_sql
    ORDER BY m.date_mouvement DESC, m.id_mouvement DESC
    LIMIT 100
", $params);

// Données pour les graphiques
$mouvements_par_type = db_fetch_all("
    SELECT type_mouvement, COUNT(*) as nombre
    FROM mouvements_stock m
    $where_sql
    GROUP BY type_mouvement
", $params);

// Données pour graphique chronologique (7 derniers jours)
$mouvements_par_jour = db_fetch_all("
    SELECT 
        DATE(date_mouvement) as jour,
        COUNT(*) as nombre
    FROM mouvements_stock
    WHERE date_mouvement >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(date_mouvement)
    ORDER BY jour ASC
");

// Liste des produits pour le formulaire
$produits = db_fetch_all("SELECT id_produit, nom_produit, code_produit, quantite_stock FROM produits WHERE est_actif = 1 ORDER BY nom_produit");

// Liste des dépôts
$depots = db_fetch_all("SELECT id_depot, nom_depot FROM depots WHERE est_actif = 1 ORDER BY est_principal DESC, nom_depot");

// Liste des fournisseurs
$fournisseurs = db_fetch_all("SELECT id_fournisseur, nom_fournisseur FROM fournisseurs WHERE est_actif = 1 ORDER BY nom_fournisseur");

require_once('header.php');
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M7 10h3v-3l-3.5 -3.5a6 6 0 0 1 8 8l6 6a2 2 0 0 1 -3 3l-6 -6a6 6 0 0 1 -8 -8l3.5 3.5"/>
                    </svg>
                    Mouvements de Stock
                </h2>
            </div>
            <?php if ($is_admin): ?>
            <div class="col-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNouveauMouvement">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Nouveau Mouvement
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        
        <!-- Statistiques -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total mouvements</div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-muted ms-auto" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Nombre total de mouvements enregistrés" style="cursor: help;">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                                <polyline points="11 12 12 12 12 16 13 16"/>
                            </svg>
                        </div>
                        <div class="h1 mb-0"><?php echo number_format($stats['total_mouvements']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Entrées</div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-muted ms-auto" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Réceptions de marchandises" style="cursor: help;">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                                <polyline points="11 12 12 12 12 16 13 16"/>
                            </svg>
                        </div>
                        <div class="h1 mb-0 text-success"><?php echo number_format($stats['total_entrees']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Sorties</div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-muted ms-auto" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Sorties de stock (hors ventes)" style="cursor: help;">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                                <polyline points="11 12 12 12 12 16 13 16"/>
                            </svg>
                        </div>
                        <div class="h1 mb-0 text-danger"><?php echo number_format($stats['total_sorties']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Transferts</div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-muted ms-auto" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Transferts entre dépôts" style="cursor: help;">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                                <polyline points="11 12 12 12 12 16 13 16"/>
                            </svg>
                        </div>
                        <div class="h1 mb-0 text-info"><?php echo number_format($stats['total_transferts']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="row row-cards mb-3">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Répartition par type</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="chartMouventsByType" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Évolution (7 derniers jours)</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="chartMouventsTimeline" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Filtres</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="mouvements_stock.php">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type">
                                <option value="">Tous les types</option>
                                <option value="entree" <?php echo $filtre_type === 'entree' ? 'selected' : ''; ?>>Entrée(Réception marchandise)</option>
                                <option value="sortie" <?php echo $filtre_type === 'sortie' ? 'selected' : ''; ?>>Sortie (Autre que vente)</option>
                                <option value="ajustement" <?php echo $filtre_type === 'ajustement' ? 'selected' : ''; ?>>Ajustement (Correction)</option>
                                <option value="transfert" <?php echo $filtre_type === 'transfert' ? 'selected' : ''; ?>>Transfert (Entre dépôts)</option>
                                <option value="inventaire" <?php echo $filtre_type === 'inventaire' ? 'selected' : ''; ?>>Inventaire (Comptage)</option>
                                <option value="perte" <?php echo $filtre_type === 'perte' ? 'selected' : ''; ?>>Perte (Casse/Vol/Expiration)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Produit</label>
                            <input type="text" class="form-control" name="produit" value="<?php echo e($filtre_produit); ?>" placeholder="Nom ou code...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date début</label>
                            <input type="date" class="form-control" name="date_debut" value="<?php echo e($filtre_date_debut); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date fin</label>
                            <input type="date" class="form-control" name="date_fin" value="<?php echo e($filtre_date_fin); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                        </div>
                    </div>
                </form>
                <div class="mt-3">
                    <?php 
                    $query_params = http_build_query($_GET);
                    $print_url = 'impression_mouvements.php' . ($query_params ? '?' . $query_params : '');
                    ?>
                    <a href="<?php echo $print_url; ?>" target="_blank" class="btn btn-outline-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2"/>
                            <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4"/>
                            <rect x="7" y="13" width="10" height="8" rx="2"/>
                        </svg>
                        Imprimer / PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- Tableau des mouvements -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Historique des mouvements</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date/Heure</th>
                            <th>Type</th>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Dépôt(s)</th>
                            <th>Motif</th>
                            <th>Utilisateur</th>
                            <?php if ($is_admin): ?>
                            <th class="text-end">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mouvements)): ?>
                        <tr>
                            <td colspan="<?php echo $is_admin ? '8' : '7'; ?>" class="text-center text-muted py-5">
                                Aucun mouvement trouvé
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($mouvements as $m): ?>
                        <tr>
                            <td style="white-space: nowrap;">
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($m['date_mouvement'])); ?></small><br>
                                <strong><?php echo date('H:i', strtotime($m['date_mouvement'])); ?></strong>
                            </td>
                            <td>
                                <?php
                                $badges = [
                                    'entree' => ['bg-success', 'Entrée'],
                                    'sortie' => ['bg-danger', 'Sortie'],
                                    'ajustement' => ['bg-warning', 'Ajustement'],
                                    'transfert' => ['bg-info', 'Transfert'],
                                    'inventaire' => ['bg-purple', 'Inventaire'],
                                    'perte' => ['bg-dark', 'Perte']
                                ];
                                $badge_info = $badges[$m['type_mouvement']] ?? ['bg-secondary', ucfirst($m['type_mouvement'])];
                                ?>
                                <span class="badge <?php echo $badge_info[0]; ?>">
                                    <?php echo $badge_info[1]; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo e($m['nom_produit']); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?php echo number_format(abs($m['quantite'])); ?></span>
                            </td>
                            <td style="font-size: 0.85rem;">
                                <?php if ($m['type_mouvement'] === 'transfert'): ?>
                                    <small class="text-muted">De:</small> <?php echo e($m['depot_source_nom']); ?><br>
                                    <small class="text-muted">Vers:</small> <?php echo e($m['depot_destination_nom']); ?>
                                <?php else: ?>
                                    <?php echo e($m['depot_source_nom'] ?: $m['depot_destination_nom']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?php echo e($m['motif'] ?: '-'); ?></small>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo e($m['utilisateur_nom']); ?></small>
                            </td>
                            <?php if ($is_admin): ?>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-icon btn-ghost-danger" onclick="supprimerMouvement(<?php echo $m['id_mouvement']; ?>)" title="Supprimer ce mouvement">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <line x1="4" y1="7" x2="20" y2="7"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                                    </svg>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouveau Mouvement -->
<div class="modal modal-blur fade" id="modalNouveauMouvement" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau Mouvement de Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNouveauMouvement">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">
                                Type de mouvement
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-info ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Sélectionnez le type d'opération" style="cursor: help;">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    <polyline points="11 12 12 12 12 16 13 16"/>
                                </svg>
                            </label>
                            <select class="form-select" name="type_mouvement" id="type_mouvement" required>
                                <option value="">Sélectionner...</option>
                                <option value="entree" data-conseil="Utilisez ce type lorsque vous recevez des produits d'un fournisseur, ou lors d'un retour client."> Entrée (Réception marchandise)</option>
                                <option value="sortie" data-conseil="Pour les dons, prélèvements, échantillons, ou usage interne. Ne pas utiliser pour les ventes (cela se fait automatiquement).">Sortie (Autre que vente)</option>
                                <option value="ajustement" data-conseil="Si vous remarquez une différence entre le stock physique et le stock informatique, utilisez ceci pour corriger.">Ajustement (Correction)</option>
                                <option value="transfert" data-conseil="Pour déplacer des produits entre vos emplacements (magasin → entrepôt, ou vice versa).">Transfert (Entre dépôts)</option>
                                <option value="inventaire" data-conseil="Après un inventaire physique, entrez la quantité réelle comptée. Le système ajustera automatiquement.">Inventaire (Comptage)</option>
                                <option value="perte" data-conseil="Pour les produits cassés, périmés, volés, ou endommagés de manière irréparable.">Perte (Casse/Vol/Expiration)</option>
                            </select>
                            <div id="type_conseil" class="alert mt-2" style="display: none; font-size: 0.85rem; padding: 0.75rem; border-left: 4px solid;">
                                <div style="font-size: 0.8rem; opacity: 0.9;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" style="vertical-align: middle; margin-right: 3px;" viewBox="0 0 16 16">
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                                    </svg>
                                    <strong>Conseil :</strong> <span id="type_conseil_text"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">
                                Produit
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-info ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Le produit concerné par ce mouvement" style="cursor: help;">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    <polyline points="11 12 12 12 12 16 13 16"/>
                                </svg>
                            </label>
                            <select class="form-select" name="id_produit" id="id_produit" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($produits as $p): ?>
                                <option value="<?php echo $p['id_produit']; ?>" data-stock="<?php echo $p['quantite_stock']; ?>">
                                    <?php echo e($p['nom_produit']); ?> (Stock: <?php echo $p['quantite_stock']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="produit_info" class="alert alert-primary mt-2" style="display: none; font-size: 0.85rem; padding: 0.75rem;">
                                <div id="produit_info_content"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6" id="div_depot_source">
                            <label class="form-label required">
                                Dépôt source
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-info ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Le dépôt/magasin concerné" style="cursor: help;">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    <polyline points="11 12 12 12 12 16 13 16"/>
                                </svg>
                            </label>
                            <select class="form-select" name="id_depot_source" id="id_depot_source" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($depots as $d): ?>
                                <option value="<?php echo $d['id_depot']; ?>"><?php echo e($d['nom_depot']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="div_depot_destination" style="display: none;">
                            <label class="form-label">Dépôt destination</label>
                            <select class="form-select" name="id_depot_destination" id="id_depot_destination">
                                <option value="">Sélectionner...</option>
                                <?php foreach ($depots as $d): ?>
                                <option value="<?php echo $d['id_depot']; ?>"><?php echo e($d['nom_depot']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">
                                Quantité
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-info ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Nombre d'unités" style="cursor: help;">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    <polyline points="11 12 12 12 12 16 13 16"/>
                                </svg>
                            </label>
                            <input type="number" class="form-control" name="quantite" id="quantite" min="1" required>
                            <div id="quantite_erreur" class="text-danger mt-1" style="display: none; font-size: 0.85rem;"></div>
                            <div id="quantite_info" class="text-muted mt-1" style="display: none; font-size: 0.85rem;"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3" id="div_fournisseur" style="display: none;">
                        <div class="col-md-6">
                            <label class="form-label">Fournisseur</label>
                            <select class="form-select" name="id_fournisseur" id="id_fournisseur">
                                <option value="">Sélectionner...</option>
                                <?php foreach ($fournisseurs as $f): ?>
                                <option value="<?php echo $f['id_fournisseur']; ?>"><?php echo e($f['nom_fournisseur']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Coût unitaire</label>
                            <input type="number" class="form-control" name="cout_unitaire" id="cout_unitaire" min="0" step="0.01">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required" id="label_motif">Motif / Commentaire</label>
                        <textarea class="form-control" name="motif" id="motif" rows="3" placeholder="Ex: Réception commande fournisseur, Correction après inventaire, Produit endommagé..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btnEnregistrer">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialiser les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Gestion du changement de produit - récupérer les infos
    const produitSelect = document.getElementById('id_produit');
    const produitInfo = document.getElementById('produit_info');
    const produitInfoContent = document.getElementById('produit_info_content');
    
    // Variable globale pour stocker le stock par dépôt du produit sélectionné
    let stockParDepot = {};
    
    // Définir la fonction AVANT de l'utiliser
    const handleProduitChange = function() {
        const produitId = this && this.value ? this.value : produitSelect.value;
        
        console.log('Produit sélectionné:', produitId);
        
        if (!produitId) {
            if (produitInfo) produitInfo.style.display = 'none';
            updateConseil();
            return;
        }
        
        // Afficher un indicateur de chargement
        if (produitInfoContent) {
            produitInfoContent.innerHTML = '<div class="text-center"><span class="spinner-border spinner-border-sm"></span> Chargement...</div>';
        }
        if (produitInfo) produitInfo.style.display = 'block';
        
        // Récupérer les informations du produit
        fetch('ajax/get_produit_info.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id_produit=' + produitId
        })
        .then(r => r.json())
        .then(data => {
            console.log('Info produit reçue:', data);
            
            if (data.success && data.data) {
                const produit = data.data;
                
                // Stocker le stock par dépôt pour validation ultérieure
                stockParDepot = {};
                if (produit.stock_par_depot && produit.stock_par_depot.length > 0) {
                    produit.stock_par_depot.forEach(depot => {
                        stockParDepot[depot.id_depot] = parseInt(depot.quantite);
                    });
                }
                
                let html = '<strong>' + produit.nom_produit + '</strong><br>';
                
                // Stock total
                html += '<span class="badge bg-cyan text-dark me-2">Stock total: ' + produit.stock_total + '</span>';
                
                // Prix
                if (produit.prix_vente) {
                    html += '<span class="badge bg-success text-white me-2">Prix vente: ' + parseFloat(produit.prix_vente).toFixed(2) + ' <?php echo $devise; ?></span>';
                }
                
                html += '<br><br><strong style="font-size: 0.75rem;">Stock par dépôt:</strong><br>';
                
                // Stock par dépôt
                if (produit.stock_par_depot && produit.stock_par_depot.length > 0) {
                    produit.stock_par_depot.forEach(depot => {
                        html += '<small class="text-muted">• ' + depot.nom_depot + ': ';
                        html += '<strong>' + depot.quantite + ' ' + produit.unite_mesure + '</strong></small><br>';
                    });
                } else {
                    html += '<small class="text-muted">Aucun stock dans les dépôts</small><br>';
                }
                
                // Seuils
                html += '<br><small class="text-muted">Seuil alerte: ' + produit.seuil_alerte + ' | ';
                html += 'Seuil critique: ' + produit.seuil_critique + '</small>';
                
                if (produitInfoContent) produitInfoContent.innerHTML = html;
                if (produitInfo) produitInfo.style.display = 'block';
            } else {
                if (produitInfoContent) produitInfoContent.innerHTML = '<small class="text-danger">Impossible de charger les infos</small>';
            }
            
            updateConseil();
        })
        .catch(err => {
            console.error('Erreur lors de la récupération des infos produit:', err);
            if (produitInfoContent) produitInfoContent.innerHTML = '<small class="text-danger">Erreur de chargement</small>';
            updateConseil();
        });
    };
    
    // Attacher les event listeners pour les changements manuels
    if (produitSelect) {
        produitSelect.addEventListener('change', handleProduitChange);
        produitSelect.addEventListener('input', handleProduitChange);
    }
    
    // Gestion des paramètres GET pour ouvrir le modal et pré-remplir le produit
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('product_id');
    const openModal = urlParams.get('open_modal');
    
    if (productId && openModal === '1') {
        console.log('Ouverture auto du modal avec produit:', productId);
        
        if (produitSelect) {
            produitSelect.value = productId;
            
            // Appeler directement la fonction au lieu de dispatcher un événement
            handleProduitChange.call({value: productId});
            
            // Attendre un petit moment pour que les infos soient chargées, puis ouvrir le modal
            setTimeout(() => {
                const modal = new bootstrap.Modal(document.getElementById('modalNouveauMouvement'));
                modal.show();
                
                // Nettoyer l'URL pour éviter de rouvrir le modal si l'utilisateur recharge
                window.history.replaceState({}, document.title, 'mouvements_stock.php');
            }, 500);
        }
    }
    
    // Gestion du changement de type de mouvement
    const typeSelect = document.getElementById('type_mouvement');
    const divDepotSource = document.getElementById('div_depot_source');
    const divDepotDestination = document.getElementById('div_depot_destination');
    const divFournisseur = document.getElementById('div_fournisseur');
    const typeConseil = document.getElementById('type_conseil');
    const typeConseilText = document.getElementById('type_conseil_text');
    const labelMotif = document.getElementById('label_motif');
    const motifTextarea = document.getElementById('motif');
    const quantiteInput = document.getElementById('quantite');
    const depotSourceSelect = document.getElementById('id_depot_source');
    const depotDestSelect = document.getElementById('id_depot_destination');
    
    // Fonction pour mettre à jour le conseil avec l'impact sur le stock
    function updateConseil() {
        const type = typeSelect ? typeSelect.value : '';
        const produitId = produitSelect ? produitSelect.value : '';
        const quantite = quantiteInput ? quantiteInput.value : '';
        const depotSource = depotSourceSelect ? depotSourceSelect.options[depotSourceSelect.selectedIndex]?.text : '';
        const depotDest = depotDestSelect ? depotDestSelect.options[depotDestSelect.selectedIndex]?.text : '';
        const produitNom = produitSelect && produitId ? produitSelect.options[produitSelect.selectedIndex]?.text : '';
        
        if (!type) {
            typeConseil.style.display = 'none';
            return;
        }
        
        let conseil = '';
        let labelText = 'Motif/Commentaire';
        let placeholderText = 'Décrivez la raison de ce mouvement';
        
        const typeColors = {
            'entree': { bg: '#d4edda', border: '#28a745' },
            'sortie': { bg: '#f8d7da', border: '#dc3545' },
            'ajustement': { bg: '#fff3cd', border: '#ffc107' },
            'transfert': { bg: '#d1ecf1', border: '#17a2b8' },
            'inventaire': { bg: '#e2d9f3', border: '#6f42c1' },
            'perte': { bg: '#f5c6cb', border: '#721c24' }
        };
        
        switch(type) {
            case 'entree':
                conseil = 'Augmente le stock';
                if (quantite && produitNom) conseil += ` de ${quantite} ${produitNom}`;
                if (depotSource) conseil += ` dans le dépôt "${depotSource}"`;
                conseil += '. Le stock général sera augmenté.';
                labelText = 'Motif de l\'entrée';
                placeholderText = 'Ex: Achat fournisseur, Retour client, Production interne...';
                break;
            case 'sortie':
                conseil = 'Diminue le stock';
                if (quantite && produitNom) conseil += ` de ${quantite} ${produitNom}`;
                if (depotSource) conseil += ` du dépôt "${depotSource}"`;
                conseil += '. Le stock général sera diminué.';
                labelText = 'Raison de la sortie';
                placeholderText = 'Ex: Utilisation interne, Don, Échantillon, Perte...';
                break;
            case 'ajustement':
                conseil = 'Modifie le stock';
                if (quantite && produitNom) conseil += ` de ${quantite} ${produitNom}`;
                if (depotSource) conseil += ` dans le dépôt "${depotSource}"`;
                conseil += '. Peut augmenter ou diminuer selon la quantité actuelle. Le stock général sera ajusté.';
                labelText = 'Raison de l\'ajustement';
                placeholderText = 'Ex: Correction d\'inventaire, Erreur de saisie...';
                break;
            case 'transfert':
                conseil = 'Déplace le stock';
                if (quantite && produitNom) conseil += ` de ${quantite} ${produitNom}`;
                if (depotSource) conseil += ` du dépôt "${depotSource}"`;
                if (depotDest) conseil += ` vers "${depotDest}"`;
                conseil += '. Le stock général reste identique, seule la répartition change.';
                labelText = 'Motif du transfert';
                placeholderText = 'Ex: Réapprovisionnement point de vente, Équilibrage stocks...';
                break;
            case 'inventaire':
                conseil = 'Réinitialise le stock à la quantité exacte comptée';
                if (quantite && produitNom) conseil += ` : ${quantite} ${produitNom}`;
                if (depotSource) conseil += ` dans le dépôt "${depotSource}"`;
                conseil += '. Le stock général sera ajusté pour correspondre au comptage physique.';
                labelText = 'Observation inventaire';
                placeholderText = 'Ex: Inventaire annuel, Inventaire tournant, Contrôle qualité...';
                break;
            case 'perte':
                conseil = 'Diminue le stock suite à une perte';
                if (quantite && produitNom) conseil += ` de ${quantite} ${produitNom}`;
                if (depotSource) conseil += ` du dépôt "${depotSource}"`;
                conseil += '. Le stock général sera diminué. Cette opération est irréversible.';
                labelText = 'Cause de la perte';
                placeholderText = 'Ex: Casse, Péremption, Vol, Détérioration...';
                break;
            default:
                conseil = 'Sélectionnez un type de mouvement';
        }
        
        typeConseilText.textContent = conseil;
        typeConseil.style.display = 'block';
        
        const colors = typeColors[type] || { bg: '#e7f3ff', border: '#0d6efd' };
        typeConseil.style.backgroundColor = colors.bg;
        typeConseil.style.borderLeftColor = colors.border;
        
        // Mettre à jour le label et placeholder du motif
        if (labelMotif) labelMotif.textContent = labelText;
        if (motifTextarea) motifTextarea.placeholder = placeholderText;
    }
    
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            const type = this.value;
            
            // Gérer l'affichage des champs selon le type
            divDepotDestination.style.display = 'none';
            divFournisseur.style.display = 'none';
            document.getElementById('id_depot_destination').removeAttribute('required');
            
            if (type === 'transfert') {
                divDepotDestination.style.display = 'block';
                document.getElementById('id_depot_destination').setAttribute('required', 'required');
            } else if (type === 'entree') {
                divFournisseur.style.display = 'block';
            }
            
            updateConseil();
        });
    }
    
    // Fonction pour valider la quantité selon le type et le dépôt
    function validateQuantite() {
        const type = typeSelect ? typeSelect.value : '';
        const quantite = quantiteInput ? parseInt(quantiteInput.value) : 0;
        const depotSourceId = depotSourceSelect ? depotSourceSelect.value : '';
        const quantiteErreur = document.getElementById('quantite_erreur');
        const quantiteInfo = document.getElementById('quantite_info');
        
        // Réinitialiser les messages
        if (quantiteErreur) quantiteErreur.style.display = 'none';
        if (quantiteInfo) quantiteInfo.style.display = 'none';
        quantiteInput.classList.remove('is-invalid');
        
        if (!type || !quantite || quantite < 1) {
            return true; // Pas de validation si champs vides
        }
        
        // Types qui nécessitent une vérification du stock disponible
        const typesAvecLimite = ['sortie', 'transfert', 'perte'];
        
        if (typesAvecLimite.includes(type) && depotSourceId && stockParDepot[depotSourceId] !== undefined) {
            const stockDisponible = stockParDepot[depotSourceId];
            
            if (quantite > stockDisponible) {
                if (quantiteErreur) {
                    quantiteErreur.textContent = `❌ Stock insuffisant ! Maximum disponible dans ce dépôt : ${stockDisponible}`;
                    quantiteErreur.style.display = 'block';
                }
                quantiteInput.classList.add('is-invalid');
                return false;
            } else if (quantite === stockDisponible) {
                if (quantiteInfo) {
                    quantiteInfo.textContent = `ℹ️ Vous videz complètement ce dépôt (${stockDisponible} disponibles)`;
                    quantiteInfo.style.display = 'block';
                }
            } else if (quantite > stockDisponible * 0.8) {
                if (quantiteInfo) {
                    quantiteInfo.textContent = `ℹ️ Stock restant après opération : ${stockDisponible - quantite}`;
                    quantiteInfo.style.display = 'block';
                }
            }
        }
        
        return true;
    }
    
    // Mettre à jour le conseil quand les autres champs changent
    if (produitSelect) produitSelect.addEventListener('change', updateConseil);
    if (quantiteInput) {
        quantiteInput.addEventListener('input', () => {
            updateConseil();
            validateQuantite();
        });
    }
    if (depotSourceSelect) {
        depotSourceSelect.addEventListener('change', () => {
            updateConseil();
            validateQuantite();
        });
    }
    if (depotDestSelect) depotDestSelect.addEventListener('change', updateConseil);
    
    // Soumission du formulaire
    const formNouveau = document.getElementById('formNouveauMouvement');
    if (formNouveau) {
        formNouveau.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Valider la quantité avant soumission
            if (!validateQuantite()) {
                showAlertModal({
                    title: 'Quantité invalide',
                    message: 'La quantité saisie dépasse le stock disponible dans le dépôt sélectionné.',
                    type: 'error'
                });
                return;
            }
            
            const btnEnregistrer = document.getElementById('btnEnregistrer');
            btnEnregistrer.disabled = true;
            btnEnregistrer.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';
            
            const formData = new FormData(formNouveau);
            
            // Pour les types sans dépôt destination, mettre la valeur à null
            const typeVal = typeSelect.value;
            if (typeVal !== 'transfert' && !formData.get('id_depot_destination')) {
                formData.set('id_depot_destination', '');
            }
            
            fetch('ajax/ajouter_mouvement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Réponse:', data);
                
                btnEnregistrer.disabled = false;
                btnEnregistrer.innerHTML = 'Enregistrer';
                
                if (data.success) {
                    showAlertModal({
                        title: 'Succès',
                        message: 'Mouvement enregistré avec succès',
                        type: 'success'
                    });
                    
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalNouveauMouvement'));
                    if (modal) modal.hide();
                    
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlertModal({
                        title: 'Erreur',
                        message: data.message || 'Une erreur est survenue',
                        type: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                btnEnregistrer.disabled = false;
                btnEnregistrer.innerHTML = 'Enregistrer';
                
                showAlertModal({
                    title: 'Erreur',
                    message: 'Impossible de communiquer avec le serveur',
                    type: 'error'
                });
            });
        });
    }
    
    // Charger Chart.js et initialiser les graphiques
    if (typeof Chart === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        script.onload = initCharts;
        document.head.appendChild(script);
    } else {
        initCharts();
    }
});

// Initialiser les graphiques
function initCharts() {
    const mouvementsParType = <?php echo json_encode($mouvements_par_type); ?>;
    const mouvementsParJour = <?php echo json_encode($mouvements_par_jour); ?>;
    
    const typeLabels = {
        'entree': 'Entrée',
        'sortie': 'Sortie',
        'ajustement': 'Ajustement',
        'transfert': 'Transfert',
        'inventaire': 'Inventaire',
        'perte': 'Perte'
    };
    
    const typeColors = {
        'entree': '#28a745',
        'sortie': '#dc3545',
        'ajustement': '#ffc107',
        'transfert': '#17a2b8',
        'inventaire': '#6f42c1',
        'perte': '#6c757d'
    };
    
    // Graphique par type
    const chartByTypeLabels = mouvementsParType.map(item => typeLabels[item.type_mouvement] || item.type_mouvement);
    const chartByTypeData = mouvementsParType.map(item => item.nombre);
    const chartByTypeColors = mouvementsParType.map(item => typeColors[item.type_mouvement] || '#0d6efd');
    
    const ctxByType = document.getElementById('chartMouventsByType');
    if (ctxByType) {
        new Chart(ctxByType, {
            type: 'doughnut',
            data: {
                labels: chartByTypeLabels,
                datasets: [{
                    data: chartByTypeData,
                    backgroundColor: chartByTypeColors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // Graphique chronologique
    const chartTimelineLabels = mouvementsParJour.map(item => {
        const date = new Date(item.jour);
        return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
    });
    const chartTimelineData = mouvementsParJour.map(item => item.nombre);
    
    const ctxTimeline = document.getElementById('chartMouventsTimeline');
    if (ctxTimeline) {
        new Chart(ctxTimeline, {
            type: 'line',
            data: {
                labels: chartTimelineLabels,
                datasets: [{
                    label: 'Mouvements',
                    data: chartTimelineData,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
}

// Fonction de suppression (async + messages visibles)
async function supprimerMouvement(id) {
    try {
        const checkResp = await fetch('ajax/check_mouvement.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id_mouvement=' + id
        });
        const checkData = await checkResp.json();
        console.log('Réponse check_mouvement:', checkData);

        if (checkData.est_vente) {
            await showAlertModal({
                title: 'Suppression impossible',
                message: 'Pour des raisons de sécurité, les mouvements provenant de ventes ne peuvent pas être supprimés.<br><br>Si vous souhaitez annuler cette vente, veuillez :<br><br>1. Aller à la page <strong>Mes ventes</strong><br>2. Trouver la facture concernée<br>3. Cliquer sur <strong>Annuler cette vente</strong><br><br>Seul un administrateur peut effectuer cette action.',
                type: 'warning'
            });
            return;
        }

        const confirmed = await showConfirmModal({
            title: 'Confirmer la suppression',
            message: 'Êtes-vous sûr de vouloir supprimer ce mouvement ? Cette action inversera l\'impact sur le stock.',
            confirmText: 'Oui, supprimer',
            cancelText: 'Annuler',
            type: 'danger'
        });

        if (!confirmed) return;

        console.log('Suppression confirmée pour mouvement:', id);

        const deleteResp = await fetch('ajax/delete_mouvement.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id_mouvement=' + id
        });
        const deleteData = await deleteResp.json();
        console.log('Réponse delete_mouvement:', deleteData);

        if (deleteData.success) {
            await showAlertModal({
                title: 'Succès',
                message: deleteData.message || 'Mouvement supprimé avec succès',
                type: 'success'
            });
            setTimeout(() => location.reload(), 1000);
        } else {
            await showAlertModal({
                title: 'Erreur',
                message: deleteData.message || 'Une erreur est survenue lors de la suppression',
                type: 'error'
            });
        }
    } catch (err) {
        console.error('Erreur globale suppression:', err);
        await showAlertModal({
            title: 'Erreur',
            message: 'Impossible de supprimer ce mouvement : ' + err.message,
            type: 'error'
        });
    }
}
</script>
