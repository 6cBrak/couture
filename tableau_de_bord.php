<?php
/**
 * TABLEAU DE BORD ADMINISTRATEUR - STORE SUITE
 * Statistiques avancées et analyses (admin uniquement)
 */
require_once 'protection_pages.php';

// Vérification admin
if (!$is_admin) {
    header('Location: accueil.php');
    exit;
}

$page_title = 'Tableau de Bord Administrateur';

// Statistiques globales
$stats_globales = db_fetch_one("
    SELECT 
        (SELECT COUNT(*) FROM produits WHERE est_actif = 1) as total_produits,
        (SELECT COUNT(*) FROM clients WHERE est_actif = 1) as total_clients,
        (SELECT COUNT(*) FROM utilisateurs WHERE est_actif = 1) as total_utilisateurs,
        (SELECT COUNT(*) FROM ventes WHERE statut = 'validee') as total_ventes
");

// Ventes du jour
$ventes_jour = db_fetch_one("
    SELECT 
        COUNT(*) as nombre,
        COALESCE(SUM(montant_total), 0) as montant
    FROM ventes
    WHERE DATE(date_vente) = CURDATE()
    AND statut = 'validee'
");

// Ventes du mois
$ventes_mois = db_fetch_one("
    SELECT 
        COUNT(*) as nombre,
        COALESCE(SUM(montant_total), 0) as montant
    FROM ventes
    WHERE MONTH(date_vente) = MONTH(CURDATE())
    AND YEAR(date_vente) = YEAR(CURDATE())
    AND statut = 'validee'
");

// Top vendeurs du mois
$top_vendeurs = db_fetch_all("
    SELECT 
        u.nom_complet,
        COUNT(v.id_vente) as nombre_ventes,
        SUM(v.montant_total) as montant_total
    FROM ventes v
    INNER JOIN utilisateurs u ON v.id_vendeur = u.id_utilisateur
    WHERE MONTH(v.date_vente) = MONTH(CURDATE())
    AND YEAR(v.date_vente) = YEAR(CURDATE())
    AND v.statut = 'validee'
    GROUP BY u.id_utilisateur
    ORDER BY montant_total DESC
    LIMIT 5
");

// Évolution des 30 derniers jours
$evolution = db_fetch_all("
    SELECT 
        DATE(date_vente) as jour,
        COUNT(*) as nombre_ventes,
        SUM(montant_total) as montant
    FROM ventes
    WHERE date_vente >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    AND statut = 'validee'
    GROUP BY DATE(date_vente)
    ORDER BY jour ASC
");

// Produits les plus rentables
$produits_rentables = db_fetch_all("
    SELECT 
        p.nom_produit,
        SUM(vd.quantite) as quantite_vendue,
        SUM(vd.benefice_ligne) as benefice_total
    FROM details_vente vd
    INNER JOIN produits p ON vd.id_produit = p.id_produit
    INNER JOIN ventes v ON vd.id_vente = v.id_vente
    WHERE v.statut = 'validee'
    AND MONTH(v.date_vente) = MONTH(CURDATE())
    GROUP BY p.id_produit
    ORDER BY benefice_total DESC
    LIMIT 10
");

include 'header.php';
?>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <rect x="4" y="4" width="6" height="6" rx="1"/>
                        <rect x="14" y="4" width="6" height="6" rx="1"/>
                        <rect x="4" y="14" width="6" height="6" rx="1"/>
                        <rect x="14" y="14" width="6" height="6" rx="1"/>
                    </svg>
                    Tableau de Bord Administrateur
                </h2>
                <div class="text-muted mt-1">Vue d'ensemble des performances</div>
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
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg me-3" style="background: linear-gradient(135deg, #206bc4, #4299e1); color: white;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/>
                                <path d="M12 12l8 -4.5"/>
                                <path d="M12 12l0 9"/>
                                <path d="M12 12l-8 -4.5"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $stats_globales['total_produits']; ?></h3>
                            <div class="text-muted">Produits</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg bg-success-lt me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $stats_globales['total_clients']; ?></h3>
                            <div class="text-muted">Clients</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg bg-warning-lt me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                <line x1="19" y1="7" x2="19" y2="10"/>
                                <line x1="19" y1="14" x2="19" y2="14.01"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $stats_globales['total_utilisateurs']; ?></h3>
                            <div class="text-muted">Utilisateurs</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg bg-info-lt me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="6" cy="19" r="2"/>
                                <circle cx="17" cy="19" r="2"/>
                                <path d="M17 17h-11v-14h-2"/>
                                <path d="M6 5l14 1l-1 7h-13"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $stats_globales['total_ventes']; ?></h3>
                            <div class="text-muted">Ventes totales</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); color: white;">
                <div class="card-body">
                    <h3 class="card-title">Ventes aujourd'hui</h3>
                    <div class="row">
                        <div class="col-6">
                            <div class="fs-1 fw-bold"><?php echo $ventes_jour['nombre']; ?></div>
                            <div class="opacity-75">Transactions</div>
                        </div>
                        <div class="col-6 text-end">
                            <div class="fs-1 fw-bold"><?php echo format_montant($ventes_jour['montant']); ?></div>
                            <div class="opacity-75">Chiffre d'affaires</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3 class="card-title">Ventes ce mois</h3>
                    <div class="row">
                        <div class="col-6">
                            <div class="fs-1 fw-bold"><?php echo $ventes_mois['nombre']; ?></div>
                            <div class="opacity-75">Transactions</div>
                        </div>
                        <div class="col-6 text-end">
                            <div class="fs-1 fw-bold"><?php echo format_montant($ventes_mois['montant']); ?></div>
                            <div class="opacity-75">Chiffre d'affaires</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Évolution des ventes (30 derniers jours)</h3>
                </div>
                <div class="card-body">
                    <canvas id="evolutionChart" height="300"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Top produits rentables (ce mois)</h3>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="text-end">Quantité vendue</th>
                                <th class="text-end">Bénéfice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produits_rentables as $pr): ?>
                            <tr>
                                <td><?php echo e($pr['nom_produit']); ?></td>
                                <td class="text-end"><?php echo $pr['quantite_vendue']; ?> unités</td>
                                <td class="text-end"><strong><?php echo format_montant($pr['benefice_total']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Top vendeurs (ce mois)</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($top_vendeurs)): ?>
                        <div class="list-group-item text-center text-muted py-4">
                            Aucune vente ce mois
                        </div>
                        <?php else: ?>
                        <?php foreach ($top_vendeurs as $index => $vendeur): ?>
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-sm me-2" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); color: white;">
                                    <?php echo $index + 1; ?>
                                </span>
                                <div class="flex-fill">
                                    <strong><?php echo e($vendeur['nom_complet']); ?></strong>
                                    <div class="text-muted small"><?php echo $vendeur['nombre_ventes']; ?> ventes</div>
                                </div>
                                <div class="text-end">
                                    <strong><?php echo format_montant($vendeur['montant_total']); ?></strong>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const evolutionData = <?php echo json_encode($evolution); ?>;
const ctx = document.getElementById('evolutionChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: evolutionData.map(d => new Date(d.jour).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })),
        datasets: [{
            label: 'Chiffre d\'affaires',
            data: evolutionData.map(d => d.montant),
            backgroundColor: '<?php echo $couleur_primaire; ?>',
            borderRadius: 8
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
</script>

<?php include 'footer.php'; ?>
