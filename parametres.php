<?php
/**
 * PAGE PARAMÈTRES - STORE SUITE
 * Configuration du système (Admin uniquement)
 */
require_once 'protection_pages.php';
require_admin(); // Vérifier que c'est un admin

$page_title = 'Paramètres du Système';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    $success = '';
    
    try {
        $nom_boutique = trim($_POST['nom_boutique'] ?? '');
        $devise = trim($_POST['devise'] ?? '');
        $couleur_primaire = trim($_POST['couleur_primaire'] ?? '');
        $couleur_secondaire = trim($_POST['couleur_secondaire'] ?? '');
        $adresse = trim($_POST['adresse_boutique'] ?? '');
        $telephone = trim($_POST['telephone_boutique'] ?? '');
        $email = trim($_POST['email_boutique'] ?? '');
        $site_web = trim($_POST['site_web_boutique'] ?? '');
        $taux_tva = max(0, min(100, (float)($_POST['taux_tva'] ?? 0)));
        $atelier_mode_val = isset($_POST['atelier_mode']) ? 1 : 0;
        $smtp_host      = trim($_POST['smtp_host'] ?? '');
        $smtp_port      = (int)($_POST['smtp_port'] ?? 587);
        $smtp_secure    = in_array($_POST['smtp_secure'] ?? '', ['tls','ssl','']) ? $_POST['smtp_secure'] : 'tls';
        $smtp_user      = trim($_POST['smtp_user'] ?? '');
        $smtp_pass      = trim($_POST['smtp_pass'] ?? '');
        $smtp_from      = trim($_POST['smtp_from'] ?? '');
        $smtp_from_name = trim($_POST['smtp_from_name'] ?? '');
        $email_bilan    = trim($_POST['email_bilan'] ?? '');
        $bilan_actif    = isset($_POST['bilan_actif']) ? 1 : 0;
        
        if (empty($nom_boutique)) {
            throw new Exception('Le nom de la boutique est obligatoire');
        }
        
        if (empty($devise)) {
            throw new Exception('La devise est obligatoire');
        }
        
        // Traitement du logo (si présent)
        $logo = $config['logo'] ?? '';
        if (!empty($_POST['logo_cropped_data'])) {
            // Décoder l'image base64
            $logoData = $_POST['logo_cropped_data'];
            if (strpos($logoData, 'data:image') === 0) {
                $logoData = substr($logoData, strpos($logoData, ',') + 1);
                $logoData = base64_decode($logoData);
                
                // Générer un nom standardisé
                $logoFilename = 'logo_boutique.png';
                $logoPath = __DIR__ . '/uploads/logos/' . $logoFilename;
                
                // Créer le dossier si nécessaire
                if (!is_dir(__DIR__ . '/uploads/logos')) {
                    mkdir(__DIR__ . '/uploads/logos', 0755, true);
                }
                
                // Sauvegarder le fichier
                if (file_put_contents($logoPath, $logoData)) {
                    $logo = 'uploads/logos/' . $logoFilename;
                }
            }
        }
        
        // Mise à jour de la configuration
        $sql = "UPDATE configuration SET
                nom_boutique = ?,
                devise = ?,
                couleur_primaire = ?,
                couleur_secondaire = ?,
                adresse = ?,
                telephone = ?,
                email = ?,
                site_web = ?,
                taux_tva = ?,
                logo = ?,
                atelier_mode = ?,
                smtp_host = ?,
                smtp_port = ?,
                smtp_secure = ?,
                smtp_user = ?,
                smtp_pass = ?,
                smtp_from = ?,
                smtp_from_name = ?,
                email_bilan = ?,
                bilan_actif = ?
                WHERE id_config = 1";

        db_execute($sql, [
            $nom_boutique, $devise, $couleur_primaire, $couleur_secondaire,
            $adresse, $telephone, $email, $site_web, $taux_tva, $logo,
            $atelier_mode_val,
            $smtp_host, $smtp_port, $smtp_secure, $smtp_user, $smtp_pass,
            $smtp_from, $smtp_from_name, $email_bilan, $bilan_actif
        ]);
        
        $success = 'Paramètres mis à jour avec succès. Actualisez la page pour voir les changements.';
        
        // Recharger la config
        $config = get_system_config();
        $nom_boutique = $config['nom_boutique'];
        $devise = $config['devise'];
        $taux_tva = isset($config['taux_tva']) ? (float)$config['taux_tva'] : 0.00;
        $couleur_primaire = $config['couleur_primaire'];
        $couleur_secondaire = $config['couleur_secondaire'];
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer les statistiques système
$stats_systeme = db_fetch_one("
    SELECT 
        (SELECT COUNT(*) FROM produits WHERE est_actif = 1) as nb_produits,
        (SELECT COUNT(*) FROM categories WHERE est_actif = 1) as nb_categories,
        (SELECT COUNT(*) FROM clients WHERE est_actif = 1) as nb_clients,
        (SELECT COUNT(*) FROM utilisateurs WHERE est_actif = 1) as nb_utilisateurs,
        (SELECT COUNT(*) FROM ventes WHERE statut = 'validee') as nb_ventes,
        (SELECT SUM(quantite_stock) FROM produits WHERE est_actif = 1) as stock_total
");

include 'header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css"/>

<style>
/* Styles pour le crop d'image */
#cropModal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
}

#cropModal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.crop-container {
    max-width: 90%;
    max-height: 90vh;
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.crop-container h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: <?php echo $couleur_primaire; ?>;
}

#cropImage {
    max-width: 100%;
    max-height: 60vh;
    display: block;
}

.crop-buttons {
    margin-top: 15px;
    text-align: right;
}

.logo-preview {
    max-width: 200px;
    max-height: 200px;
    margin-top: 10px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 5px;
}
</style>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    Paramètres du Système
                </h2>
                <div class="text-muted mt-1">Configuration générale de la boutique</div>
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

    <?php if (isset($error) && $error): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <div class="d-flex">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>
            </div>
            <div><?php echo e($error); ?></div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert"></a>
    </div>
    <?php endif; ?>

    <?php if (isset($success) && $success): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <div class="d-flex">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /><path d="M9 12l2 2l4 -4" /></svg>
            </div>
            <div><?php echo e($success); ?></div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert"></a>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-header" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); color: white;">
                    <h3 class="card-title mb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="19" x2="20" y2="19"/><polyline points="4 15 8 9 12 11 16 6 20 10"/></svg>
                        Statistiques du système
                    </h3>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); color: white;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12l0 9"/><path d="M12 12l-8 -4.5"/></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="text-muted small text-uppercase">Produits actifs</div>
                                <strong class="fs-2" style="color: <?php echo $couleur_primaire; ?>;"><?php echo number_format($stats_systeme['nb_produits']); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg bg-success-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="text-muted small text-uppercase">Clients</div>
                                <strong class="fs-2 text-success"><?php echo number_format($stats_systeme['nb_clients']); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg bg-info-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon text-info" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="6" cy="19" r="2"/><circle cx="17" cy="19" r="2"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="text-muted small text-uppercase">Ventes totales</div>
                                <strong class="fs-2 text-info"><?php echo number_format($stats_systeme['nb_ventes']); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg bg-warning-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon text-warning" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="4" width="18" height="4" rx="2"/><path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-10"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="text-muted small text-uppercase">Stock total</div>
                                <strong class="fs-2 text-warning"><?php echo number_format($stats_systeme['stock_total']); ?></strong>
                                <small class="text-muted d-block">unités</small>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg bg-primary-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon text-primary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><line x1="19" y1="7" x2="19" y2="10"/><line x1="19" y1="14" x2="19" y2="14.01"/></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="text-muted small text-uppercase">Utilisateurs</div>
                                <strong class="fs-2 text-primary"><?php echo number_format($stats_systeme['nb_utilisateurs']); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="text-center">
                        <small class="text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 15"/></svg>
                            Dernière mise à jour : <?php echo date('d/m/Y H:i'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Configuration de la boutique</h3>
                </div>
                <div class="card-body">
                    <form method="post" id="configForm">
                        <h4 class="mb-3">Informations générales</h4>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">
                                    <span class="text-danger">*</span> Nom de la boutique
                                    <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Ce nom apparaît partout dans l'application : en-tête, factures, rapports et écran de chargement">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                    </span>
                                </label>
                                <input type="text" class="form-control" name="nom_boutique" value="<?php echo e($nom_boutique); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    <span class="text-danger">*</span> Devise
                                    <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Monnaie utilisée pour toutes les transactions, prix et factures">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                    </span>
                                </label>
                                <select class="form-select" name="devise" required>
                                    <option value="XOF" <?php echo $devise === 'XOF' ? 'selected' : ''; ?>>XOF (Franc CFA)</option>

                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">
                                    Taux TVA (%)
                                    <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Taux de TVA appliqué sur les ventes. Mettre 0 pour désactiver la TVA par défaut.">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                    </span>
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="taux_tva"
                                           value="<?php echo isset($config['taux_tva']) ? (float)$config['taux_tva'] : 0; ?>"
                                           min="0" max="100" step="0.01" placeholder="Ex: 18">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Les prix produits sont saisis HT</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Adresse de la boutique
                                <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Votre adresse sera visible sur les factures et reçus">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                </span>
                            </label>
                            <input type="text" class="form-control" name="adresse_boutique" value="<?php echo isset($config['adresse_boutique']) ? e($config['adresse_boutique']) : ''; ?>" placeholder="Ex: 123 Avenue Lumumba, Kinshasa">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Numéro de téléphone
                                    <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Votre numéro sera visible sur toutes les factures et reçus pour que les clients puissent vous contacter">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                    </span>
                                </label>
                                <input type="tel" class="form-control" name="telephone_boutique" value="<?php echo isset($config['telephone_boutique']) ? e($config['telephone_boutique']) : ''; ?>" placeholder="+226 XXX XXX XXX">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Adresse email
                                    <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Email de contact affiché sur les factures">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                    </span>
                                </label>
                                <input type="email" class="form-control" name="email_boutique" value="<?php echo isset($config['email']) ? e($config['email']) : ''; ?>" placeholder="contact@boutique.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Site web
                                    <span class="text-muted ms-1" data-bs-toggle="tooltip" title="URL affichée dans le pied de page">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                    </span>
                                </label>
                                <input type="url" class="form-control" name="site_web_boutique" value="<?php echo isset($config['site_web']) ? e($config['site_web']) : ''; ?>" placeholder="https://www.votreboutique.com">
                            </div>
                        </div>

                        <hr class="my-4">
                        <h4 class="mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><polyline points="7 9 12 4 17 9"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                            Logo du système
                            <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Logo affiché dans l'en-tête, l'écran de chargement et sur les factures imprimées">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                            </span>
                        </h4>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <?php 
                                $logo_actuel = isset($config['logo']) ? $config['logo'] : '';
                                $logo_existe = !empty($logo_actuel) && file_exists(__DIR__ . '/' . $logo_actuel);
                                ?>
                                <?php if ($logo_existe): ?>
                                <div class="card">
                                    <div class="card-body text-center p-4">
                                        <img src="<?php echo BASE_URL . e($logo_actuel); ?>" alt="Logo actuel" class="img-fluid mb-2" style="max-height: 100px;">
                                        <div class="text-muted small">Logo actuel</div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="card">
                                    <div class="card-body text-center p-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted mb-2" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z"/>
                                            <path d="M9 11v-5a3 3 0 0 1 6 0v5"/>
                                        </svg>
                                        <div class="text-muted small">Aucun logo</div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">
                                    Uploader un nouveau logo
                                    <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Sélectionnez une image puis recadrez-la avant d'enregistrer">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                    </span>
                                </label>
                                <input type="file" class="form-control" id="logoInput" accept="image/*" onchange="selectLogo(this)">
                                <input type="hidden" name="logo_cropped_data" id="logoCroppedData">
                                <small class="text-muted d-block">Formats acceptés : JPG, PNG, GIF (max 5MB). Vous pourrez recadrer l'image.</small>
                                <div id="logoPreviewContainer" style="display:none; margin-top:10px;">
                                    <img id="logoPreview" class="logo-preview" alt="Aperçu du logo">
                                    <button type="button" class="btn btn-sm btn-warning mt-2" onclick="reopenCrop()">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="8" y="8" width="12" height="12" rx="1" /><path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2" /></svg>
                                        Recadrer à nouveau
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h4 class="mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21a9 9 0 1 1 0 -18a9 9 0 0 1 0 18z"/><path d="M3.6 9h16.8"/><path d="M3.6 15h16.8"/><circle cx="12" cy="12" r="9"/></svg>
                            Personnalisation visuelle
                        </h4>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Couleur primaire
                                    <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Couleur principale pour : en-tête du site, menu de navigation, boutons principaux et éléments actifs">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                    </span>
                                </label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="couleur_primaire" id="couleur1" value="<?php echo e($couleur_primaire); ?>" onchange="updateColorText('couleur1')">
                                    <input type="text" class="form-control" id="couleur1_text" value="<?php echo e($couleur_primaire); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Couleur secondaire
                                    <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Couleur secondaire pour : dégradés, survol des boutons, badges et éléments d'accentuation">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                    </span>
                                </label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="couleur_secondaire" id="couleur2" value="<?php echo e($couleur_secondaire); ?>" onchange="updateColorText('couleur2')">
                                    <input type="text" class="form-control" id="couleur2_text" value="<?php echo e($couleur_secondaire); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                            <strong>Note :</strong> Actualisez la page (F5) après la sauvegarde pour voir les changements de couleurs appliqués.
                        </div>

                        <hr class="my-4">
                        <h4 class="mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
                            Modules optionnels
                        </h4>
                        <div class="card border-0 bg-light p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-semibold">Atelier Couture</div>
                                    <small class="text-muted">Active la gestion des commandes de couture, des tailleurs et du suivi des statuts de commandes.</small>
                                </div>
                                <div class="form-check form-switch ms-4">
                                    <input class="form-check-input" type="checkbox" role="switch" name="atelier_mode" id="atelier_mode"
                                           <?php echo !empty($config['atelier_mode']) ? 'checked' : ''; ?> style="width:2.5em;height:1.4em;">
                                    <label class="form-check-label ms-2" for="atelier_mode">
                                        <?php echo !empty($config['atelier_mode']) ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>'; ?>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                                Enregistrer les paramètres
                            </button>
                            <span class="text-muted ms-3"><span class="text-danger">*</span> Champs obligatoires</span>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Section Bilan Email -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-primary" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/></svg>
                            Bilan journalier par email
                        </h3>
                        <div class="card-options">
                            <span class="badge <?php echo !empty($config['bilan_actif']) ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo !empty($config['bilan_actif']) ? 'Actif' : 'Inactif'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="bilan_actif" id="bilan_actif"
                                           <?php echo !empty($config['bilan_actif']) ? 'checked' : ''; ?> style="width:2.5em;height:1.4em;">
                                    <label class="form-check-label ms-2 fw-semibold" for="bilan_actif">
                                        Activer l'envoi automatique du bilan à 20h00
                                    </label>
                                </div>
                                <small class="text-muted">Le script doit être planifié via le Planificateur de tâches Windows.</small>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Email du destinataire (chef / patron)</label>
                                <input type="email" class="form-control" name="email_bilan"
                                       value="<?php echo e($config['email_bilan'] ?? ''); ?>"
                                       placeholder="chef@exemple.com">
                            </div>
                            <div class="col-12"><hr class="my-1"><p class="text-muted small mb-2 fw-semibold">Configuration SMTP</p></div>
                            <div class="col-md-5">
                                <label class="form-label">Hôte SMTP</label>
                                <input type="text" class="form-control" name="smtp_host"
                                       value="<?php echo e($config['smtp_host'] ?? ''); ?>"
                                       placeholder="mail.mondomaine.com">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Port</label>
                                <input type="number" class="form-control" name="smtp_port"
                                       value="<?php echo e($config['smtp_port'] ?? '587'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Chiffrement</label>
                                <select class="form-select" name="smtp_secure">
                                    <option value="tls"  <?php echo ($config['smtp_secure'] ?? 'tls') === 'tls'  ? 'selected' : ''; ?>>TLS (port 587)</option>
                                    <option value="ssl"  <?php echo ($config['smtp_secure'] ?? '') === 'ssl'  ? 'selected' : ''; ?>>SSL (port 465)</option>
                                    <option value=""     <?php echo ($config['smtp_secure'] ?? '') === ''    ? 'selected' : ''; ?>>Aucun (port 25)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Utilisateur SMTP</label>
                                <input type="text" class="form-control" name="smtp_user"
                                       value="<?php echo e($config['smtp_user'] ?? ''); ?>"
                                       placeholder="noreply@mondomaine.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mot de passe SMTP</label>
                                <input type="password" class="form-control" name="smtp_pass"
                                       value="<?php echo e($config['smtp_pass'] ?? ''); ?>"
                                       autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nom de l'expéditeur</label>
                                <input type="text" class="form-control" name="smtp_from_name"
                                       value="<?php echo e($config['smtp_from_name'] ?? ''); ?>"
                                       placeholder="<?php echo e($config['nom_boutique'] ?? 'Ma Boutique'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email expéditeur (si différent du login)</label>
                                <input type="email" class="form-control" name="smtp_from"
                                       value="<?php echo e($config['smtp_from'] ?? ''); ?>"
                                       placeholder="Laisser vide = même que login SMTP">
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="testerBilan()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="10" y1="14" x2="21" y2="3"/><path d="M21 3l-6.5 18a0.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a0.55 .55 0 0 1 0 -1l18 -6.5"/></svg>
                                    Envoyer un bilan test maintenant
                                </button>
                                <small class="text-muted ms-2">Sauvegarde d'abord les paramètres avant de tester.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Réinitialisations -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card border-danger shadow-lg">
                    <div class="card-header border-danger bg-gradient" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                        <h3 class="card-title mb-0 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="28" height="28" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <polyline points="3 5 9 5 9 11 3 11 3 5"/>
                                <polyline points="15 13 21 13 21 19 15 19 15 13"/>
                                <path d="M9 5h6 a 2 2 0 0 0 2 -2 a 2 2 0 0 0 -2 -2 h -6 a 2 2 0 0 0 -2 2 a 2 2 0 0 0 2 2"/>
                                <path d="M5 15h6 a 2 2 0 0 0 2 -2 a 2 2 0 0 0 -2 -2 h -6 a 2 2 0 0 0 -2 2 a 2 2 0 0 0 2 2"/>
                            </svg>
                            Zone de Réinitialisation du Système
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-danger border-0 rounded-lg mb-4" style="background-color: #f8d7da;">
                            <div class="d-flex align-items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-3 mt-1 flex-shrink-0" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
                                    <path d="M12 8v4"/>
                                    <path d="M12 16h.01"/>
                                </svg>
                                <div>
                                    <strong>⚠️ ATTENTION !</strong> Les opérations ci-dessous sont <strong>irréversibles</strong>. Une confirmation ET votre mot de passe seront requis. <strong>Aucune sauvegarde ne sera possible</strong> une fois exécutées.
                                </div>
                            </div>
                        </div>

                        <!-- Réinitialisation Ventes -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <div class="card border-1 border-danger h-100 hover-shadow" style="transition: all 0.3s ease;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="badge badge-lg bg-danger me-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <polyline points="3 6 5 4 7 6"/>
                                                    <path d="M5 4v5a8 8 0 0 0 13.95 7m1.3 -4a8 8 0 0 0 -13.95 -7v5"/>
                                                </svg>
                                            </div>
                                            <h5 class="card-title mb-0 text-danger">Supprimer les ventes</h5>
                                        </div>
                                        <p class="text-muted small mb-3">Efface <strong><?php echo e($stats_systeme['nb_ventes'] ?? 0); ?> ventes</strong> de la base de données</p>
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="reinitVentes()">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M4 7l16 0"/>
                                                <path d="M10 11l0 6"/>
                                                <path d="M14 11l0 6"/>
                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                            </svg>
                                            Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Réinitialisation Produits -->
                            <div class="col-md-6 mb-3">
                                <div class="card border-1 border-danger h-100 hover-shadow" style="transition: all 0.3s ease;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="badge badge-lg bg-danger me-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M6 19a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2 h10a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-10z"/>
                                                    <path d="M9 3v-1"/>
                                                    <path d="M15 3v-1"/>
                                                    <line x1="6" y1="8" x2="18" y2="8"/>
                                                </svg>
                                            </div>
                                            <h5 class="card-title mb-0 text-danger">Supprimer produits</h5>
                                        </div>
                                        <p class="text-muted small mb-3">Efface <strong><?php echo e($stats_systeme['nb_produits'] ?? 0); ?> produits</strong> et le stock</p>
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="reinitProduits()">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M4 7l16 0"/>
                                                <path d="M10 11l0 6"/>
                                                <path d="M14 11l0 6"/>
                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                            </svg>
                                            Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Réinitialisation Clients -->
                            <div class="col-md-6 mb-3">
                                <div class="card border-1 border-danger h-100 hover-shadow" style="transition: all 0.3s ease;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="badge badge-lg bg-danger me-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <circle cx="9" cy="7" r="4"/>
                                                    <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                                    <path d="M16 11h6"/>
                                                </svg>
                                            </div>
                                            <h5 class="card-title mb-0 text-danger">Supprimer clients</h5>
                                        </div>
                                        <p class="text-muted small mb-3">Efface <strong><?php echo e($stats_systeme['nb_clients'] ?? 0); ?> clients</strong> (garde client par défaut)</p>
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="reinitClients()">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M4 7l16 0"/>
                                                <path d="M10 11l0 6"/>
                                                <path d="M14 11l0 6"/>
                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                            </svg>
                                            Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Réinitialisation Utilisateurs -->
                            <div class="col-md-6 mb-3">
                                <div class="card border-1 border-danger h-100 hover-shadow" style="transition: all 0.3s ease;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="badge badge-lg bg-danger me-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <circle cx="9" cy="7" r="4"/>
                                                    <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                                    <path d="M16 5a4 4 0 0 1 4 4v2"/>
                                                </svg>
                                            </div>
                                            <h5 class="card-title mb-0 text-danger">Supprimer utilisateurs</h5>
                                        </div>
                                        <p class="text-muted small mb-3">Efface <strong><?php echo e($stats_systeme['nb_utilisateurs'] ?? 0); ?> utilisateurs</strong> (vous êtes gardé)</p>
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="reinitUtilisateurs()">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M4 7l16 0"/>
                                                <path d="M10 11l0 6"/>
                                                <path d="M14 11l0 6"/>
                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                            </svg>
                                            Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Vider Caisse -->
                            <div class="col-md-6 mb-3">
                                <div class="card border-1 border-danger h-100 hover-shadow" style="transition: all 0.3s ease;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="badge badge-lg bg-danger me-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                                                    <path d="M3 10h18"/>
                                                </svg>
                                            </div>
                                            <h5 class="card-title mb-0 text-danger">Vider historique caisse</h5>
                                        </div>
                                        <p class="text-muted small mb-3">Efface toutes les <strong>sessions de caisse</strong> et les mouvements associés</p>
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="reinitCaisse()">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/>
                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                            </svg>
                                            Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Vider Dépenses -->
                            <div class="col-md-6 mb-3">
                                <div class="card border-1 border-danger h-100 hover-shadow" style="transition: all 0.3s ease;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="badge badge-lg bg-danger me-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12"/>
                                                </svg>
                                            </div>
                                            <h5 class="card-title mb-0 text-danger">Supprimer dépenses</h5>
                                        </div>
                                        <p class="text-muted small mb-3">Efface toutes les <strong>dépenses</strong> enregistrées</p>
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="reinitDepenses()">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/>
                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                            </svg>
                                            Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Vider Tailleurs -->
                            <div class="col-md-6 mb-3">
                                <div class="card border-1 border-danger h-100 hover-shadow" style="transition: all 0.3s ease;">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="badge badge-lg bg-danger me-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M3.5 6.5l5 5l5 -5l5 5"/>
                                                    <path d="M8.5 11.5v7h7v-7"/>
                                                </svg>
                                            </div>
                                            <h5 class="card-title mb-0 text-danger">Supprimer tailleurs</h5>
                                        </div>
                                        <p class="text-muted small mb-3">Efface tous les <strong>tailleurs</strong>, commandes atelier et paiements associés</p>
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="reinitTailleurs()">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/>
                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                            </svg>
                                            Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Réinitialisation Complète -->
                            <div class="col-12 mb-3">
                                <div class="card border-2 border-dark h-100" style="background: linear-gradient(135deg, rgba(0,0,0,.05) 0%, rgba(0,0,0,.02) 100%);">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="badge badge-lg bg-dark me-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M12 3a9 9 0 1 0 0 18a9 9 0 0 0 0 -18"/>
                                                    <path d="M12 9a3 3 0 1 0 0 6a3 3 0 0 0 0 -6"/>
                                                </svg>
                                            </div>
                                            <h5 class="card-title mb-0"> RÉINITIALISATION COMPLÈTE</h5>
                                        </div>
                                        <p class="text-muted small mb-3"><strong>DANGER ULTIME !</strong> Efface <strong>TOUT</strong> : ventes, produits, clients, utilisateurs, catégories, paramètres. Le système sera ramené à zéro (état neuf).</p>
                                        <button type="button" class="btn btn-dark w-100" onclick="reinitComplet()">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M3 12a9 9 0 1 0 9 -9a9 9 0 0 0 -9 9"/>
                                                <path d="M3.6 9h16.8"/>
                                                <path d="M3.6 15h16.8"/>
                                            </svg>
                                            RÉINITIALISER LE SYSTÈME
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de crop d'image -->
<div id="cropModal">
    <div class="crop-container">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="8" y="8" width="12" height="12" rx="1" /><path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2" /></svg>
            Recadrer votre logo
        </h3>
        <p class="text-muted">Ajustez la zone de sélection pour recadrer votre logo</p>
        <div style="max-height: 60vh; overflow: hidden;">
            <img id="cropImage" alt="Image à rogner">
        </div>
        <div class="crop-buttons">
            <button type="button" class="btn btn-secondary" onclick="cancelCrop()">Annuler</button>
            <button type="button" class="btn btn-primary" onclick="applyCrop()">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 11 12 14 20 6" /><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9" /></svg>
                Appliquer
            </button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
let cropper = null;
let currentImageFile = null;

// Fonctions de réinitialisation
let currentReinitType = null;

// Modal pour demander le mot de passe
function showPasswordModal(type, message) {
    const html = `
        <div style="text-align: center;">
            <p class="text-danger mb-3"><strong>${message}</strong></p>
            <div class="mb-3">
                <label class="form-label">Entrez votre mot de passe pour confirmer:</label>
                <input type="password" id="reinitPassword" class="form-control" placeholder="Mot de passe..." autofocus>
            </div>
        </div>
    `;
    
    currentReinitType = type;
    
    // Créer un modal custom avec Bootstrap
    const modalId = 'passwordModal_' + Date.now();
    const modalHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1" style="display: none;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirmation de Réinitialisation</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${html}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-danger" onclick="confirmerReinitAvecPassword('${type}', '${modalId}')">
                            Confirmer la réinitialisation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Ajouter le modal au DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
    
    // Permettre Entrée pour valider
    document.getElementById('reinitPassword').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            confirmerReinitAvecPassword(type, modalId);
        }
    });
}

function confirmerReinitAvecPassword(type, modalId) {
    const password = document.getElementById('reinitPassword').value;
    
    if (!password) {
        showAlertModal({
            title: 'Erreur',
            message: 'Le mot de passe est requis',
            type: 'error'
        });
        return;
    }
    
    // Fermer le modal
    bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
    
    // Exécuter la réinitialisation
    executerReinit(type, password);
}

function testerBilan() {
    if (!confirm('Envoyer un bilan test maintenant ?')) return;
    fetch('ajax/envoyer_bilan.php', { method: 'POST' })
        .then(r => r.json())
        .then(d => showModal(d.success ? 'success' : 'error', d.message))
        .catch(() => showModal('error', 'Erreur réseau'));
}

function reinitVentes() {
    showPasswordModal('ventes', 'Supprimer TOUTES les ventes ? Cette action est irréversible.');
}

function reinitProduits() {
    showPasswordModal('produits', 'Supprimer TOUS les produits ? Cette action est irréversible.');
}

function reinitClients() {
    showPasswordModal('clients', 'Supprimer TOUS les clients ? Cette action est irréversible.');
}

function reinitUtilisateurs() {
    showPasswordModal('utilisateurs', 'Supprimer TOUS les utilisateurs ? Cette action est irréversible.');
}

function reinitCaisse() {
    showPasswordModal('caisse', 'Vider TOUT l\'historique de caisse (sessions et mouvements) ? Cette action est irréversible.');
}

function reinitDepenses() {
    showPasswordModal('depenses', 'Supprimer TOUTES les dépenses ? Cette action est irréversible.');
}

function reinitTailleurs() {
    showPasswordModal('tailleurs', 'Supprimer TOUS les tailleurs, commandes atelier et paiements associés ? Cette action est irréversible.');
}

function reinitComplet() {
    showPasswordModal('complet', '⚠️ RÉINITIALISATION COMPLÈTE ! Tout sera effacé (ventes, produits, clients, paramètres). Cette action est irréversible !');
}

function executerReinit(type, password) {
    fetch('ajax/reinitialiser_donnees.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'type=' + encodeURIComponent(type) + '&password=' + encodeURIComponent(password)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlertModal({
                title: 'Succès ✅',
                message: data.message + ' La page va se rafraîchir...',
                type: 'success',
                onClose: function() {
                    // Rafraîchir la page après 1.5 secondes
                    setTimeout(function() {
                        window.location.href = window.location.pathname;
                    }, 1500);
                }
            });
            // Fallback : rafraîchir même si le modal ne se ferme pas
            setTimeout(function() {
                window.location.href = window.location.pathname;
            }, 2000);
        } else {
            showAlertModal({
                title: 'Erreur ❌',
                message: data.message,
                type: 'error'
            });
        }
    })
    .catch(err => {
        showAlertModal({
            title: 'Erreur',
            message: 'Erreur lors de la réinitialisation: ' + err.message,
            type: 'error'
        });
    });
}

// Sélection du logo
function selectLogo(input) {
    if (input.files && input.files[0]) {
        currentImageFile = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const cropImage = document.getElementById('cropImage');
            cropImage.src = e.target.result;
            
            // Afficher le modal
            document.getElementById('cropModal').classList.add('active');
            
            // Initialiser Cropper
            if (cropper) {
                cropper.destroy();
            }
            
            cropper = new Cropper(cropImage, {
                aspectRatio: NaN, // Libre
                viewMode: 1,
                autoCropArea: 0.8,
                responsive: true,
                background: false,
                zoomable: true,
                scalable: true,
                cropBoxResizable: true,
                cropBoxMovable: true,
            });
        };
        
        reader.readAsDataURL(currentImageFile);
    }
}

// Appliquer le crop
function applyCrop() {
    if (cropper) {
        const canvas = cropper.getCroppedCanvas({
            maxWidth: 800,
            maxHeight: 800,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });
        
        // Convertir en base64
        const croppedDataUrl = canvas.toDataURL('image/png');
        
        // Sauvegarder dans le champ caché
        document.getElementById('logoCroppedData').value = croppedDataUrl;
        
        // Afficher l'aperçu
        const preview = document.getElementById('logoPreview');
        preview.src = croppedDataUrl;
        document.getElementById('logoPreviewContainer').style.display = 'block';
        
        // Fermer le modal
        document.getElementById('cropModal').classList.remove('active');
        
        // Détruire cropper
        cropper.destroy();
        cropper = null;
    }
}

// Annuler le crop
function cancelCrop() {
    document.getElementById('cropModal').classList.remove('active');
    document.getElementById('logoInput').value = '';
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
}

// Rouvrir le crop
function reopenCrop() {
    if (currentImageFile) {
        const input = document.getElementById('logoInput');
        const dt = new DataTransfer();
        dt.items.add(currentImageFile);
        input.files = dt.files;
        selectLogo(input);
    }
}

// Mettre à jour le champ texte de couleur
function updateColorText(inputId) {
    const color = document.getElementById(inputId).value;
    document.getElementById(inputId + '_text').value = color;
}

// Initialiser les tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'footer.php'; ?>
