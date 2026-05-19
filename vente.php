<?php
/**
 * PAGE DE VENTE - STORE SUITE
 * Interface vendeur avec panier, TVA 16%, et modal ajout produit
 */
require_once 'protection_pages.php';
$page_title = 'Nouvelle Vente';

// Récupération des produits actifs avec stock
// Admin : tous les produits (stock global) ; Vendeur : stock de son dépôt uniquement
if ($is_admin) {
    $produits = db_fetch_all("
        SELECT p.*, c.nom_categorie, p.quantite_stock AS stock_disponible
        FROM produits p
        LEFT JOIN categories c ON p.id_categorie = c.id_categorie
        WHERE p.est_actif = 1 AND (p.quantite_stock > 0 OR p.type_produit = 'service')
        ORDER BY p.nom_produit ASC
    ");
} else {
    // Récupérer le dépôt du vendeur
    $vendeur_info = db_fetch_one("SELECT id_depot FROM utilisateurs WHERE id_utilisateur = ?", [$user_id]);
    $id_depot_vendeur = $vendeur_info['id_depot'] ?? null;

    if ($id_depot_vendeur) {
        $produits = db_fetch_all("
            SELECT p.*, c.nom_categorie, COALESCE(s.quantite, 0) AS stock_disponible
            FROM produits p
            LEFT JOIN stock_par_depot s ON s.id_produit = p.id_produit AND s.id_depot = ?
            LEFT JOIN categories c ON p.id_categorie = c.id_categorie
            WHERE p.est_actif = 1
              AND ((p.type_produit = 'standard' AND s.quantite > 0) OR p.type_produit = 'service')
            ORDER BY p.nom_produit ASC
        ", [$id_depot_vendeur]);
    } else {
        // Vendeur sans dépôt : aucun produit
        $produits = [];
    }
}

// Récupération des clients
$clients = db_fetch_all("
    SELECT * FROM clients
    WHERE est_actif = 1
    ORDER BY nom_client ASC
");

// Vérification caisse ouverte
$caisse_ouverte_vente = null;
try {
    $caisse_ouverte_vente = db_fetch_one("SELECT id_caisse FROM caisse WHERE statut = 'ouverte' AND id_utilisateur = ? ORDER BY date_ouverture DESC LIMIT 1", [$user_id]);
} catch (Exception $e) { /* table pas encore créée */ }

include 'header.php';
?>

<style>
.product-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    height: 100%;
}
.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    border-color: <?php echo $couleur_primaire; ?>;
}
.product-card:active {
    transform: scale(0.98);
}
.cart-item {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
    margin-bottom: 0.5rem;
    border-radius: 8px;
}
.cart-total-box {
    background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>);
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.price-input {
    width: 100px;
    font-weight: bold;
}
.qty-input {
    width: 70px;
    text-align: center;
    font-weight: bold;
}
.cart-section {
    position: sticky;
    top: 20px;
}
</style>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="6" cy="19" r="2"/>
                        <circle cx="17" cy="19" r="2"/>
                        <path d="M17 17h-11v-14h-2"/>
                        <path d="M6 5l14 1l-1 7h-13"/>
                    </svg>
                    Nouvelle Vente
                </h2>
                <div class="text-muted mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-info-circle" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                    Cliquez sur un produit pour l'ajouter au panier. Prix et quantités modifiables.
                </div>
            </div>
            <div class="col-auto">
                <a href="mes_ventes.php" class="btn btn-outline-primary me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 5H7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2V7a2 2 0 0 0 -2 -2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg>
                    Mes ventes
                </a>
                <a href="accueil.php" class="btn btn-outline-secondary">
                    ← Retour
                </a>
            </div>
        </div>
    </div>

    <?php if (!$caisse_ouverte_vente): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 flex-shrink-0" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>
        <div><strong>Caisse fermée.</strong> Aucune vente ne peut être effectuée. <a href="caisse.php" class="alert-link">Ouvrir la caisse</a> pour continuer.</div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- PRODUITS -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📦 Produits disponibles</h3>
                    <div class="col-auto ms-auto">
                        <input type="text" class="form-control" id="searchProduct" placeholder="🔍 Rechercher...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="productsList">
                        <?php foreach ($produits as $produit): ?>
                        <div class="col-md-4 col-sm-6 product-item" data-barcode="<?php echo e($produit['code_barre'] ?? ''); ?>">
                            <div class="product-card card">
                                <div class="card-body text-center" 
                                     data-id="<?php echo $produit['id_produit']; ?>"
                                     data-nom="<?php echo e($produit['nom_produit']); ?>"
                                     data-prix="<?php echo $produit['prix_vente']; ?>"
                                     data-stock="<?php echo $produit['quantite_stock']; ?>"
                                     data-type="<?php echo $produit['type_produit']; ?>">
                                    
                                    <?php if (!empty($produit['image']) && file_exists('uploads/produits/' . $produit['image'])): ?>
                                    <div class="mb-2">
                                        <img src="uploads/produits/<?php echo e($produit['image']); ?>"
                                             alt="<?php echo e($produit['nom_produit']); ?>" 
                                             style="height: 80px; object-fit: contain;">
                                    </div>
                                    <?php else: ?>
                                    <div class="mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-primary" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                            <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" />
                                            <path d="M12 12l8 -4.5" />
                                            <path d="M12 12l0 9" />
                                            <path d="M12 12l-8 -4.5" />
                                        </svg>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <h4 class="mb-1 fs-6 fw-bold"><?php echo e($produit['nom_produit']); ?></h4>
                                    <div class="text-muted small mb-2"><?php echo e($produit['nom_categorie'] ?? 'Sans catégorie'); ?></div>
                                    
                                    <?php if (!empty($produit['code_barre'])): ?>
                                    <div class="small mb-2">
                                        <span class="badge bg-light text-dark">📦 <?php echo e($produit['code_barre']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                        <strong class="text-primary fs-6"><?php echo format_montant($produit['prix_vente'], $devise); ?></strong>
                                        <?php if ($produit['type_produit'] === 'service'): ?>
                                            <span class="badge bg-purple text-white" style="background:#6f42c1!important;">Service</span>
                                        <?php else: ?>
                                            <span class="badge <?php echo $produit['quantite_stock'] <= 10 ? 'bg-warning' : 'bg-success'; ?>">
                                                Stock: <?php echo $produit['quantite_stock']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANIER -->
        <div class="col-lg-4">
            <div class="cart-section">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title text-white mb-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="6" cy="19" r="2"/><circle cx="17" cy="19" r="2"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/></svg>
                            Panier (<span id="cartCount">0</span>)
                        </h3>
                        <div class="col-auto ms-auto">
                            <button class="btn btn-sm btn-danger" id="btnClearCart">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                Vider
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-2" style="max-height: 400px; overflow-y: auto;" id="cartItems">
                        <div class="text-center p-5 text-muted">
                            <div class="mb-2">🛒</div>
                            <div>Panier vide</div>
                            <small>Cliquez sur un produit</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold mb-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                                    Client
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" id="btnNouveauClient" title="Créer un nouveau client">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Nouveau
                                </button>
                            </div>
                            <select class="form-select" id="clientSelect">
                                <option value="">🛒 Client Comptoir</option>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id_client']; ?>">
                                    <?php echo e($client['nom_client']); ?> - <?php echo e($client['telephone'] ?? 'N/A'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="toggleTVA">
                                <label class="form-check-label fw-bold" for="toggleTVA">
                                    Appliquer TVA (<?php echo (float)$taux_tva; ?>%)
                                </label>
                            </div>
                            <small class="text-muted">Les prix produits sont HT par défaut</small>
                        </div>

                        <?php if ($atelier_mode): ?>
                        <div class="mb-3 p-2 rounded" style="border: 1px solid #e9ecef; background: #f8f9fa;">
                            <div class="fw-semibold mb-2" style="font-size: 0.85rem;">Type de commande</div>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modeConfection" id="confNon" value="0" checked>
                                    <label class="form-check-label" for="confNon" style="font-size: 0.85rem;">Vente directe</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modeConfection" id="confOui" value="1">
                                    <label class="form-check-label fw-semibold text-primary" for="confOui" style="font-size: 0.85rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7.3 20.3l10 -10a1.5 1.5 0 0 0 0 -2.1l-2.5 -2.5a1.5 1.5 0 0 0 -2.1 0l-10 10l2.5 5z"/><line x1="14" y1="9" x2="15" y2="10"/><path d="M4 20l-1 1"/><path d="M19.3 8.3l1.4 -1.4a1.5 1.5 0 0 0 0 -2.1l-1.5 -1.5a1.5 1.5 0 0 0 -2.1 0l-1.4 1.4"/></svg>
                                        Confection
                                    </label>
                                </div>
                            </div>
                            <div id="typeConfectionDiv" class="mt-2" style="display:none;">
                                <input type="text" id="typeConfection" class="form-control form-control-sm"
                                       placeholder="Ex: chemise, pantalon, boubou…" maxlength="100">
                            </div>
                        </div>
                        <?php endif; ?>


                        <div class="cart-total-box mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Sous-total HT:</span>
                                <strong id="cartSubtotal">0 <?php echo $devise; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2 pb-2" id="tvaLine" style="border-bottom: 1px solid rgba(255,255,255,0.3); display: none !important;">
                                <span>TVA (<?php echo (float)$taux_tva; ?>%):</span>
                                <strong id="cartTVA">0 <?php echo $devise; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="fs-5" id="totalLabel">TOTAL:</span>
                                <strong class="fs-2" id="cartTotal">0 <?php echo $devise; ?></strong>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-lg" id="btnProcessSale" disabled <?php if (!$caisse_ouverte_vente) echo 'title="Ouvrez la caisse pour vendre"'; ?>>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 11 12 14 20 6"/><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9"/></svg>
                                Valider la vente
                            </button>
                            <button class="btn btn-warning" id="btnProforma" disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><line x1="9" y1="7" x2="10" y2="7"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="13" y1="17" x2="15" y2="17"/></svg>
                                Facture Proforma
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter au panier -->
<div class="modal fade" id="addToCartModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalProductName">Produit</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Prix unitaire</label>
                    <div class="input-group">
                        <input type="number" class="form-control form-control-lg fw-bold" id="modalPrice" 
                               min="0" step="0.01" value="0">
                        <span class="input-group-text"><?php echo $devise; ?></span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Quantité</label>
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary" id="btnDecreaseQty">−</button>
                        <input type="number" class="form-control form-control-lg text-center fw-bold" id="modalQuantity" 
                               min="1" value="1">
                        <button type="button" class="btn btn-outline-secondary" id="btnIncreaseQty">+</button>
                    </div>
                    <small class="text-muted" id="modalStockInfo">Stock disponible: 0</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Sous-total</label>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-lg fw-bold text-success" 
                               id="modalSubtotal" readonly value="0">
                        <span class="input-group-text"><?php echo $devise; ?></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success btn-lg" id="btnConfirmAdd">
                     Ajouter au panier
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Détails Crédit -->
<div class="modal fade" id="creditModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h6 class="modal-title mb-0 fw-bold">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7z"/>
                    </svg>
                    Vente à Crédit
                </h6>
            </div>
            <div class="modal-body">
                <div id="creditClientAlert" class="alert alert-danger d-none">
                    Vous devez sélectionner un client avant d'effectuer une vente à crédit.
                </div>

                <div id="creditForm">
                    <div class="mb-3 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Montant total</span>
                            <strong id="creditTotalDisplay" class="fs-5">0 <?php echo $devise; ?></strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Acompte (optionnel)</label>
                        <div class="input-group">
                            <input type="number" id="creditAcompte" class="form-control" min="0" step="0.01" value="0" placeholder="0">
                            <span class="input-group-text"><?php echo $devise; ?></span>
                        </div>
                        <div class="form-text">Laisser à 0 si aucun acompte n'est versé.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date d'échéance (optionnelle)</label>
                        <input type="date" id="creditEcheance" class="form-control"
                               min="<?php echo date('Y-m-d'); ?>">
                        <div class="form-text">Date limite de remboursement.</div>
                    </div>

                    <div class="mb-0 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Solde restant dû</span>
                            <strong id="creditSoldeDisplay" class="fs-5 text-danger">0 <?php echo $devise; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCreditAnnuler" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning fw-bold" id="btnCreditConfirmer">
                    Valider la vente à crédit
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouveau Client -->
<div class="modal fade" id="modalNouveauClient" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ncNom" placeholder="Nom du client">
                </div>
                <div class="mb-3">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" id="ncTel" placeholder="+xxx xxx xxx xxx">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveNouveauClient">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mode de Paiement -->
<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title mb-0">Mode de Paiement</h6>
            </div>
            <div class="modal-body p-3">
                <p class="small mb-3 text-muted">Sélectionnez le mode de paiement :</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-success payment-option" data-mode="especes">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cash me-2" viewBox="0 0 16 16">
                            <path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                            <path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V4zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V6a2 2 0 0 1-2-2H3z"/>
                        </svg>
                        Espèces
                    </button>
                    <button type="button" class="btn btn-outline-primary payment-option" data-mode="carte">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-credit-card me-2" viewBox="0 0 16 16">
                            <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7z"/>
                            <path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-1z"/>
                        </svg>
                        Carte Bancaire
                    </button>
                    <button type="button" class="btn btn-outline-info payment-option" data-mode="mobile_money">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-phone me-2" viewBox="0 0 16 16">
                            <path d="M11 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h6zM5 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H5z"/>
                            <path d="M8 14a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                        </svg>
                        Mobile Money
                    </button>
                    <button type="button" class="btn btn-outline-warning payment-option" data-mode="cheque">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-text me-2" viewBox="0 0 16 16">
                            <path d="M5 4a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zM5 8a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm0 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1H5z"/>
                            <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                        </svg>
                        Chèque
                    </button>
                    <button type="button" class="btn btn-outline-secondary payment-option" data-mode="credit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-repeat me-2" viewBox="0 0 16 16">
                            <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
                            <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
                        </svg>
                        Crédit
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
console.log('🚀 Script vente.php chargement...');

// Variables globales
let cart = [];
let currentModalProduct = null;
const TVA_RATE = <?php echo (float)$taux_tva / 100; ?>;
const CAISSE_OUVERTE = <?php echo $caisse_ouverte_vente ? 'true' : 'false'; ?>;
let modalInstance = null;
let avecTVA = false;

// Attendre le chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM chargé');
    
    // Initialiser le modal Bootstrap
    const modalElement = document.getElementById('addToCartModal');
    if (!modalElement) {
        console.error('❌ Modal element not found!');
        return;
    }
    
    modalInstance = new bootstrap.Modal(modalElement);
    console.log('✅ Modal Bootstrap initialisé');
    
    // Éléments du modal
    const modalPrice = document.getElementById('modalPrice');
    const modalQty = document.getElementById('modalQuantity');
    const modalSubtotal = document.getElementById('modalSubtotal');
    const btnDecrease = document.getElementById('btnDecreaseQty');
    const btnIncrease = document.getElementById('btnIncreaseQty');
    const btnConfirm = document.getElementById('btnConfirmAdd');
    
    // Événements produits - utiliser délégation d'événements
    document.getElementById('productsList').addEventListener('click', function(e) {
        const cardBody = e.target.closest('.card-body[data-id]');
        if (!cardBody) return;
        
        const id = parseInt(cardBody.dataset.id);
        const nom = cardBody.dataset.nom;
        const prix = parseFloat(cardBody.dataset.prix);
        const stock = parseInt(cardBody.dataset.stock);
        const type = cardBody.dataset.type || 'standard';

        console.log('🛒 Produit cliqué:', {id, nom, prix, stock, type});
        openAddToCartModal(id, nom, prix, stock, type);
    });
    
    // Événements modal
    btnDecrease.addEventListener('click', () => {
        const val = Math.max(1, parseInt(modalQty.value) - 1);
        modalQty.value = val;
        updateModalSubtotal();
    });
    
    btnIncrease.addEventListener('click', () => {
        if (!currentModalProduct) return;
        const val = Math.min(currentModalProduct.stockMax, parseInt(modalQty.value) + 1);
        modalQty.value = val;
        updateModalSubtotal();
    });
    
    modalPrice.addEventListener('input', updateModalSubtotal);
    modalQty.addEventListener('input', updateModalSubtotal);
    
    btnConfirm.addEventListener('click', confirmAddToCart);
    
    // Événements panier
    document.getElementById('btnClearCart').addEventListener('click', clearCart);
    document.getElementById('btnProcessSale').addEventListener('click', processSale);
    document.getElementById('btnProforma').addEventListener('click', generateProforma);

    // Listeners crédit (accès à processSaleWithPayment ici)
    document.getElementById('creditAcompte').addEventListener('input', updateCreditSolde);
    document.getElementById('btnCreditConfirmer').addEventListener('click', function() {
        const acompte  = parseFloat(document.getElementById('creditAcompte').value) || 0;
        const echeance = document.getElementById('creditEcheance').value;
        if (creditModalInstance) creditModalInstance.hide();
        processSaleWithPayment('credit', { acompte: acompte, date_echeance: echeance });
    });
    
    // Toggle confection
    document.querySelectorAll('input[name="modeConfection"]').forEach(function(r) {
        r.addEventListener('change', function() {
            const div = document.getElementById('typeConfectionDiv');
            if (div) div.style.display = this.value === '1' ? 'block' : 'none';
        });
    });

    // Toggle TVA
    document.getElementById('toggleTVA').addEventListener('change', function() {
        avecTVA = this.checked;
        const tvaLine = document.getElementById('tvaLine');
        const totalLabel = document.getElementById('totalLabel');
        if (avecTVA) {
            tvaLine.style.removeProperty('display');
            totalLabel.textContent = 'TOTAL TTC:';
        } else {
            tvaLine.style.setProperty('display', 'none', 'important');
            totalLabel.textContent = 'TOTAL:';
        }
        updateCart();
    });

    // Recherche produits
    document.getElementById('searchProduct').addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.product-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            const barcode = item.dataset.barcode || '';
            const matches = text.includes(search) || barcode.includes(search);
            item.style.display = matches ? '' : 'none';
        });
    });
    
    console.log('✅ Tous les événements attachés');
    
    // Tester si showConfirmModal existe
    console.log('🔍 Test showConfirmModal:', typeof showConfirmModal);
    console.log('🔍 Test showAlertModal:', typeof showAlertModal);
    
    // Fonction pour ouvrir le modal
    function openAddToCartModal(id, nom, prix, stockMax, type) {
        const isService = type === 'service';
        const effectiveMax = isService ? 9999 : stockMax;

        currentModalProduct = { id, nom, prix, stockMax: effectiveMax, isService };

        document.getElementById('modalProductName').textContent = nom;
        document.getElementById('modalStockInfo').textContent = isService
            ? '🔧 Prestation de service'
            : `📦 Stock disponible: ${stockMax}`;

        modalPrice.value = prix;
        modalQty.value = 1;
        modalQty.max = isService ? '' : stockMax;
        btnIncrease.disabled = false;

        updateModalSubtotal();
        modalInstance.show();
    }
    
    function updateModalSubtotal() {
        const price = parseFloat(modalPrice.value) || 0;
        const qty = parseInt(modalQty.value) || 0;
        const subtotal = price * qty;
        modalSubtotal.value = subtotal.toLocaleString('fr-FR', {minimumFractionDigits: 2});
    }
    
    function confirmAddToCart() {
        if (!currentModalProduct) return;
        
        const price = parseFloat(modalPrice.value) || currentModalProduct.prix;
        const qty = parseInt(modalQty.value) || 1;
        
        if (qty < 1 || (!currentModalProduct.isService && qty > currentModalProduct.stockMax)) {
            if (typeof showAlertModal === 'function') {
                showAlertModal({
                    title: 'Quantité invalide',
                    message: currentModalProduct.isService
                        ? 'Veuillez saisir une quantité d\'au moins 1'
                        : `Veuillez saisir une quantité entre 1 et ${currentModalProduct.stockMax}`,
                    type: 'warning'
                });
            } else {
                alert('Quantité invalide');
            }
            return;
        }
        
        // Vérifier si déjà dans le panier
        const existing = cart.find(item => item.id === currentModalProduct.id);
        if (existing) {
            existing.quantite += qty;
            if (!currentModalProduct.isService && existing.quantite > currentModalProduct.stockMax) {
                existing.quantite = currentModalProduct.stockMax;
            }
        } else {
            cart.push({
                id: currentModalProduct.id,
                nom: currentModalProduct.nom,
                prix: price,
                quantite: qty,
                stockMax: currentModalProduct.stockMax,
                isService: currentModalProduct.isService
            });
        }
        
        updateCart();
        modalInstance.hide();
        
        if (typeof showAlertModal === 'function') {
            showAlertModal({
                title: 'Succès',
                message: `${qty}x ${currentModalProduct.nom} ajoutés`,
                type: 'success'
            });
        }
    }
    
    function updateCart() {
        const cartItems = document.getElementById('cartItems');
        const btnProcessSale = document.getElementById('btnProcessSale');
        const btnProforma = document.getElementById('btnProforma');
        const cartCount = document.getElementById('cartCount');
        
        cartCount.textContent = cart.length;
        
        if (cart.length === 0) {
            cartItems.innerHTML = `
                <div class="text-center p-5 text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg mb-2" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="6" cy="19" r="2"/><circle cx="17" cy="19" r="2"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/></svg>
                    <div>Panier vide</div>
                    <small>Cliquez sur un produit pour commencer</small>
                </div>
            `;
            btnProcessSale.disabled = true;
            btnProforma.disabled = true;
            updateTotals(0);
            return;
        }
        
        btnProcessSale.disabled = false;
        btnProforma.disabled = false;
        
        let html = '';
        let subtotal = 0;
        
        cart.forEach((item, index) => {
            const itemTotal = item.prix * item.quantite;
            subtotal += itemTotal;
            
            html += `
                <div class="cart-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <strong class="text-dark" style="flex: 1;">${item.nom}</strong>
                        <button class="btn btn-sm btn-danger ms-2" onclick="removeFromCart(${index})">×</button>
                    </div>
                    <div class="row g-2 align-items-center">
                        <div class="col-5">
                            <label class="form-label mb-0 small">Prix</label>
                            <input type="number" class="form-control form-control-sm price-input" value="${item.prix}" 
                                   onchange="updatePrice(${index}, this.value)" min="0" step="0.01">
                        </div>
                        <div class="col-3">
                            <label class="form-label mb-0 small">Qté</label>
                            <input type="number" class="form-control form-control-sm qty-input" value="${item.quantite}" 
                                   onchange="updateQuantity(${index}, this.value)" min="1" max="${item.stockMax}">
                        </div>
                        <div class="col-4 text-end">
                            <label class="form-label mb-0 small">Total</label>
                            <div class="fw-bold text-primary">${itemTotal.toLocaleString('fr-FR', {minimumFractionDigits: 2})} <?php echo $devise; ?></div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        cartItems.innerHTML = html;
        updateTotals(subtotal);
    }
    
    function updateTotals(subtotalHT) {
        // Les prix sont HT par défaut. TVA s'ajoute en option.
        const tva = avecTVA ? subtotalHT * TVA_RATE : 0;
        const total = subtotalHT + tva;

        document.getElementById('cartSubtotal').textContent = subtotalHT.toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' <?php echo $devise; ?>';
        document.getElementById('cartTVA').textContent = tva.toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' <?php echo $devise; ?>';
        document.getElementById('cartTotal').textContent = total.toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' <?php echo $devise; ?>';
    }
    
    // Fonctions globales pour les événements inline
    window.removeFromCart = function(index) {
        showConfirmModal({
            title: 'Confirmer la suppression',
            message: `Retirer ${cart[index].nom} du panier ?`,
            type: 'warning'
        }).then(confirmed => {
            if (confirmed) {
                cart.splice(index, 1);
                updateCart();
            }
        });
    };
    
    window.updatePrice = function(index, value) {
        const newPrice = parseFloat(value) || 0;
        if (newPrice >= 0) {
            cart[index].prix = newPrice;
            updateCart();
        }
    };
    
    window.updateQuantity = function(index, value) {
        const newQty = parseInt(value) || 1;
        if (newQty > 0 && (cart[index].isService || newQty <= cart[index].stockMax)) {
            cart[index].quantite = newQty;
            updateCart();
        } else {
            if (typeof showAlertModal === 'function') {
                showAlertModal({
                    title: 'Stock insuffisant',
                    message: `Stock maximum disponible: ${cart[index].stockMax}`,
                    type: 'warning'
                });
            } else {
                alert(`Stock maximum: ${cart[index].stockMax}`);
            }
            updateCart();
        }
    };
    
    function clearCart() {
        if (cart.length === 0) return;
        
        showConfirmModal({
            title: 'Vider le panier',
            message: 'Êtes-vous sûr de vouloir vider le panier ?',
            type: 'warning'
        }).then(confirmed => {
            if (confirmed) {
                cart = [];
                updateCart();
            }
        });
    }
    
    function processSale() {
        console.log('🎯 processSale appelée');

        if (!CAISSE_OUVERTE) {
            showAlertModal({ title: 'Caisse fermée', message: 'Veuillez ouvrir la caisse avant d\'effectuer une vente.', type: 'warning', icon: 'warning' });
            return;
        }

        if (cart.length === 0) {
            console.log('⚠ Panier vide');
            return;
        }
        
        const subtotalHT = cart.reduce((sum, item) => sum + (item.prix * item.quantite), 0);
        const totalTTC = avecTVA ? subtotalHT * (1 + TVA_RATE) : subtotalHT;
        
        console.log('💰 Montant total TTC:', totalTTC);
        
        const totalTTCFormatted = totalTTC.toLocaleString('fr-FR', {minimumFractionDigits: 2});
        
        showConfirmModal({
            title: 'Confirmer la vente',
            message: `Montant total : <strong>${totalTTCFormatted} <?php echo $devise; ?></strong>`,
            type: 'warning'
        }).then(confirmed => {
            if (!confirmed) {
                console.log('❌ Vente annulée par l\'utilisateur');
                return;
            }
            
            console.log('👤 Utilisateur a confirmé');
            // Afficher le modal de mode de paiement
            console.log('💳 Affichage modal mode de paiement...');
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            
            // Écouter la sélection du mode de paiement
            document.querySelectorAll('.payment-option').forEach(btn => {
                btn.onclick = function() {
                    const mode = this.dataset.mode;
                    console.log('💳 Mode sélectionné:', mode);
                    paymentModal.hide();

                    if (mode === 'credit') {
                        openCreditModal(subtotalHT);
                    } else {
                        processSaleWithPayment(mode);
                    }
                };
            });
            
            paymentModal.show();
        });
    }
    
    // ── Gestion crédit ─────────────────────────────────────────────────
    let creditModalInstance = null;
    let creditTotalHT = 0;

    function openCreditModal(subtotalHT) {
        const clientId = document.getElementById('clientSelect').value;
        const alertEl  = document.getElementById('creditClientAlert');
        const formEl   = document.getElementById('creditForm');
        const confirmBtn = document.getElementById('btnCreditConfirmer');

        if (!clientId) {
            alertEl.classList.remove('d-none');
            formEl.classList.add('d-none');
            confirmBtn.disabled = true;
        } else {
            alertEl.classList.add('d-none');
            formEl.classList.remove('d-none');
            confirmBtn.disabled = false;
        }

        const totalTTC = avecTVA ? subtotalHT * (1 + TVA_RATE) : subtotalHT;
        creditTotalHT  = totalTTC;

        document.getElementById('creditTotalDisplay').textContent =
            totalTTC.toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' <?php echo $devise; ?>';
        document.getElementById('creditAcompte').max   = totalTTC;
        document.getElementById('creditAcompte').value = 0;
        document.getElementById('creditEcheance').value = '';
        updateCreditSolde();

        if (!creditModalInstance) {
            creditModalInstance = new bootstrap.Modal(document.getElementById('creditModal'));
        }
        creditModalInstance.show();
    }

    function updateCreditSolde() {
        const acompte = parseFloat(document.getElementById('creditAcompte').value) || 0;
        const solde   = Math.max(0, creditTotalHT - acompte);
        document.getElementById('creditSoldeDisplay').textContent =
            solde.toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' <?php echo $devise; ?>';
    }

    // Listeners crédit — branchés dans le DOMContentLoaded principal (ci-dessous)
    // ───────────────────────────────────────────────────────────────────

    function processSaleWithPayment(modePaiement, creditData = {}) {
        console.log('🚀 Début exécution de la vente avec mode:', modePaiement);

        const formData = new FormData();
        formData.append('id_client', document.getElementById('clientSelect').value || '');
        formData.append('cart', JSON.stringify(cart));
        formData.append('mode_paiement', modePaiement);
        formData.append('avec_tva', avecTVA ? '1' : '0');
        const confectionRadio = document.querySelector('input[name="modeConfection"]:checked');
        formData.append('is_confection', confectionRadio ? confectionRadio.value : '0');
        formData.append('type_confection', document.getElementById('typeConfection')?.value.trim() || '');
        if (modePaiement === 'credit') {
            formData.append('acompte',       creditData.acompte      || 0);
            formData.append('date_echeance', creditData.date_echeance || '');
        }
        console.log('📦 Données à envoyer:', {
            id_client: document.getElementById('clientSelect').value,
            cart: cart,
            mode_paiement: modePaiement,
            url: 'ajax/process_vente.php'
        });
        
        fetch('ajax/process_vente.php', {
            method: 'POST',
            body: formData
        })
        .then(r => {
            console.log('📡 Réponse HTTP reçue:', r.status, r.statusText);
            if (!r.ok) {
                throw new Error(`HTTP ${r.status}: ${r.statusText}`);
            }
            return r.text();
        })
        .then(text => {
            console.log('📄 Texte brut reçu:', text);
            try {
                const data = JSON.parse(text);
                console.log('✅ JSON parsé:', data);
                
                if (data.success) {
                    console.log('✅ Vente réussie!');
                    cart = [];
                    updateCart();
                    
                    // Afficher modal de succès
                    if (typeof showAlertModal === 'function') {
                        showAlertModal({
                            title: 'Succès',
                            message: data.message || 'Vente enregistrée avec succès',
                            type: 'success'
                        });
                    } else {
                        alert(data.message || 'Vente enregistrée avec succès');
                    }
                    
                    // Ouvrir la facture
                    if (data.id_vente) {
                        console.log('📄 Ouverture facture ID:', data.id_vente);
                        window.open('facture_impression.php?id=' + data.id_vente, '_blank');
                    }
                    
                    // Recharger la page après 2 secondes pour mettre à jour les stocks
                    setTimeout(() => {
                        console.log('🔄 Rechargement de la page...');
                        location.reload();
                    }, 2000);
                } else {
                    console.error('❌ Erreur métier:', data.message);
                    if (typeof showAlertModal === 'function') {
                        showAlertModal({
                            title: 'Erreur',
                            message: data.message || 'Une erreur est survenue',
                            type: 'error'
                        });
                    } else {
                        alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
                    }
                }
            } catch (e) {
                console.error('❌ Erreur parsing JSON:', e);
                console.log('Texte reçu:', text);
                alert('Erreur: Réponse serveur invalide');
            }
        })
        .catch(e => {
            console.error('❌ Erreur réseau ou fetch:', e);
            alert('Erreur de connexion: ' + e.message);
        });
    }
    
    function generateProforma() {
        if (cart.length === 0) {
            if (typeof showAlertModal === 'function') {
                showAlertModal({
                    title: 'Panier vide',
                    message: 'Ajoutez des produits au panier avant de générer un proforma',
                    type: 'warning'
                });
            } else {
                alert('Ajoutez des produits au panier avant de générer un proforma');
            }
            return;
        }
        
        // Créer un formulaire et le soumettre vers proforma.php
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'proforma.php';
        form.target = '_blank';
        
        // Ajouter les items du panier
        cart.forEach((item, index) => {
            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = `cart_items[${index}][id]`;
            inputId.value = item.id;
            form.appendChild(inputId);
            
            const inputNom = document.createElement('input');
            inputNom.type = 'hidden';
            inputNom.name = `cart_items[${index}][nom]`;
            inputNom.value = item.nom;
            form.appendChild(inputNom);
            
            const inputPrix = document.createElement('input');
            inputPrix.type = 'hidden';
            inputPrix.name = `cart_items[${index}][prix]`;
            inputPrix.value = item.prix;
            form.appendChild(inputPrix);
            
            const inputQte = document.createElement('input');
            inputQte.type = 'hidden';
            inputQte.name = `cart_items[${index}][quantite]`;
            inputQte.value = item.quantite;
            form.appendChild(inputQte);
        });
        
        // Calculer et ajouter les totaux (prix HT par défaut)
        const subtotal = cart.reduce((sum, item) => sum + (item.prix * item.quantite), 0);
        const tva = avecTVA ? subtotal * TVA_RATE : 0;
        const total = subtotal + tva;
        
        const inputSubtotal = document.createElement('input');
        inputSubtotal.type = 'hidden';
        inputSubtotal.name = 'subtotal';
        inputSubtotal.value = subtotal;
        form.appendChild(inputSubtotal);
        
        const inputTVA = document.createElement('input');
        inputTVA.type = 'hidden';
        inputTVA.name = 'tva';
        inputTVA.value = tva;
        form.appendChild(inputTVA);
        
        const inputTotal = document.createElement('input');
        inputTotal.type = 'hidden';
        inputTotal.name = 'total';
        inputTotal.value = total;
        form.appendChild(inputTotal);

        const inputAvecTVA = document.createElement('input');
        inputAvecTVA.type = 'hidden';
        inputAvecTVA.name = 'avec_tva';
        inputAvecTVA.value = avecTVA ? '1' : '0';
        form.appendChild(inputAvecTVA);

        // Ajouter le client
        const clientSelect = document.getElementById('clientSelect');
        const inputClientId = document.createElement('input');
        inputClientId.type = 'hidden';
        inputClientId.name = 'id_client';
        inputClientId.value = clientSelect.value;
        form.appendChild(inputClientId);
        
        const inputClientName = document.createElement('input');
        inputClientName.type = 'hidden';
        inputClientName.name = 'client_name';
        inputClientName.value = clientSelect.options[clientSelect.selectedIndex].text;
        form.appendChild(inputClientName);
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
    
    console.log('✅ Script vente.php complètement chargé et prêt');

    // ── Nouveau client en cours de vente ─────────────────────────────────────
    document.getElementById('btnNouveauClient').addEventListener('click', function() {
        document.getElementById('ncNom').value = '';
        document.getElementById('ncTel').value = '';
        const m = new bootstrap.Modal(document.getElementById('modalNouveauClient'));
        m.show();
    });

    document.getElementById('btnSaveNouveauClient').addEventListener('click', function() {
        const nom = document.getElementById('ncNom').value.trim();
        if (!nom) { alert('Le nom est obligatoire'); return; }
        const btn = this;
        btn.disabled = true;
        fetch('ajax/clients.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'add_client', nom_client: nom, telephone: document.getElementById('ncTel').value.trim()})
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                const id = data.id_client;
                const label = nom + (document.getElementById('ncTel').value.trim() ? ' - ' + document.getElementById('ncTel').value.trim() : '');
                const opt = new Option(label, id, true, true);
                document.getElementById('clientSelect').appendChild(opt);
                bootstrap.Modal.getInstance(document.getElementById('modalNouveauClient')).hide();
            } else {
                alert('Erreur : ' + data.message);
            }
        })
        .catch(() => { btn.disabled = false; alert('Erreur de connexion'); });
    });
});
</script>

<?php include 'footer.php'; ?>
