<?php
require_once('protection_pages.php');
$page_title = 'Listes';
$page = isset($_GET['page']) ? $_GET['page'] : 'produits';
$available_pages = ['produits', 'clients', 'categories', 'mouvements', 'ventes', 'credits', 'fournisseurs', 'depots'];
if (!in_array($page, $available_pages)) $page = 'produits';

switch($page) {
    case 'produits': $page_title = 'Gestion des Produits'; break;
    case 'clients': $page_title = 'Gestion des Clients'; break;
    case 'categories': $page_title = 'Gestion des Catégories'; break;
    case 'mouvements': $page_title = 'Mouvements de Stock'; break;
    case 'ventes': $page_title = 'Historique des Ventes'; break;
    case 'credits': $page_title = 'Ventes à Crédit'; break;
    case 'fournisseurs': $page_title = 'Gestion des Fournisseurs'; break;
    case 'depots': $page_title = 'Gestion des Dépôts'; break;
}

$is_vendeur = ($user_niveau == NIVEAU_VENDEUR);
$page_hints = [
    'produits' => 'Astuce : classez vos produits par catégorie et surveillez les seuils pour éviter les ruptures.',
    'clients' => 'Astuce : complétez téléphone et email pour mieux relancer vos clients.',
    'categories' => 'Astuce : des catégories claires accélèrent la recherche en caisse.',
    'mouvements' => 'Astuce : notez un motif précis à chaque ajustement de stock.',
    'ventes' => 'Astuce : contrôlez montants et clients avant d\'imprimer les factures.',
    'credits' => 'Astuce : relancez les clients dont l\'échéance est dépassée et enregistrez chaque versement.',
    'fournisseurs' => 'Astuce : gardez à jour les coordonnées de vos fournisseurs pour faciliter les commandes.',
    'depots' => 'Astuce : organisez vos emplacements de stockage pour optimiser la gestion du stock.'
];
$page_hint = $page_hints[$page];

require_once('header.php');
?>

<style>
.list-card {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: none;
}
.list-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}
.btn-action {
    transition: all 0.2s ease;
}
.btn-action:hover {
    transform: scale(1.05);
}
.table-hover tbody tr {
    transition: all 0.2s ease;
}
.table-hover tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateX(4px);
}
.table.card-table th, .table.card-table td { color: #2f2f2f; vertical-align: middle; }
.badge-category { background: #e7f1ff; color: #0b57d0; font-weight: 600; }
.page-header-title {
    font-weight: 700;
    background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.nav-tabs-modern {
    border-bottom: 2px solid #e9ecef;
    gap: 0.5rem;
}
.nav-tabs-modern .nav-link {
    border: none;
    border-radius: 8px 8px 0 0;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
    position: relative;
}
.nav-tabs-modern .nav-link:hover {
    color: <?php echo $couleur_primaire; ?>;
    background: #f8f9fa;
}
.nav-tabs-modern .nav-link.active {
    color: white !important;
    background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.nav-tabs-modern .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: <?php echo $couleur_primaire; ?>;
}
.badge-stock {
    font-size: 0.875rem;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
}
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}
.stat-card h4 {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 1rem;
    max-width: 100%;
}
.stat-card .text-muted {
    font-size: 0.85rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<div class="container-xl">
    <!-- En-tête de page amélioré -->
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <h2 class="page-title page-header-title mb-2"><?php echo $page_title; ?></h2>
                <p class="text-muted mb-0"><?php echo $page_hint; ?></p>
            </div>
            <div class="col-md-4 text-end">
                <a href="accueil.php" class="btn btn-outline-secondary btn-action">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <line x1="5" y1="12" x2="9" y2="16"/>
                        <line x1="5" y1="12" x2="9" y2="8"/>
                    </svg>
                    Retour
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation moderne avec onglets -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs-modern" role="tablist">
                <li class="nav-item"><a href="?page=produits" class="nav-link <?php echo $page === 'produits' ? 'active' : ''; ?>">Produits</a></li>
                <li class="nav-item"><a href="?page=clients" class="nav-link <?php echo $page === 'clients' ? 'active' : ''; ?>">Clients</a></li>
                <li class="nav-item"><a href="?page=categories" class="nav-link <?php echo $page === 'categories' ? 'active' : ''; ?>">Catégories</a></li>
                <li class="nav-item"><a href="?page=fournisseurs" class="nav-link <?php echo $page === 'fournisseurs' ? 'active' : ''; ?>">Fournisseurs</a></li>
                <li class="nav-item"><a href="?page=depots" class="nav-link <?php echo $page === 'depots' ? 'active' : ''; ?>">Dépôts</a></li>
                <li class="nav-item"><a href="?page=mouvements" class="nav-link <?php echo $page === 'mouvements' ? 'active' : ''; ?>">Mouvements</a></li>
                <li class="nav-item"><a href="?page=ventes" class="nav-link <?php echo $page === 'ventes' ? 'active' : ''; ?>">Ventes</a></li>
                <li class="nav-item"><a href="?page=credits" class="nav-link <?php echo $page === 'credits' ? 'active' : ''; ?>">Crédits</a></li>
            </ul>
        </div>
    </div>

    <?php
    switch ($page) {
        case 'produits':
            // Admin : tous les produits avec stock global
            // Vendeur : produits de son dépôt avec stock dépôt
            if ($is_admin) {
                $produits = db_fetch_all("
                    SELECT p.*, c.nom_categorie, p.quantite_stock AS stock_disponible, NULL AS nom_depot_stock
                    FROM produits p
                    LEFT JOIN categories c ON p.id_categorie = c.id_categorie
                    WHERE p.est_actif = 1
                    ORDER BY p.nom_produit ASC
                ");
            } else {
                $info_vendeur = db_fetch_one("SELECT id_depot FROM utilisateurs WHERE id_utilisateur = ?", [$user_id]);
                $id_depot_listes = $info_vendeur['id_depot'] ?? null;
                if ($id_depot_listes) {
                    $produits = db_fetch_all("
                        SELECT p.*, c.nom_categorie, s.quantite AS stock_disponible, d.nom_depot AS nom_depot_stock
                        FROM produits p
                        INNER JOIN stock_par_depot s ON s.id_produit = p.id_produit AND s.id_depot = ?
                        LEFT JOIN categories c ON p.id_categorie = c.id_categorie
                        LEFT JOIN depots d ON d.id_depot = s.id_depot
                        WHERE p.est_actif = 1
                        ORDER BY p.nom_produit ASC
                    ", [$id_depot_listes]);
                } else {
                    $produits = [];
                }
            }
            $categories = db_fetch_all("SELECT * FROM categories WHERE est_actif = 1 ORDER BY nom_categorie ASC");

            // Calculer statistiques
            $total_produits = count($produits);
            $stock_faible = 0;
            $valeur_stock = 0;
            foreach($produits as $p) {
                $stock_ref = $p['stock_disponible'] ?? $p['quantite_stock'];
                if($stock_ref <= $p['stock_minimum']) $stock_faible++;
                $valeur_stock += $stock_ref * $p['prix_achat'];
            }
            ?>
            
            <!-- Cartes statistiques -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1" data-bs-toggle="tooltip" title="Nombre total de produits actifs dans le catalogue">Total Produits</h6>
                                <h3 class="mb-0"><?php echo $total_produits; ?></h3>
                            </div>
                            <div class="text-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" />
                                    <path d="M12 12l8 -4.5" />
                                    <path d="M12 12l0 9" />
                                    <path d="M12 12l-8 -4.5" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1" data-bs-toggle="tooltip" title="Valeur totale du stock : somme (quantité × prix d'achat) de tous les produits">Valeur Stock</h6>
                                <h3 class="mb-0"><?php echo format_montant($valeur_stock, $devise); ?></h3>
                            </div>
                            <div class="text-success">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 3a9 9 0 1 0 9 9" />
                                    <path d="M12 7v10" />
                                    <path d="M9 10c0 -1.657 1.343 -3 3 -3h2" />
                                    <path d="M15 14c0 1.657 -1.343 3 -3 3h-2" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1" data-bs-toggle="tooltip" title="Produits dont le stock est inférieur ou égal au seuil minimum défini">Stock Faible</h6>
                                <h3 class="mb-0 <?php echo $stock_faible > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo $stock_faible; ?></h3>
                            </div>
                            <div class="<?php echo $stock_faible > 0 ? 'text-danger' : 'text-success'; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 9v4" />
                                    <path d="M12 17v.01" />
                                    <path d="M5.07 19h13.86a1 1 0 0 0 .87 -1.5l-6.93 -12a1 1 0 0 0 -1.74 0l-6.93 12a1 1 0 0 0 .87 1.5z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card list-card">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h3 class="card-title mb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" />
                        </svg>
                        Liste des produits
                        <span class="badge bg-primary ms-2"><?php echo $total_produits; ?></span>
                    </h3>
                    <div>
                        <?php if (!$is_vendeur): ?>
                        <button type="button" id="btnAddProduct" class="btn btn-primary btn-action" data-bs-toggle="tooltip" title="Créer un nouveau produit">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 5v14" />
                                <path d="M5 12h14" />
                            </svg>
                            Nouveau produit
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body border-bottom pb-2">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="10" cy="10" r="7" />
                                <line x1="21" y1="21" x2="15" y2="15" />
                            </svg>
                        </span>
                        <input type="text" id="searchProduits" class="form-control" placeholder="Rechercher un produit (nom, code, catégorie)..." />
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter table-hover">
                        <thead>
                            <tr>
                                <th data-bs-toggle="tooltip" title="Image ou initiales du produit">Image</th>
                                <th data-bs-toggle="tooltip" title="Nom complet du produit">Nom</th>
                                <th data-bs-toggle="tooltip" title="Catégorie d'appartenance">Catégorie</th>
                                <?php if (!$is_vendeur): ?><th class="text-end" data-bs-toggle="tooltip" title="Coût d'achat (réservé gestionnaire)">Prix Achat</th><?php endif; ?>
                                <th class="text-end" data-bs-toggle="tooltip" title="Tarif de vente conseillé">Prix Vente</th>
                                <th class="text-center" data-bs-toggle="tooltip" title="Quantité disponible">Stock</th>
                                <?php if (!$is_admin): ?><th data-bs-toggle="tooltip" title="Dépôt associé">Dépôt</th><?php endif; ?>
                                <th class="text-center" data-bs-toggle="tooltip" title="Actions disponibles">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="produitsTableBody">
                            <?php if(empty($produits)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <div style="font-size: 3rem;">📦</div>
                                        <p class="mb-0">Aucun produit trouvé</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($produits as $p): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($p['image_produit'])): ?>
                                            <img src="uploads/produits/<?php echo e($p['image_produit']); ?>" alt="<?php echo e($p['nom_produit']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                <?php echo strtoupper(substr($p['nom_produit'], 0, 2)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo e($p['nom_produit']); ?></div>
                                        <?php if (!empty($p['code_barre'])): ?>
                                            <small class="text-muted"><?php echo e($p['code_barre']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['nom_categorie'])): ?>
                                            <span class="badge badge-category" data-bs-toggle="tooltip" title="Catégorie du produit"><?php echo e($p['nom_categorie']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted" data-bs-toggle="tooltip" title="Aucune catégorie assignée">Sans catégorie</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if (!$is_vendeur): ?><td class="text-end" data-bs-toggle="tooltip" title="Coût d'achat pour le stock"><?php echo format_montant($p['prix_achat'], $devise); ?></td><?php endif; ?>
                                    <td class="text-end fw-bold" data-bs-toggle="tooltip" title="Tarif de vente conseillé"><?php echo format_montant($p['prix_vente'], $devise); ?></td>
                                    <td class="text-center">
                                        <?php
                                        $stock_aff = $p['stock_disponible'] ?? $p['quantite_stock'];
                                        $stock_class = 'bg-success';
                                        if ($stock_aff == 0) $stock_class = 'bg-danger';
                                        elseif ($stock_aff <= $p['stock_minimum']) $stock_class = 'bg-warning';
                                        ?>
                                        <span class="badge badge-stock <?php echo $stock_class; ?>" data-bs-toggle="tooltip" title="Quantité disponible">
                                            <?php echo $stock_aff; ?>
                                        </span>
                                    </td>
                                    <?php if (!$is_admin): ?>
                                    <td class="text-muted small"><?php echo e($p['nom_depot_stock'] ?? '—'); ?></td>
                                    <?php endif; ?>
                                    <td class="text-center">
                                        <?php if ($is_vendeur): ?>
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-success btn-action btn-adjust-stock" data-id="<?php echo $p['id_produit']; ?>" data-name="<?php echo e($p['nom_produit']); ?>" title="Ajuster le stock">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M12 5v14" />
                                                    <path d="M5 12h14" />
                                                    <path d="M9 7h6a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2v-6a2 2 0 0 1 2 -2z" />
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-primary btn-action btn-edit-product" data-product='<?php echo json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT); ?>' title="Modifier">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                                                        <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                                                        <path d="M16 5l3 3" />
                                                    </svg>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-success btn-action btn-adjust-stock" data-id="<?php echo $p['id_produit']; ?>" data-name="<?php echo e($p['nom_produit']); ?>" title="Ajuster le stock">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M12 5v14" />
                                                        <path d="M5 12h14" />
                                                        <path d="M9 7h6a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2v-6a2 2 0 0 1 2 -2z" />
                                                    </svg>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger btn-action btn-delete-product" data-id="<?php echo $p['id_produit']; ?>" data-name="<?php echo e($p['nom_produit']); ?>" title="Supprimer">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <line x1="4" y1="7" x2="20" y2="7" />
                                                        <line x1="10" y1="11" x2="10" y2="17" />
                                                        <line x1="14" y1="11" x2="14" y2="17" />
                                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                    </svg>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Produit -->
            <div class="modal fade" id="modalProduit" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalProduitTitle">Nouveau produit</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="productForm" data-no-loader>
                            <div class="modal-body">
                                <input type="hidden" id="product_id" name="product_id">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Type de produit *</label>
                                        <div class="d-flex gap-3">
                                            <label class="form-check">
                                                <input class="form-check-input" type="radio" name="product_type" id="product_type_standard" value="standard" checked onchange="toggleStockFields()">
                                                <span class="form-check-label">Standard <small class="text-muted">(avec gestion de stock)</small></span>
                                            </label>
                                            <label class="form-check">
                                                <input class="form-check-input" type="radio" name="product_type" id="product_type_service" value="service" onchange="toggleStockFields()">
                                                <span class="form-check-label">Service <small class="text-muted">(sans stock — couture, main d'œuvre…)</small></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Nom complet du produit">Nom du produit *</label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Code-barres pour scanner rapide en caisse">Code-barres</label>
                                        <input type="text" class="form-control" id="product_barcode" name="product_barcode" placeholder="Ex: 3760123456789">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Classement du produit pour faciliter la recherche">Catégorie *</label>
                                        <select class="form-select" id="product_category" name="product_category" required>
                                            <option value="">Sélectionner...</option>
                                            <?php foreach($categories as $cat): ?>
                                                <option value="<?php echo $cat['id_categorie']; ?>"><?php echo e($cat['nom_categorie']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Qui fournit ce produit">Fournisseur *</label>
                                        <select class="form-select" id="product_fournisseur" name="product_fournisseur" required>
                                            <option value="">Sélectionner...</option>
                                            <?php $fournisseurs_list = db_fetch_all("SELECT * FROM fournisseurs WHERE est_actif = 1 ORDER BY nom_fournisseur"); ?>
                                            <?php foreach($fournisseurs_list as $f): ?>
                                                <option value="<?php echo $f['id_fournisseur']; ?>"><?php echo e($f['nom_fournisseur']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Unité de mesure (ex: pièce, kg, litre, boîte, carton)">Unité *</label>
                                        <input type="text" class="form-control" id="product_unit" name="product_unit" value="pièce" required placeholder="Ex: pièce, kg, litre...">
                                    </div>
                                    <div class="col-md-6 mb-3 stock-only-field">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Où ranger ce produit initialement">Dépôt initial *</label>
                                        <select class="form-select" id="product_depot" name="product_depot">
                                            <option value="">Sélectionner...</option>
                                            <?php $depots_list = db_fetch_all("SELECT * FROM depots WHERE est_actif = 1 ORDER BY est_principal DESC, nom_depot"); ?>
                                            <?php foreach($depots_list as $d): ?>
                                                <option value="<?php echo $d['id_depot']; ?>"><?php echo e($d['nom_depot']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php if ($is_vendeur): ?>
                                        <input type="hidden" id="product_purchase_price" name="product_purchase_price" value="0">
                                    <?php else: ?>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Coût d'achat (réservé gestionnaire)">Prix d'achat (<?php echo $devise; ?>) *</label>
                                        <input type="number" class="form-control" id="product_purchase_price" name="product_purchase_price" step="0.01" required placeholder="0.00">
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-<?php echo $is_vendeur ? '6' : '4'; ?> mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Tarif de vente conseillé">Prix de vente (<?php echo $devise; ?>) *</label>
                                        <input type="number" class="form-control" id="product_sale_price" name="product_sale_price" step="0.01" required placeholder="0.00">
                                    </div>
                                    <!-- Création : champ stock initial -->
                                    <div class="col-md-4 mb-3 stock-only-field" id="stock_initial_field">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Quantité au moment de la création">Stock initial</label>
                                        <input type="number" class="form-control" id="product_stock" name="product_stock" value="0">
                                    </div>
                                    <!-- Édition : stock actuel en lecture seule -->
                                    <div class="col-md-4 mb-3 stock-only-field" id="stock_actuel_field" style="display:none;">
                                        <label class="form-label">Stock actuel</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="product_stock_display" readonly style="background:#f8f9fa;">
                                            <button type="button" class="btn btn-outline-secondary" onclick="goToStockAdjust()" title="Ajuster le stock">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                            </button>
                                        </div>
                                        <small class="text-muted">Modifiable via <a href="#" onclick="goToStockAdjust(); return false;">Ajustement de stock</a></small>
                                    </div>
                                    <div class="col-md-6 mb-3 stock-only-field">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Seuil d'alerte avant rupture">Stock minimum</label>
                                        <input type="number" class="form-control" id="product_min_stock" name="product_min_stock" value="5">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Photo du produit (sera redimensionnée automatiquement)">Image</label>
                                        <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*" onchange="previewProductImage(this)">
                                        <small class="text-muted">Format recommandé : carré, max 2MB</small>
                                        <div id="product_image_preview" class="mt-2" style="display:none;">
                                            <img id="product_image_preview_img" src="" alt="Aperçu" style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 2px solid #e9ecef;">
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Détails utiles pour la vente (format, couleur, etc.)">Description</label>
                                        <textarea class="form-control" id="product_description" name="product_description" rows="3" placeholder="Ex: Savon parfumé 250g, format familial, couleur blanche avec emballage recyclable"></textarea>
                                    </div>
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

            <!-- Modal Ajustement Stock -->
            <div class="modal fade" id="modalAjustStock" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Ajuster le stock</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="stockForm" data-no-loader>
                            <div class="modal-body">
                                <input type="hidden" id="stock_product_id" name="stock_product_id">
                                <p class="mb-3">Produit: <strong id="stock_product_name"></strong></p>
                                <div class="mb-3">
                                    <label class="form-label">Type de mouvement *</label>
                                    <select class="form-select" id="stock_type" name="stock_type" required>
                                        <option value="entree">Entrée (ajout au stock)</option>
                                        <option value="sortie">Sortie (retrait du stock)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Quantité *</label>
                                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="1" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Motif</label>
                                    <textarea class="form-control" id="stock_reason" name="stock_reason" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Valider</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php
            break;

        case 'clients':
            $clients = db_fetch_all("
                SELECT c.*, 
                       COUNT(DISTINCT v.id_vente) as nb_ventes,
                       COALESCE(SUM(v.montant_total), 0) as total_achats
                FROM clients c
                LEFT JOIN ventes v ON c.id_client = v.id_client
                WHERE c.est_actif = 1
                GROUP BY c.id_client
                ORDER BY c.nom_client ASC
            ");
            
            $total_clients = count($clients);
            $clients_actifs = 0;
            $ca_total = 0;
            foreach($clients as $cli) {
                if($cli['nb_ventes'] > 0) $clients_actifs++;
                $ca_total += $cli['total_achats'];
            }
            ?>
            
            <!-- Statistiques Clients -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-muted" data-bs-toggle="tooltip" title="Nombre total de clients enregistrés dans le système">Total Clients</div>
                                <h3 class="mb-0"><?php echo $total_clients; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                            </div>
                            <div>
                                <div class="text-muted" data-bs-toggle="tooltip" title="Clients ayant effectué au moins une vente">Clients Actifs</div>
                                <h3 class="mb-0"><?php echo $clients_actifs; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#17a2b8" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-muted" data-bs-toggle="tooltip" title="Chiffre d'Affaires : somme totale des ventes de tous les clients">CA Total</div>
                                <h3 class="mb-0"><?php echo format_montant($ca_total, $devise); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions et tableau -->
            <div class="card list-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des Clients</h5>
                    <button type="button" id="btnAddClient" class="btn btn-primary btn-action">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Nouveau client
                    </button>
                </div>
                <div class="card-body border-bottom pb-2">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="10" cy="10" r="7" />
                                <line x1="21" y1="21" x2="15" y2="15" />
                            </svg>
                        </span>
                        <input type="text" id="searchClients" class="form-control" placeholder="Rechercher un client (nom, téléphone, email)..." />
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover card-table">
                            <thead>
                                <tr>
                                    <th data-bs-toggle="tooltip" title="Nom complet du client">Nom</th>
                                    <th data-bs-toggle="tooltip" title="Numéro de téléphone">Téléphone</th>
                                    <th data-bs-toggle="tooltip" title="Adresse email">Email</th>
                                    <th data-bs-toggle="tooltip" title="Adresse physique">Adresse</th>
                                    <th data-bs-toggle="tooltip" title="Nombre de ventes effectuées">Ventes</th>
                                    <th data-bs-toggle="tooltip" title="Chiffre d'affaires généré">CA Total</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clients)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Aucun client enregistré</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach($clients as $client): ?>
                                <tr>
                                    <td><strong><?php echo e($client['nom_client']); ?></strong></td>
                                    <td><?php echo e($client['telephone'] ?: '—'); ?></td>
                                    <td><?php echo e($client['email'] ?: '—'); ?></td>
                                    <td><?php echo e($client['adresse'] ?: '—'); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $client['nb_ventes']; ?></span>
                                    </td>
                                    <td><?php echo format_montant($client['total_achats'], $devise); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-edit-client" 
                                                    data-client='<?php echo json_encode($client); ?>'
                                                    data-bs-toggle="tooltip" title="Modifier">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-delete-client" 
                                                    data-id="<?php echo $client['id_client']; ?>"
                                                    data-name="<?php echo e($client['nom_client']); ?>"
                                                    data-bs-toggle="tooltip" title="Supprimer">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
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

            <!-- Modal Client -->
            <div class="modal fade" id="modalClient" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalClientTitle">Nouveau client</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="clientForm" data-no-loader>
                            <div class="modal-body">
                                <input type="hidden" id="client_id" name="client_id">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Nom complet du client">Nom du client *</label>
                                        <input type="text" class="form-control" id="client_name" name="client_name" required placeholder="Ex: Jean Dupont">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Téléphone pour contact et rappels">Téléphone</label>
                                        <input type="tel" class="form-control" id="client_telephone" name="client_telephone" placeholder="Ex: +226 900 000 000">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Email pour factures et communications">Email</label>
                                        <input type="email" class="form-control" id="client_email" name="client_email" placeholder="Ex: client@exemple.com">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" data-bs-toggle="tooltip" title="Adresse physique ou quartier">Adresse</label>
                                        <input type="text" class="form-control" id="client_adresse" name="client_adresse" placeholder="Ex: Avenue Lumumba, Q. Lubumbashi">
                                    </div>
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
            
            <?php
            break;
        case 'categories':
            $categories = db_fetch_all("
                SELECT c.*, 
                       COUNT(p.id_produit) as nb_produits
                FROM categories c
                LEFT JOIN produits p ON c.id_categorie = p.id_categorie AND p.est_actif = 1
                WHERE c.est_actif = 1
                GROUP BY c.id_categorie
                ORDER BY c.nom_categorie ASC
            ");
            
            $total_categories = count($categories);
            $categories_utilisees = 0;
            $total_produits = 0;
            foreach($categories as $cat) {
                if($cat['nb_produits'] > 0) $categories_utilisees++;
                $total_produits += $cat['nb_produits'];
            }
            ?>
            
            <!-- Statistiques Catégories -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                </svg>
                            </div>
                            <div>
                                <div class="text-muted" data-bs-toggle="tooltip" title="Nombre total de catégories créées">Total Catégories</div>
                                <h3 class="mb-0"><?php echo $total_categories; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                </svg>
                            </div>
                            <div>
                                <div class="text-muted" data-bs-toggle="tooltip" title="Catégories contenant au moins un produit">Catégories Utilisées</div>
                                <h3 class="mb-0"><?php echo $categories_utilisees; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#17a2b8" stroke-width="2">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-muted" data-bs-toggle="tooltip" title="Nombre total de produits classés dans toutes les catégories">Total Produits</div>
                                <h3 class="mb-0"><?php echo $total_produits; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions et tableau -->
            <div class="card list-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des Catégories</h5>
                    <button type="button" id="btnAddCategory" class="btn btn-primary btn-action">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Nouvelle catégorie
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover card-table">
                            <thead>
                                <tr>
                                    <th data-bs-toggle="tooltip" title="Nom de la catégorie">Catégorie</th>
                                    <th data-bs-toggle="tooltip" title="Description ou notes">Description</th>
                                    <th data-bs-toggle="tooltip" title="Nombre de produits dans cette catégorie">Produits</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Aucune catégorie créée</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach($categories as $category): ?>
                                <tr>
                                    <td>
                                        <span class="badge-category"><?php echo e($category['nom_categorie']); ?></span>
                                    </td>
                                    <td><?php echo e($category['description'] ?: '—'); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $category['nb_produits']; ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-edit-category" 
                                                    data-category='<?php echo json_encode($category); ?>'
                                                    data-bs-toggle="tooltip" title="Modifier">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-delete-category" 
                                                    data-id="<?php echo $category['id_categorie']; ?>"
                                                    data-name="<?php echo e($category['nom_categorie']); ?>"
                                                    data-products="<?php echo $category['nb_produits']; ?>"
                                                    data-bs-toggle="tooltip" title="Supprimer">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
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

            <!-- Modal Catégorie -->
            <div class="modal fade" id="modalCategory" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalCategoryTitle">Nouvelle catégorie</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="categoryForm" data-no-loader>
                            <div class="modal-body">
                                <input type="hidden" id="category_id" name="category_id">
                                <div class="mb-3">
                                    <label class="form-label" data-bs-toggle="tooltip" title="Nom court et descriptif">Nom de la catégorie *</label>
                                    <input type="text" class="form-control" id="category_name" name="category_name" required placeholder="Ex: Boissons, Épicerie, Hygiène">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" data-bs-toggle="tooltip" title="Description ou notes pour usage interne">Description</label>
                                    <textarea class="form-control" id="category_description" name="category_description" rows="3" placeholder="Ex: Produits d'épicerie sèche et conserves"></textarea>
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
            
            <?php
            break;
        case 'mouvements':
            try {
                $mouvements = db_fetch_all("
                    SELECT m.*, p.nom_produit, p.unite_mesure as unite, u.nom_complet
                    FROM mouvements_stock m
                    LEFT JOIN produits p ON m.id_produit = p.id_produit
                    LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur
                    ORDER BY m.date_mouvement DESC
                    LIMIT 200
                ");
                
                $entrees = 0;
                $sorties = 0;
                $ajustements = 0;
                foreach($mouvements as $mv) {
                    if($mv['type_mouvement'] == 'entree') $entrees++;
                    elseif($mv['type_mouvement'] == 'sortie') $sorties++;
                    elseif($mv['type_mouvement'] == 'ajustement') $ajustements++;
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">';
                echo '<strong>Erreur lors du chargement des mouvements:</strong><br>';
                echo e($e->getMessage());
                echo '<br><br><a href="migration_mouvements.php" class="btn btn-primary">Vérifier la table mouvements_stock</a>';
                echo '</div>';
                break;
            }
            ?>
            
            <!-- Statistiques Mouvements -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <polyline points="5 12 12 19 19 12"></polyline>
                                </svg>
                            </div>
                            <div>
                                <div class="text-muted" data-bs-toggle="tooltip" title="Nombre de mouvements d'entrée en stock (achats, réceptions)">Entrées</div>
                                <h3 class="mb-0 text-success"><?php echo $entrees; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2">
                                    <line x1="12" y1="19" x2="12" y2="5"></line>
                                    <polyline points="5 12 12 5 19 12"></polyline>
                                </svg>
                            </div>
                            <div>
                                <div class="text-muted" data-bs-toggle="tooltip" title="Nombre de mouvements de sortie de stock (ventes, pertes)">Sorties</div>
                                <h3 class="mb-0 text-danger"><?php echo $sorties; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ffc107" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div>
                                <div class="text-muted" data-bs-toggle="tooltip" title="Nombre de corrections manuelles de stock (inventaire, erreurs)">Ajustements</div>
                                <h3 class="mb-0 text-warning"><?php echo $ajustements; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des mouvements -->
            <div class="card list-card">
                <div class="card-header">
                    <h5 class="mb-0">Historique des Mouvements (200 derniers)</h5>
                </div>
                <div class="card-body border-bottom pb-2">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="10" cy="10" r="7" />
                                <line x1="21" y1="21" x2="15" y2="15" />
                            </svg>
                        </span>
                        <input type="text" id="searchMouvements" class="form-control" placeholder="Rechercher un mouvement (produit, type, motif, utilisateur)..." />
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover card-table">
                            <thead>
                                <tr>
                                    <th data-bs-toggle="tooltip" title="Date et heure du mouvement">Date</th>
                                    <th data-bs-toggle="tooltip" title="Produit concerné">Produit</th>
                                    <th data-bs-toggle="tooltip" title="Type d'opération">Type</th>
                                    <th data-bs-toggle="tooltip" title="Quantité modifiée">Quantité</th>
                                    <th data-bs-toggle="tooltip" title="Stock après l'opération">Nouveau Stock</th>
                                    <th data-bs-toggle="tooltip" title="Raison du mouvement">Motif</th>
                                    <th data-bs-toggle="tooltip" title="Utilisateur ayant effectué l'opération">Utilisateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mouvements)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Aucun mouvement enregistré</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach($mouvements as $mv): ?>
                                <tr>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i', strtotime($mv['date_mouvement'])); ?></small>
                                    </td>
                                    <td><strong><?php echo e($mv['nom_produit'] ?? 'Produit supprimé'); ?></strong></td>
                                    <td>
                                        <?php
                                        $badge_class = 'secondary';
                                        $type_label = $mv['type_mouvement'];
                                        if($mv['type_mouvement'] == 'entree') {
                                            $badge_class = 'success';
                                            $type_label = 'Entrée';
                                        } elseif($mv['type_mouvement'] == 'sortie') {
                                            $badge_class = 'danger';
                                            $type_label = 'Sortie';
                                        } elseif($mv['type_mouvement'] == 'ajustement') {
                                            $badge_class = 'warning';
                                            $type_label = 'Ajustement';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $type_label; ?></span>
                                    </td>
                                    <td>
                                        <strong class="<?php echo $mv['quantite'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo ($mv['quantite'] > 0 ? '+' : '') . $mv['quantite']; ?> <?php echo e($mv['unite'] ?? ''); ?>
                                        </strong>
                                    </td>
                                    <td><?php echo $mv['stock_apres']; ?> <?php echo e($mv['unite'] ?? ''); ?></td>
                                    <td><?php echo e($mv['motif'] ?: '—'); ?></td>
                                    <td><small><?php echo e($mv['nom_complet'] ?? 'Utilisateur supprimé'); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php
            break;
        case 'ventes':
            try {
                // Filtres
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
                           u.nom_complet as vendeur,
                           COUNT(DISTINCT d.id_detail) as nb_articles
                    FROM ventes v
                    LEFT JOIN clients c ON v.id_client = c.id_client
                    LEFT JOIN utilisateurs u ON v.id_vendeur = u.id_utilisateur
                    LEFT JOIN details_vente d ON v.id_vente = d.id_vente
                    WHERE 1=1
                ";
                
                if ($filter_date_from) $query .= " AND DATE(v.date_vente) >= '" . date('Y-m-d', strtotime($filter_date_from)) . "'";
                if ($filter_date_to) $query .= " AND DATE(v.date_vente) <= '" . date('Y-m-d', strtotime($filter_date_to)) . "'";
                if ($filter_client) $query .= " AND v.id_client = " . intval($filter_client);
                if ($filter_vendeur) $query .= " AND v.id_vendeur = " . intval($filter_vendeur);
                if ($filter_paiement) $query .= " AND v.mode_paiement = '" . $filter_paiement . "'";
                if ($filter_statut) $query .= " AND v.statut = '" . $filter_statut . "'";
                if ($search_numero) $query .= " AND v.numero_facture LIKE '%". str_replace("'", "''", $search_numero) ."%'";
                
                $query .= " GROUP BY v.id_vente ORDER BY v.date_vente DESC LIMIT 500";
                
                $ventes = db_fetch_all($query);
                
                // Statistiques globales
                $total_ventes = count($ventes);
                $ca_total = 0;
                $ca_ht_total = 0;
                $tva_total = 0;
                $ca_jour = 0;
                $aujourd_hui = date('Y-m-d');
                $stats_vendeurs = [];
                $stats_modes_paiement = [];
                $ventes_validees = 0;
                $ventes_annulees = 0;
                $nb_articles_total = 0;
                
                foreach($ventes as $vente) {
                    $ca_total += $vente['montant_total'];
                    $ca_ht_total += $vente['montant_ht'];
                    $tva_total += $vente['montant_tva'];
                    $nb_articles_total += $vente['nb_articles'];
                    
                    // Compter statuts
                    if ($vente['statut'] == 'validee') {
                        $ventes_validees++;
                    } elseif ($vente['statut'] == 'annulee') {
                        $ventes_annulees++;
                    }
                    
                    if (date('Y-m-d', strtotime($vente['date_vente'])) == $aujourd_hui) {
                        $ca_jour += $vente['montant_total'];
                    }
                    
                    $vendeur = $vente['vendeur'] ?: 'Non attribué';
                    if (!isset($stats_vendeurs[$vendeur])) {
                        $stats_vendeurs[$vendeur] = ['count' => 0, 'montant' => 0];
                    }
                    $stats_vendeurs[$vendeur]['count']++;
                    $stats_vendeurs[$vendeur]['montant'] += $vente['montant_total'];
                    
                    $mode = $vente['mode_paiement'];
                    if (!isset($stats_modes_paiement[$mode])) {
                        $stats_modes_paiement[$mode] = ['count' => 0, 'montant' => 0];
                    }
                    $stats_modes_paiement[$mode]['count']++;
                    $stats_modes_paiement[$mode]['montant'] += $vente['montant_total'];
                }
                
                // Calculs supplémentaires
                $panier_moyen = $ventes_validees > 0 ? $ca_total / $ventes_validees : 0;
                $articles_moyen = $total_ventes > 0 ? $nb_articles_total / $total_ventes : 0;
                
                // Listes pour filtres
                $clients = db_fetch_all("SELECT id_client, nom_client FROM clients ORDER BY nom_client");
                $vendeurs = db_fetch_all("SELECT id_utilisateur, nom_complet FROM utilisateurs WHERE niveau_acces = " . NIVEAU_VENDEUR . " ORDER BY nom_complet");
                
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">';
                echo '<strong>Erreur lors du chargement des ventes:</strong><br>';
                echo e($e->getMessage());
                echo '</div>';
                break;
            }
            ?>
            
            <!-- Filtres -->
            <div class="card list-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">🔍 Filtres</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Numéro facture</label>
                            <input type="text" class="form-control" name="search_numero" 
                                   value="<?php echo e($search_numero); ?>" placeholder="Ex: FAC-001">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Du</label>
                            <input type="date" class="form-control" name="filter_date_from" 
                                   value="<?php echo e($filter_date_from); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Au</label>
                            <input type="date" class="form-control" name="filter_date_to" 
                                   value="<?php echo e($filter_date_to); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Client</label>
                            <select class="form-select" name="filter_client">
                                <option value="">Tous</option>
                                <?php foreach($clients as $client): ?>
                                <option value="<?php echo $client['id_client']; ?>" 
                                    <?php echo $filter_client == $client['id_client'] ? 'selected' : ''; ?>>
                                    <?php echo e($client['nom_client']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Vendeur</label>
                            <select class="form-select" name="filter_vendeur">
                                <option value="">Tous</option>
                                <?php foreach($vendeurs as $v): ?>
                                <option value="<?php echo $v['id_utilisateur']; ?>" 
                                    <?php echo $filter_vendeur == $v['id_utilisateur'] ? 'selected' : ''; ?>>
                                    <?php echo e($v['nom_complet']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Paiement</label>
                            <select class="form-select" name="filter_paiement">
                                <option value="">Tous</option>
                                <option value="especes" <?php echo $filter_paiement == 'especes' ? 'selected' : ''; ?>>Espèces</option>
                                <option value="carte" <?php echo $filter_paiement == 'carte' ? 'selected' : ''; ?>>Carte</option>
                                <option value="mobile_money" <?php echo $filter_paiement == 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                                <option value="cheque" <?php echo $filter_paiement == 'cheque' ? 'selected' : ''; ?>>Chèque</option>
                                <option value="credit" <?php echo $filter_paiement == 'credit' ? 'selected' : ''; ?>>Crédit</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="filter_statut">
                                <option value="">Tous</option>
                                <option value="validee" <?php echo $filter_statut == 'validee' ? 'selected' : ''; ?>>Validée</option>
                                <option value="en_cours" <?php echo $filter_statut == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                <option value="annulee" <?php echo $filter_statut == 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                            <a href="listes.php?page=ventes" class="btn btn-secondary">Réinitialiser</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Statistiques principales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted" data-bs-toggle="tooltip" title="Nombre total de ventes correspondant aux filtres">Ventes</div>
                                <h4 class="mb-0"><?php echo $total_ventes; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted" data-bs-toggle="tooltip" title="Chiffre d'affaires TTC du jour">CA Aujourd'hui</div>
                                <h4 class="mb-0" style="font-size: 0.95rem;"><?php echo format_montant($ca_jour, $devise); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#17a2b8" stroke-width="2">
                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                    <polyline points="17 6 23 6 23 12"></polyline>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted" data-bs-toggle="tooltip" title="Chiffre d'affaires TTC total (avec filtres)">CA Total TTC</div>
                                <h4 class="mb-0" style="font-size: 0.95rem;"><?php echo format_montant($ca_total, $devise); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ffc107" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted" data-bs-toggle="tooltip" title="Montant moyen par vente validée">Panier Moyen</div>
                                <h4 class="mb-0" style="font-size: 0.95rem;"><?php echo format_montant($panier_moyen, $devise); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques supplémentaires -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#6f42c1" stroke-width="2">
                                    <rect x="1" y="3" width="15" height="13"></rect>
                                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted" data-bs-toggle="tooltip" title="Montant total hors taxes">CA HT</div>
                                <h4 class="mb-0" style="font-size: 0.9rem;"><?php echo format_montant($ca_ht_total, $devise); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted" data-bs-toggle="tooltip" title="TVA collectée (<?php echo (float)$taux_tva; ?>%)">TVA Collectée</div>
                                <h4 class="mb-0" style="font-size: 0.9rem;"><?php echo format_montant($tva_total, $devise); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#20c997" stroke-width="2">
                                    <polyline points="9 11 12 14 22 4"></polyline>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted" data-bs-toggle="tooltip" title="Nombre de ventes validées">Validées</div>
                                <h4 class="mb-0"><?php echo $ventes_validees; ?> <small class="text-muted">/ <?php echo $total_ventes; ?></small></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted" data-bs-toggle="tooltip" title="Nombre de ventes annulées">Annulées</div>
                                <h4 class="mb-0"><?php echo $ventes_annulees; ?> <small class="text-muted"><?php echo $total_ventes > 0 ? '(' . round(($ventes_annulees / $total_ventes) * 100, 1) . '%)' : ''; ?></small></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques détaillées -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card list-card">
                        <div class="card-header">
                            <h6 class="mb-0">💼 CA par Vendeur</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($stats_vendeurs)): ?>
                            <p class="text-muted">Aucune vente</p>
                            <?php else: ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr><th>Vendeur</th><th class="text-end">Ventes</th><th class="text-end">Montant</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($stats_vendeurs as $nom => $stats): ?>
                                    <tr>
                                        <td><?php echo e($nom); ?></td>
                                        <td class="text-end"><span class="badge bg-secondary"><?php echo $stats['count']; ?></span></td>
                                        <td class="text-end"><strong><?php echo format_montant($stats['montant'], $devise); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card list-card">
                        <div class="card-header">
                            <h6 class="mb-0">💳 CA par Mode de Paiement</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($stats_modes_paiement)): ?>
                            <p class="text-muted">Aucune vente</p>
                            <?php else: ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr><th>Mode</th><th class="text-end">Ventes</th><th class="text-end">Montant</th></tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $mode_labels = ['especes' => 'Espèces', 'carte' => 'Carte', 'mobile_money' => 'Mobile Money', 'cheque' => 'Chèque', 'credit' => 'Crédit'];
                                    foreach($stats_modes_paiement as $mode => $stats): 
                                    ?>
                                    <tr>
                                        <td><?php echo $mode_labels[$mode] ?? $mode; ?></td>
                                        <td class="text-end"><span class="badge bg-secondary"><?php echo $stats['count']; ?></span></td>
                                        <td class="text-end"><strong><?php echo format_montant($stats['montant'], $devise); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détails financiers -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card list-card">
                        <div class="card-header">
                            <h6 class="mb-0">📊 Résumé Financier</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <p class="text-muted small">Montant HT</p>
                                    <h5><?php echo format_montant($ca_ht_total, $devise); ?></h5>
                                </div>
                                <div class="col-md-4">
                                    <p class="text-muted small">TVA (<?php echo (float)$taux_tva; ?>%)</p>
                                    <h5><?php echo format_montant($tva_total, $devise); ?></h5>
                                </div>
                                <div class="col-md-4">
                                    <p class="text-muted small">Montant Moyen</p>
                                    <h5><?php echo format_montant($total_ventes > 0 ? $ca_total / $total_ventes : 0, $devise); ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des ventes -->
            <div class="card list-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📋 Détail des Ventes (<?php echo $total_ventes; ?> résultats)</h5>
                    <div>
                        <button type="button" class="btn btn-outline-success btn-sm me-2" onclick="exportVentesExcel()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Excel
                        </button>
                        <a href="vente_professionnel.php" class="btn btn-success btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Nouvelle Vente
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover card-table" style="min-width: 100%; white-space: nowrap;">
                            <thead>
                                <tr>
                                    <th style="min-width: 120px;" data-bs-toggle="tooltip" title="Numéro de facture unique">N° Facture</th>
                                    <th style="min-width: 100px;" data-bs-toggle="tooltip" title="Date et heure de la vente">Date</th>
                                    <th style="min-width: 120px;" data-bs-toggle="tooltip" title="Nom du client">Client</th>
                                    <th style="min-width: 90px;" class="text-end" data-bs-toggle="tooltip" title="Montant total TTC">Montant TTC</th>
                                    <th style="min-width: 100px;" data-bs-toggle="tooltip" title="Mode de paiement utilisé">Paiement</th>
                                    <th style="min-width: 80px;" data-bs-toggle="tooltip" title="Statut de la vente">Statut</th>
                                    <th style="min-width: 100px;" data-bs-toggle="tooltip" title="Vendeur ayant effectué la vente">Vendeur</th>
                                    <th style="min-width: 150px;" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ventes)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Aucune vente correspondant aux filtres</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach($ventes as $vente): ?>
                                <tr>
                                    <td style="white-space: nowrap;"><strong><?php echo e($vente['numero_facture']); ?></strong></td>
                                    <td style="white-space: nowrap;"><small><?php echo date('d/m/Y H:i', strtotime($vente['date_vente'])); ?></small></td>
                                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;" data-bs-toggle="tooltip" title="<?php echo e($vente['nom_client'] ?: 'Vente comptoir'); ?>">
                                        <?php echo e($vente['nom_client'] ?: 'Comptoir'); ?>
                                    </td>
                                    <td class="text-end" style="white-space: nowrap;"><strong><?php echo format_montant($vente['montant_total'], $devise); ?></strong></td>
                                    <td>
                                        <?php
                                        $mode_badges = [
                                            'especes' => 'success',
                                            'carte' => 'primary',
                                            'mobile_money' => 'warning',
                                            'cheque' => 'info',
                                            'credit' => 'secondary'
                                        ];
                                        $mode_labels = [
                                            'especes' => '💵',
                                            'carte' => '💳',
                                            'mobile_money' => '📱',
                                            'cheque' => '📄',
                                            'credit' => '🔄'
                                        ];
                                        $badge_class = $mode_badges[$vente['mode_paiement']] ?? 'secondary';
                                        $mode_label = $mode_labels[$vente['mode_paiement']] ?? $vente['mode_paiement'];
                                        $mode_title = [
                                            'especes' => 'Espèces',
                                            'carte' => 'Carte Bancaire',
                                            'mobile_money' => 'Mobile Money',
                                            'cheque' => 'Chèque',
                                            'credit' => 'Crédit'
                                        ][$vente['mode_paiement']] ?? $vente['mode_paiement'];
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>" data-bs-toggle="tooltip" title="<?php echo $mode_title; ?>"><?php echo $mode_label; ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statut_badges = [
                                            'validee' => 'success',
                                            'en_cours' => 'warning',
                                            'annulee' => 'danger'
                                        ];
                                        $statut_labels = [
                                            'validee' => '✓',
                                            'en_cours' => '⏳',
                                            'annulee' => '✗'
                                        ];
                                        $badge_class = $statut_badges[$vente['statut']] ?? 'secondary';
                                        $statut_label = $statut_labels[$vente['statut']] ?? $vente['statut'];
                                        $statut_title = [
                                            'validee' => 'Validée',
                                            'en_cours' => 'En cours',
                                            'annulee' => 'Annulée'
                                        ][$vente['statut']] ?? $vente['statut'];
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>" data-bs-toggle="tooltip" title="<?php echo $statut_title; ?>"><?php echo $statut_label; ?></span>
                                    </td>
                                    <td style="max-width: 100px; overflow: hidden; text-overflow: ellipsis;"><small><?php echo e($vente['vendeur']); ?></small></td>
                                    <td class="text-end" style="white-space: nowrap;">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info btn-view-vente" 
                                                    data-id="<?php echo $vente['id_vente']; ?>"
                                                    data-bs-toggle="tooltip" title="Voir détails complets">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </button>
                                            <a href="facture_impression.php?id=<?php echo $vente['id_vente']; ?>" 
                                               target="_blank"
                                               class="btn btn-outline-primary"
                                               data-bs-toggle="tooltip" title="Imprimer facture professionnelle">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                                    <rect x="6" y="14" width="12" height="8"></rect>
                                                </svg>
                                            </a>
                                            <?php if ($vente['statut'] == 'validee'): ?>
                                            <button type="button" class="btn btn-outline-danger btn-cancel-vente" 
                                                    data-id="<?php echo $vente['id_vente']; ?>"
                                                    data-numero="<?php echo e($vente['numero_facture']); ?>"
                                                    data-bs-toggle="tooltip" title="Annuler la vente (Admin seulement)">
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
                                                    data-bs-toggle="tooltip" title="Restaurer la vente (Admin seulement)">
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
                                                    data-bs-toggle="tooltip" title="Supprimer définitivement (Admin seulement)">
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

            <!-- Modal voir détails -->
            <div class="modal fade" id="venteDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Détails de la Vente</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="venteDetailsContent">
                            <p class="text-center"><spinner class="spinner-border spinner-border-sm"></spinner> Chargement...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php
            break;

        case 'credits':
            // ── Données ventes à crédit ──────────────────────────────────
            $credits = db_fetch_all("
                SELECT v.*,
                       c.nom_client, c.telephone as tel_client,
                       u.nom_complet AS vendeur,
                       COALESCE(SUM(p.montant), 0) AS total_paye
                FROM ventes v
                LEFT JOIN clients      c ON v.id_client  = c.id_client
                LEFT JOIN utilisateurs u ON v.id_vendeur = u.id_utilisateur
                LEFT JOIN paiements_credit p ON p.id_vente = v.id_vente
                WHERE v.mode_paiement = 'credit' AND v.statut != 'annulee'
                GROUP BY v.id_vente
                ORDER BY v.date_vente DESC
            ");

            // Statistiques crédit
            $total_credit_montant = 0;
            $total_credit_restant = 0;
            $nb_en_retard         = 0;
            $nb_soldes            = 0;
            $aujourd_hui          = date('Y-m-d');

            foreach ($credits as $cr) {
                $solde = round($cr['montant_total'] - $cr['total_paye'], 2);
                $total_credit_montant += $cr['montant_total'];
                if ($solde > 0.01) {
                    $total_credit_restant += $solde;
                    if (!empty($cr['date_echeance']) && $cr['date_echeance'] < $aujourd_hui) {
                        $nb_en_retard++;
                    }
                } else {
                    $nb_soldes++;
                }
            }
            ?>

            <!-- Cartes statistiques crédit -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" stroke="#6c757d" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="1" y="4" width="22" height="16" rx="2"/>
                                    <line x1="1" y1="10" x2="23" y2="10"/>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted">Total créances</div>
                                <h4 class="mb-0" style="font-size:0.95rem;"><?php echo format_montant($total_credit_montant, $devise); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" stroke="#dc3545" stroke-width="2" viewBox="0 0 24 24">
                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted">Solde total dû</div>
                                <h4 class="mb-0 text-danger" style="font-size:0.95rem;"><?php echo format_montant($total_credit_restant, $devise); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" stroke="#ffc107" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted">En retard</div>
                                <h4 class="mb-0 text-warning"><?php echo $nb_en_retard; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" stroke="#28a745" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted">Soldés</div>
                                <h4 class="mb-0 text-success"><?php echo $nb_soldes; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des crédits -->
            <div class="card list-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Liste des ventes à crédit (<?php echo count($credits); ?>)</h5>
                    <div class="btn-group btn-group-sm" id="creditFilterBtns">
                        <button type="button" class="btn btn-outline-secondary active" data-filter="all">Tous</button>
                        <button type="button" class="btn btn-outline-danger"           data-filter="retard">En retard</button>
                        <button type="button" class="btn btn-outline-warning"          data-filter="encours">En cours</button>
                        <button type="button" class="btn btn-outline-success"          data-filter="solde">Soldés</button>
                    </div>
                </div>
                <div class="card-body border-bottom pb-2">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <circle cx="10" cy="10" r="7"/>
                                <line x1="21" y1="21" x2="15" y2="15"/>
                            </svg>
                        </span>
                        <input type="text" id="searchCredits" class="form-control" placeholder="Rechercher (client, facture, vendeur)…">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover card-table" id="creditsTable">
                            <thead>
                                <tr>
                                    <th>N° Facture</th>
                                    <th>Client</th>
                                    <th>Date vente</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Payé</th>
                                    <th class="text-end">Solde dû</th>
                                    <th>Échéance</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($credits)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Aucune vente à crédit enregistrée</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($credits as $cr):
                                    $solde      = round($cr['montant_total'] - $cr['total_paye'], 2);
                                    $est_solde  = ($solde <= 0.01);
                                    $en_retard  = !$est_solde && !empty($cr['date_echeance']) && $cr['date_echeance'] < $aujourd_hui;
                                    $en_cours   = !$est_solde && !$en_retard;
                                    $data_statut = $est_solde ? 'solde' : ($en_retard ? 'retard' : 'encours');
                                ?>
                                <tr data-statut="<?php echo $data_statut; ?>">
                                    <td>
                                        <a href="facture_impression.php?id=<?php echo $cr['id_vente']; ?>" target="_blank"
                                           class="fw-bold text-decoration-none">
                                            <?php echo e($cr['numero_facture']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div><?php echo e($cr['nom_client'] ?? '—'); ?></div>
                                        <?php if (!empty($cr['tel_client'])): ?>
                                        <small class="text-muted"><?php echo e($cr['tel_client']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y', strtotime($cr['date_vente'])); ?></small>
                                    </td>
                                    <td class="text-end fw-bold">
                                        <?php echo format_montant($cr['montant_total'], $devise); ?>
                                    </td>
                                    <td class="text-end text-success">
                                        <?php echo format_montant($cr['total_paye'], $devise); ?>
                                    </td>
                                    <td class="text-end fw-bold <?php echo $est_solde ? 'text-success' : ($en_retard ? 'text-danger' : 'text-warning'); ?>">
                                        <?php echo $est_solde ? '—' : format_montant($solde, $devise); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($cr['date_echeance'])): ?>
                                            <span class="<?php echo $en_retard ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                                <?php echo date('d/m/Y', strtotime($cr['date_echeance'])); ?>
                                                <?php if ($en_retard): ?> <span class="badge bg-danger">Dépassée</span><?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($est_solde): ?>
                                            <span class="badge bg-success">Soldé</span>
                                        <?php elseif ($en_retard): ?>
                                            <span class="badge bg-danger">En retard</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">En cours</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info btn-historique-credit"
                                                    data-id="<?php echo $cr['id_vente']; ?>"
                                                    title="Historique des paiements">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <polyline points="12 6 12 12 16 14"/>
                                                </svg>
                                            </button>
                                            <?php if (!$est_solde): ?>
                                            <button type="button" class="btn btn-outline-success btn-ajouter-paiement"
                                                    data-id="<?php echo $cr['id_vente']; ?>"
                                                    data-facture="<?php echo e($cr['numero_facture']); ?>"
                                                    data-client="<?php echo e($cr['nom_client'] ?? ''); ?>"
                                                    data-solde="<?php echo $solde; ?>"
                                                    title="Enregistrer un paiement">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                                    <line x1="5" y1="12" x2="19" y2="12"/>
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

            <!-- Modal : Ajouter un paiement -->
            <div class="modal fade" id="modalAjouterPaiement" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Enregistrer un paiement</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formAjouterPaiement" data-no-loader>
                            <div class="modal-body">
                                <input type="hidden" id="paiement_id_vente" name="id_vente">
                                <input type="hidden" name="action" value="add_payment">

                                <div class="mb-3 p-3 bg-light rounded">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Facture</span>
                                        <strong id="paiement_facture_label">—</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span class="text-muted">Client</span>
                                        <strong id="paiement_client_label">—</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span class="text-muted">Solde restant</span>
                                        <strong id="paiement_solde_label" class="text-danger">—</strong>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label required">Montant du paiement</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="paiement_montant"
                                               name="montant" min="0.01" step="0.01" required>
                                        <span class="input-group-text"><?php echo $devise; ?></span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mode de paiement</label>
                                    <select class="form-select" name="mode_paiement">
                                        <option value="especes">Espèces</option>
                                        <option value="carte">Carte bancaire</option>
                                        <option value="mobile_money">Mobile Money</option>
                                        <option value="cheque">Chèque</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <input type="text" class="form-control" name="notes" placeholder="Ex: 2ème versement…">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-success">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal : Historique des paiements -->
            <div class="modal fade" id="modalHistoriquePaiements" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Historique des paiements</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="historiquePaiementsContent">
                            <p class="text-center text-muted">Chargement…</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            break;

        case 'fournisseurs':
            // Récupérer les fournisseurs
            $fournisseurs = db_fetch_all("
                SELECT 
                    f.*,
                    COUNT(DISTINCT p.id_produit) as nb_produits
                FROM fournisseurs f
                LEFT JOIN produits p ON f.id_fournisseur = p.id_fournisseur_principal
                WHERE f.est_actif = 1
                GROUP BY f.id_fournisseur
                ORDER BY f.nom_fournisseur ASC
            ");
            ?>
            
            <div class="card list-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📦 Liste des Fournisseurs</h5>
                    <?php if ($is_admin): ?>
                    <button type="button" class="btn btn-primary" onclick="ouvrirModalFournisseur()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Nouveau Fournisseur
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body border-bottom pb-2">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="10" cy="10" r="7" />
                                <line x1="21" y1="21" x2="15" y2="15" />
                            </svg>
                        </span>
                        <input type="text" id="searchFournisseurs" class="form-control" placeholder="Rechercher un fournisseur (nom, contact, téléphone, email)..." />
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover card-table">
                            <thead>
                                <tr>
                                    <th>Nom du fournisseur</th>
                                    <th>Contact</th>
                                    <th>Téléphone</th>
                                    <th>Email</th>
                                    <th>Adresse</th>
                                    <th class="text-center">Produits liés</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($fournisseurs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        Aucun fournisseur enregistré
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($fournisseurs as $f): ?>
                                <tr>
                                    <td><strong><?php echo e($f['nom_fournisseur']); ?></strong></td>
                                    <td><?php echo e($f['contact'] ?: '-'); ?></td>
                                    <td><?php echo e($f['telephone'] ?: '-'); ?></td>
                                    <td><?php echo e($f['email'] ?: '-'); ?></td>
                                    <td><?php echo e($f['adresse'] ?: '-'); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $f['nb_produits']; ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($is_admin): ?>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-warning" onclick='modifierFournisseur(<?php echo json_encode($f); ?>)' title="Modifier">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                                                    <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                                                    <path d="M16 5l3 3" />
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="supprimerFournisseur(<?php echo $f['id_fournisseur']; ?>, '<?php echo e($f['nom_fournisseur']); ?>', <?php echo $f['nb_produits']; ?>)" title="Supprimer">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <line x1="4" y1="7" x2="20" y2="7" />
                                                    <line x1="10" y1="11" x2="10" y2="17" />
                                                    <line x1="14" y1="11" x2="14" y2="17" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Modal Fournisseur -->
            <div class="modal fade" id="modalFournisseur" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalFournisseurTitle">Nouveau Fournisseur</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formFournisseur" data-no-loader>
                            <div class="modal-body">
                                <input type="hidden" id="fournisseur_id" name="id_fournisseur">
                                
                                <div class="mb-3">
                                    <label class="form-label required">Nom du fournisseur</label>
                                    <input type="text" class="form-control" id="fournisseur_nom" name="nom_fournisseur" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Personne de contact</label>
                                    <input type="text" class="form-control" id="fournisseur_contact" name="contact">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="fournisseur_telephone" name="telephone">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" id="fournisseur_email" name="email">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Adresse</label>
                                    <textarea class="form-control" id="fournisseur_adresse" name="adresse" rows="2"></textarea>
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
            
            <?php
            break;
            
        case 'depots':
            // Récupérer les dépôts
            $depots = db_fetch_all("
                SELECT 
                    d.*,
                    COUNT(DISTINCT spd.id_produit) as nb_produits,
                    COALESCE(SUM(spd.quantite), 0) as stock_total
                FROM depots d
                LEFT JOIN stock_par_depot spd ON d.id_depot = spd.id_depot
                WHERE d.est_actif = 1
                GROUP BY d.id_depot
                ORDER BY d.est_principal DESC, d.nom_depot ASC
            ");
            ?>
            
            <div class="card list-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🏪 Liste des Dépôts</h5>
                    <?php if ($is_admin): ?>
                    <button type="button" class="btn btn-primary" onclick="ouvrirModalDepot()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Nouveau Dépôt
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover card-table">
                            <thead>
                                <tr>
                                    <th>Nom du dépôt</th>
                                    <th class="text-center">Type</th>
                                    <th>Responsable</th>
                                    <th>Adresse</th>
                                    <th class="text-center">Produits</th>
                                    <th class="text-center">Stock total</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($depots)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        Aucun dépôt enregistré
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($depots as $d): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($d['nom_depot']); ?></strong>
                                        <?php if ($d['est_principal']): ?>
                                        <span class="badge bg-success ms-2">Principal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $type_labels = ['magasin'=>'Magasin','depot'=>'Dépôt','entrepot'=>'Entrepôt','autre'=>'Autre'];
                                        $type_colors = ['magasin'=>'bg-success','depot'=>'bg-info','entrepot'=>'bg-warning','autre'=>'bg-secondary'];
                                        $t = $d['type_depot'] ?? 'depot';
                                        ?>
                                        <span class="badge <?php echo $type_colors[$t] ?? 'bg-secondary'; ?>">
                                            <?php echo $type_labels[$t] ?? $t; ?>
                                        </span>
                                        <?php if ($d['est_principal']): ?><br><span class="badge bg-dark mt-1">Principal</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($d['responsable'])): ?>
                                        <div><?php echo e($d['responsable']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($d['telephone'])): ?>
                                        <small class="text-muted"><?php echo e($d['telephone']); ?></small>
                                        <?php endif; ?>
                                        <?php if (empty($d['responsable']) && empty($d['telephone'])): ?>—<?php endif; ?>
                                    </td>
                                    <td><?php echo e($d['adresse'] ?: '—'); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $d['nb_produits']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-dark"><?php echo number_format($d['stock_total']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($is_admin): ?>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-warning" onclick='modifierDepot(<?php echo json_encode($d); ?>)' title="Modifier">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                                                    <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                                                    <path d="M16 5l3 3" />
                                                </svg>
                                            </button>
                                            <?php if (!$d['est_principal']): ?>
                                            <button type="button" class="btn btn-danger" onclick="supprimerDepot(<?php echo $d['id_depot']; ?>, '<?php echo e($d['nom_depot']); ?>', <?php echo $d['stock_total']; ?>)" title="Supprimer">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <line x1="4" y1="7" x2="20" y2="7" />
                                                    <line x1="10" y1="11" x2="10" y2="17" />
                                                    <line x1="14" y1="11" x2="14" y2="17" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                            </button>
                                            <?php else: ?>
                                            <button type="button" class="btn btn-secondary" disabled title="Le dépôt principal ne peut pas être supprimé">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <rect x="5" y="11" width="14" height="10" rx="2" />
                                                    <circle cx="12" cy="16" r="1" />
                                                    <path d="M8 11v-4a4 4 0 0 1 8 0v4" />
                                                </svg>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Modal Dépôt -->
            <div class="modal fade" id="modalDepot" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalDepotTitle">Nouveau Dépôt</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formDepot" data-no-loader>
                            <div class="modal-body">
                                <input type="hidden" id="depot_id" name="id_depot">

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Nom du dépôt <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="depot_nom" name="nom_depot" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" id="depot_type" name="type_depot">
                                            <option value="magasin">Magasin</option>
                                            <option value="depot" selected>Dépôt</option>
                                            <option value="entrepot">Entrepôt</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Responsable</label>
                                        <input type="text" class="form-control" id="depot_responsable" name="responsable" placeholder="Nom du responsable">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Téléphone</label>
                                        <input type="text" class="form-control" id="depot_telephone" name="telephone" placeholder="+XXX XX XX XX">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Adresse</label>
                                        <input type="text" class="form-control" id="depot_adresse" name="adresse">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" id="depot_description" name="description" rows="2"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="depot_principal" name="est_principal" value="1">
                                            <label class="form-check-label" for="depot_principal">Définir comme dépôt principal</label>
                                            <small class="d-block text-muted">Un seul dépôt peut être principal.</small>
                                        </div>
                                    </div>
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
            
            <?php
            break;
    }
    ?>
</div>

<script>
// ===== DEBUG =====
console.log('=== Script chargé ===');
console.log('jQuery disponible:', typeof $ !== 'undefined');
console.log('Bootstrap disponible:', typeof bootstrap !== 'undefined');
console.log('showConfirmModal disponible:', typeof showConfirmModal !== 'undefined');
console.log('showAlertModal disponible:', typeof showAlertModal !== 'undefined');
const IS_VENDEUR  = <?php echo $is_vendeur ? 'true' : 'false'; ?>;
const DEVISE      = <?php echo json_encode($devise); ?>;
const COULEUR_PRI = <?php echo json_encode($couleur_primaire); ?>;
const COULEUR_SEC = <?php echo json_encode($couleur_secondaire); ?>;

function refuseIfVendeur() {
    if (!IS_VENDEUR) return false;
    if (typeof showAlertModal === 'function') {
        showAlertModal({
            title: 'Action non autorisée',
            message: 'Réservé aux gestionnaires.',
            type: 'warning'
        });
    } else {
        alert('Action réservée aux gestionnaires.');
    }
    return true;
}

// ===== GESTION PRODUITS =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé, initialisation des événements...');
    
    // Bouton Nouveau produit
    const btnAddProduct = document.getElementById('btnAddProduct');
    if (btnAddProduct) {
        console.log('Bouton Nouveau produit trouvé');
        btnAddProduct.addEventListener('click', function() {
            if (refuseIfVendeur()) return;
            console.log('Click sur Nouveau produit');
            openProductModal();
        });
    }
    
    // Boutons Modifier
    document.querySelectorAll('.btn-edit-product').forEach(btn => {
        btn.addEventListener('click', function() {
            if (refuseIfVendeur()) return;
            console.log('Click sur Modifier');
            const productData = JSON.parse(this.getAttribute('data-product'));
            editProduct(productData);
        });
    });
    
    // Boutons Ajuster stock
    document.querySelectorAll('.btn-adjust-stock').forEach(btn => {
        btn.addEventListener('click', function() {
            console.log('Click sur Ajuster stock');
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            adjustStock(id, name);
        });
    });
    
    // Boutons Supprimer
    document.querySelectorAll('.btn-delete-product').forEach(btn => {
        btn.addEventListener('click', function() {
            if (refuseIfVendeur()) return;
            console.log('Click sur Supprimer');
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            deleteProduct(id, name);
        });
    });
    
    // Formulaire produit
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            if (refuseIfVendeur()) { e.preventDefault(); return; }
            handleProductSubmit.call(this, e);
        });
    }
    
    // Formulaire stock
    const stockForm = document.getElementById('stockForm');
    if (stockForm) {
        stockForm.addEventListener('submit', handleStockSubmit);
    }
    
    // Initialiser tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    console.log('Initialisation terminée');
});

// Ouvrir modal nouveau produit
function openProductModal() {
    if (refuseIfVendeur()) return;
    document.getElementById('modalProduitTitle').textContent = 'Nouveau produit';
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';
    // Mode création : stock initial visible, stock actuel masqué
    document.getElementById('stock_initial_field').style.display = '';
    document.getElementById('stock_actuel_field').style.display = 'none';
    toggleStockFields();

    const modal = new bootstrap.Modal(document.getElementById('modalProduit'));
    modal.show();
}

// Éditer un produit
function toggleStockFields() {
    const isService = document.getElementById('product_type_service')?.checked;
    document.querySelectorAll('.stock-only-field').forEach(el => {
        el.style.display = isService ? 'none' : '';
    });
    // Fournisseur : optionnel pour les services
    const fournisseurSelect = document.getElementById('product_fournisseur');
    if (fournisseurSelect) {
        fournisseurSelect.required = !isService;
        const fournisseurLabel = fournisseurSelect.closest('.col-md-6')?.querySelector('.form-label');
        if (fournisseurLabel) {
            fournisseurLabel.textContent = isService ? 'Fournisseur' : 'Fournisseur *';
        }
    }
}

function editProduct(product) {
    if (refuseIfVendeur()) return;
    console.log('editProduct() appelé', product);
    document.getElementById('modalProduitTitle').textContent = 'Modifier le produit';
    document.getElementById('product_id').value = product.id_produit;
    document.getElementById('product_name').value = product.nom_produit;
    document.getElementById('product_barcode').value = product.code_produit || '';
    document.getElementById('product_category').value = product.id_categorie;
    document.getElementById('product_unit').value = product.unite_mesure || product.unite || 'pièce';
    document.getElementById('product_purchase_price').value = product.prix_achat;
    document.getElementById('product_sale_price').value = product.prix_vente;
    // Mode édition : masquer stock initial, afficher stock actuel en lecture seule
    document.getElementById('stock_initial_field').style.display = 'none';
    const stockActuelField = document.getElementById('stock_actuel_field');
    stockActuelField.style.display = '';
    document.getElementById('product_stock_display').value = product.quantite_stock;
    stockActuelField.dataset.productId = product.id_produit;
    document.getElementById('product_min_stock').value = product.seuil_alerte;
    document.getElementById('product_description').value = product.description || '';
    document.getElementById('product_fournisseur').value = product.id_fournisseur_principal || '0';
    // Type produit
    const typeProd = product.type_produit || 'standard';
    const radioEl = document.querySelector(`input[name="product_type"][value="${typeProd}"]`);
    if (radioEl) { radioEl.checked = true; }
    toggleStockFields();

    const modal = new bootstrap.Modal(document.getElementById('modalProduit'));
    modal.show();
}

function goToStockAdjust() {
    const productId = document.getElementById('product_id').value;
    if (productId) {
        bootstrap.Modal.getInstance(document.getElementById('modalProduit'))?.hide();
        window.location.href = 'mouvements_stock.php?product_id=' + encodeURIComponent(productId) + '&open_modal=1';
    }
}

// Ajuster stock
function adjustStock(id, name) {
    console.log('adjustStock() appelé', id, name);
    // Rediriger vers mouvements_stock.php avec l'ID du produit
    window.location.href = 'mouvements_stock.php?product_id=' + encodeURIComponent(id) + '&open_modal=1';
}

// Supprimer produit
function deleteProduct(id, name) {
    if (refuseIfVendeur()) return;
    console.log('deleteProduct() appelé', id, name);
    
    showConfirmModal({
        title: 'Confirmer la suppression',
        message: `Êtes-vous sûr de vouloir supprimer "${name}" ?`,
        type: 'warning'
    }).then(confirmed => {
        if (!confirmed) return;
        
        fetch('ajax/produits.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'delete',
                id: id
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlertModal({
                    title: 'Succès',
                    message: data.message,
                    type: 'success'
                }).then(() => location.reload());
            } else {
                showAlertModal({
                    title: 'Erreur',
                    message: data.message,
                    type: 'error'
                });
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erreur réseau');
        });
    });
}

// Formater un montant comme PHP format_montant()
function formatMontant(montant) {
    return new Intl.NumberFormat('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(montant) + ' ' + DEVISE;
}

// Construire une ligne TR à partir des données produit
function buildProductRow(p) {
    const initials = (p.nom_produit || '').substring(0, 2).toUpperCase();
    const imageHtml = p.image
        ? `<img src="uploads/produits/${p.image}" alt="${p.nom_produit}" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">`
        : `<div style="width:50px;height:50px;background:linear-gradient(135deg,${COULEUR_PRI},${COULEUR_SEC});border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;">${initials}</div>`;

    const catHtml = p.nom_categorie
        ? `<span class="badge badge-category">${p.nom_categorie}</span>`
        : `<span class="text-muted">Sans catégorie</span>`;

    const stock = parseInt(p.quantite_stock) || 0;
    const seuil = parseInt(p.seuil_alerte) || 0;
    const stockClass = stock === 0 ? 'bg-danger' : (stock <= seuil ? 'bg-warning' : 'bg-success');

    const prixAchatCol = IS_VENDEUR ? '' : `<td class="text-end">${formatMontant(p.prix_achat)}</td>`;

    const productJson = JSON.stringify(p).replace(/'/g, '&#39;').replace(/"/g, '&quot;');

    const actionsBtns = IS_VENDEUR
        ? `<button type="button" class="btn btn-sm btn-icon btn-outline-success btn-action btn-adjust-stock"
               data-id="${p.id_produit}" data-name="${p.nom_produit}" title="Ajuster le stock">
               <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5v14"/><path d="M5 12h14"/></svg>
           </button>`
        : `<div class="btn-group" role="group">
               <button type="button" class="btn btn-sm btn-icon btn-outline-primary btn-action btn-edit-product"
                   data-product="${productJson}" title="Modifier">
                   <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0-2.97-2.97l-8.415 8.385v3h3l8.385-8.415z"/><path d="M16 5l3 3"/></svg>
               </button>
               <button type="button" class="btn btn-sm btn-icon btn-outline-success btn-action btn-adjust-stock"
                   data-id="${p.id_produit}" data-name="${p.nom_produit}" title="Ajuster le stock">
                   <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5v14"/><path d="M5 12h14"/></svg>
               </button>
               <button type="button" class="btn btn-sm btn-icon btn-outline-danger btn-action btn-delete-product"
                   data-id="${p.id_produit}" data-name="${p.nom_produit}" title="Supprimer">
                   <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12"/><path d="M9 7v-3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/></svg>
               </button>
           </div>`;

    const tr = document.createElement('tr');
    tr.dataset.productId = p.id_produit;
    tr.innerHTML = `
        <td>${imageHtml}</td>
        <td>
            <div class="fw-bold">${p.nom_produit}</div>
            ${p.code_produit ? `<small class="text-muted">${p.code_produit}</small>` : ''}
        </td>
        <td>${catHtml}</td>
        ${prixAchatCol}
        <td class="text-end fw-bold">${formatMontant(p.prix_vente)}</td>
        <td class="text-center"><span class="badge badge-stock ${stockClass}">${stock}</span></td>
        <td class="text-center">${actionsBtns}</td>
    `;
    return tr;
}

// Soumettre formulaire produit
function handleProductSubmit(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const productId = document.getElementById('product_id').value;
    const action = productId ? 'update_product' : 'add_product';
    formData.append('action', action);
    if (productId) formData.append('id_produit', productId);

    fetch('ajax/produits.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (typeof hideLoader === 'function') hideLoader();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalProduit')).hide();

            // Récupérer le produit complet puis mettre à jour le tableau
            const fd = new FormData();
            fd.append('action', 'get_product');
            fd.append('id_produit', productId || data.id_produit);

            fetch('ajax/produits.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    const tbody = document.getElementById('produitsTableBody');
                    const newRow = buildProductRow(res.produit);

                    if (productId) {
                        // Mise à jour : remplacer la ligne existante
                        const existing = tbody.querySelector(`tr[data-product-id="${productId}"]`);
                        if (existing) existing.replaceWith(newRow);
                    } else {
                        // Ajout : supprimer la ligne "vide" si présente et insérer en tête
                        const emptyRow = tbody.querySelector('tr td[colspan]');
                        if (emptyRow) emptyRow.closest('tr').remove();
                        tbody.insertBefore(newRow, tbody.firstChild);
                    }
                });

            if (typeof showAlertModal === 'function') {
                showAlertModal({ title: 'Succès', message: data.message, type: 'success' });
            }
        } else {
            if (typeof showAlertModal === 'function') {
                showAlertModal({ title: 'Erreur', message: data.message, type: 'error' });
            } else {
                alert(data.message);
            }
        }
    })
    .catch(err => {
        if (typeof hideLoader === 'function') hideLoader();
        console.error(err);
        alert('Erreur réseau');
    });
}

// Aperçu image produit
function previewProductImage(input) {
    const preview = document.getElementById('product_image_preview');
    const previewImg = document.getElementById('product_image_preview_img');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Vérifier taille (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            if (typeof showAlertModal === 'function') {
                showAlertModal({
                    title: 'Image trop volumineuse',
                    message: 'La taille maximale est de 2MB. Compressez l\'image avant de la télécharger.',
                    type: 'warning'
                });
            } else {
                alert('Image trop volumineuse (max 2MB)');
            }
            input.value = '';
            preview.style.display = 'none';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
}

// Soumettre formulaire stock
function handleStockSubmit(e) {
    e.preventDefault();
    console.log('Soumission formulaire stock');
    
    const formData = new FormData(this);
    formData.append('action', 'adjust_stock');
    
    fetch('ajax/produits.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalAjustStock')).hide();
            if (typeof showAlertModal === 'function') {
                showAlertModal({
                    title: 'Succès',
                    message: data.message,
                    type: 'success',
                    onClose: () => location.reload()
                });
            } else {
                alert(data.message);
                location.reload();
            }
        } else {
            if (typeof showAlertModal === 'function') {
                showAlertModal({
                    title: 'Erreur',
                    message: data.message,
                    type: 'error'
                });
            } else {
                alert(data.message);
            }
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erreur réseau');
    });
}

// ===== GESTION CLIENTS =====
const btnAddClient = document.getElementById('btnAddClient');
const modalClient = document.getElementById('modalClient');
const clientForm = document.getElementById('clientForm');

if (btnAddClient) {
    btnAddClient.addEventListener('click', () => openClientModal());
}
if (clientForm) {
    clientForm.addEventListener('submit', function(e) {
        e.preventDefault();
        saveClient();
    });
}
document.querySelectorAll('.btn-edit-client').forEach(btn => {
    btn.addEventListener('click', function() {
        editClient(JSON.parse(this.getAttribute('data-client')));
    });
});
document.querySelectorAll('.btn-delete-client').forEach(btn => {
    btn.addEventListener('click', function() {
        deleteClient(this.getAttribute('data-id'), this.getAttribute('data-name'));
    });
});

function openClientModal(client = null) {
    if (client) {
        document.getElementById('modalClientTitle').textContent = 'Modifier le client';
        document.getElementById('client_id').value = client.id_client;
        document.getElementById('client_name').value = client.nom_client;
        document.getElementById('client_telephone').value = client.telephone || '';
        document.getElementById('client_email').value = client.email || '';
        document.getElementById('client_adresse').value = client.adresse || '';
    } else {
        document.getElementById('modalClientTitle').textContent = 'Nouveau client';
        clientForm.reset();
        document.getElementById('client_id').value = '';
    }
    new bootstrap.Modal(modalClient).show();
}

function editClient(client) {
    openClientModal(client);
}

function deleteClient(id, name) {
    const doDelete = () => {
        fetch('ajax/clients.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'delete_client', id_client: id })
        })
        .then(r => r.json())
        .then(data => {
            if (typeof showAlertModal === 'function') {
                showAlertModal({
                    title: data.success ? 'Succès' : 'Erreur',
                    message: data.message,
                    type: data.success ? 'success' : 'error',
                    onClose: () => { if (data.success) location.reload(); }
                });
            } else {
                alert(data.message);
                if (data.success) location.reload();
            }
        });
    };
    
    showConfirmModal({
        title: 'Confirmer la suppression',
        message: `Supprimer le client "${name}" ?`,
        type: 'warning'
    }).then(confirmed => {
        if (confirmed) doDelete();
    });
}

function saveClient() {
    const formData = new URLSearchParams({
        action: document.getElementById('client_id').value ? 'update_client' : 'add_client',
        id_client: document.getElementById('client_id').value,
        nom_client: document.getElementById('client_name').value,
        telephone: document.getElementById('client_telephone').value,
        email: document.getElementById('client_email').value,
        adresse: document.getElementById('client_adresse').value
    });
    
    fetch('ajax/clients.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(modalClient).hide();
        }
        showAlertModal({
            title: data.success ? 'Succès' : 'Erreur',
            message: data.message,
            type: data.success ? 'success' : 'error'
        }).then(() => {
            if (data.success) location.reload();
        });
    })
    .catch(err => { console.error(err); alert('Erreur réseau'); });
}

// ===== GESTION CATÉGORIES =====
const btnAddCategory = document.getElementById('btnAddCategory');
const modalCategory = document.getElementById('modalCategory');
const categoryForm = document.getElementById('categoryForm');

if (btnAddCategory) {
    btnAddCategory.addEventListener('click', () => openCategoryModal());
}
if (categoryForm) {
    categoryForm.addEventListener('submit', function(e) {
        e.preventDefault();
        saveCategory();
    });
}
document.querySelectorAll('.btn-edit-category').forEach(btn => {
    btn.addEventListener('click', function() {
        editCategory(JSON.parse(this.getAttribute('data-category')));
    });
});
document.querySelectorAll('.btn-delete-category').forEach(btn => {
    btn.addEventListener('click', function() {
        deleteCategory(this.getAttribute('data-id'), this.getAttribute('data-name'), parseInt(this.getAttribute('data-products')));
    });
});

function openCategoryModal(category = null) {
    if (category) {
        document.getElementById('modalCategoryTitle').textContent = 'Modifier la catégorie';
        document.getElementById('category_id').value = category.id_categorie;
        document.getElementById('category_name').value = category.nom_categorie;
        document.getElementById('category_description').value = category.description || '';
    } else {
        document.getElementById('modalCategoryTitle').textContent = 'Nouvelle catégorie';
        categoryForm.reset();
        document.getElementById('category_id').value = '';
    }
    new bootstrap.Modal(modalCategory).show();
}

function editCategory(category) {
    openCategoryModal(category);
}

function deleteCategory(id, name, nbProducts) {
    if (nbProducts > 0) {
        if (typeof showAlertModal === 'function') {
            showAlertModal({
                title: 'Suppression impossible',
                message: `La catégorie "${name}" contient ${nbProducts} produit(s).`,
                type: 'error'
            });
        } else {
            alert(`Impossible : ${nbProducts} produit(s) dans cette catégorie.`);
        }
        return;
    }
    
    const doDelete = () => {
        fetch('ajax/categories.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'delete_category', id_categorie: id })
        })
        .then(r => r.json())
        .then(data => {
            if (typeof showAlertModal === 'function') {
                showAlertModal({
                    title: data.success ? 'Succès' : 'Erreur',
                    message: data.message,
                    type: data.success ? 'success' : 'error',
                    onClose: () => { if (data.success) location.reload(); }
                });
            } else {
                alert(data.message);
                if (data.success) location.reload();
            }
        });
    };
    
    showConfirmModal({
        title: 'Confirmer la suppression',
        message: `Supprimer la catégorie "${name}" ?`,
        type: 'warning'
    }).then(confirmed => {
        if (confirmed) doDelete();
    });
}

function saveCategory() {
    const formData = new URLSearchParams({
        action: document.getElementById('category_id').value ? 'update_category' : 'add_category',
        id_categorie: document.getElementById('category_id').value,
        nom_categorie: document.getElementById('category_name').value,
        description: document.getElementById('category_description').value
    });
    
    fetch('ajax/categories.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(modalCategory).hide();
        }
        showAlertModal({
            title: data.success ? 'Succès' : 'Erreur',
            message: data.message,
            type: data.success ? 'success' : 'error'
        }).then(() => {
            if (data.success) location.reload();
        });
    })
    .catch(err => { console.error(err); alert('Erreur réseau'); });
}

// ===== GESTION VENTES =====
document.querySelectorAll('.btn-cancel-vente').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const numero = this.getAttribute('data-numero');
        cancelVente(id, numero);
    });
});

// ===== GESTION VENTES =====
document.addEventListener('DOMContentLoaded', function() {
    // Boutons voir détails vente
    document.querySelectorAll('.btn-view-vente').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            viewVenteDetails(id);
        });
    });
    
    // Boutons annuler vente
    document.querySelectorAll('.btn-cancel-vente').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const numero = this.getAttribute('data-numero');
            cancelVente(id, numero);
        });
    });
    
    // Boutons restaurer vente (ventes annulées)
    document.querySelectorAll('.btn-restore-vente').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const numero = this.getAttribute('data-numero');
            
            if (typeof showConfirmModal === 'function') {
                showConfirmModal({
                    title: 'Restaurer la vente',
                    message: `Restaurer la vente ${numero} en statut validé ? Le stock sera réduit.`,
                    type: 'info',
                    confirmText: 'Restaurer',
                    cancelText: 'Annuler'
                }).then(confirmed => {
                    if (!confirmed) return;
                    restoreVente(id);
                });
            }
        });
    });
    
    // Boutons supprimer vente (ventes annulées, admin seulement)
    document.querySelectorAll('.btn-delete-vente').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const numero = this.getAttribute('data-numero');
            
            if (typeof showConfirmModal === 'function') {
                showConfirmModal({
                    title: 'Supprimer définitivement',
                    message: `Supprimer la vente ${numero} ? Cette action est irréversible.`,
                    type: 'danger',
                    confirmText: 'Supprimer définitivement',
                    cancelText: 'Revenir'
                }).then(confirmed => {
                    if (!confirmed) return;
                    deleteVente(id);
                });
            }
        });
    });
});

function viewVenteDetails(id) {
    const modal = new bootstrap.Modal(document.getElementById('venteDetailsModal'));
    const contentDiv = document.getElementById('venteDetailsContent');
    
    contentDiv.innerHTML = '<p class="text-center"><div class="spinner-border spinner-border-sm"></div> Chargement...</p>';
    
    fetch('ajax/get_vente_details.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                contentDiv.innerHTML = data.html;
            } else {
                contentDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        })
        .catch(err => {
            console.error(err);
            contentDiv.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement</div>';
        });
    
    modal.show();
}

function exportVentesExcel() {
    // Récupérer les filtres actuels
    const formData = new FormData();
    const inputs = document.querySelectorAll('form input, form select');
    inputs.forEach(input => {
        if (input.name && input.value) {
            formData.append(input.name, input.value);
        }
    });
    
    // Créer formulaire de soumission POST
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'ajax/export_ventes.php';
    
    for (let [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function cancelVente(id, numero) {
    if (typeof showConfirmModal === 'function') {
        showConfirmModal({
            title: 'Confirmer l\'annulation',
            message: `Êtes-vous sûr de vouloir annuler la vente ${numero} ? Cette action est irréversible et le stock sera remis à jour.`,
            type: 'danger',
            confirmText: 'Annuler la vente',
            cancelText: 'Revenir'
        }).then(confirmed => {
            if (!confirmed) return;
            
            fetch('ajax/valider_vente.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'cancel_vente',
                    id_vente: id
                })
            })
            .then(r => r.json())
            .then(data => {
                console.log('📝 Réponse annulation:', data);
                if (typeof showAlertModal === 'function') {
                    showAlertModal({
                        title: data.success ? 'Succès' : 'Erreur',
                        message: data.message,
                        type: data.success ? 'success' : 'error'
                    }).then(() => {
                        if (data.success) location.reload();
                    });
                } else {
                    alert(data.message);
                    if (data.success) location.reload();
                }
            })
            .catch(err => {
                console.error('❌ Erreur requête:', err);
                if (typeof showAlertModal === 'function') {
                    showAlertModal({
                        title: 'Erreur',
                        message: 'Erreur de connexion: ' + err.message,
                        type: 'error'
                    });
                } else {
                    alert('Erreur: ' + err.message);
                }
            });
        });
    } else if (confirm(`Annuler la vente ${numero} ? Le stock sera remis à jour.`)) {
        fetch('ajax/valider_vente.php', {
            method: 'POST',
            body: new URLSearchParams({
                action: 'cancel_vente',
                id_vente: id
            })
        })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    }
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

// ==================== FOURNISSEURS ====================
function ouvrirModalFournisseur() {
    document.getElementById('formFournisseur').reset();
    document.getElementById('fournisseur_id').value = '';
    document.getElementById('modalFournisseurTitle').textContent = 'Nouveau Fournisseur';
    const modal = new bootstrap.Modal(document.getElementById('modalFournisseur'));
    modal.show();
}

function modifierFournisseur(fournisseur) {
    document.getElementById('fournisseur_id').value = fournisseur.id_fournisseur;
    document.getElementById('fournisseur_nom').value = fournisseur.nom_fournisseur;
    document.getElementById('fournisseur_contact').value = fournisseur.contact || '';
    document.getElementById('fournisseur_telephone').value = fournisseur.telephone || '';
    document.getElementById('fournisseur_email').value = fournisseur.email || '';
    document.getElementById('fournisseur_adresse').value = fournisseur.adresse || '';
    document.getElementById('modalFournisseurTitle').textContent = 'Modifier Fournisseur';
    const modal = new bootstrap.Modal(document.getElementById('modalFournisseur'));
    modal.show();
}

function supprimerFournisseur(id, nom, nbProduits) {
    if (nbProduits > 0) {
        showAlertModal({
            title: 'Suppression impossible',
            message: `Ce fournisseur est lié à ${nbProduits} produit(s). Veuillez d'abord retirer l'association avec ces produits.`,
            type: 'warning'
        });
        return;
    }
    
    showConfirmModal({
        title: 'Confirmer la suppression',
        message: `Êtes-vous sûr de vouloir supprimer le fournisseur "${nom}" ?`,
        onConfirm: () => {
            const formData = new URLSearchParams({
                action: 'delete',
                id_fournisseur: id
            });
            
            fetch('ajax/fournisseurs.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showAlertModal({
                            title: 'Succès',
                            message: data.message,
                            type: 'success'
                        });
                        setTimeout(() => location.reload(), 1000);
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

document.getElementById('formFournisseur')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const id = document.getElementById('fournisseur_id').value;
    const formData = new URLSearchParams({
        action: id ? 'update' : 'create',
        id_fournisseur: id,
        nom_fournisseur: document.getElementById('fournisseur_nom').value,
        contact: document.getElementById('fournisseur_contact').value,
        telephone: document.getElementById('fournisseur_telephone').value,
        email: document.getElementById('fournisseur_email').value,
        adresse: document.getElementById('fournisseur_adresse').value
    });
    
    fetch('ajax/fournisseurs.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlertModal({
                    title: 'Succès',
                    message: data.message,
                    type: 'success'
                });
                bootstrap.Modal.getInstance(document.getElementById('modalFournisseur')).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlertModal({
                    title: 'Erreur',
                    message: data.message,
                    type: 'error'
                });
            }
        });
});

// ==================== DEPOTS ====================
function ouvrirModalDepot() {
    document.getElementById('formDepot').reset();
    document.getElementById('depot_id').value = '';
    document.getElementById('modalDepotTitle').textContent = 'Nouveau Dépôt';
    const modal = new bootstrap.Modal(document.getElementById('modalDepot'));
    modal.show();
}

function modifierDepot(depot) {
    document.getElementById('depot_id').value = depot.id_depot;
    document.getElementById('depot_nom').value = depot.nom_depot;
    document.getElementById('depot_type').value = depot.type_depot || 'depot';
    document.getElementById('depot_responsable').value = depot.responsable || '';
    document.getElementById('depot_telephone').value = depot.telephone || '';
    document.getElementById('depot_adresse').value = depot.adresse || '';
    document.getElementById('depot_description').value = depot.description || '';
    document.getElementById('depot_principal').checked = depot.est_principal == 1;
    document.getElementById('modalDepotTitle').textContent = 'Modifier Dépôt';
    const modal = new bootstrap.Modal(document.getElementById('modalDepot'));
    modal.show();
}

function supprimerDepot(id, nom, stockTotal) {
    if (stockTotal > 0) {
        showAlertModal({
            title: 'Suppression impossible',
            message: `Ce dépôt contient ${stockTotal} unités en stock. Veuillez d'abord vider le stock ou le transférer vers un autre dépôt.`,
            type: 'warning'
        });
        return;
    }
    
    showConfirmModal({
        title: 'Confirmer la suppression',
        message: `Êtes-vous sûr de vouloir supprimer le dépôt "${nom}" ?`,
        onConfirm: () => {
            const formData = new URLSearchParams({
                action: 'delete',
                id_depot: id
            });
            
            fetch('ajax/depots.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showAlertModal({
                            title: 'Succès',
                            message: data.message,
                            type: 'success'
                        });
                        setTimeout(() => location.reload(), 1000);
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

document.getElementById('formDepot')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const id = document.getElementById('depot_id').value;
    const formData = new URLSearchParams({
        action: id ? 'update' : 'create',
        id_depot: id,
        nom_depot: document.getElementById('depot_nom').value,
        type_depot: document.getElementById('depot_type').value,
        responsable: document.getElementById('depot_responsable').value,
        telephone: document.getElementById('depot_telephone').value,
        adresse: document.getElementById('depot_adresse').value,
        description: document.getElementById('depot_description').value,
        est_principal: document.getElementById('depot_principal').checked ? '1' : '0'
    });
    
    fetch('ajax/depots.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlertModal({
                    title: 'Succès',
                    message: data.message,
                    type: 'success'
                });
                bootstrap.Modal.getInstance(document.getElementById('modalDepot')).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlertModal({
                    title: 'Erreur',
                    message: data.message,
                    type: 'error'
                });
            }
        });
});

// Fonction de recherche en temps réel pour les produits
const searchProduitsInput = document.getElementById('searchProduits');
if (searchProduitsInput) {
    searchProduitsInput.addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        const rows = document.querySelectorAll('table.card-table tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// Fonction de recherche en temps réel pour les clients
const searchClientsInput = document.getElementById('searchClients');
if (searchClientsInput) {
    searchClientsInput.addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        const table = searchClientsInput.closest('.card-body') ? searchClientsInput.closest('.card').querySelector('table') : null;
        if (table) {
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    });
}

// Fonction de recherche en temps réel pour les fournisseurs
const searchFournisseursInput = document.getElementById('searchFournisseurs');
if (searchFournisseursInput) {
    searchFournisseursInput.addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        const table = searchFournisseursInput.closest('.card-body') ? searchFournisseursInput.closest('.card').querySelector('table') : null;
        if (table) {
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    });
}

// ── Crédits ──────────────────────────────────────────────────────────────

// Filtre par statut
const creditFilterBtns = document.getElementById('creditFilterBtns');
if (creditFilterBtns) {
    creditFilterBtns.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-filter]');
        if (!btn) return;
        creditFilterBtns.querySelectorAll('button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const filter = btn.dataset.filter;
        document.querySelectorAll('#creditsTable tbody tr[data-statut]').forEach(row => {
            row.style.display = (filter === 'all' || row.dataset.statut === filter) ? '' : 'none';
        });
    });
}

// Recherche en temps réel crédits
const searchCreditsInput = document.getElementById('searchCredits');
if (searchCreditsInput) {
    searchCreditsInput.addEventListener('keyup', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#creditsTable tbody tr[data-statut]').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

// Bouton "Ajouter un paiement"
document.querySelectorAll('.btn-ajouter-paiement').forEach(btn => {
    btn.addEventListener('click', function() {
        const id      = this.dataset.id;
        const facture = this.dataset.facture;
        const client  = this.dataset.client;
        const solde   = parseFloat(this.dataset.solde);
        document.getElementById('paiement_id_vente').value        = id;
        document.getElementById('paiement_facture_label').textContent = facture;
        document.getElementById('paiement_client_label').textContent  = client || '—';
        document.getElementById('paiement_solde_label').textContent   = solde.toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' <?php echo $devise; ?>';
        document.getElementById('paiement_montant').max   = solde;
        document.getElementById('paiement_montant').value = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAjouterPaiement')).show();
    });
});

// Soumission paiement crédit
const formAjouterPaiement = document.getElementById('formAjouterPaiement');
if (formAjouterPaiement) {
    formAjouterPaiement.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fetch('ajax/credits.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                bootstrap.Modal.getInstance(document.getElementById('modalAjouterPaiement')).hide();
                if (typeof showAlertModal === 'function') {
                    showAlertModal({
                        title: data.success ? 'Succès' : 'Erreur',
                        message: data.message,
                        type: data.success ? 'success' : 'error'
                    }).then(() => { if (data.success) location.reload(); });
                } else {
                    alert(data.message);
                    if (data.success) location.reload();
                }
            })
            .catch(() => alert('Erreur réseau'));
    });
}

// Bouton "Historique"
document.querySelectorAll('.btn-historique-credit').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const contentEl = document.getElementById('historiquePaiementsContent');
        contentEl.innerHTML = '<p class="text-center"><span class="spinner-border spinner-border-sm"></span> Chargement…</p>';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHistoriquePaiements')).show();

        const fd = new FormData();
        fd.append('action', 'get_historique');
        fd.append('id_vente', id);
        fetch('ajax/credits.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    contentEl.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                    return;
                }
                const v  = data.vente;
                const ps = data.paiements;
                const dv = data.devise;
                const fmt = n => parseFloat(n).toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' ' + dv;

                let rows = '';
                if (ps.length === 0) {
                    rows = '<tr><td colspan="5" class="text-center text-muted">Aucun paiement enregistré</td></tr>';
                } else {
                    ps.forEach(p => {
                        const modes = {especes:'Espèces', carte:'Carte', mobile_money:'Mobile Money', cheque:'Chèque'};
                        rows += `<tr>
                            <td><small>${new Date(p.date_paiement).toLocaleDateString('fr-FR')}</small></td>
                            <td class="fw-bold text-success">${fmt(p.montant)}</td>
                            <td>${modes[p.mode_paiement] || p.mode_paiement}</td>
                            <td>${p.notes || '—'}</td>
                            <td><small class="text-muted">${p.utilisateur || '—'}</small></td>
                        </tr>`;
                    });
                }

                contentEl.innerHTML = `
                    <div class="mb-3 p-3 bg-light rounded">
                        <div class="row">
                            <div class="col-6"><span class="text-muted">Facture</span><br><strong>${v.numero_facture}</strong></div>
                            <div class="col-6"><span class="text-muted">Client</span><br><strong>${v.nom_client || '—'}</strong></div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-4"><span class="text-muted">Total</span><br><strong>${fmt(v.montant_total)}</strong></div>
                            <div class="col-4"><span class="text-muted">Payé</span><br><strong class="text-success">${fmt(data.total_paye)}</strong></div>
                            <div class="col-4"><span class="text-muted">Reste dû</span><br><strong class="text-danger">${fmt(data.solde)}</strong></div>
                        </div>
                        ${v.date_echeance ? '<div class="mt-2"><span class="text-muted">Échéance :</span> <strong>' + new Date(v.date_echeance).toLocaleDateString('fr-FR') + '</strong></div>' : ''}
                    </div>
                    <table class="table table-sm">
                        <thead><tr><th>Date</th><th>Montant</th><th>Mode</th><th>Notes</th><th>Saisi par</th></tr></thead>
                        <tbody>${rows}</tbody>
                    </table>`;
            })
            .catch(() => {
                contentEl.innerHTML = '<div class="alert alert-danger">Erreur de chargement</div>';
            });
    });
});

// ─────────────────────────────────────────────────────────────────────────────

// Fonction de recherche en temps réel pour les mouvements
const searchMouvementsInput = document.getElementById('searchMouvements');
if (searchMouvementsInput) {
    searchMouvementsInput.addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        const table = searchMouvementsInput.closest('.card-body') ? searchMouvementsInput.closest('.card').querySelector('table') : null;
        if (table) {
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    });
}

</script>

<?php require_once('footer.php'); ?>
