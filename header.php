<?php
/**
 * HEADER PRINCIPAL - STORE SUITE
 * Navigation et en-tete du systeme
 */
mb_internal_encoding('UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($nom_boutique); ?> - <?php echo isset($page_title) ? e($page_title) : 'Dashboard'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tabler CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/tabler.min.css" rel="stylesheet"/>
    <link href="<?php echo BASE_URL; ?>assets/css/tabler-vendors.min.css" rel="stylesheet"/>
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/loader.css" rel="stylesheet"/>
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet"/>
    
    <!-- Modals Professionnels -->
    <script src="<?php echo BASE_URL; ?>assets/js/modals.js"></script>
    
    <style>
        :root {
            --couleur-primaire: <?php echo $couleur_primaire; ?>;
            --couleur-secondaire: <?php echo $couleur_secondaire; ?>;
        }
        
        /* Menu Navigation Professionnel - Cohérence couleurs */
        .navbar-nav {
            align-items: center;
        }
        
        .nav-item .nav-link {
            position: relative;
            font-size: 0.925rem;
            letter-spacing: 0.3px;
            border-radius: 8px !important;
            margin: 0 2px;
            color: #495057 !important;
            transition: all 0.3s ease;
            cursor: pointer;
            z-index: 10;
            pointer-events: auto;
        }
        
        .nav-item .nav-link:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
            background: #e9ecef;
            z-index: 20;
        }
        
        /* Menu actif - TOUJOURS EN BLANC */
        .nav-item.active .nav-link {
            position: relative;
            overflow: hidden;
            color: white !important;
            font-weight: 600;
            background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        }
        
        .nav-item.active .nav-link * {
            color: white !important;
        }
        
        .nav-item.active .nav-link svg {
            stroke: white !important;
        }
        
        .nav-item.active .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: white;
            box-shadow: 0 2px 8px rgba(255,255,255,0.4);
        }
        
        /* Hover sur menu actif - garde le blanc */
        .nav-item.active .nav-link:hover {
            background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>) !important;
            color: white !important;
            transform: translateY(-2px);
        }
        
        /* Dropdown Administration Style Amélioré */
        .dropdown-menu {
            animation: slideDown 0.3s ease;
        }

        /* Empêcher le clipping des dropdowns dans la barre de navigation */
        /* Haute spécificité pour écraser tabler.min.css */
        div#navbar-menu.navbar-collapse,
        div#navbar-menu.collapse,
        div#navbar-menu .navbar,
        div#navbar-menu .navbar.navbar-light,
        div#navbar-menu .container-xl,
        div#navbar-menu ul.navbar-nav,
        div#navbar-menu li.nav-item {
            overflow: visible !important;
        }

        /* Z-index du menu déroulant Administration */
        div#navbar-menu .dropdown-menu {
            z-index: 1060 !important;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            transition: all 0.2s ease;
            border-radius: 6px;
            margin: 2px 6px;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>15, <?php echo $couleur_secondaire; ?>15);
            transform: translateX(5px);
            padding-left: 20px !important;
        }
        
        .dropdown-item svg {
            transition: transform 0.2s ease;
        }
        
        .dropdown-item:hover svg {
            transform: scale(1.15);
        }
        
        /* Badge Admin */
        .badge {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.8;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav-item .nav-link {
                margin: 4px 0;
            }
        }
        
        /* Logo dans la navbar */
        .navbar-brand-logo {
            max-height: 42px;
            width: auto;
            transition: transform 0.3s ease;
        }
        
        .navbar-brand-logo:hover {
            transform: scale(1.05);
        }
        
        .navbar-brand-text {
            font-size: 1.25rem;
            font-weight: 700;
            background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .navbar-brand-text:hover {
            transform: scale(1.05);
            filter: brightness(1.2);
        }
    </style>
</head>
<body>
    <?php if (!isset($skip_page_loader) || !$skip_page_loader): ?>
    <?php include 'loading.php'; ?>
    <?php endif; ?>
    
    <div class="page">
        <header class="navbar navbar-expand-md navbar-light d-print-none sticky-top bg-white border-bottom">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3 m-0">
                    <?php if (!empty($logo_boutique) && file_exists('uploads/logos/' . $logo_boutique)): ?>
                        <a href="accueil.php">
                            <img src="<?php echo BASE_URL . 'uploads/logos/' . e($logo_boutique); ?>" 
                                 class="navbar-brand-logo" alt="Logo">
                        </a>
                    <?php else: ?>
                        <a href="accueil.php" class="navbar-brand-text"><?php echo e($nom_boutique); ?></a>
                    <?php endif; ?>
                </h1>
                
                <div class="navbar-nav flex-row order-md-last">
                    <?php if ($products_alert_count > 0): ?>
                    <div class="nav-item d-none d-md-flex me-2">
                        <a href="notification.php" class="btn btn-outline-danger btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 9v2m0 4v.01"/>
                                <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                            </svg>
                            <span class="d-none d-lg-inline">Alerte<?php echo $products_alert_count > 1 ? 's' : ''; ?></span>
                            <span class="badge bg-red ms-1"><?php echo $products_alert_count; ?></span>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="nav-item dropdown d-none d-md-flex me-2">
                        <a href="#" class="nav-link px-2 position-relative" data-bs-toggle="dropdown">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/>
                                <path d="M9 17v1a3 3 0 0 0 6 0v-1"/>
                            </svg>
                            <?php if ($products_alert_count > 0): ?>
                            <span class="notification-badge"><?php echo $products_alert_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <div class="dropdown-header">Notifications</div>
                            <?php if ($products_alert_count > 0): ?>
                            <a href="notification.php" class="dropdown-item">
                                <div class="d-flex">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M12 9v2m0 4v.01"/>
                                            <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                                        </svg>
                                    </div>
                                    <div class="flex-fill ms-2">
                                        <strong>Alerte Stock</strong>
                                        <div class="text-muted small"><?php echo $products_alert_count; ?> produit<?php echo $products_alert_count > 1 ? 's' : ''; ?> en alerte</div>
                                    </div>
                                </div>
                            </a>
                            <?php else: ?>
                            <div class="dropdown-item text-muted">Aucune notification</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Logout button - Desktop (md+) -->
                    <a href="deconnexion.php" class="btn btn-outline-danger btn-sm d-none d-md-flex align-items-center me-2" style="gap: 4px;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/>
                            <path d="M7 12h14l-3 -3m0 6l3 -3"/>
                        </svg>
                        Déconnexion
                    </a>

                    <!-- User dropdown - Desktop (md+) -->
                    <div class="nav-item dropdown d-none d-md-flex me-2">
                        <a href="#" class="nav-link px-2 d-flex align-items-center" data-bs-toggle="dropdown" style="height: 40px; cursor: pointer;">
                            <span class="avatar avatar-sm" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); color: white; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="7" r="4"/>
                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                </svg>
                            </span>
                            <div class="d-none d-xl-block ps-2">
                                <div class="fw-bold" style="font-size: 0.875rem; line-height: 1.2;"><?php echo e($user_name); ?></div>
                                <div class="small text-muted" style="font-size: 0.75rem; line-height: 1.2;"><?php echo $is_admin ? 'Administrateur' : 'Vendeur'; ?></div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow" style="max-height: calc(100vh - 80px); overflow-y: auto;">
                            <a href="profil.php" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9"/>
                                    <circle cx="12" cy="10" r="3"/>
                                    <path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855"/>
                                </svg>
                                Mon profil
                            </a>
                            <a href="deconnexion.php" class="dropdown-item text-danger">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/>
                                    <path d="M7 12h14l-3 -3m0 6l3 -3"/>
                                </svg>
                                Déconnexion
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="aide.php" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    <polyline points="11 12 12 12 12 16 13 16"/>
                                </svg>
                                Aide & À Propos
                            </a>
                            <?php if ($is_admin): ?>
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-item dropdown-item-heading">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/>
                                    <path d="M12 12l8 -4.5"/>
                                    <path d="M12 12l0 9"/>
                                    <path d="M12 12l-8 -4.5"/>
                                </svg>
                                <strong>Administration</strong>
                            </div>
                            <a href="tableau_de_bord.php" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <rect x="4" y="4" width="6" height="6" rx="1"/>
                                    <rect x="14" y="4" width="6" height="6" rx="1"/>
                                    <rect x="4" y="14" width="6" height="6" rx="1"/>
                                    <rect x="14" y="14" width="6" height="6" rx="1"/>
                                </svg>
                                Statistiques avancées
                            </a>
                            <a href="utilisateurs.php" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/>
                                </svg>
                                Gestion utilisateurs
                            </a>
                            <a href="parametres.php" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                Paramètres système
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- User icon - Mobile (< md) -->
                    <div class="nav-item dropdown d-md-none">
                        <a href="#" class="nav-link px-2" data-bs-toggle="dropdown" style="cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="7" r="4"/>
                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                            </svg>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow" style="max-height: calc(100vh - 80px); overflow-y: auto;">
                            <div class="dropdown-header">
                                <span><?php echo e($user_name); ?></span>
                                <span class="badge badge-sm" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>);">
                                    <?php echo $is_admin ? 'Admin' : 'Vendeur'; ?>
                                </span>
                            </div>
                            <a href="profil.php" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="10" r="3"/>
                                    <path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855"/>
                                </svg>
                                Mon profil
                            </a>
                            <a href="deconnexion.php" class="dropdown-item text-danger">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/>
                                    <path d="M7 12h14l-3 -3m0 6l3 -3"/>
                                </svg>
                                Déconnexion
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="aide.php" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="12" r="9"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    <polyline points="11 12 12 12 12 16 13 16"/>
                                </svg>
                                Aide & À Propos
                            </a>
                            <?php if ($is_admin): ?>
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-item dropdown-item-heading">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/>
                                    <path d="M12 12l8 -4.5"/>
                                    <path d="M12 12l0 9"/>
                                    <path d="M12 12l-8 -4.5"/>
                                </svg>
                                <strong>Administration</strong>
                            </div>
                            <a href="tableau_de_bord.php" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <rect x="4" y="4" width="6" height="6" rx="1"/>
                                    <rect x="14" y="4" width="6" height="6" rx="1"/>
                                    <rect x="4" y="14" width="6" height="6" rx="1"/>
                                    <rect x="14" y="14" width="6" height="6" rx="1"/>
                                </svg>
                                Statistiques avancées
                            </a>
                            <a href="utilisateurs.php" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/>
                                </svg>
                                Gestion utilisateurs
                            </a>
                            <a href="parametres.php" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                Paramètres système
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="navbar-expand-md">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar navbar-light" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-bottom: 2px solid #e9ecef; box-shadow: 0 2px 4px rgba(0,0,0,0.04); overflow: visible !important;">
                    <div class="container-xl">
                        <ul class="navbar-nav" style="gap: 0.5rem;">
                            <!-- Accueil -->
                            <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'accueil.php') ? 'active' : ''; ?>">
                                <a class="nav-link d-flex align-items-center px-3 py-2 rounded-2" href="accueil.php">
                                    <span class="nav-link-icon d-inline-block me-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <polyline points="5 12 3 12 12 3 21 12 19 12"/>
                                            <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/>
                                            <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title fw-medium">Accueil</span>
                                </a>
                            </li>
                            
                            <!-- Vente -->
                            <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'vente.php') ? 'active' : ''; ?>">
                                <a class="nav-link d-flex align-items-center px-3 py-2 rounded-2" href="vente.php">
                                    <span class="nav-link-icon d-inline-block me-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <circle cx="6" cy="19" r="2"/>
                                            <circle cx="17" cy="19" r="2"/>
                                            <path d="M17 17h-11v-14h-2"/>
                                            <path d="M6 5l14 1l-1 7h-13"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title fw-medium">Nouvelle Vente</span>
                                </a>
                            </li>
                            
                            <!-- Produits & Stock (Admin Only) -->
                            <?php if ($is_admin): ?>
                            <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'listes.php') ? 'active' : ''; ?>">
                                <a class="nav-link d-flex align-items-center px-3 py-2 rounded-2" href="listes.php">
                                    <span class="nav-link-icon d-inline-block me-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"/>
                                            <rect x="9" y="3" width="6" height="4" rx="2"/>
                                            <line x1="9" y1="12" x2="9.01" y2="12"/>
                                            <line x1="13" y1="12" x2="15" y2="12"/>
                                            <line x1="9" y1="16" x2="9.01" y2="16"/>
                                            <line x1="13" y1="16" x2="15" y2="16"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title fw-medium">Produits & Stock</span>
                                    <span class="badge bg-warning ms-2" style="font-size: 0.65rem;">Admin</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <!-- Mouvements de stock (Visible pour Admin et Vendeur) -->
                            <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'mouvements_stock.php') ? 'active' : ''; ?>">
                                <a class="nav-link d-flex align-items-center px-3 py-2 rounded-2" href="mouvements_stock.php">
                                    <span class="nav-link-icon d-inline-block me-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"/>
                                            <rect x="9" y="3" width="6" height="4" rx="2"/>
                                            <path d="M9 14l2 2l4 -4"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title fw-medium">Mouvements de stock</span>
                                </a>
                            </li>
                            
                            <!-- Rapports -->
                            <li class="nav-item dropdown <?php echo in_array(basename($_SERVER['PHP_SELF']), ['rapports.php', 'rapport_caisse.php', 'rapport_depenses.php']) ? 'active' : ''; ?>">
                                <a class="nav-link dropdown-toggle d-flex align-items-center px-3 py-2 rounded-2" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-inline-block me-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <rect x="3" y="12" width="6" height="8" rx="1"/>
                                            <rect x="9" y="8" width="6" height="12" rx="1"/>
                                            <rect x="15" y="4" width="6" height="16" rx="1"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title fw-medium">Rapports</span>
                                </a>
                                <div class="dropdown-menu shadow border-0" style="min-width: 240px; border-radius: 10px; margin-top: 6px;">
                                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="rapports.php">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-primary" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="12" width="6" height="8" rx="1"/><rect x="9" y="8" width="6" height="12" rx="1"/><rect x="15" y="4" width="6" height="16" rx="1"/></svg>
                                        <div>
                                            <div class="fw-medium">Rapport Ventes</div>
                                            <small class="text-muted">Statistiques des ventes</small>
                                        </div>
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="rapport_caisse.php">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-success" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                        <div>
                                            <div class="fw-medium">Rapport Caisse</div>
                                            <small class="text-muted">Sessions et mouvements</small>
                                        </div>
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="rapport_depenses.php">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-danger" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l6 -6"/><circle cx="9.5" cy="9.5" r=".5" fill="currentColor"/><circle cx="14.5" cy="14.5" r=".5" fill="currentColor"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/></svg>
                                        <div>
                                            <div class="fw-medium">Rapport Dépenses</div>
                                            <small class="text-muted">Analyse par catégorie</small>
                                        </div>
                                    </a>
                                </div>
                            </li>
                            <!-- Caisse & Dépenses -->
                            <li class="nav-item dropdown <?php echo in_array(basename($_SERVER['PHP_SELF']), ['caisse.php', 'depenses.php']) ? 'active' : ''; ?>">
                                <a class="nav-link dropdown-toggle d-flex align-items-center px-3 py-2 rounded-2" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-inline-block me-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <rect x="2" y="6" width="20" height="12" rx="2"/>
                                            <circle cx="12" cy="12" r="2"/>
                                            <path d="M6 12h.01M18 12h.01"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title fw-medium">Caisse</span>
                                </a>
                                <div class="dropdown-menu shadow border-0" style="min-width: 230px; border-radius: 10px; margin-top: 6px;">
                                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="caisse.php">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-success" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                                        <div>
                                            <div class="fw-medium">Gestion de la Caisse</div>
                                            <small class="text-muted">Solde, entrées et sorties</small>
                                        </div>
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="depenses.php">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-danger" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l6 -6"/><circle cx="9.5" cy="9.5" r=".5" fill="currentColor"/><circle cx="14.5" cy="14.5" r=".5" fill="currentColor"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/></svg>
                                        <div>
                                            <div class="fw-medium">Dépenses</div>
                                            <small class="text-muted">Enregistrer et suivre</small>
                                        </div>
                                    </a>
                                </div>
                            </li>
                            <!-- Atelier Couture (si module activé) -->
                            <?php if ($atelier_mode): ?>
                            <li class="nav-item dropdown <?php echo (basename($_SERVER['PHP_SELF']) === 'atelier.php') ? 'active' : ''; ?>">
                                <a class="nav-link dropdown-toggle d-flex align-items-center px-3 py-2 rounded-2" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-inline-block me-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"/>
                                            <path d="M8 11h8"/><path d="M8 15h5"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title fw-medium">Atelier</span>
                                </a>
                                <div class="dropdown-menu shadow border-0" style="min-width: 240px; border-radius: 10px; margin-top: 6px;">
                                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="atelier.php?tab=commandes">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-primary" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 10h8"/><path d="M8 14h5"/></svg>
                                        <div>
                                            <div class="fw-medium">Commandes</div>
                                            <small class="text-muted">Suivi et statuts</small>
                                        </div>
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="atelier.php?tab=affectation">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-warning" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/><circle cx="12" cy="12" r="9"/></svg>
                                        <div>
                                            <div class="fw-medium">Affectation</div>
                                            <small class="text-muted">Affecter ventes aux tailleurs</small>
                                        </div>
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="atelier.php?tab=tailleurs">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-success" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 11l2 2l4 -4"/></svg>
                                        <div>
                                            <div class="fw-medium">Tailleurs</div>
                                            <small class="text-muted">Gérer les tailleurs</small>
                                        </div>
                                    </a>
                                </div>
                            </li>
                            <?php endif; ?>
                            <!-- Administration (Admin Only) -->
                            <?php if ($is_admin): ?>
                            <li class="nav-item dropdown <?php echo in_array(basename($_SERVER['PHP_SELF']), ['tableau_de_bord.php', 'parametres.php']) ? 'active' : ''; ?>">
                                <a class="nav-link dropdown-toggle d-flex align-items-center px-3 py-2 rounded-2" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-inline-block me-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/>
                                            <path d="M12 12l8 -4.5"/>
                                            <path d="M12 12l0 9"/>
                                            <path d="M12 12l-8 -4.5"/>
                                        </svg>
                                    </span>
                                    <span class="nav-link-title fw-medium">Administration</span>
                                    <span class="badge bg-primary ms-2" style="font-size: 0.65rem;">Admin</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 260px; border-radius: 12px; margin-top: 8px;">
                                    <div class="dropdown-header d-flex align-items-center py-3 px-3" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); color: white; font-weight: 600; border-radius: 12px 12px 0 0;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/>
                                        </svg>
                                        Panneau d'Administration
                                    </div>
                                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="tableau_de_bord.php" style="transition: all 0.2s;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-primary" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <rect x="4" y="4" width="6" height="6" rx="1"/>
                                            <rect x="14" y="4" width="6" height="6" rx="1"/>
                                            <rect x="4" y="14" width="6" height="6" rx="1"/>
                                            <rect x="14" y="14" width="6" height="6" rx="1"/>
                                        </svg>
                                        <div>
                                            <div class="fw-medium">Tableau de Bord</div>
                                            <small class="text-muted">Statistiques avancées</small>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider my-1"></div>
                                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="utilisateurs.php" style="transition: all 0.2s;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-success" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                            <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/>
                                        </svg>
                                        <div>
                                            <div class="fw-medium">Utilisateurs</div>
                                            <small class="text-muted">Gestion des accès</small>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider my-1"></div>
                                    <a class="dropdown-item d-flex align-items-center py-2 px-3" href="parametres.php" style="transition: all 0.2s;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-warning" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <div>
                                            <div class="fw-medium">Paramètres Système</div>
                                            <small class="text-muted">Configuration générale</small>
                                        </div>
                                    </a>
                                </div>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Gestion manuelle de tous les dropdowns du navbar
        (function() {
            function initAllDropdowns() {
                var allDropdownItems = document.querySelectorAll('.navbar .nav-item.dropdown');

                allDropdownItems.forEach(function(item) {
                    var toggle = item.querySelector('[data-bs-toggle="dropdown"]');
                    var menu = item.querySelector('.dropdown-menu');
                    if (!toggle || !menu) return;

                    // Forcer overflow visible sur les ancêtres pour que le menu soit visible
                    var el = item.parentElement;
                    while (el && el !== document.body) {
                        el.style.overflow = 'visible';
                        el = el.parentElement;
                    }

                    toggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        var isOpen = menu.classList.contains('show');

                        // Fermer tous les dropdowns ouverts
                        document.querySelectorAll('.navbar .dropdown-menu.show').forEach(function(m) {
                            m.classList.remove('show');
                        });
                        document.querySelectorAll('.navbar .nav-item.dropdown.show').forEach(function(i) {
                            i.classList.remove('show');
                            var t = i.querySelector('[data-bs-toggle="dropdown"]');
                            if (t) t.setAttribute('aria-expanded', 'false');
                        });

                        if (!isOpen) {
                            menu.classList.add('show');
                            item.classList.add('show');
                            toggle.setAttribute('aria-expanded', 'true');
                        }
                    });
                });

                // Fermer au clic en dehors du navbar
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.navbar .nav-item.dropdown')) {
                        document.querySelectorAll('.navbar .dropdown-menu.show').forEach(function(m) { m.classList.remove('show'); });
                        document.querySelectorAll('.navbar .nav-item.dropdown.show').forEach(function(i) {
                            i.classList.remove('show');
                            var t = i.querySelector('[data-bs-toggle="dropdown"]');
                            if (t) t.setAttribute('aria-expanded', 'false');
                        });
                    }
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAllDropdowns);
            } else {
                initAllDropdowns();
            }
        })();
        </script>

        <div class="page-wrapper">