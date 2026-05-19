<?php
/**
 * IMPRESSION REÇU DE PAIEMENT TAILLEUR
 */
require_once 'protection_pages.php';
if (!$atelier_mode) { echo 'Module Atelier non activé.'; exit; }
if (!$is_admin)     { echo 'Accès réservé aux administrateurs.'; exit; }

$id_paiement = intval($_GET['id'] ?? 0);
if (!$id_paiement) { echo 'ID paiement manquant.'; exit; }

$paiement = db_fetch_one(
    "SELECT p.*, t.nom AS nom_tailleur, t.telephone, t.specialite,
            u.nom_complet AS utilisateur
     FROM paiements_tailleur p
     JOIN tailleurs       t ON p.id_tailleur    = t.id_tailleur
     JOIN utilisateurs    u ON p.id_utilisateur = u.id_utilisateur
     WHERE p.id_paiement = ?",
    [$id_paiement]
);
if (!$paiement) { echo 'Paiement introuvable.'; exit; }

$commandes = db_fetch_all(
    "SELECT ca.reference, ca.acompte_tailleur, ca.date_commande,
            v.numero_facture, v.type_confection,
            cl.nom_client
     FROM commandes_atelier ca
     LEFT JOIN ventes  v  ON ca.id_vente  = v.id_vente
     LEFT JOIN clients cl ON ca.id_client = cl.id_client
     WHERE ca.id_paiement_tailleur = ?
     ORDER BY ca.date_commande ASC",
    [$id_paiement]
);

function e2($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reçu paiement tailleur — <?php echo e2($paiement['nom_tailleur']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #1a1a1a; background: white; }
        .container { max-width: 700px; margin: 0 auto; padding: 30px 20px; }

        .header { text-align: center; margin-bottom: 24px; }
        .header h1 { font-size: 22px; font-weight: 700; color: #1e293b; }
        .header .subtitle { font-size: 13px; color: #64748b; margin-top: 4px; }

        .boutique { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e293b; padding-bottom: 16px; }
        .boutique .nom { font-size: 18px; font-weight: 700; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .info-box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
        .info-box h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 8px; }
        .info-box p { font-size: 13px; line-height: 1.6; }
        .info-box strong { font-size: 14px; }

        .recap { background: #f0fdf4; border: 2px solid #16a34a; border-radius: 8px; padding: 14px 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .recap .label { font-size: 14px; font-weight: 600; color: #166534; }
        .recap .montant { font-size: 22px; font-weight: 800; color: #16a34a; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #1e293b; color: white; padding: 8px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        tfoot td { padding: 10px; font-weight: 700; background: #f0fdf4; font-size: 13px; }

        .signature-zone { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; }
        .signature-box { border-top: 1px solid #94a3b8; padding-top: 8px; }
        .signature-box p { font-size: 11px; color: #64748b; margin-bottom: 60px; }

        .footer-note { text-align: center; font-size: 11px; color: #94a3b8; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 12px; }

        @media print {
            body { font-size: 12px; }
            .no-print { display: none !important; }
            .container { padding: 10px; }
        }
    </style>
</head>
<body>
<div class="container">

    <div class="no-print" style="text-align:right; margin-bottom: 16px;">
        <button onclick="window.print()" style="background:#1e293b; color:white; border:none; padding:8px 20px; border-radius:6px; cursor:pointer; font-size:13px;">
            🖨 Imprimer
        </button>
        <button onclick="window.close()" style="background:#6b7280; color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:13px; margin-left:8px;">
            Fermer
        </button>
    </div>

    <div class="boutique">
        <div class="nom"><?php echo e2($nom_boutique); ?></div>
        <div style="font-size:12px; color:#64748b;">Module Atelier Couture</div>
    </div>

    <div class="header">
        <h1>REÇU DE PAIEMENT — TAILLEUR</h1>
        <div class="subtitle">N° PAY-<?php echo str_pad($id_paiement, 6, '0', STR_PAD_LEFT); ?> &nbsp;|&nbsp; Émis le <?php echo date('d/m/Y à H:i', strtotime($paiement['date_paiement'])); ?></div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h3>Tailleur</h3>
            <p>
                <strong><?php echo e2($paiement['nom_tailleur']); ?></strong><br>
                <?php if ($paiement['telephone']): ?>Tél : <?php echo e2($paiement['telephone']); ?><br><?php endif; ?>
                <?php if ($paiement['specialite']): ?><?php echo e2($paiement['specialite']); ?><?php endif; ?>
            </p>
        </div>
        <div class="info-box">
            <h3>Période couverte</h3>
            <p>
                Du <strong><?php echo date('d/m/Y', strtotime($paiement['periode_debut'])); ?></strong><br>
                Au <strong><?php echo date('d/m/Y', strtotime($paiement['periode_fin'])); ?></strong><br>
                <span style="color:#64748b;"><?php echo $paiement['nb_commandes']; ?> commande<?php echo $paiement['nb_commandes'] > 1 ? 's' : ''; ?></span>
            </p>
        </div>
    </div>

    <div class="recap">
        <div class="label">Total commission payée</div>
        <div class="montant"><?php echo number_format($paiement['montant'], 2, ',', ' ') . ' ' . $devise; ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Référence</th>
                <th>Facture</th>
                <th>Type confection</th>
                <th>Client</th>
                <th>Date</th>
                <th class="text-right">Commission</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($commandes as $c): ?>
            <tr>
                <td><?php echo e2($c['reference']); ?></td>
                <td><?php echo $c['numero_facture'] ? e2($c['numero_facture']) : '—'; ?></td>
                <td><?php echo $c['type_confection'] ? e2($c['type_confection']) : '—'; ?></td>
                <td><?php echo $c['nom_client'] ? e2($c['nom_client']) : 'Comptoir'; ?></td>
                <td><?php echo date('d/m/Y', strtotime($c['date_commande'])); ?></td>
                <td class="text-right" style="color:#16a34a; font-weight:600;"><?php echo number_format($c['acompte_tailleur'], 2, ',', ' ') . ' ' . $devise; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;">TOTAL PAYÉ :</td>
                <td class="text-right" style="color:#16a34a;"><?php echo number_format($paiement['montant'], 2, ',', ' ') . ' ' . $devise; ?></td>
            </tr>
        </tfoot>
    </table>

    <?php if ($paiement['notes']): ?>
    <div style="background:#fffbeb; border:1px solid #fbbf24; border-radius:6px; padding:10px 14px; margin-bottom:20px; font-size:12px;">
        <strong>Note :</strong> <?php echo e2($paiement['notes']); ?>
    </div>
    <?php endif; ?>

    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; padding:10px 14px; margin-bottom:20px; font-size:12px; color:#1e40af;">
        <strong>Enregistré par :</strong> <?php echo e2($paiement['utilisateur']); ?> — <?php echo date('d/m/Y à H:i', strtotime($paiement['date_paiement'])); ?>
        <?php if ($paiement['id_depense']): ?>
        &nbsp;|&nbsp; <strong>Déduit de la caisse</strong> (Dép. #<?php echo $paiement['id_depense']; ?>)
        <?php endif; ?>
    </div>

    <div class="signature-zone">
        <div class="signature-box">
            <p>Signature du tailleur (bon pour accord)</p>
        </div>
        <div class="signature-box">
            <p>Cachet et signature du responsable</p>
        </div>
    </div>

    <div class="footer-note">
        Document généré par StoreSuite — Module Atelier Couture
    </div>

</div>
</body>
</html>
