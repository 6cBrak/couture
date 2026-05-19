<?php
/**
 * AFFICHAGE RAPPORT - STORE SUITE
 * Page d'affichage HTML des rapports avec bouton impression
 */
require_once 'protection_pages.php';

// Récupérer le type de rapport
$type = $_GET['type'] ?? 'ventes';
$date_debut = $_GET['date_debut'] ?? date('Y-m-d');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$periode = $_GET['periode'] ?? 'custom';

// Titre et données selon le type
$titre = '';
$data = [];

switch ($type) {
    case 'ventes':
        $titre = 'Rapport des Ventes';
        $data = db_fetch_all("
            SELECT 
                v.id_vente,
                v.date_vente,
                c.nom_client,
                v.montant_ht,
                v.montant_tva,
                v.montant_total,
                v.statut,
                COUNT(dv.id_detail) as nombre_articles
            FROM ventes v
            LEFT JOIN clients c ON v.id_client = c.id_client
            LEFT JOIN details_vente dv ON v.id_vente = dv.id_vente
            WHERE DATE(v.date_vente) BETWEEN ? AND ?
            GROUP BY v.id_vente
            ORDER BY v.date_vente DESC
        ", [$date_debut, $date_fin]);
        break;
        
    case 'produits':
        $titre = 'Rapport Inventaire Produits';
        $data = db_fetch_all("
            SELECT 
                p.nom_produit,
                cat.nom_categorie,
                p.prix_vente,
                p.quantite_stock as stock_actuel,
                COALESCE(SUM(dv.quantite), 0) as quantite_vendue,
                COALESCE(SUM(dv.prix_total), 0) as montant_total
            FROM produits p
            LEFT JOIN categories cat ON p.id_categorie = cat.id_categorie
            LEFT JOIN details_vente dv ON p.id_produit = dv.id_produit
            LEFT JOIN ventes v ON dv.id_vente = v.id_vente 
                AND DATE(v.date_vente) BETWEEN ? AND ?
                AND v.statut = 'validee'
            GROUP BY p.id_produit
            ORDER BY quantite_vendue DESC
        ", [$date_debut, $date_fin]);
        break;
        
    case 'benefices':
        // Vérification admin
        if (!$is_admin) {
            die('<html><body><div style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px;"><h3 style="color: #721c24;">Accès refusé</h3><p style="color: #721c24;">Seuls les administrateurs peuvent consulter ce rapport. Les données de profitabilité et de coûts d\'achat sont confidentielles.</p></div></body></html>');
        }
        
        $titre = 'Rapport Performance Financière';
        $data = db_fetch_one("
            SELECT 
                COUNT(*) as total_ventes,
                SUM(montant_total) as chiffre_affaires,
                SUM(montant_ht) as montant_ht,
                SUM(montant_tva) as montant_tva,
                AVG(montant_total) as panier_moyen
            FROM ventes
            WHERE DATE(date_vente) BETWEEN ? AND ?
            AND statut = 'validee'
        ", [$date_debut, $date_fin]);
        break;
        
    case 'categories':
        $titre = 'Rapport Performance Catégories';
        $data = db_fetch_all("
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
            AND v.statut = 'validee'
            GROUP BY cat.id_categorie
            ORDER BY montant_total DESC
        ", [$date_debut, $date_fin]);
        break;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titre; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .container { max-width: 100% !important; }
        }
        
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .report-container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .report-header {
            background: #f8f9fa;
            padding: 2rem 1rem;
            border-bottom: 2px solid #dee2e6;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .report-header img {
            max-height: 100px;
            margin-bottom: 1rem;
        }
        
        .report-header h1 {
            margin: 0.5rem 0;
            color: #333;
            font-size: 1.8rem;
        }
        
        .report-header p {
            margin: 0.5rem 0 0 0;
            color: #666;
        }
        
        .logo-header {
            max-width: 120px;
            max-height: 60px;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <!-- Header avec Logo -->
        <div class="report-header">
            <div class="text-center mb-3">
                <?php if (!empty($logo_boutique)): ?>
                    <img src="<?php echo e($logo_boutique); ?>" alt="Logo" class="logo-header mb-2">
                <?php endif; ?>
                <h1 class="h2 mb-0"><?php echo e($nom_boutique); ?></h1>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-8">
                    <h2 class="h4"><?php echo $titre; ?></h2>
                </div>
                <div class="col-md-4 text-end">
                    <div><strong>Période:</strong><br>
                        <small><?php echo date('d/m/Y', strtotime($date_debut)); ?> - <?php echo date('d/m/Y', strtotime($date_fin)); ?></small>
                    </div>
                    <div class="mt-2"><small>Généré le <?php echo date('d/m/Y à H:i'); ?></small></div>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="text-center mb-4 no-print">
            <button onclick="window.print()" class="btn btn-primary btn-lg me-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Imprimer
            </button>
            <a href="rapports.php?periode=<?php echo $periode; ?>&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" class="btn btn-outline-secondary btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Retour
            </a>
        </div>

        <!-- Contenu selon le type -->
        <?php if ($type === 'ventes'): ?>
            <h3 class="mb-3">Liste des ventes</h3>
            <?php if (empty($data)): ?>
                <div class="alert alert-info">Aucune vente enregistrée pour cette période</div>
            <?php else: ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th class="text-center">Articles</th>
                            <th class="text-end">Montant HT</th>
                            <th class="text-end">TVA</th>
                            <th class="text-end">Total TTC</th>
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_ht = 0;
                        $total_tva = 0;
                        $total_ttc = 0;
                        foreach ($data as $v): 
                            $total_ht += $v['montant_ht'];
                            $total_tva += $v['montant_tva'];
                            $total_ttc += $v['montant_total'];
                        ?>
                        <tr>
                            <td><strong>#<?php echo $v['id_vente']; ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($v['date_vente'])); ?></td>
                            <td><?php echo e($v['nom_client'] ?? 'Client anonyme'); ?></td>
                            <td class="text-center"><span class="badge bg-info"><?php echo $v['nombre_articles']; ?></span></td>
                            <td class="text-end"><?php echo format_montant($v['montant_ht']); ?></td>
                            <td class="text-end"><?php echo format_montant($v['montant_tva']); ?></td>
                            <td class="text-end"><strong><?php echo format_montant($v['montant_total']); ?></strong></td>
                            <td class="text-center">
                                <?php
                                $badge = match($v['statut']) {
                                    'validee' => 'bg-success',
                                    'brouillon' => 'bg-warning',
                                    'annulee' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $badge; ?>"><?php echo ucfirst($v['statut']); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary fw-bold">
                            <th colspan="4" class="text-end">TOTAUX:</th>
                            <th class="text-end"><?php echo format_montant($total_ht); ?></th>
                            <th class="text-end"><?php echo format_montant($total_tva); ?></th>
                            <th class="text-end"><?php echo format_montant($total_ttc); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>

        <?php elseif ($type === 'produits'): ?>
            <h3 class="mb-3">Inventaire des produits</h3>
            <?php if (empty($data)): ?>
                <div class="alert alert-info">Aucun produit trouvé</div>
            <?php else: ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th class="text-end">Prix</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Qté Vendue</th>
                            <th class="text-end">Montant Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $p): ?>
                        <tr>
                            <td><strong><?php echo e($p['nom_produit']); ?></strong></td>
                            <td><?php echo e($p['nom_categorie']); ?></td>
                            <td class="text-end"><?php echo format_montant($p['prix_vente']); ?></td>
                            <td class="text-center">
                                <span class="badge <?php echo $p['stock_actuel'] < 5 ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo $p['stock_actuel']; ?>
                                </span>
                            </td>
                            <td class="text-center"><span class="badge bg-primary"><?php echo $p['quantite_vendue']; ?></span></td>
                            <td class="text-end"><strong><?php echo format_montant($p['montant_total']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php elseif ($type === 'benefices'): ?>
            <h3 class="mb-4">Analyse financière</h3>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="stat-box">
                        <h5 class="text-muted mb-2">Nombre de ventes</h5>
                        <h2 class="mb-0"><?php echo number_format($data['total_ventes'], 0, ',', ' '); ?></h2>
                        <small class="text-muted">ventes validées</small>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="stat-box">
                        <h5 class="text-muted mb-2">Chiffre d'affaires TTC</h5>
                        <h2 class="mb-0"><?php echo format_montant($data['chiffre_affaires'] ?? 0); ?></h2>
                        <small class="text-muted">total des ventes</small>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="stat-box">
                        <h5 class="text-muted mb-2">Montant HT</h5>
                        <h2 class="mb-0"><?php echo format_montant($data['montant_ht'] ?? 0); ?></h2>
                        <small class="text-muted">hors taxes</small>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="stat-box">
                        <h5 class="text-muted mb-2">TVA collectée (<?php echo (float)$taux_tva; ?>%)</h5>
                        <h2 class="mb-0"><?php echo format_montant($data['montant_tva'] ?? 0); ?></h2>
                        <small class="text-muted">taxes collectées</small>
                    </div>
                </div>
                <div class="col-md-12 mb-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                        <h5 class="mb-2">Panier moyen</h5>
                        <h2 class="mb-0"><?php echo format_montant($data['panier_moyen'] ?? 0); ?></h2>
                        <small>montant moyen par vente</small>
                    </div>
                </div>
            </div>

        <?php elseif ($type === 'categories'): ?>
            <h3 class="mb-3">Performance par catégorie</h3>
            <?php if (empty($data)): ?>
                <div class="alert alert-info">Aucune catégorie trouvée</div>
            <?php else: ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Catégorie</th>
                            <th class="text-center">Nb Ventes</th>
                            <th class="text-center">Nb Produits</th>
                            <th class="text-center">Quantité Vendue</th>
                            <th class="text-end">Montant Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_montant = 0;
                        foreach ($data as $c): 
                            $total_montant += $c['montant_total'];
                        ?>
                        <tr>
                            <td><strong><?php echo e($c['nom_categorie']); ?></strong></td>
                            <td class="text-center"><span class="badge bg-info"><?php echo $c['nombre_ventes']; ?></span></td>
                            <td class="text-center"><span class="badge bg-success"><?php echo $c['nombre_produits']; ?></span></td>
                            <td class="text-center"><span class="badge bg-primary"><?php echo $c['quantite_vendue']; ?></span></td>
                            <td class="text-end"><strong><?php echo format_montant($c['montant_total']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary fw-bold">
                            <th colspan="4" class="text-end">TOTAL:</th>
                            <th class="text-end"><?php echo format_montant($total_montant); ?></th>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center mt-5 pt-4 border-top">
            <p class="text-muted mb-0">
                <small>Rapport généré par <?php echo e($nom_boutique); ?> - <?php echo date('d/m/Y à H:i:s'); ?></small>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
