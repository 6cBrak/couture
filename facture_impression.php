<?php
/**
 * PAGE IMPRESSION FACTURE - STORE SUITE
 * Génération et impression de facture de vente
 */
require_once 'protection_pages.php';

$id_vente = $_GET['id'] ?? 0;
$format = $_GET['format'] ?? 'thermal';
if (!in_array($format, ['thermal', 'a4'], true)) {
    $format = 'thermal';
}

// Récupérer les informations de la vente
$vente = db_fetch_one("
    SELECT 
        v.*,
        c.nom_client,
        c.telephone as client_tel,
        c.adresse as client_adresse,
        u.nom_complet as nom_vendeur
    FROM ventes v
    LEFT JOIN clients c ON v.id_client = c.id_client
    INNER JOIN utilisateurs u ON v.id_vendeur = u.id_utilisateur
    WHERE v.id_vente = ?
", [$id_vente]);

if (!$vente) {
    die('Facture introuvable');
}

// Récupérer les détails de la vente
$details = db_fetch_all("
    SELECT 
        vd.*,
        p.nom_produit
    FROM details_vente vd
    INNER JOIN produits p ON vd.id_produit = p.id_produit
    WHERE vd.id_vente = ?
    ORDER BY vd.id_detail
", [$id_vente]);

$page_title = 'Facture ' . $vente['numero_facture'];

// Pour les ventes à crédit, récupérer les paiements
$paiements_credit = [];
$total_paye_credit = 0;
$solde_credit = 0;
if ($vente['mode_paiement'] === 'credit') {
    $paiements_credit = db_fetch_all("
        SELECT p.*, u.nom_complet as utilisateur
        FROM paiements_credit p
        LEFT JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
        WHERE p.id_vente = ?
        ORDER BY p.date_paiement ASC
    ", [$id_vente]);
    $total_paye_credit = array_sum(array_column($paiements_credit, 'montant'));
    $solde_credit = round($vente['montant_total'] - $total_paye_credit, 2);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?php echo $vente['numero_facture']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
        
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .invoice-container {
            max-width: <?php echo $format === 'thermal' ? '360px' : '800px'; ?>;
            margin: 20px auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .invoice-header {
            background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>);
            color: white;
            padding: <?php echo $format === 'thermal' ? '16px' : '30px'; ?>;
        }

        .invoice-body {
            padding: <?php echo $format === 'thermal' ? '16px' : '30px'; ?>;
        }

        .info-block {
            margin-bottom: <?php echo $format === 'thermal' ? '12px' : '20px'; ?>;
        }

        .invoice-table th {
            background: <?php echo $couleur_primaire; ?>20;
            color: <?php echo $couleur_primaire; ?>;
            font-weight: 600;
            padding: <?php echo $format === 'thermal' ? '6px' : '10px'; ?>;
            font-size: <?php echo $format === 'thermal' ? '12px' : '14px'; ?>;
        }

        .invoice-table td {
            padding: <?php echo $format === 'thermal' ? '6px' : '10px'; ?>;
            font-size: <?php echo $format === 'thermal' ? '12px' : '14px'; ?>;
        }

        .total-row {
            font-size: <?php echo $format === 'thermal' ? '14px' : '1.2em'; ?>;
            font-weight: bold;
            background: <?php echo $couleur_primaire; ?>10;
        }

        .footer-text {
            text-align: center;
            color: #666;
            font-size: <?php echo $format === 'thermal' ? '11px' : '0.9em'; ?>;
            padding: <?php echo $format === 'thermal' ? '12px' : '20px'; ?>;
            border-top: 2px solid #eee;
            margin-top: <?php echo $format === 'thermal' ? '16px' : '30px'; ?>;
        }

        .badge-paid {
            background: #28a745;
            color: white;
            padding: <?php echo $format === 'thermal' ? '4px 10px' : '5px 15px'; ?>;
            border-radius: 20px;
            font-size: <?php echo $format === 'thermal' ? '11px' : '0.9em'; ?>;
        }

        .qr-code {
            width: <?php echo $format === 'thermal' ? '70px' : '100px'; ?>;
            height: <?php echo $format === 'thermal' ? '70px' : '100px'; ?>;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
        }

        <?php if ($format === 'thermal'): ?>
        body {
            background: #fff;
        }
        .invoice-container {
            box-shadow: none;
            border: 1px dashed #ddd;
        }
        .invoice-header h1 { font-size: 18px; }
        .invoice-header h2 { font-size: 20px; }
        .invoice-header h4 { font-size: 16px; }
        .invoice-body { padding-bottom: 12px; }
        .invoice-table th:nth-child(1), .invoice-table td:nth-child(1) { width: 30px; }
        .invoice-table th:nth-child(3), .invoice-table td:nth-child(3) { width: 80px; }
        .invoice-table th:nth-child(4), .invoice-table td:nth-child(4) { width: 90px; }
        .invoice-table th:nth-child(5), .invoice-table td:nth-child(5) { width: 90px; }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0"><?php echo htmlspecialchars($config['nom_boutique']); ?></h1>
                    <p class="mb-0"><?php echo htmlspecialchars($config['adresse'] ?? ''); ?></p>
                    <p class="mb-0">
                        <?php if (!empty($config['telephone'])): ?>
                        Tél: <?php echo htmlspecialchars($config['telephone']); ?>
                        <?php endif; ?>
                        <?php if (!empty($config['email'])): ?>
                        | Email: <?php echo htmlspecialchars($config['email']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <h2 class="mb-0">FACTURE</h2>
                    <h4 class="mb-0"><?php echo $vente['numero_facture']; ?></h4>
                    <span class="badge-paid"><?php echo strtoupper($vente['statut']); ?></span>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="invoice-body">
            <!-- Info blocks -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="info-block">
                        <h5 class="text-uppercase text-muted mb-2">Client</h5>
                        <strong><?php echo htmlspecialchars($vente['nom_client'] ?? 'Vente au comptoir'); ?></strong>
                        <?php if (!empty($vente['client_tel'])): ?>
                        <br>Tél: <?php echo htmlspecialchars($vente['client_tel']); ?>
                        <?php endif; ?>
                        <?php if (!empty($vente['client_adresse'])): ?>
                        <br><?php echo htmlspecialchars($vente['client_adresse']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="info-block">
                        <h5 class="text-uppercase text-muted mb-2">Détails</h5>
                        <strong>Date:</strong> <?php echo date('d/m/Y à H:i', strtotime($vente['date_vente'])); ?><br>
                        <strong>Vendeur:</strong> <?php echo htmlspecialchars($vente['nom_vendeur']); ?><br>
                        <strong>Mode:</strong> <?php echo ucfirst($vente['mode_paiement']); ?>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <table class="table table-bordered invoice-table">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Produit</th>
                        <th width="100" class="text-center">Quantité</th>
                        <th width="120" class="text-end">Prix unitaire</th>
                        <th width="120" class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $index => $detail): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($detail['nom_produit']); ?></strong>
                        </td>
                        <td class="text-center"><?php echo $detail['quantite']; ?></td>
                        <td class="text-end"><?php echo number_format($detail['prix_unitaire'], 0, ',', ' '); ?> <?php echo $devise; ?></td>
                        <?php $total_ligne = ($detail['prix_total'] > 0) ? $detail['prix_total'] : ($detail['quantite'] * $detail['prix_unitaire']); ?>
                        <td class="text-end"><?php echo number_format($total_ligne, 0, ',', ' '); ?> <?php echo $devise; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4" class="text-end"><strong>TOTAL À PAYER</strong></td>
                        <td class="text-end"><strong><?php echo number_format($vente['montant_total'], 0, ',', ' '); ?> <?php echo $devise; ?></strong></td>
                    </tr>
                </tfoot>
            </table>

            <?php if (!empty($vente['is_confection']) && !empty($vente['type_confection'])): ?>
            <div class="mt-2" style="border-top: 1px dashed #ccc; padding-top: 8px;">
                <span style="font-size: 0.85rem; color: #666;">
                    <strong>Remarque :</strong> <?php echo htmlspecialchars($vente['type_confection']); ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- Section Crédit -->
            <?php if ($vente['mode_paiement'] === 'credit'): ?>
            <div class="mt-3 p-3" style="background: #fff8f8; border: 1px solid #fca5a5; border-radius: 8px;">
                <h6 style="color: #dc2626; font-weight: 700; margin-bottom: 12px;">
                    Informations de paiement à crédit
                </h6>
                <div class="row mb-2">
                    <div class="col-6"><strong>Montant total :</strong></div>
                    <div class="col-6 text-end"><?php echo number_format($vente['montant_total'], 0, ',', ' '); ?> <?php echo $devise; ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Déjà payé :</strong></div>
                    <div class="col-6 text-end text-success"><?php echo number_format($total_paye_credit, 0, ',', ' '); ?> <?php echo $devise; ?></div>
                </div>
                <div class="row mb-2" style="font-size: 1.05em;">
                    <div class="col-6"><strong>Solde restant :</strong></div>
                    <div class="col-6 text-end fw-bold <?php echo $solde_credit > 0 ? 'text-danger' : 'text-success'; ?>">
                        <?php echo number_format($solde_credit, 0, ',', ' '); ?> <?php echo $devise; ?>
                    </div>
                </div>
                <?php if (!empty($vente['date_echeance'])): ?>
                <div class="row mb-2">
                    <div class="col-6"><strong>Échéance :</strong></div>
                    <div class="col-6 text-end"><?php echo date('d/m/Y', strtotime($vente['date_echeance'])); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($paiements_credit)): ?>
                <hr style="border-color: #fca5a5;">
                <p style="font-size: 0.85em; font-weight: 700; color: #dc2626; margin-bottom: 6px;">Historique des paiements</p>
                <table class="table table-sm" style="font-size: 0.85em; margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Mode</th>
                            <th class="text-end">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paiements_credit as $paiement): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($paiement['date_paiement'])); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $paiement['mode_paiement'])); ?></td>
                            <td class="text-end"><?php echo number_format($paiement['montant'], 0, ',', ' '); ?> <?php echo $devise; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Notes -->
            <?php if (!empty($vente['notes'])): ?>
            <div class="mt-4">
                <strong>Notes:</strong>
                <p><?php echo nl2br(htmlspecialchars($vente['notes'])); ?></p>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="footer-text">
                <p class="mb-1"><strong>Merci pour votre confiance !</strong></p>
                <p class="mb-0">Cette facture a été générée électroniquement par <?php echo htmlspecialchars($config['nom_boutique']); ?></p>
                <p class="mb-0 text-muted small">Imprimé le <?php echo date('d/m/Y à H:i:s'); ?></p>
            </div>
        </div>
    </div>

    <!-- Buttons -->
    <div class="text-center my-4 no-print">
        <div class="mb-2">
            <div class="btn-group" role="group" aria-label="Choix format">
                <a href="facture_impression.php?id=<?php echo $id_vente; ?>&format=thermal" class="btn btn-sm <?php echo $format === 'thermal' ? 'btn-primary' : 'btn-outline-primary'; ?>">Thermique (80mm)</a>
                <a href="facture_impression.php?id=<?php echo $id_vente; ?>&format=a4" class="btn btn-sm <?php echo $format === 'a4' ? 'btn-primary' : 'btn-outline-primary'; ?>">A4</a>
            </div>
        </div>
        <button onclick="window.print()" class="btn btn-primary btn-lg me-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2"/><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4"/><rect x="7" y="13" width="10" height="8" rx="2"/></svg>
            Imprimer
        </button>
        <a href="listes.php?page=ventes" class="btn btn-outline-secondary btn-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="5" y1="12" x2="19" y2="12"/><line x1="5" y1="12" x2="9" y2="16"/><line x1="5" y1="12" x2="9" y2="8"/></svg>
            Retour aux ventes
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
