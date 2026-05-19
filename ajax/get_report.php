<?php
/**
 * AJAX endpoint - Get report content for modal display
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'html' => '', 'title' => ''];

try {
    if (empty($_POST['type'])) throw new Exception('Type de rapport manquant');
    
    $type = $_POST['type'];
    $date_debut = $_POST['date_debut'] ?? date('Y-m-d');
    $date_fin = $_POST['date_fin'] ?? date('Y-m-d');
    
    switch ($type) {
        case 'ventes':
            $response['title'] = 'Rapport Ventes';
            
            // Get ventes data
            $ventes = db_fetch_all("
                SELECT 
                    v.id_vente,
                    v.date_vente,
                    c.nom_client,
                    COUNT(dv.id_detail) as nombre_articles,
                    v.montant_total
                FROM ventes v
                LEFT JOIN clients c ON v.id_client = c.id_client
                LEFT JOIN details_vente dv ON v.id_vente = dv.id_vente
                WHERE DATE(v.date_vente) BETWEEN ? AND ?
                AND v.statut = 'validee'
                GROUP BY v.id_vente
                ORDER BY v.date_vente DESC
                LIMIT 50
            ", [$date_debut, $date_fin]);
            
            $html = '<table class="table table-sm table-hover">';
            $html .= '<thead><tr><th>ID</th><th>Date</th><th>Client</th><th>Articles</th><th class="text-end">Total</th></tr></thead><tbody>';
            
            foreach ($ventes as $v) {
                $html .= '<tr>';
                $html .= '<td><strong>#' . $v['id_vente'] . '</strong></td>';
                $html .= '<td>' . date('d/m/Y', strtotime($v['date_vente'])) . '</td>';
                $html .= '<td>' . e($v['nom_client'] ?? 'Client anonyme') . '</td>';
                $html .= '<td><span class="badge bg-info">' . $v['nombre_articles'] . '</span></td>';
                $html .= '<td class="text-end"><strong>' . format_montant($v['montant_total']) . '</strong></td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            
            if (empty($ventes)) {
                $html = '<div class="alert alert-info">Aucune vente enregistrée pour cette période</div>';
            }
            
            $response['html'] = $html;
            $response['success'] = true;
            break;
            
        case 'produits':
            $response['title'] = 'Inventaire Produits';
            
            // Inventaire complet, même sans ventes dans la période
            $produits = db_fetch_all("
                SELECT 
                    p.id_produit,
                    p.nom_produit,
                    p.prix_vente,
                    p.quantite_stock,
                    p.seuil_alerte,
                    p.seuil_critique,
                    p.est_actif,
                    cat.nom_categorie,
                    COALESCE(SUM(CASE WHEN v.statut = 'validee' AND DATE(v.date_vente) BETWEEN ? AND ? THEN dv.quantite ELSE 0 END), 0) AS quantite_vendue,
                    COALESCE(SUM(CASE WHEN v.statut = 'validee' AND DATE(v.date_vente) BETWEEN ? AND ? THEN dv.prix_total ELSE 0 END), 0) AS montant_total
                FROM produits p
                LEFT JOIN categories cat ON p.id_categorie = cat.id_categorie
                LEFT JOIN details_vente dv ON p.id_produit = dv.id_produit
                LEFT JOIN ventes v ON dv.id_vente = v.id_vente
                GROUP BY p.id_produit
                ORDER BY p.nom_produit ASC
                LIMIT 200
            ", [$date_debut, $date_fin, $date_debut, $date_fin]);
            
            $html = '<table class="table table-sm table-hover">';
            $html .= '<thead><tr><th>Produit</th><th>Catégorie</th><th class="text-end">Prix vente</th><th class="text-center">Stock</th><th class="text-center">Vendu</th><th class="text-end">Montant ventes</th></tr></thead><tbody>';
            
            foreach ($produits as $p) {
                $statut_class = $p['est_actif'] ? 'bg-success-lt' : 'bg-secondary-lt';
                $html .= '<tr>';
                $html .= '<td><strong>' . e($p['nom_produit']) . '</strong><br><small class="text-muted">#' . $p['id_produit'] . '</small></td>';
                $html .= '<td><span class="badge bg-purple-lt text-dark">' . e($p['nom_categorie'] ?? 'Non classé') . '</span></td>';
                $html .= '<td class="text-end">' . format_montant($p['prix_vente']) . '</td>';
                $html .= '<td class="text-center"><span class="badge ' . ($p['quantite_stock'] <= $p['seuil_critique'] ? 'bg-danger' : ($p['quantite_stock'] <= $p['seuil_alerte'] ? 'bg-warning' : 'bg-success')) . '">' . ($p['quantite_stock'] ?? 0) . '</span></td>';
                $html .= '<td class="text-center"><span class="badge bg-primary">' . ($p['quantite_vendue'] ?? 0) . '</span></td>';
                $html .= '<td class="text-end"><strong>' . format_montant($p['montant_total'] ?? 0) . '</strong></td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            
            if (empty($produits)) {
                $html = '<div class="alert alert-info">Aucun produit trouvé</div>';
            }
            
            $response['html'] = $html;
            $response['success'] = true;
            break;
            
        case 'benefices':
            // Vérification admin
            if (!$is_admin) {
                throw new Exception('Accès refusé. Seuls les administrateurs peuvent consulter ce rapport.');
            }
            
            $response['title'] = 'Performance Financière';
            
            // Get benefices data
            $stats = db_fetch_one("
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
            
            $html = '<div class="row g-2">';
            $html .= '<div class="col-md-6"><div class="bg-light p-3 rounded"><small class="text-muted">Chiffre d\'affaires TTC</small><h4>' . format_montant($stats['chiffre_affaires'] ?? 0) . '</h4></div></div>';
            $html .= '<div class="col-md-6"><div class="bg-light p-3 rounded"><small class="text-muted">Montant HT</small><h4>' . format_montant($stats['montant_ht'] ?? 0) . '</h4></div></div>';
            $tva_label = ($taux_tva > 0) ? 'TVA (' . (float)$taux_tva . '%)' : 'TVA';
            $html .= '<div class="col-md-6"><div class="bg-light p-3 rounded"><small class="text-muted">' . $tva_label . '</small><h4>' . format_montant($stats['montant_tva'] ?? 0) . '</h4></div></div>';
            $html .= '<div class="col-md-6"><div class="bg-light p-3 rounded"><small class="text-muted">Panier moyen</small><h4>' . format_montant($stats['panier_moyen'] ?? 0) . '</h4></div></div>';
            $html .= '</div>';
            
            $response['html'] = $html;
            $response['success'] = true;
            break;
            
        case 'categories':
            $response['title'] = 'Performance Catégories';
            
            // Get categories data
            $categories = db_fetch_all("
                SELECT 
                    cat.id_categorie,
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
            
            $html = '<table class="table table-sm table-hover">';
            $html .= '<thead><tr><th>Catégorie</th><th class="text-center">Produits</th><th class="text-center">Quantité</th><th class="text-end">Montant</th></tr></thead><tbody>';
            
            foreach ($categories as $c) {
                $html .= '<tr>';
                $html .= '<td><strong>' . e($c['nom_categorie']) . '</strong></td>';
                $html .= '<td class="text-center"><span class="badge bg-success">' . $c['nombre_produits'] . '</span></td>';
                $html .= '<td class="text-center"><span class="badge bg-primary">' . $c['quantite_vendue'] . '</span></td>';
                $html .= '<td class="text-end"><strong>' . format_montant($c['montant_total']) . '</strong></td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            
            if (empty($categories)) {
                $html = '<div class="alert alert-info">Aucune vente par catégorie pour cette période</div>';
            }
            
            $response['html'] = $html;
            $response['success'] = true;
            break;
            
        case 'inventaire_depot':
            $response['title'] = 'Inventaire par Dépôt';
            
            $id_depot = $_POST['id_depot'] ?? 'all';
            
            if ($id_depot === 'all') {
                // Vue globale tous dépôts
                $inventaire = db_fetch_all("
                    SELECT * FROM vue_inventaire_complet
                    ORDER BY nom_produit ASC
                    LIMIT 200
                ");
                
                $html = '<div class="mb-3"><span class="badge bg-info">Tous les dépôts</span></div>';
                $html .= '<table class="table table-sm table-hover">';
                $html .= '<thead><tr><th>Produit</th><th>Catégorie</th><th class="text-center">Stock Total</th><th class="text-center">Magasin</th><th class="text-center">Autres Dépôts</th><th class="text-center">Statut</th></tr></thead><tbody>';
                
                foreach ($inventaire as $item) {
                    $statut_class = $item['quantite_totale'] <= $item['seuil_critique'] ? 'bg-danger' : ($item['quantite_totale'] <= $item['seuil_alerte'] ? 'bg-warning' : 'bg-success');
                    
                    $html .= '<tr>';
                    $html .= '<td><strong>' . e($item['nom_produit']) . '</strong></td>';
                    $html .= '<td><span class="badge bg-purple-lt">' . e($item['nom_categorie']) . '</span></td>';
                    $html .= '<td class="text-center"><span class="badge ' . $statut_class . '">' . $item['quantite_totale'] . '</span></td>';
                    $html .= '<td class="text-center">' . ($item['magasin_principal'] ?? 0) . '</td>';
                    $html .= '<td class="text-center">' . ($item['autres_depots'] ?? 0) . '</td>';
                    $html .= '<td class="text-center">' . $item['nombre_depots'] . ' dépôt(s)</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</tbody></table>';
                
            } else {
                // Inventaire d'un dépôt spécifique
                $depot = db_fetch_one("SELECT * FROM depots WHERE id_depot = ?", [$id_depot]);
                
                if (!$depot) {
                    throw new Exception('Dépôt introuvable');
                }
                
                $inventaire = db_fetch_all("
                    SELECT 
                        p.nom_produit,
                        cat.nom_categorie,
                        spd.quantite,
                        p.seuil_alerte,
                        p.seuil_critique
                    FROM stock_par_depot spd
                    INNER JOIN produits p ON spd.id_produit = p.id_produit
                    LEFT JOIN categories cat ON p.id_categorie = cat.id_categorie
                    WHERE spd.id_depot = ?
                    ORDER BY p.nom_produit ASC
                ", [$id_depot]);
                
                $html = '<div class="mb-3"><span class="badge bg-primary">' . e($depot['nom_depot']) . '</span> <small class="text-muted">' . e($depot['description']) . '</small></div>';
                $html .= '<table class="table table-sm table-hover">';
                $html .= '<thead><tr><th>Produit</th><th>Catégorie</th><th class="text-center">Quantité</th><th class="text-center">Statut</th></tr></thead><tbody>';
                
                foreach ($inventaire as $item) {
                    $statut_class = $item['quantite'] <= $item['seuil_critique'] ? 'bg-danger' : ($item['quantite'] <= $item['seuil_alerte'] ? 'bg-warning' : 'bg-success');
                    
                    $html .= '<tr>';
                    $html .= '<td><strong>' . e($item['nom_produit']) . '</strong></td>';
                    $html .= '<td><span class="badge bg-purple-lt">' . e($item['nom_categorie']) . '</span></td>';
                    $html .= '<td class="text-center"><span class="badge ' . $statut_class . '">' . $item['quantite'] . '</span></td>';
                    $html .= '<td class="text-center">' . 
                        ($item['quantite'] <= $item['seuil_critique'] ? '<span class="badge bg-danger">Critique</span>' : 
                        ($item['quantite'] <= $item['seuil_alerte'] ? '<span class="badge bg-warning">Alerte</span>' : 
                        '<span class="badge bg-success">OK</span>')) . '</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</tbody></table>';
            }
            
            if (empty($inventaire)) {
                $html = '<div class="alert alert-info">Aucun produit en stock pour ce dépôt</div>';
            }
            
            $response['html'] = $html;
            $response['success'] = true;
            break;
            
        case 'mouvements_stock':
            $response['title'] = 'Historique Mouvements de Stock';
            
            $mouvements = db_fetch_all("
                SELECT * FROM vue_mouvements_stock_detail
                WHERE DATE(date_mouvement) BETWEEN ? AND ?
                ORDER BY date_mouvement DESC
                LIMIT 100
            ", [$date_debut, $date_fin]);
            
            $html = '<table class="table table-sm table-hover">';
            $html .= '<thead><tr><th>Date</th><th>Produit</th><th>Type</th><th class="text-center">Qté</th><th>Dépôt</th><th>Utilisateur</th></tr></thead><tbody>';
            
            foreach ($mouvements as $m) {
                // Badge couleur selon type
                $badge_colors = [
                    'entree' => 'bg-success',
                    'sortie' => 'bg-danger',
                    'ajustement' => 'bg-warning',
                    'transfert' => 'bg-info',
                    'inventaire' => 'bg-purple',
                    'perte' => 'bg-dark'
                ];
                $badge_class = $badge_colors[$m['type_mouvement']] ?? 'bg-secondary';
                
                $html .= '<tr>';
                $html .= '<td>' . date('d/m/Y H:i', strtotime($m['date_mouvement'])) . '</td>';
                $html .= '<td><strong>' . e($m['nom_produit']) . '</strong></td>';
                $html .= '<td><span class="badge ' . $badge_class . '">' . ucfirst($m['type_mouvement']) . '</span></td>';
                $html .= '<td class="text-center"><span class="badge bg-dark">' . ($m['quantite'] > 0 ? '+' : '') . $m['quantite'] . '</span></td>';
                $html .= '<td>' . e($m['nom_depot_source'] ?? 'N/A');
                if ($m['type_mouvement'] === 'transfert' && $m['nom_depot_destination']) {
                    $html .= ' <i class="ti ti-arrow-right"></i> ' . e($m['nom_depot_destination']);
                }
                $html .= '</td>';
                $html .= '<td><small>' . e($m['nom_utilisateur']) . '</small></td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            
            if (empty($mouvements)) {
                $html = '<div class="alert alert-info">Aucun mouvement de stock pour cette période</div>';
            }
            
            $response['html'] = $html;
            $response['success'] = true;
            break;
            
        case 'valeur_stock':
            // Vérification admin
            if (!$is_admin) {
                throw new Exception('Accès refusé. Seuls les administrateurs peuvent consulter ce rapport.');
            }
            
            $response['title'] = 'Valeur du Stock';
            
            // Calcul de la valeur du stock
            $valeur_stock = db_fetch_all("
                SELECT 
                    d.nom_depot,
                    d.description,
                    COUNT(DISTINCT spd.id_produit) as nombre_produits,
                    SUM(spd.quantite) as quantite_totale,
                    SUM(spd.quantite * p.prix_achat) as valeur_achat,
                    SUM(spd.quantite * p.prix_vente) as valeur_vente,
                    SUM(spd.quantite * (p.prix_vente - p.prix_achat)) as marge_potentielle
                FROM stock_par_depot spd
                INNER JOIN produits p ON spd.id_produit = p.id_produit
                INNER JOIN depots d ON spd.id_depot = d.id_depot
                WHERE spd.quantite > 0
                GROUP BY d.id_depot
                ORDER BY valeur_vente DESC
            ");
            
            $totaux = db_fetch_one("
                SELECT 
                    COUNT(DISTINCT spd.id_produit) as total_produits,
                    SUM(spd.quantite) as total_quantite,
                    SUM(spd.quantite * p.prix_achat) as total_achat,
                    SUM(spd.quantite * p.prix_vente) as total_vente,
                    SUM(spd.quantite * (p.prix_vente - p.prix_achat)) as total_marge
                FROM stock_par_depot spd
                INNER JOIN produits p ON spd.id_produit = p.id_produit
                WHERE spd.quantite > 0
            ");
            
            // Totaux globaux
            $html = '<div class="row g-2 mb-3">';
            $html .= '<div class="col-md-3"><div class="bg-primary text-white p-3 rounded"><small>Valeur Achat</small><h4>' . format_montant($totaux['total_achat'] ?? 0) . '</h4></div></div>';
            $html .= '<div class="col-md-3"><div class="bg-success text-white p-3 rounded"><small>Valeur Vente</small><h4>' . format_montant($totaux['total_vente'] ?? 0) . '</h4></div></div>';
            $html .= '<div class="col-md-3"><div class="bg-warning text-white p-3 rounded"><small>Marge Potentielle</small><h4>' . format_montant($totaux['total_marge'] ?? 0) . '</h4></div></div>';
            $html .= '<div class="col-md-3"><div class="bg-info text-white p-3 rounded"><small>Produits</small><h4>' . ($totaux['total_produits'] ?? 0) . '</h4></div></div>';
            $html .= '</div>';
            
            // Détail par dépôt
            $html .= '<table class="table table-sm table-hover">';
            $html .= '<thead><tr><th>Dépôt</th><th class="text-center">Produits</th><th class="text-center">Quantité</th><th class="text-end">Valeur Achat</th><th class="text-end">Valeur Vente</th><th class="text-end">Marge</th></tr></thead><tbody>';
            
            foreach ($valeur_stock as $vs) {
                $html .= '<tr>';
                $html .= '<td><strong>' . e($vs['nom_depot']) . '</strong><br><small class="text-muted">' . e($vs['description']) . '</small></td>';
                $html .= '<td class="text-center"><span class="badge bg-purple">' . $vs['nombre_produits'] . '</span></td>';
                $html .= '<td class="text-center"><span class="badge bg-dark">' . $vs['quantite_totale'] . '</span></td>';
                $html .= '<td class="text-end">' . format_montant($vs['valeur_achat']) . '</td>';
                $html .= '<td class="text-end"><strong>' . format_montant($vs['valeur_vente']) . '</strong></td>';
                $html .= '<td class="text-end text-success"><strong>+' . format_montant($vs['marge_potentielle']) . '</strong></td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            
            if (empty($valeur_stock)) {
                $html = '<div class="alert alert-info">Aucun stock disponible</div>';
            }
            
            $response['html'] = $html;
            $response['success'] = true;
            break;
            
        case 'alertes_stock':
            $response['title'] = 'Alertes & Ruptures de Stock';
            
            // Produits en alerte ou rupture
            $alertes = db_fetch_all("
                SELECT 
                    p.nom_produit,
                    cat.nom_categorie,
                    p.quantite_stock,
                    p.seuil_alerte,
                    p.seuil_critique,
                    CASE 
                        WHEN p.quantite_stock = 0 THEN 'Rupture'
                        WHEN p.quantite_stock <= p.seuil_critique THEN 'Critique'
                        WHEN p.quantite_stock <= p.seuil_alerte THEN 'Alerte'
                        ELSE 'OK'
                    END as niveau_alerte,
                    GROUP_CONCAT(CONCAT(d.nom_depot, ' (', spd.quantite, ')') SEPARATOR ', ') as detail_depots
                FROM produits p
                LEFT JOIN categories cat ON p.id_categorie = cat.id_categorie
                LEFT JOIN stock_par_depot spd ON p.id_produit = spd.id_produit
                LEFT JOIN depots d ON spd.id_depot = d.id_depot
                WHERE p.quantite_stock <= p.seuil_alerte
                AND p.est_actif = 1
                GROUP BY p.id_produit
                ORDER BY p.quantite_stock ASC, p.nom_produit ASC
            ");
            
            // Statistiques
            $stats_alertes = db_fetch_one("
                SELECT 
                    COUNT(CASE WHEN quantite_stock = 0 THEN 1 END) as ruptures,
                    COUNT(CASE WHEN quantite_stock > 0 AND quantite_stock <= seuil_critique THEN 1 END) as critiques,
                    COUNT(CASE WHEN quantite_stock > seuil_critique AND quantite_stock <= seuil_alerte THEN 1 END) as alertes
                FROM produits
                WHERE est_actif = 1
            ");
            
            $html = '<div class="row g-2 mb-3">';
            $html .= '<div class="col-md-4"><div class="bg-danger text-white p-3 rounded"><small>Ruptures</small><h4>' . ($stats_alertes['ruptures'] ?? 0) . '</h4></div></div>';
            $html .= '<div class="col-md-4"><div class="bg-warning p-3 rounded"><small>Critiques</small><h4>' . ($stats_alertes['critiques'] ?? 0) . '</h4></div></div>';
            $html .= '<div class="col-md-4"><div class="bg-info text-white p-3 rounded"><small>Alertes</small><h4>' . ($stats_alertes['alertes'] ?? 0) . '</h4></div></div>';
            $html .= '</div>';
            
            $html .= '<table class="table table-sm table-hover">';
            $html .= '<thead><tr><th>Produit</th><th>Catégorie</th><th class="text-center">Stock</th><th class="text-center">Seuil</th><th class="text-center">Niveau</th><th>Dépôts</th></tr></thead><tbody>';
            
            foreach ($alertes as $a) {
                $badge_colors = [
                    'Rupture' => 'bg-danger',
                    'Critique' => 'bg-warning text-dark',
                    'Alerte' => 'bg-info',
                    'OK' => 'bg-success'
                ];
                $badge_class = $badge_colors[$a['niveau_alerte']] ?? 'bg-secondary';
                
                $html .= '<tr>';
                $html .= '<td><strong>' . e($a['nom_produit']) . '</strong></td>';
                $html .= '<td><span class="badge bg-purple-lt">' . e($a['nom_categorie']) . '</span></td>';
                $html .= '<td class="text-center"><span class="badge bg-dark">' . $a['quantite_stock'] . '</span></td>';
                $html .= '<td class="text-center"><small class="text-muted">Alerte: ' . $a['seuil_alerte'] . '<br>Critique: ' . $a['seuil_critique'] . '</small></td>';
                $html .= '<td class="text-center"><span class="badge ' . $badge_class . '">' . $a['niveau_alerte'] . '</span></td>';
                $html .= '<td><small>' . e($a['detail_depots'] ?? 'N/A') . '</small></td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            
            if (empty($alertes)) {
                $html = '<div class="alert alert-success"><i class="ti ti-check"></i> Aucune alerte de stock. Tous les produits sont correctement approvisionnés.</div>';
            }
            
            $response['html'] = $html;
            $response['success'] = true;
            break;
            
        default:
            throw new Exception('Type de rapport non reconnu');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
