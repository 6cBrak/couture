<?php
/**
 * PAGE PROFIL UTILISATEUR - STORE SUITE
 * Gestion du profil personnel
 */
require_once 'protection_pages.php';
$page_title = 'Mon Profil';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom_complet'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $error = '';
    $success = '';
    
    try {
        // Vérifier le mot de passe actuel si on veut en changer
        if (!empty($new_password)) {
            if (empty($current_password)) {
                throw new Exception('Le mot de passe actuel est requis');
            }
            
            $user = db_fetch_one("SELECT password FROM utilisateurs WHERE id_utilisateur = ?", [$user_id]);
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception('Le mot de passe actuel est incorrect');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('Les nouveaux mots de passe ne correspondent pas');
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception('Le mot de passe doit contenir au moins 6 caractères');
            }
            
            // Mettre à jour avec nouveau mot de passe
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            db_execute(
                "UPDATE utilisateurs SET nom_complet = ?, email = ?, password = ? WHERE id_utilisateur = ?",
                [$nom, $email, $password_hash, $user_id]
            );
            $success = 'Profil et mot de passe mis à jour avec succès';
        } else {
            // Mise à jour sans changer le mot de passe
            db_execute(
                "UPDATE utilisateurs SET nom_complet = ?, email = ? WHERE id_utilisateur = ?",
                [$nom, $email, $user_id]
            );
            $success = 'Profil mis à jour avec succès';
        }
        
        // Recharger les données
        $user_data = get_user_by_id($user_id);
        $user_name = $user_data['nom_complet'];
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Passer le succès en variable JS plutôt que l'afficher sur la page
$success_for_modal = false;
if (isset($success) && $success) {
    $success_for_modal = true;
}

// Statistiques personnelles
$mes_stats = db_fetch_one("
    SELECT 
        COUNT(*) as nb_ventes,
        COALESCE(SUM(montant_total), 0) as total_ventes,
        COALESCE(AVG(montant_total), 0) as panier_moyen
    FROM ventes
    WHERE id_vendeur = ? AND statut = 'validee'
", [$user_id]);

$mes_ventes_mois = db_fetch_one("
    SELECT 
        COUNT(*) as nb,
        COALESCE(SUM(montant_total), 0) as montant
    FROM ventes
    WHERE id_vendeur = ? 
    AND statut = 'validee'
    AND MONTH(date_vente) = MONTH(CURDATE())
    AND YEAR(date_vente) = YEAR(CURDATE())
", [$user_id]);

include 'header.php';
?>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="12" cy="12" r="9"/>
                        <circle cx="12" cy="10" r="3"/>
                        <path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855"/>
                    </svg>
                    Mon Profil
                </h2>
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
    
    <?php if ($success_for_modal): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof showAlertModal === 'function') {
                showAlertModal({
                    title: 'Succès',
                    message: '<?php echo addslashes($success); ?>',
                    type: 'success'
                });
            }
        });
    </script>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="avatar avatar-xl mb-3 mx-auto" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); width: 96px; height: 96px; font-size: 2rem; color: white;">
                        <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                    </div>
                    <h3 class="mb-1"><?php echo e($user_name); ?></h3>
                    <div class="text-muted mb-2">@<?php echo e($user_login); ?></div>
                    <span class="badge <?php echo $is_admin ? 'bg-primary' : 'bg-secondary'; ?> badge-lg">
                        <?php echo $is_admin ? 'Administrateur' : 'Vendeur'; ?>
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Mes statistiques</h3>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="avatar avatar-sm" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); color: white;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="6" cy="19" r="2"/><circle cx="17" cy="19" r="2"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/></svg>
                                </div>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Total ventes</div>
                                <strong><?php echo $mes_stats['nb_ventes']; ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="avatar avatar-sm bg-success-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2"/><path d="M12 3v3m0 12v3"/></svg>
                                </div>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Chiffre d'affaires total</div>
                                <strong><?php echo format_montant($mes_stats['total_ventes']); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="avatar avatar-sm bg-info-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="19" x2="20" y2="19"/><polyline points="4 15 8 9 12 11 16 6 20 10"/></svg>
                                </div>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Panier moyen</div>
                                <strong><?php echo format_montant($mes_stats['panier_moyen']); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="avatar avatar-sm bg-warning-lt">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="4" y="5" width="16" height="16" rx="2"/><line x1="16" y1="3" x2="16" y2="7"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="4" y1="11" x2="20" y2="11"/></svg>
                                </div>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Ventes ce mois</div>
                                <strong><?php echo $mes_ventes_mois['nb']; ?> (<?php echo format_montant($mes_ventes_mois['montant']); ?>)</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Modifier mes informations</h3>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label required">Nom complet</label>
                            <input type="text" class="form-control" name="nom_complet" value="<?php echo e($user_name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo e($user_email ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Login</label>
                            <input type="text" class="form-control" value="<?php echo e($user_login); ?>" disabled>
                            <small class="form-hint">Le login ne peut pas être modifié</small>
                        </div>
                        
                        <hr>
                        <h4 class="mb-3">Changer le mot de passe</h4>
                        <p class="text-muted">Laissez vide si vous ne souhaitez pas modifier votre mot de passe</p>
                        
                        <div class="mb-3">
                            <label class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" name="current_password" autocomplete="current-password">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" name="new_password" autocomplete="new-password">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" name="confirm_password" autocomplete="new-password">
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
