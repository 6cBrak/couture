<?php
require_once __DIR__ . '/SmtpMailer.php';

function envoyer_bilan(PDO $pdo, array $config): array {
    $devise       = $config['devise'] ?? 'XOF';
    $nom_boutique = $config['nom_boutique'] ?? 'Ma Boutique';
    $aujourd_hui  = date('d/m/Y');
    $heure        = date('H:i');

    $fn = fn(float $v) => number_format($v, 0, ',', ' ') . ' ' . $devise;

    $row = $pdo->query("
        SELECT COUNT(*) AS nb, COALESCE(SUM(montant_total),0) AS ca
        FROM ventes WHERE DATE(date_vente)=CURDATE() AND statut='validee'
    ")->fetch(PDO::FETCH_ASSOC);
    $nb_ventes = (int)$row['nb'];
    $ca_total  = (float)$row['ca'];

    $benefice = (float)$pdo->query("
        SELECT COALESCE(SUM(d.benefice_ligne),0)
        FROM details_vente d
        INNER JOIN ventes v ON d.id_vente=v.id_vente
        WHERE DATE(v.date_vente)=CURDATE() AND v.statut='validee'
    ")->fetchColumn();

    $depenses = (float)$pdo->query("
        SELECT COALESCE(SUM(montant),0) FROM depenses WHERE DATE(date_depense)=CURDATE()
    ")->fetchColumn();

    $cr = $pdo->query("
        SELECT COUNT(*) AS nb, COALESCE(SUM(montant_total-montant_paye),0) AS reste
        FROM ventes WHERE DATE(date_vente)=CURDATE() AND mode_paiement='credit' AND statut='validee'
    ")->fetch(PDO::FETCH_ASSOC);

    $tops = $pdo->query("
        SELECT d.nom_produit, SUM(d.quantite) AS qte, SUM(d.prix_total) AS ca_p
        FROM details_vente d
        INNER JOIN ventes v ON d.id_vente=v.id_vente
        WHERE DATE(v.date_vente)=CURDATE() AND v.statut='validee'
        GROUP BY d.nom_produit ORDER BY qte DESC LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    $lignes = '';
    if (empty($tops)) {
        $lignes = '<tr><td colspan="3" style="padding:12px;text-align:center;color:#999;">Aucune vente enregistrée</td></tr>';
    } else {
        foreach ($tops as $p) {
            $lignes .= '<tr>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;">' . htmlspecialchars($p['nom_produit']) . '</td>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:center;">' . $p['qte'] . '</td>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:right;">' . $fn((float)$p['ca_p']) . '</td>'
                . '</tr>';
        }
    }

    $c_benef = $benefice >= 0 ? '#2e7d32' : '#c62828';
    $b_benef = $benefice >= 0 ? '#e8f5e9' : '#ffebee';

    $html = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head>'
        . '<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
        . '<div style="max-width:620px;margin:30px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">'
        // En-tête
        . '<div style="background:linear-gradient(135deg,#1565c0,#0d47a1);padding:30px 32px;color:white;">'
        . '<div style="font-size:13px;opacity:.8;margin-bottom:4px;">Rapport automatique</div>'
        . '<div style="font-size:22px;font-weight:700;">' . htmlspecialchars($nom_boutique) . '</div>'
        . '<div style="font-size:15px;margin-top:6px;opacity:.9;">Bilan du ' . $aujourd_hui . ' — généré à ' . $heure . '</div>'
        . '</div>'
        // Stats
        . '<div style="padding:24px 32px;background:#fafafa;border-bottom:1px solid #eee;">'
        . '<table width="100%"><tr>'
        . '<td style="text-align:center;width:33%;">'
        .   '<div style="font-size:32px;font-weight:700;color:#1565c0;">' . $nb_ventes . '</div>'
        .   '<div style="font-size:11px;color:#888;letter-spacing:.5px;">VENTES</div>'
        . '</td>'
        . '<td style="text-align:center;width:33%;border-left:1px solid #eee;">'
        .   '<div style="font-size:18px;font-weight:700;color:#1565c0;">' . $fn($ca_total) . '</div>'
        .   '<div style="font-size:11px;color:#888;letter-spacing:.5px;">CHIFFRE D\'AFFAIRES</div>'
        . '</td>'
        . '<td style="text-align:center;width:33%;border-left:1px solid #eee;">'
        .   '<div style="display:inline-block;background:' . $b_benef . ';border-radius:8px;padding:8px 16px;">'
        .   '<div style="font-size:18px;font-weight:700;color:' . $c_benef . ';">' . $fn($benefice) . '</div>'
        .   '<div style="font-size:11px;color:' . $c_benef . ';letter-spacing:.5px;">BÉNÉFICE</div>'
        .   '</div>'
        . '</td>'
        . '</tr></table></div>'
        // Corps
        . '<div style="padding:24px 32px;">'
        . '<table width="100%" style="margin-bottom:24px;"><tr>'
        . '<td style="width:48%;background:#fff3e0;border-radius:8px;padding:16px;">'
        .   '<div style="font-size:11px;color:#e65100;font-weight:600;letter-spacing:.5px;margin-bottom:6px;">DÉPENSES DU JOUR</div>'
        .   '<div style="font-size:18px;font-weight:700;color:#bf360c;">' . $fn($depenses) . '</div>'
        . '</td><td style="width:4%;"></td>'
        . '<td style="width:48%;background:#f3e5f5;border-radius:8px;padding:16px;">'
        .   '<div style="font-size:11px;color:#6a1b9a;font-weight:600;letter-spacing:.5px;margin-bottom:6px;">CRÉDITS EN COURS</div>'
        .   '<div style="font-size:18px;font-weight:700;color:#4a148c;">' . $cr['nb'] . ' vente(s)</div>'
        .   '<div style="font-size:12px;color:#6a1b9a;margin-top:4px;">Reste : ' . $fn((float)$cr['reste']) . '</div>'
        . '</td></tr></table>'
        . '<div style="font-size:14px;font-weight:600;color:#333;margin-bottom:10px;">Top produits vendus</div>'
        . '<table width="100%" style="border-collapse:collapse;font-size:13px;">'
        . '<thead><tr style="background:#f5f5f5;">'
        . '<th style="padding:8px 12px;text-align:left;color:#555;">Produit</th>'
        . '<th style="padding:8px 12px;text-align:center;color:#555;">Qté</th>'
        . '<th style="padding:8px 12px;text-align:right;color:#555;">CA</th>'
        . '</tr></thead><tbody>' . $lignes . '</tbody></table>'
        . '</div>'
        // Pied
        . '<div style="padding:16px 32px;background:#f9f9f9;border-top:1px solid #eee;text-align:center;font-size:11px;color:#aaa;">'
        . 'Envoyé automatiquement par ' . htmlspecialchars($nom_boutique) . ' · Store Suite'
        . '</div></div></body></html>';

    $email_dest = trim($config['email_bilan'] ?? '');
    if (empty($email_dest)) throw new RuntimeException('Email destinataire non configuré');

    $mailer = new SmtpMailer([
        'host'      => $config['smtp_host'] ?? '',
        'port'      => (int)($config['smtp_port'] ?? 587),
        'secure'    => $config['smtp_secure'] ?? 'tls',
        'user'      => $config['smtp_user'] ?? '',
        'pass'      => $config['smtp_pass'] ?? '',
        'from'      => $config['smtp_from'] ?: ($config['smtp_user'] ?? ''),
        'from_name' => $config['smtp_from_name'] ?: $nom_boutique,
    ]);

    $mailer->send($email_dest, "Bilan du {$aujourd_hui} — {$nom_boutique}", $html);

    return ['success' => true, 'message' => "Bilan envoyé à {$email_dest}"];
}
