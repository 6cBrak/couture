<?php
/**
 * PAGE NOTIFICATIONS - STORE SUITE
 * Gestion des alertes de stock
 */
require_once 'protection_pages.php';
$page_title = 'Alertes de Stock';

// Récupération des produits en alerte
$alertes = db_fetch_all("
    SELECT 
        p.*,
        c.nom_categorie,
        CASE 
            WHEN p.quantite_stock = 0 THEN 'rupture'
            WHEN p.quantite_stock <= (p.seuil_alerte * 0.5) THEN 'critique'
            ELSE 'faible'
        END as niveau_alerte
    FROM produits p
    LEFT JOIN categories c ON p.id_categorie = c.id_categorie
    WHERE p.quantite_stock <= p.seuil_alerte
    AND p.est_actif = 1
    ORDER BY 
        CASE 
            WHEN p.quantite_stock = 0 THEN 1
            WHEN p.quantite_stock <= (p.seuil_alerte * 0.5) THEN 2
            ELSE 3
        END,
        p.quantite_stock ASC
");

$count_rupture = count(array_filter($alertes, fn($a) => $a['niveau_alerte'] === 'rupture'));
$count_critique = count(array_filter($alertes, fn($a) => $a['niveau_alerte'] === 'critique'));
$count_faible = count(array_filter($alertes, fn($a) => $a['niveau_alerte'] === 'faible'));

include 'header.php';
?>

<style>
.alert-card {
    transition: all 0.3s ease;
    border-left: 4px solid;
}
.alert-card.rupture {
    border-left-color: #d63939;
    background: linear-gradient(135deg, #fff5f5, #ffffff);
}
.alert-card.critique {
    border-left-color: #f76707;
    background: linear-gradient(135deg, #fff8f0, #ffffff);
}
.alert-card.faible {
    border-left-color: #f59f00;
    background: linear-gradient(135deg, #fffbf0, #ffffff);
}
.alert-card:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 9v2m0 4v.01"/>
                        <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                    </svg>
                    Alertes de Stock
                </h2>
                <div class="text-muted mt-1"><?php echo count($alertes); ?> produit(s) nécessitant votre attention</div>
            </div>
            <div class="col-auto">
                <a href="accueil.php" class="btn btn-outline-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
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

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg bg-danger-lt me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M3 21l18 0"/>
                                <path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4"/>
                                <line x1="5" y1="21" x2="5" y2="10.85"/>
                                <line x1="19" y1="21" x2="19" y2="10.85"/>
                                <path d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $count_rupture; ?></h3>
                            <div class="text-muted">En rupture</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg bg-warning-lt me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 9v2m0 4v.01"/>
                                <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $count_critique; ?></h3>
                            <div class="text-muted">Critique</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg bg-yellow-lt me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                                <polyline points="11 12 12 12 12 16 13 16"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $count_faible; ?></h3>
                            <div class="text-muted">Stock faible</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($alertes)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="avatar avatar-xl bg-success-lt mb-3 mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M5 12l5 5l10 -10"/>
                </svg>
            </div>
            <h3 class="mb-2">Aucune alerte</h3>
            <p class="text-muted">Tous vos produits ont un stock suffisant</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Liste des alertes</h3>
        </div>
        <div class="list-group list-group-flush">
            <?php foreach ($alertes as $produit): ?>
            <div class="list-group-item alert-card <?php echo $produit['niveau_alerte']; ?>">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="d-flex align-items-center">
                            <span class="badge 
                                <?php 
                                    echo $produit['niveau_alerte'] === 'rupture' ? 'bg-danger' : 
                                        ($produit['niveau_alerte'] === 'critique' ? 'bg-warning' : 'bg-yellow');
                                ?> me-2">
                                <?php 
                                    echo $produit['niveau_alerte'] === 'rupture' ? 'RUPTURE' : 
                                        ($produit['niveau_alerte'] === 'critique' ? 'CRITIQUE' : 'FAIBLE');
                                ?>
                            </span>
                            <div>
                                <strong><?php echo e($produit['nom_produit']); ?></strong>
                                <div class="text-muted small"><?php echo e($produit['nom_categorie'] ?? 'Sans catégorie'); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto text-center">
                        <div class="text-muted small">Stock actuel</div>
                        <strong class="<?php echo $produit['quantite_stock'] === 0 ? 'text-danger' : 'text-warning'; ?>">
                            <?php echo $produit['quantite_stock']; ?>
                        </strong>
                    </div>
                    <div class="col-auto text-center">
                        <div class="text-muted small">Seuil alerte</div>
                        <strong><?php echo $produit['seuil_alerte']; ?></strong>
                    </div>
                    <div class="col-auto">
                        <a href="listes.php?page=produits&id=<?php echo $produit['id_produit']; ?>" class="btn btn-sm btn-outline-primary">
                            Réapprovisionner
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
