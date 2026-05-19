<?php
/**
 * PAGE : MOUVEMENTS DE STOCK
 * Historique complet des mouvements de stock avec filtres avancés
 */

require_once('protection_pages.php');

// Désactiver le loader pour cette page
$skip_page_loader = true;

$page_title = 'Mouvements de Stock';

// Récupérer les filtres
$type_filtre = $_GET['type'] ?? '';
$produit_filtre = $_GET['produit'] ?? '';
$depot_filtre = $_GET['depot'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

// Construire la requête avec filtres
$where_conditions = ['1=1'];
$params = [];

if ($type_filtre) {
    $where_conditions[] = "ms.type_mouvement = ?";
    $params[] = $type_filtre;
}

if ($produit_filtre) {
    $where_conditions[] = "ms.id_produit = ?";
    $params[] = $produit_filtre;
}

if ($depot_filtre) {
    $where_conditions[] = "(ms.id_depot_source = ? OR ms.id_depot_destination = ?)";
    $params[] = $depot_filtre;
    $params[] = $depot_filtre;
}

if ($date_debut) {
    $where_conditions[] = "DATE(ms.date_mouvement) >= ?";
    $params[] = $date_debut;
}

if ($date_fin) {
    $where_conditions[] = "DATE(ms.date_mouvement) <= ?";
    $params[] = $date_fin;
}

$where_clause = implode(' AND ', $where_conditions);

// Récupérer les mouvements
$mouvements = db_fetch_all("
    SELECT 
        ms.*,
        p.nom_produit,
        ds.nom_depot as depot_source_nom,
        dd.nom_depot as depot_destination_nom,
        f.nom_fournisseur,
        u.nom_complet as utilisateur_nom
    FROM mouvements_stock ms
    LEFT JOIN produits p ON ms.id_produit = p.id_produit
    LEFT JOIN depots ds ON ms.id_depot_source = ds.id_depot
    LEFT JOIN depots dd ON ms.id_depot_destination = dd.id_depot
    LEFT JOIN fournisseurs f ON ms.id_fournisseur = f.id_fournisseur
    LEFT JOIN utilisateurs u ON ms.id_utilisateur = u.id_utilisateur
    WHERE $where_clause
    ORDER BY ms.date_mouvement DESC
    LIMIT 500
", $params);

// Statistiques
$stats = db_fetch_one("
    SELECT 
        COUNT(*) as total_mouvements,
        SUM(CASE WHEN type_mouvement = 'entree' THEN quantite ELSE 0 END) as total_entrees,
        SUM(CASE WHEN type_mouvement = 'sortie' THEN quantite ELSE 0 END) as total_sorties,
        SUM(CASE WHEN type_mouvement = 'transfert' THEN quantite ELSE 0 END) as total_transferts
    FROM mouvements_stock ms
    WHERE $where_clause
", $params);

// Mouvements par type (pour le graphique)
$mouvements_par_type = db_fetch_all("
    SELECT 
        type_mouvement,
        COUNT(*) as nombre
    FROM mouvements_stock ms
    WHERE $where_clause
    GROUP BY type_mouvement
    ORDER BY nombre DESC
", $params);

// Mouvements par jour (pour le graphique timeline)
$mouvements_par_jour = db_fetch_all("
    SELECT 
        DATE(date_mouvement) as jour,
        COUNT(*) as nombre,
        type_mouvement
    FROM mouvements_stock ms
    WHERE $where_clause
    GROUP BY DATE(date_mouvement), type_mouvement
    ORDER BY jour ASC
", $params);

// Listes pour filtres
$produits = db_fetch_all("SELECT id_produit, nom_produit FROM produits WHERE est_actif = 1 ORDER BY nom_produit");
$depots = db_fetch_all("SELECT id_depot, nom_depot FROM depots WHERE est_actif = 1 ORDER BY nom_depot");
$fournisseurs = db_fetch_all("SELECT id_fournisseur, nom_fournisseur FROM fournisseurs WHERE est_actif = 1 ORDER BY nom_fournisseur");

require_once('header.php');
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    📦 Mouvements de Stock
                </h2>
                <div class="text-muted mt-1">Historique complet des opérations de stock</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <?php if ($is_admin): ?>
                    <button type="button" class="btn btn-primary" id="btnNouveauMouvement">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Nouveau Mouvement
                    </button>
                    <?php endif; ?>
                    <a href="impression_mouvements.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" target="_blank" class="btn btn-outline-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2"/>
                            <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4"/>
                            <rect x="7" y="13" width="10" height="8" rx="2"/>
                        </svg>
                        Imprimer
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        
        <!-- Statistiques -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total mouvements</div>
                        </div>
                        <div class="h1 mb-0"><?php echo number_format($stats['total_mouvements']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Entrées</div>
                        </div>
                        <div class="h1 mb-0 text-success"><?php echo number_format($stats['total_entrees']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Sorties</div>
                        </div>
                        <div class="h1 mb-0 text-danger"><?php echo number_format($stats['total_sorties']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Transferts</div>
                        </div>
                        <div class="h1 mb-0 text-info"><?php echo number_format($stats['total_transferts']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Graphique des mouvements par type -->
        <div class="row row-deck mb-3">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📊 Distribution des mouvements par type</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="chartMouventsByType" style="height: 250px; max-height: 250px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📈 Évolution des mouvements</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="chartMouvementsTimeline" style="height: 250px; max-height: 250px;"></canvas>
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
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Type de mouvement</label>
                            <select class="form-select" name="type">
                                <option value="">Tous les types</option>
                                <option value="entree" <?php echo $type_filtre === 'entree' ? 'selected' : ''; ?>>Entrée</option>
                                <option value="sortie" <?php echo $type_filtre === 'sortie' ? 'selected' : ''; ?>>Sortie</option>
                                <option value="ajustement" <?php echo $type_filtre === 'ajustement' ? 'selected' : ''; ?>>Ajustement</option>
                                <option value="transfert" <?php echo $type_filtre === 'transfert' ? 'selected' : ''; ?>>Transfert</option>
                                <option value="inventaire" <?php echo $type_filtre === 'inventaire' ? 'selected' : ''; ?>>Inventaire</option>
                                <option value="perte" <?php echo $type_filtre === 'perte' ? 'selected' : ''; ?>>Perte</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Produit</label>
                            <select class="form-select" name="produit">
                                <option value="">Tous les produits</option>
                                <?php foreach ($produits as $p): ?>
                                <option value="<?php echo $p['id_produit']; ?>" <?php echo $produit_filtre == $p['id_produit'] ? 'selected' : ''; ?>>
                                    <?php echo e($p['nom_produit']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Dépôt</label>
                            <select class="form-select" name="depot">
                                <option value="">Tous les dépôts</option>
                                <?php foreach ($depots as $d): ?>
                                <option value="<?php echo $d['id_depot']; ?>" <?php echo $depot_filtre == $d['id_depot'] ? 'selected' : ''; ?>>
                                    <?php echo e($d['nom_depot']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date début</label>
                            <input type="date" class="form-control" name="date_debut" value="<?php echo e($date_debut); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date fin</label>
                            <input type="date" class="form-control" name="date_fin" value="<?php echo e($date_fin); ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="10" cy="10" r="7"/>
                                <line x1="21" y1="21" x2="15" y2="15"/>
                            </svg>
                            Filtrer
                        </button>
                        <a href="mouvements_stock.php" class="btn btn-link">Réinitialiser</a>
                        <a href="impression_mouvements.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" target="_blank" class="btn btn-outline-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2"/>
                                <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4"/>
                                <rect x="7" y="13" width="10" height="8" rx="2"/>
                            </svg>
                            Imprimer/PDF
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Liste des mouvements -->
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
                                <span class="badge bg-blue"><?php echo number_format($m['quantite']); ?></span>
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
<div class="modal fade" id="modalNouveauMouvement" tabindex="-1" aria-labelledby="modalNouveauMouvementLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNouveauMouvementLabel">Nouveau Mouvement de Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNouveauMouvement">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">
                                Type de mouvement
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-info ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" data-bs-placement="right" title="Sélectionnez le type d'opération. Chaque type a un impact différent sur votre stock." style="cursor: help;">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    <polyline points="11 12 12 12 12 16 13 16"/>
                                </svg>
                            </label>
                            <select class="form-select" name="type_mouvement" id="type_mouvement" required>
                                <option value="">Sélectionner...</option>
                                <option value="entree" 
                                    data-description="Ajoute des produits au stock" 
                                    data-conseil="Utilisez ce type lorsque vous recevez des produits d'un fournisseur, ou lors d'un retour client.">
                                    Entrée (Réception marchandise)
                                </option>
                                <option value="sortie" 
                                    data-description="Retire des produits du stock" 
                                    data-conseil="Pour les dons, prélèvements, échantillons, ou usage interne. Ne pas utiliser pour les ventes (cela se fait automatiquement).">
                                    Sortie (Autre que vente)
                                </option>
                                <option value="ajustement" 
                                    data-description="Corrige le stock" 
                                    data-conseil="Si vous remarquez une différence entre le stock physique et le stock informatique, utilisez ceci pour corriger.">
                                    Ajustement (Correction)
                                </option>
                                <option value="transfert" 
                                    data-description="Déplace des produits d'un dépôt à un autre" 
                                    data-conseil="Pour déplacer des produits entre vos emplacements (magasin → entrepôt, ou vice versa).">
                                    Transfert (Entre dépôts)
                                </option>
                                <option value="inventaire" 
                                    data-description="Enregistre le comptage physique" 
                                    data-conseil="Après un inventaire physique, entrez la quantité réelle comptée. Le système ajustera automatiquement.">
                                    Inventaire (Comptage)
                                </option>
                                <option value="perte" 
                                    data-description="Retire définitivement du stock" 
                                    data-conseil="Pour les produits cassés, périmés, volés, ou endommagés de manière irréparable.">
                                    Perte (Casse/Vol/Expiration)
                                </option>
                            </select>
                            <div id="type_description" class="alert mt-2" style="display: none; font-size: 0.85rem; padding: 0.75rem; border-left: 4px solid;">
                                <div class="mb-1"><strong>Description :</strong> <span id="type_description_text"></span></div>
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-info ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Le produit concerné par ce mouvement de stock" style="cursor: help;">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    <polyline points="11 12 12 12 12 16 13 16"/>
                                </svg>
                            </label>
                            <select class="form-select" name="id_produit" id="id_produit" required>
                                <option value="">Sélectionner un produit...</option>
                                <?php foreach ($produits as $p): ?>
                                <option value="<?php echo $p['id_produit']; ?>"><?php echo e($p['nom_produit']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <!-- Informations du produit sélectionné -->
                            <div id="produit_info" class="alert alert-light mt-2" style="display: none; font-size: 0.8rem; padding: 0.75rem;">
                                <div id="produit_info_content"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6" id="div_depot_source">
                            <label class="form-label required">
                                Dépôt source
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-info ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Le dépôt/magasin concerné par l'opération" style="cursor: help;">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    <polyline points="11 12 12 12 12 16 13 16"/>
                                </svg>
                            </label>
                            <select class="form-select" name="id_depot_source" id="id_depot_source">
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-info ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Nombre d'unités à ajouter, retirer ou transférer" style="cursor: help;">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    <polyline points="11 12 12 12 12 16 13 16"/>
                                </svg>
                            </label>
                            <input type="number" class="form-control" name="quantite" id="quantite" min="1" required>
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
                        <label class="form-label">
                            Motif / Commentaire
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-info ms-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" data-bs-toggle="tooltip" title="Expliquez la raison de ce mouvement pour garder une trace claire" style="cursor: help;">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                                <polyline points="11 12 12 12 12 16 13 16"/>
                            </svg>
                        </label>
                        <textarea class="form-control" name="motif" id="motif" rows="3" placeholder="Ex: Réception commande fournisseur, Correction après inventaire, Produit endommagé..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page chargée, Bootstrap disponible:', typeof bootstrap !== 'undefined');
    
    // Initialiser les tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Bouton Nouveau Mouvement
    const btnNouveau = document.getElementById('btnNouveauMouvement');
    if (btnNouveau) {
        btnNouveau.addEventListener('click', function() {
            console.log('Clic sur Nouveau Mouvement');
            const modalEl = document.getElementById('modalNouveauMouvement');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
                console.log('Modal affiché');
            } else {
                console.error('Element modal introuvable');
            }
        });
    }
    
    // Gestion du changement de produit - Afficher les informations
    const produitSelect = document.getElementById('id_produit');
    if (produitSelect) {
        produitSelect.addEventListener('change', function() {
            const id_produit = this.value;
            const produitInfo = document.getElementById('produit_info');
            const produitInfoContent = document.getElementById('produit_info_content');
            
            if (!id_produit) {
                produitInfo.style.display = 'none';
                return;
            }
            
            // Récupérer les infos du produit
            const formData = new URLSearchParams({
                id_produit: id_produit
            });
            
            fetch('ajax/get_produit_info.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    const produit = data.data;
                    let html = '<strong>' + produit.nom_produit + '</strong><br>';
                    
                    // Code et catégorie
                    if (produit.code_produit) {
                        html += '<small class="text-muted">Code: ' + produit.code_produit + '</small><br>';
                    }
                    if (produit.categorie && produit.categorie !== '-') {
                        html += '<small class="text-muted">Catégorie: ' + produit.categorie + '</small><br>';
                    }
                    if (produit.fournisseur && produit.fournisseur !== '-') {
                        html += '<small class="text-muted">Fournisseur: ' + produit.fournisseur + '</small><br>';
                    }
                    
                    html += '<br>';
                    
                    // Stock
                    let badge_stock = 'bg-success';
                    if (produit.alerte_niveau === 'critique') {
                        badge_stock = 'bg-danger';
                    } else if (produit.alerte_niveau === 'alerte') {
                        badge_stock = 'bg-warning';
                    }
                    
                    html += '<span class="badge ' + badge_stock + ' me-2">';
                    html += '📦 ' + produit.stock_total + ' ' + produit.unite_mesure;
                    html += '</span>';
                    
                    // Prix
                    if (produit.prix_vente) {
                        html += '<span class="badge bg-primary me-2">Prix vente: ' + parseFloat(produit.prix_vente).toFixed(2) + ' ' + produit.devise + '</span>';
                    }
                    
                    html += '<br><br><strong style="font-size: 0.75rem;">Stock par dépôt:</strong><br>';
                    
                    // Stock par dépôt
                    produit.stock_par_depot.forEach(depot => {
                        html += '<small class="text-muted">• ' + depot.nom_depot + ': ';
                        html += '<strong>' + depot.quantite + ' ' + produit.unite_mesure + '</strong></small><br>';
                    });
                    
                    // Seuils
                    html += '<br><small class="text-muted">Seuil alerte: ' + produit.seuil_alerte + ' | ';
                    html += 'Seuil critique: ' + produit.seuil_critique + '</small>';
                    
                    produitInfoContent.innerHTML = html;
                    produitInfo.style.display = 'block';
                } else {
                    produitInfo.style.display = 'none';
                }
            })
            .catch(err => {
                console.error('Erreur:', err);
                produitInfo.style.display = 'none';
            });
        });
    }
    
    const typeSelect = document.getElementById('type_mouvement');
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            // Afficher la description et le conseil du type sélectionné
            const selectedOption = this.options[this.selectedIndex];
            const description = selectedOption.getAttribute('data-description');
            const conseil = selectedOption.getAttribute('data-conseil');
            const descDiv = document.getElementById('type_description');
            const descText = document.getElementById('type_description_text');
            const conseilText = document.getElementById('type_conseil_text');
            
            if (description && this.value) {
                descText.textContent = description;
                conseilText.textContent = conseil || '';
                descDiv.style.display = 'block';
                
                // Définir la couleur selon le type
                const typeColors = {
                    'entree': { bg: '#d4edda', border: '#28a745' },
                    'sortie': { bg: '#f8d7da', border: '#dc3545' },
                    'ajustement': { bg: '#fff3cd', border: '#ffc107' },
                    'transfert': { bg: '#d1ecf1', border: '#17a2b8' },
                    'inventaire': { bg: '#e2d9f3', border: '#6f42c1' },
                    'perte': { bg: '#f5c6cb', border: '#721c24' }
                };
                
                const colors = typeColors[this.value] || { bg: '#e7f3ff', border: '#0d6efd' };
                descDiv.style.backgroundColor = colors.bg;
                descDiv.style.borderLeftColor = colors.border;
                descDiv.classList.remove('alert-info', 'alert-success', 'alert-danger', 'alert-warning');
            } else {
                descDiv.style.display = 'none';
            }
            
            const type = this.value;
            const divSource = document.getElementById('div_depot_source');
            const divDestination = document.getElementById('div_depot_destination');
            const divFournisseur = document.getElementById('div_fournisseur');
            const labelSource = divSource.querySelector('label');
            
            // Reset
            divDestination.style.display = 'none';
            divFournisseur.style.display = 'none';
            document.getElementById('id_depot_destination').removeAttribute('required');
            
            if (type === 'transfert') {
                divDestination.style.display = 'block';
                labelSource.textContent = 'Dépôt source';
                document.getElementById('id_depot_source').setAttribute('required', 'required');
                document.getElementById('id_depot_destination').setAttribute('required', 'required');
            } else if (type === 'entree') {
                labelSource.textContent = 'Dépôt destination';
                divFournisseur.style.display = 'flex';
                document.getElementById('id_depot_source').setAttribute('required', 'required');
            } else if (type === 'sortie' || type === 'perte') {
                labelSource.textContent = 'Dépôt source';
                document.getElementById('id_depot_source').setAttribute('required', 'required');
            } else {
                labelSource.textContent = 'Dépôt';
                document.getElementById('id_depot_source').setAttribute('required', 'required');
            }
        });
    }
    
    // Soumission du formulaire avec confirmation
    const formNouveau = document.getElementById('formNouveauMouvement');
    if (formNouveau) {
        formNouveau.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Récupérer les données du formulaire
            const type = document.getElementById('type_mouvement').value;
            const produitSelect = document.getElementById('id_produit');
            const quantite = document.getElementById('quantite').value;
            const depotSourceSelect = document.getElementById('id_depot_source');
            const depotDestSelect = document.getElementById('id_depot_destination');
            
            // Validation
            if (!type) {
                showAlertModal({
                    title: 'Champ requis',
                    message: 'Veuillez sélectionner un type de mouvement',
                    type: 'error'
                });
                return;
            }
            
            if (!produitSelect.value) {
                showAlertModal({
                    title: 'Champ requis',
                    message: 'Veuillez sélectionner un produit',
                    type: 'error'
                });
                return;
            }
            
            if (!depotSourceSelect.value) {
                showAlertModal({
                    title: 'Champ requis',
                    message: 'Veuillez sélectionner un dépôt',
                    type: 'error'
                });
                return;
            }
            
            if (!quantite || quantite <= 0) {
                showAlertModal({
                    title: 'Quantité invalide',
                    message: 'Veuillez entrer une quantité valide (> 0)',
                    type: 'error'
                });
                return;
            }
            
            if (type === 'transfert' && !depotDestSelect.value) {
                showAlertModal({
                    title: 'Champ requis',
                    message: 'Veuillez sélectionner un dépôt de destination pour un transfert',
                    type: 'error'
                });
                return;
            }
            
            const produitNom = produitSelect.options[produitSelect.selectedIndex].text;
            
            // Construire le message de confirmation
            let message = '';
            let impact = '';
            
            switch(type) {
                case 'entree':
                    const depotDest = depotSourceSelect.options[depotSourceSelect.selectedIndex].text;
                    impact = `<strong>+${quantite} unités</strong> seront <strong class="text-success">ajoutées</strong> au stock`;
                    message = `Produit : <strong>${produitNom}</strong><br>Dépôt : <strong>${depotDest}</strong><br><br>${impact}`;
                    break;
                case 'sortie':
                    const depotSrc = depotSourceSelect.options[depotSourceSelect.selectedIndex].text;
                    impact = `<strong>${quantite} unités</strong> seront <strong class="text-danger">retirées</strong> du stock`;
                    message = `Produit : <strong>${produitNom}</strong><br>Dépôt : <strong>${depotSrc}</strong><br><br>${impact}`;
                    break;
                case 'ajustement':
                    const depotAdj = depotSourceSelect.options[depotSourceSelect.selectedIndex].text;
                    impact = `Le stock sera <strong class="text-warning">ajusté</strong> à <strong>${quantite} unités</strong>`;
                    message = `Produit : <strong>${produitNom}</strong><br>Dépôt : <strong>${depotAdj}</strong><br><br>${impact}<br><small class="text-muted">La différence sera automatiquement calculée</small>`;
                    break;
                case 'transfert':
                    const depotFrom = depotSourceSelect.options[depotSourceSelect.selectedIndex].text;
                    const depotTo = depotDestSelect.options[depotDestSelect.selectedIndex].text;
                    impact = `<strong>${quantite} unités</strong> seront <strong>transférées</strong>`;
                    message = `Produit : <strong>${produitNom}</strong><br>De : <strong class="text-danger">${depotFrom}</strong> (${quantite})<br>Vers : <strong class="text-success">${depotTo}</strong> (+${quantite})<br><br>${impact}`;
                    break;
                case 'inventaire':
                    const depotInv = depotSourceSelect.options[depotSourceSelect.selectedIndex].text;
                    impact = `Le stock sera <strong>ajusté</strong> selon le comptage de <strong>${quantite} unités</strong>`;
                    message = `Produit : <strong>${produitNom}</strong><br>Dépôt : <strong>${depotInv}</strong><br><br>${impact}`;
                    break;
                case 'perte':
                    const depotPerte = depotSourceSelect.options[depotSourceSelect.selectedIndex].text;
                    impact = `<strong>${quantite} unités</strong> seront <strong class="text-danger">retirées définitivement</strong> (perte)`;
                    message = `Produit : <strong>${produitNom}</strong><br>Dépôt : <strong>${depotPerte}</strong><br><br>${impact}`;
                    break;
            }
            
            // Afficher le modal de confirmation
            showConfirmModal({
                title: 'Confirmer le mouvement de stock',
                message: message + '<br><br><small class="text-muted">Cette action sera enregistrée dans l\'historique et modifiera immédiatement votre stock.</small>',
                confirmText: 'Confirmer',
                cancelText: 'Annuler',
                onConfirm: () => {
                    // Soumettre le formulaire
                    const formData = new FormData(formNouveau);
                    
                    // Afficher un indicateur de chargement
                    showAlertModal({
                        title: 'Enregistrement en cours...',
                        message: 'Votre mouvement est en cours d\'enregistrement. Veuillez patienter.',
                        type: 'info'
                    });
                    
                    fetch('ajax/ajouter_mouvement.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => {
                        if (!r.ok) throw new Error('Erreur HTTP: ' + r.status);
                        return r.json();
                    })
                    .then(data => {
                        console.log('Réponse serveur:', data);
                        
                        if (data.success) {
                            showAlertModal({
                                title: 'Succès',
                                message: 'Mouvement enregistré avec succès. La page se met à jour...',
                                type: 'success'
                            });
                            
                            // Fermer le modal du formulaire
                            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNouveauMouvement'));
                            if (modal) modal.hide();
                            
                            // Recharger après 1.5 secondes
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showAlertModal({
                                title: 'Erreur lors de l\'enregistrement',
                                message: data.message || 'Une erreur est survenue',
                                type: 'error'
                            });
                        }
                    })
                    .catch(err => {
                        console.error('Erreur réseau:', err);
                        showAlertModal({
                            title: 'Erreur de communication',
                            message: 'Impossible de contacter le serveur. Détails: ' + err.message,
                            type: 'error'
                        });
                    });
                }
            });
        });
    }
});

// Fonction de suppression
function supprimerMouvement(id) {
    // D'abord, récupérer les infos du mouvement
    fetch('ajax/check_mouvement.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_mouvement=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.est_vente) {
            // C'est un mouvement de vente, bloquer la suppression
            showAlertModal({
                title: 'Suppression impossible',
                message: 'Pour des raisons de sécurité, les mouvements provenant de ventes ne peuvent pas être supprimés.<br><br>Si vous souhaitez annuler cette vente, veuillez :<br><br>1. Aller à la page <strong>Mes ventes</strong><br>2. Trouver la facture concernée<br>3. Cliquer sur <strong>Annuler cette vente</strong><br><br>Seul un administrateur peut effectuer cette action.',
                type: 'warning'
            });
        } else {
            // Ce n'est pas un mouvement de vente, on peut le supprimer
            showConfirmModal({
                title: 'Confirmer la suppression',
                message: 'Êtes-vous sûr de vouloir supprimer ce mouvement ? Cette action inversera l\'impact sur le stock.',
                onConfirm: () => {
                    fetch('ajax/delete_mouvement.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'id_mouvement=' + id
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showAlertModal({
                                title: 'Succès',
                                message: data.message,
                                type: 'success'
                            });
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlertModal({
                                title: 'Erreur',
                                message: data.message,
                                type: 'error'
                            });
                        }
                    });
                }
            });
        }
    })
    .catch(err => {
        console.error('Erreur:', err);
        showAlertModal({
            title: 'Erreur',
            message: 'Impossible de vérifier ce mouvement',
            type: 'error'
        });
    });
}

// Graphiques Chart.js
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si Chart.js est disponible
    if (typeof Chart === 'undefined') {
        // Charger Chart.js si nécessaire
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js';
        script.onload = function() {
            initCharts();
        };
        document.head.appendChild(script);
    } else {
        initCharts();
    }
});

function initCharts() {
    // Données pour le graphique par type
    const mouvementsParType = <?php echo json_encode($mouvements_par_type); ?>;
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
    
    const chartByTypeLabels = mouvementsParType.map(item => typeLabels[item.type_mouvement] || item.type_mouvement);
    const chartByTypeData = mouvementsParType.map(item => item.nombre);
    const chartByTypeColors = mouvementsParType.map(item => typeColors[item.type_mouvement] || '#0d6efd');
    
    // Graphique par type (Doughnut)
    const ctxByType = document.getElementById('chartMouventsByType');
    if (ctxByType) {
        new Chart(ctxByType, {
            type: 'doughnut',
            data: {
                labels: chartByTypeLabels,
                datasets: [{
                    data: chartByTypeData,
                    backgroundColor: chartByTypeColors,
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    }
                }
            }
        });
    }
    
    // Données pour le graphique timeline
    const mouvementsParJour = <?php echo json_encode($mouvements_par_jour); ?>;
    
    // Regrouper par jour
    const joursUniques = [...new Set(mouvementsParJour.map(m => m.jour))].sort();
    const typesMouvement = ['entree', 'sortie', 'transfert', 'ajustement', 'inventaire', 'perte'];
    
    const datasetsTimeline = typesMouvement.map(type => {
        const data = joursUniques.map(jour => {
            const mouvement = mouvementsParJour.find(m => m.jour === jour && m.type_mouvement === type);
            return mouvement ? mouvement.nombre : 0;
        });
        
        return {
            label: typeLabels[type] || type,
            data: data,
            borderColor: typeColors[type],
            backgroundColor: typeColors[type] + '30',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        };
    });
    
    // Graphique timeline (Line)
    const ctxTimeline = document.getElementById('chartMouvementsTimeline');
    if (ctxTimeline) {
        new Chart(ctxTimeline, {
            type: 'line',
            data: {
                labels: joursUniques.map(jour => new Date(jour).toLocaleDateString('fr-FR', { month: 'short', day: 'numeric' })),
                datasets: datasetsTimeline
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 11 }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
}
</script>
