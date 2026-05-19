<?php
/**
 * PAGE D'IMPRESSION - HISTORIQUE DES MOUVEMENTS DE STOCK
 * Version stylée pour impression/PDF
 */
require_once('protection_pages.php');

// Récupération des paramètres de filtre (mêmes que mouvements_stock.php)
$filtre_type = $_GET['type'] ?? '';
$filtre_produit = $_GET['produit'] ?? '';
$filtre_depot = $_GET['depot'] ?? '';
$filtre_date_debut = $_GET['date_debut'] ?? '';
$filtre_date_fin = $_GET['date_fin'] ?? '';
$filtre_utilisateur = $_GET['utilisateur'] ?? '';

// Construction de la requête
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

if ($filtre_utilisateur) {
    $where_clauses[] = "m.id_utilisateur = ?";
    $params[] = $filtre_utilisateur;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Récupération des mouvements (sans limite pour l'impression)
$mouvements = db_fetch_all("
    SELECT 
        m.*,
        p.nom_produit,
        p.code_produit,
        ds.nom_depot as nom_depot_source,
        dd.nom_depot as nom_depot_destination,
        u.nom_complet as nom_utilisateur
    FROM mouvements_stock m
    INNER JOIN produits p ON m.id_produit = p.id_produit
    LEFT JOIN depots ds ON m.id_depot_source = ds.id_depot
    LEFT JOIN depots dd ON m.id_depot_destination = dd.id_depot
    INNER JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur
    $where_sql
    ORDER BY m.date_mouvement DESC, m.id_mouvement DESC
    LIMIT 500
", $params);

$total_mouvements = count($mouvements);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Mouvements de Stock - <?php echo e($nom_boutique); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* En-tête du document */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid <?php echo e($couleur_primaire); ?>;
        }
        
        .header .logo {
            max-width: 120px;
            max-height: 80px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            color: <?php echo e($couleur_primaire); ?>;
            font-size: 24pt;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .header .subtitle {
            font-size: 14pt;
            color: #666;
            margin-bottom: 10px;
        }
        
        /* Informations du rapport */
        .report-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid <?php echo e($couleur_secondaire); ?>;
        }
        
        .report-info .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .report-info .info-item {
            display: flex;
            gap: 8px;
        }
        
        .report-info .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .report-info .info-value {
            color: #333;
        }
        
        /* Tableau */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        thead {
            background: linear-gradient(135deg, <?php echo e($couleur_primaire); ?>, <?php echo e($couleur_secondaire); ?>);
            color: white;
        }
        
        th {
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            border-bottom: 1px solid #e9ecef;
        }
        
        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        tbody tr:hover {
            background: #e7f3ff;
        }
        
        td {
            padding: 10px 8px;
            font-size: 10pt;
        }
        
        /* Badges pour types de mouvements */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-entree { background: #d4edda; color: #155724; }
        .badge-sortie { background: #f8d7da; color: #721c24; }
        .badge-ajustement { background: #fff3cd; color: #856404; }
        .badge-transfert { background: #d1ecf1; color: #0c5460; }
        .badge-inventaire { background: #e2d9f3; color: #5a3c7e; }
        .badge-perte { background: #f5c6cb; color: #721c24; }
        
        /* Quantité avec couleurs */
        .quantite {
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .quantite-positive {
            color: #28a745;
            background: #d4edda;
        }
        
        .quantite-negative {
            color: #dc3545;
            background: #f8d7da;
        }
        
        /* Pied de page */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            color: #666;
            font-size: 9pt;
        }
        
        .summary-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .summary-box .count {
            font-size: 32pt;
            font-weight: 700;
            color: <?php echo e($couleur_primaire); ?>;
        }
        
        .summary-box .label {
            font-size: 12pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Styles d'impression */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .container {
                max-width: 100%;
                padding: 10mm;
            }
            
            .no-print {
                display: none !important;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
        }
        
        /* Bouton d'impression */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: <?php echo e($couleur_primaire); ?>;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 12pt;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .print-button:hover {
            background: <?php echo e($couleur_secondaire); ?>;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" style="vertical-align: middle; margin-right: 5px;" viewBox="0 0 16 16">
            <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
            <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
        </svg>
        Imprimer / PDF
    </button>
    
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <?php if (!empty($logo_boutique) && file_exists('uploads/logos/' . $logo_boutique)): ?>
                <img src="uploads/logos/<?php echo e($logo_boutique); ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <h1><?php echo e($nom_boutique); ?></h1>
            <div class="subtitle">Historique des Mouvements de Stock</div>
        </div>
        
        <!-- Informations du rapport -->
        <div class="report-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Date d'édition:</span>
                    <span class="info-value"><?php echo date('d/m/Y à H:i'); ?></span>
                </div>
                <?php if ($filtre_date_debut && $filtre_date_fin): ?>
                <div class="info-item">
                    <span class="info-label">Période:</span>
                    <span class="info-value">
                        Du <?php echo date('d/m/Y', strtotime($filtre_date_debut)); ?> 
                        au <?php echo date('d/m/Y', strtotime($filtre_date_fin)); ?>
                    </span>
                </div>
                <?php elseif ($filtre_date_debut): ?>
                <div class="info-item">
                    <span class="info-label">À partir du:</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($filtre_date_debut)); ?></span>
                </div>
                <?php elseif ($filtre_date_fin): ?>
                <div class="info-item">
                    <span class="info-label">Jusqu'au:</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($filtre_date_fin)); ?></span>
                </div>
                <?php else: ?>
                <div class="info-item">
                    <span class="info-label">Période:</span>
                    <span class="info-value">Tous les mouvements</span>
                </div>
                <?php endif; ?>
                <?php if ($filtre_type): ?>
                <div class="info-item">
                    <span class="info-label">Type filtré:</span>
                    <span class="info-value"><?php echo ucfirst($filtre_type); ?></span>
                </div>
                <?php else: ?>
                <div class="info-item">
                    <span class="info-label">Type:</span>
                    <span class="info-value">Tous les types</span>
                </div>
                <?php endif; ?>
                <?php if ($filtre_produit): ?>
                <div class="info-item">
                    <span class="info-label">Produit:</span>
                    <span class="info-value"><?php echo e($filtre_produit); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($filtre_depot): ?>
                <div class="info-item">
                    <span class="info-label">Dépôt:</span>
                    <span class="info-value">
                        <?php 
                        $depot_info = db_fetch_one("SELECT nom_depot FROM depots WHERE id_depot = ?", [$filtre_depot]);
                        echo e($depot_info['nom_depot'] ?? 'Dépôt #' . $filtre_depot);
                        ?>
                    </span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Édité par:</span>
                    <span class="info-value"><?php echo e($user_name); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Résumé détaillé par type -->
        <div class="summary-box">
            <?php
            // Calculer les mouvements par type
            $mouvements_par_type = [];
            foreach ($mouvements as $m) {
                if (!isset($mouvements_par_type[$m['type_mouvement']])) {
                    $mouvements_par_type[$m['type_mouvement']] = 0;
                }
                $mouvements_par_type[$m['type_mouvement']]++;
            }
            ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; text-align: center;">
                <div>
                    <div class="count"><?php echo $mouvements_par_type['entree'] ?? 0; ?></div>
                    <div class="label">Entrées</div>
                </div>
                <div>
                    <div class="count"><?php echo $mouvements_par_type['sortie'] ?? 0; ?></div>
                    <div class="label">Sorties</div>
                </div>
                <div>
                    <div class="count"><?php echo $mouvements_par_type['transfert'] ?? 0; ?></div>
                    <div class="label">Transferts</div>
                </div>
                <div>
                    <div class="count"><?php echo $mouvements_par_type['ajustement'] ?? 0; ?></div>
                    <div class="label">Ajustements</div>
                </div>
                <div>
                    <div class="count"><?php echo $mouvements_par_type['inventaire'] ?? 0; ?></div>
                    <div class="label">Inventaires</div>
                </div>
                <div>
                    <div class="count"><?php echo $mouvements_par_type['perte'] ?? 0; ?></div>
                    <div class="label">Pertes</div>
                </div>
            </div>
        </div>
        
        <!-- Tableau des mouvements -->
        <table>
            <thead>
                <tr>
                    <th>Date/Heure</th>
                    <th>Produit</th>
                    <th>Type</th>
                    <th style="text-align: center;">Quantité</th>
                    <th>Dépôt(s)</th>
                    <th>Utilisateur</th>
                    <th>Motif</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mouvements)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                        Aucun mouvement trouvé pour les critères sélectionnés
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($mouvements as $m): ?>
                <tr>
                    <td style="white-space: nowrap;">
                        <?php echo date('d/m/Y', strtotime($m['date_mouvement'])); ?><br>
                        <small style="color: #999;"><?php echo date('H:i', strtotime($m['date_mouvement'])); ?></small>
                    </td>
                    <td>
                        <strong><?php echo e($m['nom_produit']); ?></strong>
                        <?php if (!empty($m['code_produit'])): ?>
                        <br><small style="color: #999;"><?php echo e($m['code_produit']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $labels = [
                            'entree' => 'Entrée',
                            'sortie' => 'Sortie',
                            'ajustement' => 'Ajustement',
                            'transfert' => 'Transfert',
                            'inventaire' => 'Inventaire',
                            'perte' => 'Perte'
                        ];
                        ?>
                        <span class="badge badge-<?php echo $m['type_mouvement']; ?>">
                            <?php echo $labels[$m['type_mouvement']] ?? ucfirst($m['type_mouvement']); ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <span class="quantite <?php echo $m['quantite'] >= 0 ? 'quantite-positive' : 'quantite-negative'; ?>">
                            <?php echo $m['quantite'] > 0 ? '+' . $m['quantite'] : $m['quantite']; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($m['type_mouvement'] === 'transfert'): ?>
                            <small style="color: #666;">De:</small> <?php echo e($m['nom_depot_source']); ?><br>
                            <small style="color: #666;">Vers:</small> <?php echo e($m['nom_depot_destination']); ?>
                        <?php else: ?>
                            <?php echo e($m['nom_depot_source'] ?? 'N/A'); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <small><?php echo e($m['nom_utilisateur']); ?></small>
                    </td>
                    <td>
                        <small><?php echo e($m['motif'] ?? $m['notes'] ?? '-'); ?></small>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pied de page -->
        <div class="footer">
            <p><strong><?php echo e($nom_boutique); ?></strong></p>
            <p>Document généré automatiquement le <?php echo date('d/m/Y à H:i'); ?></p>
            <p style="margin-top: 10px; color: #999;">Intégré par Fix iT Engineering - STORESUITE</p>
        </div>
    </div>
</body>
</html>
