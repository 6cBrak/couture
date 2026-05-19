<?php
/**
 * ============================================================================
 * TABLEAU DE BORD MODERNE - STORE SUITE
 * ============================================================================
 * Dashboard professionnel avec couleurs personnalisées et menus selon rôle
 * ============================================================================
 */

// Désactiver le cache pour éviter les données périmées
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'protection_pages.php';
$page_title = 'Tableau de bord';

// Helper pour éviter un 500 si une vue/table manque en prod
function safe_query(callable $fn, $default) {
    try {
        return $fn();
    } catch (Exception $e) {
        error_log('accueil.php query error: ' . $e->getMessage());
        return $default;
    }
}

// Récupération des statistiques du jour
$stats_jour = safe_query(function() {
    return db_fetch_one("
        SELECT 
            COUNT(id_vente) as nombre_ventes,
            COALESCE(SUM(montant_total), 0) as chiffre_affaires
        FROM ventes 
        WHERE DATE(date_vente) = CURDATE() AND statut = 'validee'
    ");
}, ['nombre_ventes' => 0, 'chiffre_affaires' => 0]);

// Récupération des statistiques du mois
$stats_mois = safe_query(function() {
    return db_fetch_one("
        SELECT 
            COUNT(id_vente) as nombre_ventes,
            COALESCE(SUM(montant_total), 0) as chiffre_affaires
        FROM ventes 
        WHERE MONTH(date_vente) = MONTH(CURDATE()) 
        AND YEAR(date_vente) = YEAR(CURDATE())
        AND statut = 'validee'
    ");
}, ['nombre_ventes' => 0, 'chiffre_affaires' => 0]);

// Bénéfices (uniquement pour admin)
$benefices_jour = 0;
$benefices_mois = 0;
if ($is_admin) {
    $benefice_data_jour = safe_query(function() {
        return db_fetch_one("
            SELECT COALESCE(SUM(vd.benefice_ligne), 0) as benefice_total
            FROM details_vente vd
            INNER JOIN ventes v ON vd.id_vente = v.id_vente
            WHERE DATE(v.date_vente) = CURDATE() AND v.statut = 'validee'
        ");
    }, ['benefice_total' => 0]);
    $benefices_jour = $benefice_data_jour['benefice_total'];
    
    $benefice_data_mois = safe_query(function() {
        return db_fetch_one("
            SELECT COALESCE(SUM(vd.benefice_ligne), 0) as benefice_total
            FROM details_vente vd
            INNER JOIN ventes v ON vd.id_vente = v.id_vente
            WHERE MONTH(v.date_vente) = MONTH(CURDATE())
            AND YEAR(v.date_vente) = YEAR(CURDATE())
            AND v.statut = 'validee'
        ");
    }, ['benefice_total' => 0]);
    $benefices_mois = $benefice_data_mois['benefice_total'];
}

// Statistiques stock
$stats_stock = safe_query(function() {
    return db_fetch_one("
        SELECT 
            COUNT(*) as total_produits,
            COALESCE(SUM(quantite_stock), 0) as quantite_totale,
            COALESCE(SUM(quantite_stock * prix_vente), 0) as valeur_stock
        FROM produits 
        WHERE est_actif = 1
    ");
}, ['total_produits' => 0, 'quantite_totale' => 0, 'valeur_stock' => 0]);

// Produits en alerte
$produits_alerte = safe_query(function() {
    return db_fetch_all("
        SELECT * FROM vue_produits_alertes 
        ORDER BY 
            CASE niveau_alerte
                WHEN 'rupture' THEN 1
                WHEN 'critique' THEN 2
                WHEN 'faible' THEN 3
            END
        LIMIT 5
    ");
}, []);

// Dernières ventes
$dernieres_ventes = safe_query(function() {
    return db_fetch_all("
        SELECT v.*, c.nom_client, u.nom_complet as vendeur
        FROM ventes v
        LEFT JOIN clients c ON v.id_client = c.id_client
        LEFT JOIN utilisateurs u ON v.id_vendeur = u.id_utilisateur
        WHERE v.statut = 'validee'
        ORDER BY v.date_vente DESC
        LIMIT 5
    ");
}, []);

// Top 5 produits du mois
$top_produits = safe_query(function() {
    return db_fetch_all("
        SELECT 
            p.nom_produit,
            p.code_produit,
            SUM(vd.quantite) as quantite_vendue,
            SUM(vd.prix_total) as montant_total
        FROM details_vente vd
        INNER JOIN ventes v ON vd.id_vente = v.id_vente
        INNER JOIN produits p ON vd.id_produit = p.id_produit
        WHERE MONTH(v.date_vente) = MONTH(CURDATE())
        AND YEAR(v.date_vente) = YEAR(CURDATE())
        AND v.statut = 'validee'
        GROUP BY p.id_produit
        ORDER BY quantite_vendue DESC
        LIMIT 5
    ");
}, []);

// Données pour les graphiques - CA des 7 derniers jours
$ca_7jours = safe_query(function() {
    return db_fetch_all("
        SELECT 
            DATE(date_vente) as date,
            COALESCE(SUM(montant_total), 0) as total
        FROM ventes
        WHERE date_vente >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND statut = 'validee'
        GROUP BY DATE(date_vente)
        ORDER BY date ASC
    ");
}, []);

// Préparer les données pour les 7 derniers jours (même si pas de ventes)
$dates_ca = [];
$montants_ca = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates_ca[] = date('d/m', strtotime($date));
    
    $montant = 0;
    foreach ($ca_7jours as $vente) {
        if ($vente['date'] == $date) {
            $montant = $vente['total'];
            break;
        }
    }
    $montants_ca[] = $montant;
}

// Répartition des ventes par mode de paiement (ce mois)
$repartition_paiement = safe_query(function() {
    return db_fetch_all("
        SELECT 
            mode_paiement,
            COUNT(*) as nombre,
            COALESCE(SUM(montant_total), 0) as total
        FROM ventes
        WHERE MONTH(date_vente) = MONTH(CURDATE())
        AND YEAR(date_vente) = YEAR(CURDATE())
        AND statut = 'validee'
        GROUP BY mode_paiement
        ORDER BY total DESC
    ");
}, []);

// Statistiques crédit du mois
$stats_credit = safe_query(function() {
    return db_fetch_one("
        SELECT
            COUNT(*) as nb_credits,
            COALESCE(SUM(montant_total), 0) as total_credit,
            COALESCE(SUM(montant_total) - SUM(COALESCE(montant_paye, 0)), 0) as solde_restant,
            SUM(CASE WHEN date_echeance IS NOT NULL AND date_echeance < CURDATE() AND (montant_total - COALESCE(montant_paye, 0)) > 0.01 THEN 1 ELSE 0 END) as nb_en_retard
        FROM ventes
        WHERE mode_paiement = 'credit'
        AND statut = 'validee'
    ");
}, ['nb_credits' => 0, 'total_credit' => 0, 'solde_restant' => 0, 'nb_en_retard' => 0]);

// Créances en retard (échéance dépassée avec solde restant)
$credits_en_retard = safe_query(function() {
    return db_fetch_all("
        SELECT v.id_vente, v.numero_facture, v.montant_total,
               COALESCE(v.montant_paye, 0) as montant_paye,
               (v.montant_total - COALESCE(v.montant_paye, 0)) as solde_restant,
               v.date_echeance, c.nom_client
        FROM ventes v
        LEFT JOIN clients c ON v.id_client = c.id_client
        WHERE v.mode_paiement = 'credit'
        AND v.statut = 'validee'
        AND v.date_echeance IS NOT NULL
        AND v.date_echeance < CURDATE()
        AND (v.montant_total - COALESCE(v.montant_paye, 0)) > 0.01
        ORDER BY v.date_echeance ASC
        LIMIT 5
    ");
}, []);

include 'header.php';
?>

<style>
:root {
    --couleur-user-primaire: <?php echo $couleur_primaire; ?>;
    --couleur-user-secondaire: <?php echo $couleur_secondaire; ?>;
}

.stat-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid #e9ecef;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    background: white;
    position: relative;
    cursor: pointer;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--couleur-user-primaire);
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    border-color: var(--couleur-user-primaire);
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stat-card:hover::before {
    transform: scaleY(1);
}

.stat-icon {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--couleur-user-primaire) 0%, var(--couleur-user-secondaire) 100%);
    color: white;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1) rotate(-5deg);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.stat-value {
    font-size: 2.6rem;
    font-weight: 800;
    line-height: 1.1;
    margin: 14px 0 8px;
    background: linear-gradient(135deg, var(--couleur-user-primaire) 0%, var(--couleur-user-secondaire) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    letter-spacing: -1px;
}

.stat-label {
    color: #6c757d;
    font-size: 0.95rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stat-detail {
    font-size: 0.85rem;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stat-detail strong {
    font-size: 0.9rem;
}

.welcome-banner {
    background: white;
    border-radius: 20px;
    padding: 0;
    margin-bottom: 1.2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    border: 2px solid #e9ecef;
}

.welcome-banner-gradient {
    background: linear-gradient(135deg, var(--couleur-user-primaire) 0%, var(--couleur-user-secondaire) 100%);
    padding: 1.5rem 2.5rem;
    position: relative;
    overflow: hidden;
}

.welcome-banner-gradient::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 80%;
    height: 200%;
    background: rgba(255, 255, 255, 0.1);
    transform: rotate(15deg);
    pointer-events: none;
}

.welcome-banner-gradient > * {
    position: relative;
    z-index: 1;
}

.welcome-banner-stats {
    background: #f8f9fa;
    padding: 1rem 2.5rem;
    border-top: 2px solid #e9ecef;
}

.welcome-banner h1 {
    position: relative;
    z-index: 1;
}

.welcome-banner .btn {
    position: relative;
    z-index: 2;
}

.action-menu {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    margin-bottom: 2rem;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 12px;
    border: 2px solid #e9ecef;
    background: white;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    color: #495057;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.action-btn:hover {
    border-color: var(--couleur-user-primaire);
    background: linear-gradient(135deg, var(--couleur-user-primaire) 0%, var(--couleur-user-secondaire) 100%);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.action-btn-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    transition: all 0.3s ease;
}

.action-btn:hover .action-btn-icon {
    background: rgba(255,255,255,0.2);
    transform: scale(1.1) rotate(5deg);
}

.chart-card {
    border-radius: 16px;
    border: none;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.3s ease;
}

.chart-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
}


.table-hover tbody tr {
    transition: all 0.2s ease;
}

.table-hover tbody tr:hover {
    background-color: rgba(var(--tblr-primary-rgb), 0.04);
    transform: scale(1.002);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animated-card {
    animation: fadeInUp 0.6s ease-out;
}

.animated-card:nth-child(1) { animation-delay: 0.1s; }
.animated-card:nth-child(2) { animation-delay: 0.2s; }
.animated-card:nth-child(3) { animation-delay: 0.3s; }
.animated-card:nth-child(4) { animation-delay: 0.4s; }

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<div class="page-wrapper">
    <div class="container-xl">
        <!-- Bannière de bienvenue moderne -->
        <div class="welcome-banner" style="animation: slideDown 0.6s ease-out;">
            <!-- Section gradient avec informations utilisateur -->
            <div class="welcome-banner-gradient">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1.25rem; border: 3px solid rgba(255,255,255,0.3);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" stroke-width="2" stroke="white" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="7" r="4"/>
                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                </svg>
                            </div>
                            <div>
                                <h1 style="font-size: 1.75rem; font-weight: 700; margin: 0; color: white; text-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                    Bienvenue, <?php echo e($user_data['nom_complet']); ?>
                                </h1>
                                <div class="mt-2">
                                    <span class="badge" style="background: rgba(255,255,255,0.25); color: white; padding: 5px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; margin-right: 10px;">
                                        <?php echo $is_admin ? 'Administrateur' : 'Vendeur'; ?>
                                    </span>
                                    <span style="color: rgba(255,255,255,0.9); font-size: 0.95rem; font-weight: 500;">
                                        <?php echo e($nom_boutique); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="vente.php" class="btn btn-light btn-lg d-inline-flex align-items-center" style="font-weight: 600; text-decoration: none; padding: 12px 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease; border: none;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 18px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';">
                            <svg xmlns="http://www.w3.org/2000/svg" class="me-2" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="6" cy="19" r="2" />
                                <circle cx="17" cy="19" r="2" />
                                <path d="M17 17h-11v-14h-2" />
                                <path d="M6 5l14 1l-1 7h-13" />
                            </svg>
                            Nouvelle vente
                            <svg xmlns="http://www.w3.org/2000/svg" class="ms-2" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <polyline points="9 6 15 12 9 18" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Section statistiques rapides avec fond clair -->
            <div class="welcome-banner-stats">
                <div class="row align-items-center text-center">
                    <div class="col-md-3 col-6 mb-2 mb-md-0">
                        <div class="d-flex align-items-center justify-content-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="var(--couleur-user-primaire)" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <rect x="4" y="5" width="16" height="16" rx="2" />
                                <line x1="16" y1="3" x2="16" y2="7" />
                                <line x1="8" y1="3" x2="8" y2="7" />
                                <line x1="4" y1="11" x2="20" y2="11" />
                            </svg>
                            <span style="color: #6c757d; font-size: 0.9rem;">
                                <?php
                                $jours_fr = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                                $mois_fr = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                                echo $jours_fr[date('w')] . ' ' . date('j') . ' ' . $mois_fr[date('n')];
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2 mb-md-0">
                        <div class="d-flex align-items-center justify-content-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="var(--couleur-user-primaire)" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="6" cy="19" r="2" />
                                <circle cx="17" cy="19" r="2" />
                                <path d="M17 17h-11v-14h-2" />
                                <path d="M6 5l14 1l-1 7h-13" />
                            </svg>
                            <span style="color: #6c757d; font-size: 0.9rem;">
                                <strong style="color: var(--couleur-user-primaire);"><?php echo number_format($stats_jour['nombre_ventes']); ?></strong> ventes aujourd'hui
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="d-flex align-items-center justify-content-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="var(--couleur-user-primaire)" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2" />
                                <path d="M12 3v3m0 12v3" />
                            </svg>
                            <span style="color: #6c757d; font-size: 0.9rem;">
                                <strong style="color: var(--couleur-user-primaire);"><?php echo format_montant($stats_jour['chiffre_affaires']); ?></strong>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="d-flex align-items-center justify-content-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="var(--couleur-user-primaire)" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <polyline points="12 3 20 7.5 20 16.5 12 21 4 16.5 4 7.5 12 3" />
                                <line x1="12" y1="12" x2="20" y2="7.5" />
                                <line x1="12" y1="12" x2="12" y2="21" />
                                <line x1="12" y1="12" x2="4" y2="7.5" />
                            </svg>
                            <span style="color: #6c757d; font-size: 0.9rem;">
                                <strong style="color: var(--couleur-user-primaire);"><?php echo number_format($stats_stock['total_produits']); ?></strong> produits
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu d'actions rapides -->
        <div class="action-menu">
            <h3 class="mb-3" style="font-size: 1.1rem; font-weight: 700; color: #1e293b;">Actions rapides</h3>
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <a href="vente.php" class="action-btn w-100">
                        <div class="action-btn-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="6" cy="19" r="2" /><circle cx="17" cy="19" r="2" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
                        </div>
                        <span>Nouvelle vente</span>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="mes_ventes.php" class="action-btn w-100">
                        <div class="action-btn-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        </div>
                        <span>Mes Ventes</span>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="profil.php" class="action-btn w-100">
                        <div class="action-btn-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="4"></circle><path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855"></path></svg>
                        </div>
                        <span>Mon Profil</span>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="rapports.php" class="action-btn w-100">
                        <div class="action-btn-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M9 8m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M15 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /></svg>
                        </div>
                        <span>Rapports</span>
                    </a>
                </div>
            </div>
            <?php if ($is_admin): ?>
            <div class="row g-3 mt-2">
                <div class="col-md-3 col-sm-6">
                    <a href="listes.php?page=categories" class="action-btn w-100">
                        <div class="action-btn-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="4" y="4" width="6" height="6" rx="1" /><rect x="14" y="4" width="6" height="6" rx="1" /><rect x="4" y="14" width="6" height="6" rx="1" /><rect x="14" y="14" width="6" height="6" rx="1" /></svg>
                        </div>
                        <span>Catégories</span>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="listes.php?page=utilisateurs" class="action-btn w-100">
                        <div class="action-btn-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 11l2 2l4 -4" /></svg>
                        </div>
                        <span>Utilisateurs</span>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="listes.php?page=mouvements" class="action-btn w-100">
                        <div class="action-btn-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 11 12 14 20 6" /><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9" /></svg>
                        </div>
                        <span>Mouvements stock</span>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6">
                    <a href="notification.php" class="action-btn w-100">
                        <div class="action-btn-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" /><path d="M9 17v1a3 3 0 0 0 6 0v-1" /></svg>
                        </div>
                        <span>Notifications</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Cartes de statistiques principales -->
        <div class="row row-deck row-cards mb-4">
            <!-- Ventes aujourd'hui -->
            <div class="col-sm-6 col-lg-3 animated-card">
                <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="top" title="Nombre total de ventes effectuées aujourd'hui avec le chiffre d'affaires généré">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start mb-3">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="6" cy="19" r="2" /><circle cx="17" cy="19" r="2" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
                            </div>
                        </div>
                        <div class="stat-label">Ventes du jour</div>
                        <div class="stat-value"><?php echo number_format($stats_jour['nombre_ventes']); ?></div>
                        <div class="stat-detail">
                            <strong style="font-size: 0.85rem;"><?php echo format_montant($stats_jour['chiffre_affaires']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ventes ce mois -->
            <div class="col-sm-6 col-lg-3 animated-card">
                <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="top" title="Nombre total de ventes effectuées ce mois avec le chiffre d'affaires cumulé">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start mb-3">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="3" y1="21" x2="21" y2="21" /><path d="M3 10l6 -6l4 4l4 -4l4 4v11h-18z" /></svg>
                            </div>
                        </div>
                        <div class="stat-label">Ventes du mois</div>
                        <div class="stat-value"><?php echo number_format($stats_mois['nombre_ventes']); ?></div>
                        <div class="stat-detail">
                            <strong style="font-size: 0.85rem;"><?php echo format_montant($stats_mois['chiffre_affaires']); ?></strong>
                            <span class="text-muted ms-1">· moy. <?php echo format_montant($stats_mois['nombre_ventes'] > 0 ? $stats_mois['chiffre_affaires'] / $stats_mois['nombre_ventes'] : 0); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($is_admin): ?>
            <!-- Bénéfice aujourd'hui -->
            <div class="col-sm-6 col-lg-3 animated-card">
                <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="top" title="Bénéfice net réalisé aujourd'hui (différence entre prix de vente et prix d'achat)">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start mb-3">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2" /><path d="M12 3v3m0 12v3" /></svg>
                            </div>
                        </div>
                        <div class="stat-label">Bénéfice jour <span class="badge bg-teal ms-2">Admin</span></div>
                        <div class="stat-value" style="font-size: 1.8rem;"><?php echo format_montant($benefices_jour); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Bénéfice ce mois -->
            <div class="col-sm-6 col-lg-3 animated-card">
                <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="top" title="Bénéfice net cumulé du mois en cours">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start mb-3">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2" /><path d="M12 3v3m0 12v3" /></svg>
                            </div>
                        </div>
                        <div class="stat-label">Bénéfice mois <span class="badge bg-cyan ms-2">Admin</span></div>
                        <div class="stat-value" style="font-size: 1.8rem;"><?php echo format_montant($benefices_mois); ?></div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Produits en stock -->
            <div class="col-sm-6 col-lg-3 animated-card">
                <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="top" title="Nombre total de produits différents en stock avec la quantité totale disponible">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start mb-3">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="12 3 20 7.5 20 16.5 12 21 4 16.5 4 7.5 12 3" /><line x1="12" y1="12" x2="20" y2="7.5" /><line x1="12" y1="12" x2="12" y2="21" /><line x1="12" y1="12" x2="4" y2="7.5" /></svg>
                            </div>
                        </div>
                        <div class="stat-label">Produits</div>
                        <div class="stat-value"><?php echo number_format($stats_stock['total_produits']); ?></div>
                        <div class="stat-detail">
                            <?php echo number_format($stats_stock['quantite_totale']); ?> unités
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Valeur du stock -->
            <div class="col-sm-6 col-lg-3 animated-card">
                <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="top" title="Valeur totale du stock actuel basée sur les prix d'achat">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start mb-3">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="7" y="9" width="14" height="10" rx="2" /><circle cx="14" cy="14" r="2" /><path d="M17 9v-2a2 2 0 0 0 -2 -2h-10a2 2 0 0 0 -2 2v6a2 2 0 0 0 2 2h2" /></svg>
                            </div>
                        </div>
                        <div class="stat-label">Valeur stock</div>
                        <div class="stat-value" style="font-size: 1.8rem;"><?php echo format_montant($stats_stock['valeur_stock']); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Crédit en cours -->
        <?php if ($stats_credit['nb_credits'] > 0 || $stats_credit['solde_restant'] > 0): ?>
        <div class="row row-deck row-cards mb-4">
            <div class="col-md-6 col-lg-3">
                <a href="listes.php?page=credits" class="text-decoration-none">
                <div class="stat-card" style="background: white; border: 2px solid #fde8e8; border-radius: 14px; padding: 1.5rem; cursor: pointer;" data-bs-toggle="tooltip" data-bs-placement="top" title="Nombre de ventes à crédit avec solde restant à recouvrer">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="width: 60px; height: 60px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 14px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" stroke-width="2" stroke="white" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/></svg>
                        </div>
                        <div class="ms-3">
                            <small style="color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Crédits actifs</small>
                            <div style="font-size: 1.4rem; font-weight: 800; color: #ef4444; margin-top: 5px;">
                                <?php echo number_format($stats_credit['nb_credits']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="listes.php?page=credits" class="text-decoration-none">
                <div class="stat-card" style="background: white; border: 2px solid #fde8e8; border-radius: 14px; padding: 1.5rem; cursor: pointer;" data-bs-toggle="tooltip" data-bs-placement="top" title="Solde total restant à recouvrer sur les ventes à crédit">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="width: 60px; height: 60px; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); border-radius: 14px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" stroke-width="2" stroke="white" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2"/><path d="M12 3v3m0 12v3"/></svg>
                        </div>
                        <div class="ms-3">
                            <small style="color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Solde à recouvrer</small>
                            <div style="font-size: 1.1rem; font-weight: 800; color: #f97316; margin-top: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo format_montant($stats_credit['solde_restant']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <?php if ($stats_credit['nb_en_retard'] > 0): ?>
            <div class="col-md-6 col-lg-3">
                <a href="listes.php?page=credits&filtre=en_retard" class="text-decoration-none">
                <div class="stat-card" style="background: white; border: 2px solid #fde8e8; border-radius: 14px; padding: 1.5rem; cursor: pointer;" data-bs-toggle="tooltip" data-bs-placement="top" title="Créances dont la date d'échéance est dépassée">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="width: 60px; height: 60px; background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border-radius: 14px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" stroke-width="2" stroke="white" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>
                        </div>
                        <div class="ms-3">
                            <small style="color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">En retard</small>
                            <div style="font-size: 1.4rem; font-weight: 800; color: #dc2626; margin-top: 5px;">
                                <?php echo number_format($stats_credit['nb_en_retard']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="row row-deck row-cards mb-4">
            <div class="col-lg-9">
                <div class="chart-card" style="background: #f8f9fa; border: 2px solid #e9ecef;" data-bs-toggle="tooltip" data-bs-placement="top" title="Suivi quotidien du chiffre d'affaires sur une semaine pour visualiser la tendance">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--couleur-user-primaire) 0%, var(--couleur-user-secondaire) 100%); color: white; border: none;">
                        <h3 class="card-title mb-0 d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="19" x2="20" y2="19" /><polyline points="4 15 8 9 12 11 16 6 20 10" /></svg>
                            Évolution du CA (7 derniers jours)
                        </h3>
                    </div>
                    <div class="card-body" style="background: white;">
                        <canvas id="chartCA" style="height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="chart-card" style="background: #f8f9fa; border: 2px solid #e9ecef;" data-bs-toggle="tooltip" data-bs-placement="top" title="Distribution des ventes par mode de paiement utilisé ce mois">
                    <div class="card-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
                        <h3 class="card-title mb-0 d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /><path d="M14.5 9a3.5 4 0 1 0 0 6" /></svg>
                            Modes de paiement
                        </h3>
                    </div>
                    <div class="card-body" style="background: white;">
                        <canvas id="chartRepartition" style="height: 180px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards">
            <!-- Dernières ventes -->
            <div class="col-lg-8">
                <div class="chart-card" style="background: #f8f9fa; border: 2px solid #e9ecef;">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--couleur-user-primaire) 0%, var(--couleur-user-secondaire) 100%); color: white; border: none;">
                        <h3 class="card-title mb-0 d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 11 12 14 20 6" /><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9" /></svg>
                            Dernières ventes
                        </h3>
                    </div>
                    <div class="card-body p-0" style="background: white;">
                        <div class="table-responsive">
                            <table class="table table-hover card-table table-vcenter mb-0">
                                <thead>
                                    <tr>
                                        <th>N° Facture</th>
                                        <th>Client</th>
                                        <th>Vendeur</th>
                                        <th class="text-end">Montant</th>
                                        <th>Date</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($dernieres_ventes)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /><line x1="9" y1="10" x2="9.01" y2="10" /><line x1="15" y1="10" x2="15.01" y2="10" /><path d="M9.5 15.25a3.5 3.5 0 0 1 5 0" /></svg>
                                                <div>Aucune vente enregistrée</div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($dernieres_ventes as $vente): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge fw-bold" style="background-color: <?php echo $couleur_primaire; ?>; color: white; font-size: 0.75rem;"><?php echo e($vente['numero_facture']); ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm me-2" style="background: linear-gradient(135deg, var(--couleur-user-primaire) 0%, var(--couleur-user-secondaire) 100%); color: white;">
                                                            <?php echo strtoupper(substr($vente['nom_client'] ?: 'C', 0, 1)); ?>
                                                        </div>
                                                        <small><?php echo e($vente['nom_client'] ?: 'Client Comptoir'); ?></small>
                                                    </div>
                                                </td>
                                                <td class="text-muted"><small><?php echo e($vente['vendeur']); ?></small></td>
                                                <td class="text-end">
                                                    <strong style="font-size: 0.9rem;"><?php echo format_montant($vente['montant_total']); ?></strong>
                                                </td>
                                                <td>
                                                    <div class="text-muted" style="font-size: 0.85rem;">
                                                        <?php echo date('d/m/Y', strtotime($vente['date_vente'])); ?>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <a href="facture_impression.php?id=<?php echo $vente['id_vente']; ?>" class="btn btn-sm btn-icon btn-ghost-info" target="_blank" title="Imprimer la facture">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" /><path d="M9 5h6" /><path d="M9 19h6" /><rect x="17" y="13" width="6" height="4" rx="1" /></svg>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if (!empty($dernieres_ventes)): ?>
                        <div class="card-footer text-center">
                            <a href="mes_ventes.php" class="btn btn-link">
                                Voir toutes les ventes
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 6 15 12 9 18" /></svg>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Produits en alerte + Top produits -->
            <div class="col-lg-4">
                <!-- Alertes de stock -->
                <div class="chart-card mb-3" style="background: #f8f9fa; border: 2px solid #e9ecef;">
                    <div class="card-header bg-danger" style="color: white; border: none;">
                        <h3 class="card-title mb-0 d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01" /><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75" /></svg>
                            Alertes stock
                        </h3>
                    </div>
                    <div class="card-body p-0" style="background: white;">
                        <?php if (empty($produits_alerte)): ?>
                            <div class="text-center text-muted py-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon text-success mb-2" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 11 12 14 20 6" /><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9" /></svg>
                                <div>Aucun produit en alerte</div>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($produits_alerte as $produit): ?>
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col">
                                                <strong><?php echo e($produit['nom_produit']); ?></strong>
                                                <div class="text-muted small mt-1">
                                                    Seuil : <?php echo $produit['seuil_alerte']; ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <?php if ($produit['niveau_alerte'] == 'rupture'): ?>
                                                <span class="badge bg-danger" style="font-size: 0.85rem; padding: 8px 14px;">
                                                    <?php echo $produit['quantite_stock']; ?>
                                                </span>
                                                <?php elseif ($produit['niveau_alerte'] == 'critique'): ?>
                                                <span class="badge bg-orange" style="font-size: 0.85rem; padding: 8px 14px;">
                                                    <?php echo $produit['quantite_stock']; ?>
                                                </span>
                                                <?php else: ?>
                                                <span class="badge bg-yellow" style="font-size: 0.85rem; padding: 8px 14px;">
                                                    <?php echo $produit['quantite_stock']; ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top produits -->
                <div class="chart-card" style="background: #f8f9fa; border: 2px solid #e9ecef;">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--couleur-user-primaire) 0%, var(--couleur-user-secondaire) 100%); color: white; border: none;">
                        <h3 class="card-title mb-0 d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6l-8 4l8 4l8 -4l-8 -4" /><path d="M4 14l8 4l8 -4" /></svg>
                            Top produits du mois
                        </h3>
                    </div>
                    <div class="card-body p-0" style="background: white;">
                        <?php if (empty($top_produits)): ?>
                            <div class="text-center text-muted py-4">
                                Aucune vente ce mois
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($top_produits as $index => $produit): ?>
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="avatar" style="background: linear-gradient(135deg, var(--couleur-user-primaire) 0%, var(--couleur-user-secondaire) 100%); color: white; font-weight: 700;">
                                                    <?php echo ($index + 1); ?>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <strong><?php echo e($produit['nom_produit']); ?></strong>
                                                <div class="text-muted small mt-1">
                                                    <?php echo number_format($produit['quantite_vendue']); ?> vendus
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="text-end">
                                                    <strong style="color: var(--couleur-user-primaire);">
                                                        <?php echo format_montant($produit['montant_total']); ?>
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Créances en retard -->
        <?php if (!empty($credits_en_retard)): ?>
        <div class="row row-deck row-cards mt-4">
            <div class="col-12">
                <div class="chart-card" style="background: #f8f9fa; border: 2px solid #fde8e8;">
                    <div class="card-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none;">
                        <h3 class="card-title mb-0 d-flex align-items-center justify-content-between">
                            <span class="d-flex align-items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>
                                Créances en retard
                            </span>
                            <a href="listes.php?page=credits" class="btn btn-sm btn-outline-light">Voir tout</a>
                        </h3>
                    </div>
                    <div class="card-body p-0" style="background: white;">
                        <div class="table-responsive">
                            <table class="table table-hover card-table table-vcenter mb-0">
                                <thead>
                                    <tr>
                                        <th>N° Facture</th>
                                        <th>Client</th>
                                        <th class="text-end">Montant total</th>
                                        <th class="text-end">Déjà payé</th>
                                        <th class="text-end">Solde restant</th>
                                        <th>Échéance</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($credits_en_retard as $cr): ?>
                                    <tr>
                                        <td><span class="badge fw-bold" style="background:#ef4444; color:white;"><?php echo e($cr['numero_facture']); ?></span></td>
                                        <td><strong><?php echo e($cr['nom_client'] ?? 'Client'); ?></strong></td>
                                        <td class="text-end"><?php echo format_montant($cr['montant_total']); ?></td>
                                        <td class="text-end text-success"><?php echo format_montant($cr['montant_paye']); ?></td>
                                        <td class="text-end fw-bold text-danger"><?php echo format_montant($cr['solde_restant']); ?></td>
                                        <td>
                                            <span class="badge bg-danger-lt text-danger">
                                                <?php echo date('d/m/Y', strtotime($cr['date_echeance'])); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="listes.php?page=credits" class="btn btn-sm btn-danger">Payer</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Charger Chart.js depuis CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Couleurs du thème
    const couleurPrimaire = '<?php echo $couleur_primaire; ?>';
    const couleurSecondaire = '<?php echo $couleur_secondaire; ?>';
    
    // Configuration commune pour tous les graphiques
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.padding = 15;
    
    // ========================================
    // GRAPHIQUE 1 : Évolution du CA (7 jours)
    // ========================================
    const ctxCA = document.getElementById('chartCA');
    if (ctxCA) {
        new Chart(ctxCA, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates_ca); ?>,
                datasets: [{
                    label: 'Chiffre d\'affaires',
                    data: <?php echo json_encode($montants_ca); ?>,
                    borderColor: couleurPrimaire,
                    backgroundColor: function(context) {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, couleurPrimaire + '40');
                        gradient.addColorStop(1, couleurPrimaire + '00');
                        return gradient;
                    },
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: couleurPrimaire,
                    pointBorderWidth: 2,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: couleurPrimaire,
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#fff',
                        titleColor: '#333',
                        bodyColor: '#666',
                        borderColor: '#ddd',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return new Intl.NumberFormat('fr-FR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }).format(context.parsed.y) + ' <?php echo $devise; ?>';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('fr-FR', {
                                    notation: 'compact',
                                    compactDisplay: 'short'
                                }).format(value);
                            }
                        },
                        grid: {
                            color: '#f0f0f0',
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }
    
    // ========================================
    // GRAPHIQUE 2 : Répartition des ventes
    // ========================================
    const ctxRepartition = document.getElementById('chartRepartition');
    if (ctxRepartition) {
        <?php
        $modes = [];
        $montants = [];
        $couleurs = [
            'especes' => '#10b981',
            'carte' => '#3b82f6',
            'mobile_money' => '#f59e0b',
            'cheque' => '#8b5cf6',
            'credit' => '#ef4444'
        ];
        
        $labels_modes = [
            'especes' => 'Espèces',
            'carte' => 'Carte Bancaire',
            'mobile_money' => 'Mobile Money',
            'cheque' => 'Chèque',
            'credit' => 'Crédit'
        ];
        
        foreach ($repartition_paiement as $repartition) {
            $mode = $repartition['mode_paiement'];
            $modes[] = $labels_modes[$mode] ?? ucfirst($mode);
            $montants[] = $repartition['total'];
        }
        ?>
        
        new Chart(ctxRepartition, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($modes); ?>,
                datasets: [{
                    data: <?php echo json_encode($montants); ?>,
                    backgroundColor: [
                        '#10b981',
                        '#3b82f6',
                        '#f59e0b',
                        '#8b5cf6',
                        '#ef4444'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#fff',
                        titleColor: '#333',
                        bodyColor: '#666',
                        borderColor: '#ddd',
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = new Intl.NumberFormat('fr-FR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }).format(context.parsed);
                                
                                // Calculer le pourcentage
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                
                                return label + ': ' + value + ' <?php echo $devise; ?> (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Initialiser les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'footer.php'; ?>
