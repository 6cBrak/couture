<?php
/**
 * PAGE ACCÈS REFUSÉ - STORE SUITE
 * Affichée quand un utilisateur n'a pas les permissions nécessaires
 */
require_once 'protection_pages.php';
$page_title = 'Accès Refusé';
include 'header.php';
?>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                        <path d="M12 8v4" />
                        <path d="M12 16h.01" />
                    </svg>
                    Accès Refusé
                </h2>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-5 text-center">
                    <div style="margin-bottom: 2rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="80" height="80" viewBox="0 0 24 24" stroke-width="2" stroke="#dc3545" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <circle cx="12" cy="12" r="9" />
                            <line x1="9" y1="9" x2="15" y2="15" />
                            <line x1="15" y1="9" x2="9" y2="15" />
                        </svg>
                    </div>

                    <h3 class="mb-2">Permissions Insuffisantes</h3>
                    
                    <p class="text-muted mb-4">
                        Désolé, vous n'avez pas les permissions nécessaires pour accéder à cette page.
                        <br><br>
                        Cette fonction est réservée aux administrateurs ou aux utilisateurs avec des droits spécifiques.
                    </p>

                    <div class="alert alert-info alert-sm mb-4" role="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <circle cx="12" cy="12" r="9" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <strong>Conseil :</strong> Si vous pensez que vous devriez avoir accès à cette page, contactez votre administrateur.
                    </div>

                    <div class="btn-list">
                        <a href="accueil.php" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <line x1="5" y1="12" x2="19" y2="12" />
                                <line x1="5" y1="12" x2="9" y2="16" />
                                <line x1="5" y1="12" x2="9" y2="8" />
                            </svg>
                            Retour à l'accueil
                        </a>
                        <a href="profil.php" class="btn btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="12" r="4" />
                                <path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855" />
                            </svg>
                            Mon Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
