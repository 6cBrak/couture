<?php
/**
 * EXPORT EXCEL - STORE SUITE
 * Export des rapports au format Excel
 */

// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../protection_pages.php';
} catch (Exception $e) {
    die('Erreur de chargement : ' . $e->getMessage());
}

// Type de rapport
$type = $_GET['type'] ?? '';
$date_debut = $_GET['date_debut'] ?? date('Y-m-d');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');

// Vérifier que le type est valide
$types_valides = ['produits', 'ventes', 'benefices', 'categories', 'stock', 'inventaire_depot', 'mouvements_stock', 'valeur_stock', 'alertes_stock'];
if (empty($type) || !in_array($type, $types_valides)) {
    die('<html><body><h1>Erreur</h1><p>Type de rapport invalide ou non spécifié.</p><p>Types valides : ' . implode(', ', $types_valides) . '</p></body></html>');
}

// Headers pour téléchargement Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="rapport_' . $type . '_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF"; // BOM UTF-8

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #206bc4; color: white; font-weight: bold; }
        .header { font-size: 18px; font-weight: bold; margin-bottom: 20px; text-align: center; }
        .header img { max-width: 150px; max-height: 80px; margin-bottom: 10px; }
        .info { margin-bottom: 10px; }
        
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<?php
// En-tête du rapport
try {
    if (!isset($config['nom_boutique'])) {
        throw new Exception('Configuration non chargée. Vérifiez protection_pages.php');
    }
    
    echo '<div class="header">';
    if (!empty($config['logo_boutique']) && file_exists(__DIR__ . '/../uploads/logos/' . $config['logo_boutique'])) {
        echo '<img src="../uploads/logos/' . htmlspecialchars($config['logo_boutique']) . '" alt="Logo"><br>';
    }
    echo htmlspecialchars($config['nom_boutique']) . '</div>';
    echo '<div class="info">Date du rapport : ' . date('d/m/Y à H:i') . '</div>';
    echo '<div class="info">Période : du ' . date('d/m/Y', strtotime($date_debut)) . ' au ' . date('d/m/Y', strtotime($date_fin)) . '</div>';
    echo '<br>';
} catch (Exception $e) {
    die('<div class="header" style="color: red;">ERREUR : ' . htmlspecialchars($e->getMessage()) . '</div>');
}

switch ($type) {
    case 'produits':
        try {
            // Liste des produits
            $produits = db_fetch_all("
                SELECT 
                    p.nom_produit,
                    c.nom_categorie,
                    p.prix_achat,
                    p.prix_vente,
                    p.quantite_stock,
                    p.seuil_alerte,
                    p.est_actif
                FROM produits p
                LEFT JOIN categories c ON p.id_categorie = c.id_categorie
                ORDER BY p.nom_produit
            ");
        } catch (Exception $e) {
            die('<h2 style="color: red;">Erreur SQL : ' . htmlspecialchars($e->getMessage()) . '</h2>');
        }
        
        echo '<h2>Liste des Produits</h2>';
        echo '<table>';
        echo '<tr>
                <th>Produit</th>
                <th>Catégorie</th>
                <th>Prix Achat (' . $devise . ')</th>
                <th>Prix Vente (' . $devise . ')</th>
                <th>Stock</th>
                <th>Seuil</th>
                <th>Statut</th>
              </tr>';
        
        foreach ($produits as $p) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($p['nom_produit']) . '</td>';
            echo '<td>' . htmlspecialchars($p['nom_categorie'] ?? 'Sans catégorie') . '</td>';
            echo '<td>' . number_format($p['prix_achat'], 0, ',', ' ') . '</td>';
            echo '<td>' . number_format($p['prix_vente'], 0, ',', ' ') . '</td>';
            echo '<td>' . $p['quantite_stock'] . '</td>';
            echo '<td>' . $p['seuil_alerte'] . '</td>';
            echo '<td>' . ($p['est_actif'] ? 'Actif' : 'Inactif') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        break;

    case 'ventes':
        // Rapport des ventes
        $ventes = db_fetch_all("
            SELECT 
                v.numero_facture,
                v.date_vente,
                c.nom_client,
                u.nom_utilisateur,
                v.montant_total,
                v.statut
            FROM ventes v
            LEFT JOIN clients c ON v.id_client = c.id_client
            INNER JOIN utilisateurs u ON v.id_utilisateur = u.id_utilisateur
            WHERE DATE(v.date_vente) BETWEEN ? AND ?
            ORDER BY v.date_vente DESC
        ", [$date_debut, $date_fin]);
        
        $total = array_sum(array_column($ventes, 'montant_total'));
        
        echo '<h2>Rapport des Ventes</h2>';
        echo '<table>';
        echo '<tr>
                <th>Facture</th>
                <th>Date</th>
                <th>Client</th>
                <th>Vendeur</th>
                <th>Montant (' . $devise . ')</th>
                <th>Statut</th>
              </tr>';
        
        foreach ($ventes as $v) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($v['numero_facture']) . '</td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($v['date_vente'])) . '</td>';
            echo '<td>' . htmlspecialchars($v['nom_client'] ?? 'Comptoir') . '</td>';
            echo '<td>' . htmlspecialchars($v['nom_utilisateur']) . '</td>';
            echo '<td>' . number_format($v['montant_total'], 0, ',', ' ') . '</td>';
            echo '<td>' . ucfirst($v['statut']) . '</td>';
            echo '</tr>';
        }
        
        echo '<tr style="font-weight: bold; background-color: #f0f0f0;">';
        echo '<td colspan="4">TOTAL</td>';
        echo '<td>' . number_format($total, 0, ',', ' ') . '</td>';
        echo '<td></td>';
        echo '</tr>';
        echo '</table>';
        break;

    case 'benefices':
        // Rapport des bénéfices
        $benefices = db_fetch_all("
            SELECT 
                p.nom_produit,
                c.nom_categorie,
                p.prix_achat,
                p.prix_vente,
                SUM(vd.quantite) as quantite_vendue,
                SUM(vd.prix_unitaire * vd.quantite) as ca,
                SUM((vd.prix_unitaire - p.prix_achat) * vd.quantite) as benefice
            FROM details_vente vd
            INNER JOIN produits p ON vd.id_produit = p.id_produit
            LEFT JOIN categories c ON p.id_categorie = c.id_categorie
            INNER JOIN ventes v ON vd.id_vente = v.id_vente
            WHERE DATE(v.date_vente) BETWEEN ? AND ?
            AND v.statut = 'validee'
            AND p.prix_achat > 0
            GROUP BY p.id_produit
            ORDER BY benefice DESC
        ", [$date_debut, $date_fin]);
        
        $total_ca = array_sum(array_column($benefices, 'ca'));
        $total_benefice = array_sum(array_column($benefices, 'benefice'));
        $marge = $total_ca > 0 ? ($total_benefice / $total_ca * 100) : 0;
        
        echo '<h2>Rapport des Bénéfices</h2>';
        echo '<p><strong>Chiffre d\'affaires total : </strong>' . number_format($total_ca, 0, ',', ' ') . ' ' . $devise . '</p>';
        echo '<p><strong>Bénéfice total : </strong>' . number_format($total_benefice, 0, ',', ' ') . ' ' . $devise . '</p>';
        echo '<p><strong>Marge globale : </strong>' . number_format($marge, 2) . ' %</p>';
        echo '<br>';
        
        echo '<table>';
        echo '<tr>
                <th>Produit</th>
                <th>Catégorie</th>
                <th>Prix Achat</th>
                <th>Prix Vente</th>
                <th>Qté Vendue</th>
                <th>CA (' . $devise . ')</th>
                <th>Bénéfice (' . $devise . ')</th>
                <th>Marge (%)</th>
              </tr>';
        
        foreach ($benefices as $b) {
            $marge_produit = $b['ca'] > 0 ? ($b['benefice'] / $b['ca'] * 100) : 0;
            echo '<tr>';
            echo '<td>' . htmlspecialchars($b['nom_produit']) . '</td>';
            echo '<td>' . htmlspecialchars($b['nom_categorie'] ?? 'Sans catégorie') . '</td>';
            echo '<td>' . number_format($b['prix_achat'], 0, ',', ' ') . '</td>';
            echo '<td>' . number_format($b['prix_vente'], 0, ',', ' ') . '</td>';
            echo '<td>' . $b['quantite_vendue'] . '</td>';
            echo '<td>' . number_format($b['ca'], 0, ',', ' ') . '</td>';
            echo '<td>' . number_format($b['benefice'], 0, ',', ' ') . '</td>';
            echo '<td>' . number_format($marge_produit, 2) . ' %</td>';
            echo '</tr>';
        }
        echo '</table>';
        break;

    case 'categories':
        // Rapport par catégories
        $categories = db_fetch_all("
            SELECT 
                COALESCE(c.nom_categorie, 'Sans catégorie') as categorie,
                COUNT(DISTINCT p.id_produit) as nb_produits,
                SUM(vd.quantite) as quantite_vendue,
                SUM(vd.prix_total) as ca
            FROM categories c
            LEFT JOIN produits p ON c.id_categorie = p.id_categorie
            LEFT JOIN ventes_details vd ON p.id_produit = vd.id_produit
            LEFT JOIN ventes v ON vd.id_vente = v.id_vente AND DATE(v.date_vente) BETWEEN ? AND ?
            WHERE v.statut = 'validee' OR v.statut IS NULL
            GROUP BY c.id_categorie
            ORDER BY ca DESC
        ", [$date_debut, $date_fin]);
        
        $total_ca = array_sum(array_column($categories, 'ca'));
        
        echo '<h2>Rapport par Catégories</h2>';
        echo '<table>';
        echo '<tr>
                <th>Catégorie</th>
                <th>Nb Produits</th>
                <th>Qté Vendue</th>
                <th>CA (' . $devise . ')</th>
                <th>Part (%)</th>
              </tr>';
        
        foreach ($categories as $cat) {
            $part = $total_ca > 0 ? ($cat['ca'] / $total_ca * 100) : 0;
            echo '<tr>';
            echo '<td>' . htmlspecialchars($cat['categorie']) . '</td>';
            echo '<td>' . $cat['nb_produits'] . '</td>';
            echo '<td>' . ($cat['quantite_vendue'] ?? 0) . '</td>';
            echo '<td>' . number_format($cat['ca'] ?? 0, 0, ',', ' ') . '</td>';
            echo '<td>' . number_format($part, 2) . ' %</td>';
            echo '</tr>';
        }
        
        echo '<tr style="font-weight: bold; background-color: #f0f0f0;">';
        echo '<td colspan="3">TOTAL</td>';
        echo '<td>' . number_format($total_ca, 0, ',', ' ') . '</td>';
        echo '<td>100 %</td>';
        echo '</tr>';
        echo '</table>';
        break;

    case 'stock':
        // État du stock
        $produits = db_fetch_all("
            SELECT 
                p.nom_produit,
                c.nom_categorie,
                p.quantite_stock,
                p.seuil_alerte,
                p.prix_achat,
                p.prix_vente,
                (p.quantite_stock * p.prix_achat) as valeur_achat,
                (p.quantite_stock * p.prix_vente) as valeur_vente
            FROM produits p
            LEFT JOIN categories c ON p.id_categorie = c.id_categorie
            WHERE p.est_actif = 1
            ORDER BY p.nom_produit
        ");
        
        $total_achat = array_sum(array_column($produits, 'valeur_achat'));
        $total_vente = array_sum(array_column($produits, 'valeur_vente'));
        
        echo '<h2>État du Stock</h2>';
        echo '<p><strong>Valeur stock (prix achat) : </strong>' . number_format($total_achat, 0, ',', ' ') . ' ' . $devise . '</p>';
        echo '<p><strong>Valeur stock (prix vente) : </strong>' . number_format($total_vente, 0, ',', ' ') . ' ' . $devise . '</p>';
        echo '<br>';
        
        echo '<table>';
        echo '<tr>
                <th>Produit</th>
                <th>Catégorie</th>
                <th>Stock</th>
                <th>Seuil</th>
                <th>Prix Achat</th>
                <th>Valeur Achat</th>
                <th>Statut</th>
              </tr>';
        
        foreach ($produits as $p) {
            $statut = $p['quantite_stock'] <= $p['seuil_alerte'] ? 'ALERTE' : 'OK';
            echo '<tr>';
            echo '<td>' . htmlspecialchars($p['nom_produit']) . '</td>';
            echo '<td>' . htmlspecialchars($p['nom_categorie'] ?? 'Sans catégorie') . '</td>';
            echo '<td>' . $p['quantite_stock'] . '</td>';
            echo '<td>' . $p['seuil_alerte'] . '</td>';
            echo '<td>' . number_format($p['prix_achat'], 0, ',', ' ') . '</td>';
            echo '<td>' . number_format($p['valeur_achat'], 0, ',', ' ') . '</td>';
            echo '<td>' . $statut . '</td>';
            echo '</tr>';
        }
        
        echo '<tr style="font-weight: bold; background-color: #f0f0f0;">';
        echo '<td colspan="5">TOTAL</td>';
        echo '<td>' . number_format($total_achat, 0, ',', ' ') . '</td>';
        echo '<td></td>';
        echo '</tr>';
        echo '</table>';
        break;

    default:
        echo '<p>Type de rapport non reconnu.</p>';
        break;
}
?>

</body>
</html>
