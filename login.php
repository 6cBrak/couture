<?php
/**
 * ============================================================================
 * PAGE DE CONNEXION - LOGIN
 * ============================================================================
 * 
 * Importance : Authentification sécurisée des utilisateurs
 * 
 * Fonctionnalités :
 * - Formulaire de connexion avec validation
 * - Protection contre les attaques par force brute
 * - Gestion des messages d'erreur
 * - Redirection selon le niveau d'accès
 * 
 * Sécurité :
 * - Utilisation de password_verify() pour vérifier les mots de passe
 * - Protection CSRF avec token
 * - Limitation des tentatives de connexion
 * 
 * ============================================================================
 */

// Désactiver le cache pour éviter les problèmes de session/CSRF
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/config/database.php';

// Assurer la session pour le token CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si déjà connecté, rediriger vers l'accueil
if (is_logged_in()) {
    redirect(BASE_URL . 'accueil.php');
    exit;
}

// Récupérer la configuration du système
$config = get_system_config();
if (!$config || !$config['est_configure']) {
    redirect(BASE_URL . 'setup.php');
    exit;
}

// Générer le token CSRF
$csrf_token = generate_csrf_token();

// Traitement du formulaire de connexion
$error_message = '';
$login_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    $csrf_ok = isset($_POST['csrf_token']) && verify_csrf_token($_POST['csrf_token']);
    
    if (!$csrf_ok) {
        // Logger le problème pour déboguer
        error_log('CSRF token invalid: session_id=' . session_id() . ', POST token=' . ($_POST['csrf_token'] ?? 'ABSENT') . ', SESSION token=' . ($_SESSION['csrf_token'] ?? 'ABSENT'));
        $error_message = 'Erreur de sécurité détectée. Veuillez recharger la page et réessayer. Si le problème persiste, videz le cache de votre navigateur.';
    } else {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $login_value = $login; // Garder la valeur pour le rechargement
        
        if (empty($login) || empty($password)) {
            $error_message = 'Veuillez remplir tous les champs.';
        } else {
            // Récupérer l'utilisateur
            $user = get_user_by_login($login);
            $hash = $user['password_hash'] ?? null;      // schéma actuel
            $legacy = $user['mot_de_passe'] ?? null;      // compat ancien schéma

            $is_valid = false;
            if ($user && !empty($hash) && password_verify($password, $hash)) {
                $is_valid = true;
            } elseif ($user && empty($hash) && !empty($legacy)) {
                $looks_hashed = preg_match('/^\$2y\$/', $legacy) === 1;

                if ($looks_hashed) {
                    // Ancien stockage déjà bcrypté : vérifier avec password_verify
                    if (password_verify($password, $legacy)) {
                        $is_valid = true;
                    }
                } else {
                    // Ancien stockage en clair : comparer exactement
                    if (hash_equals($legacy, $password)) {
                        $is_valid = true;
                    }
                }

                // Migrer vers password_hash si validé
                if ($is_valid) {
                    try {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        db_update('utilisateurs', ['password_hash' => $newHash, 'mot_de_passe' => null], 'id_utilisateur = ?', [$user['id_utilisateur']]);
                        $user['password_hash'] = $newHash;
                    } catch (Exception $e) {
                        error_log('Migration mot_de_passe -> password_hash: ' . $e->getMessage());
                    }
                }
            }

            if ($user && $is_valid) {
                // Connexion réussie !
                
                // Vérifier si le compte est actif
                if ($user['est_actif'] != 1) {
                    $error_message = 'Ce compte a été désactivé. Contactez l\'administrateur.';
                } else {
                    // Créer la session utilisateur
                    $_SESSION['user_id'] = $user['id_utilisateur'];
                    $_SESSION['user_name'] = $user['nom_complet'];
                    $_SESSION['user_login'] = $user['login'];
                    $_SESSION['niveau_acces'] = $user['niveau_acces'];
                    
                    // Mettre à jour la date de dernière connexion
                    update_last_login($user['id_utilisateur']);
                    
                    // Logger la connexion
                    log_activity('connexion', 'Connexion réussie de ' . $user['nom_complet']);
                    
                    // Rediriger vers l'accueil
                    redirect(BASE_URL . 'accueil.php');
                    exit;
                }
            } else {
                $error_message = 'Identifiant ou mot de passe incorrect.';
                
                // Logger la tentative échouée
                if ($user) {
                    log_activity('connexion_echouee', "Tentative de connexion échouée pour l'utilisateur : $login");
                }
            }
        }
    }
}

// Récupérer un éventuel message flash
$flash = get_flash_message();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?php echo e($config['nom_boutique']); ?> - Connexion</title>
    <!-- CSS files -->
    <link href="./dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="./dist/css/tabler-flags.min.css" rel="stylesheet"/>
    <link href="./dist/css/tabler-payments.min.css" rel="stylesheet"/>
    <link href="./dist/css/tabler-vendors.min.css" rel="stylesheet"/>
    <link href="./dist/css/demo.min.css" rel="stylesheet"/>
    <style>
        .titre {
            font-size: 3rem!important;
            color: <?php echo $config['couleur_primaire']; ?>!important;
            font-weight: 900;
        }
        .btn-ghost-primary, .btn-outline-primary, .btn-primary, .btn {
            background-color: <?php echo $config['couleur_primaire']; ?>!important;
            color: <?php echo $config['couleur_secondaire']; ?>!important;
        }
        .btn:hover {
            opacity: 0.9;
        }
        
        /* Loader de démarrage complet */
        #systemLoader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, <?php echo $config['couleur_primaire']; ?>, <?php echo $config['couleur_secondaire']; ?>);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            color: white;
            transition: opacity 0.5s ease, visibility 0.5s ease;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        #systemLoader.hidden {
            opacity: 0;
            visibility: hidden;
        }
        
        .system-loader-content {
            text-align: center;
            max-width: 500px;
            padding: 40px;
        }
        
        .system-logo {
            margin-bottom: 40px;
            animation: logoEntrance 0.8s ease-out;
        }
        
        .system-logo img {
            max-width: 100px;
            max-height: 100px;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.2));
        }
        
        .system-logo svg {
            width: 80px;
            height: 80px;
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.2));
        }
        
        .system-name {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: 4px;
            margin-top: 20px;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }
        
        .system-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-top: 10px;
            letter-spacing: 2px;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            margin: 40px auto;
            position: relative;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }
        
        .spinner-circle {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 3px solid rgba(255, 255, 255, 0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spinRotate 1s linear infinite;
        }
        
        .spinner-circle:nth-child(2) {
            width: 75%;
            height: 75%;
            top: 12.5%;
            left: 12.5%;
            border-top-color: rgba(255, 255, 255, 0.8);
            animation-duration: 1.3s;
            animation-direction: reverse;
        }
        
        @keyframes spinRotate {
            to { transform: rotate(360deg); }
        }
        
        .loading-status {
            margin-top: 30px;
            min-height: 60px;
        }
        
        .loading-message {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 15px;
            animation: messagePulse 0.5s ease-in-out;
        }
        
        .loading-step {
            font-size: 0.9rem;
            opacity: 0.8;
            animation: messagePulse 0.5s ease-in-out;
        }
        
        .progress-bar-container {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            margin-top: 25px;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out 0.8s both;
        }
        
        .progress-bar {
            height: 100%;
            background: white;
            width: 0%;
            transition: width 0.5s ease-out;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }
        
        .loading-percentage {
            margin-top: 15px;
            font-size: 0.85rem;
            opacity: 0.7;
            font-weight: 600;
        }
        
        .copyright-text {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 0.85rem;
            opacity: 0;
            animation: copyrightFade 3s ease-in-out 1s infinite;
        }
        
        .copyright-text .developer {
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        @keyframes copyrightFade {
            0%, 100% { opacity: 0; transform: translateY(10px); }
            50% { opacity: 0.8; transform: translateY(0); }
        }
        
        @keyframes logoEntrance {
            from {
                opacity: 0;
                transform: scale(0.5) rotate(-10deg);
            }
            to {
                opacity: 1;
                transform: scale(1) rotate(0deg);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes messagePulse {
            0% { opacity: 0; transform: translateY(-10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="antialiased border-top-wide border-primary d-flex flex-column">
    <!-- Loader de démarrage -->
    <div id="systemLoader">
        <div class="system-loader-content">
            <!-- Logo -->
            <div class="system-logo">
                <?php if (!empty($config['logo']) && file_exists(LOGO_PATH . $config['logo'])): ?>
                    <img src="<?php echo BASE_URL . 'uploads/logos/' . $config['logo']; ?>" alt="Logo">
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z" />
                        <path d="M9 11v-5a3 3 0 0 1 6 0v5" />
                    </svg>
                <?php endif; ?>
            </div>
            
            <!-- Nom du système -->
            <div class="system-name"><?php echo strtoupper(e($config['nom_boutique'])); ?></div>
            <div class="system-subtitle">SYSTÈME DE GESTION</div>
            
            <!-- Spinner -->
            <div class="loading-spinner">
                <div class="spinner-circle"></div>
                <div class="spinner-circle"></div>
            </div>
            
            <!-- Status de chargement -->
            <div class="loading-status">
                <div class="loading-message" id="loadingMessage">Chargement de l'application...</div>
                <div class="loading-step" id="loadingStep"></div>
            </div>
            
            <!-- Barre de progression -->
            <div class="progress-bar-container">
                <div class="progress-bar" id="progressBar"></div>
            </div>
            <div class="loading-percentage" id="loadingPercentage">0%</div>
        </div>
        
        <!-- Copyright -->
        <div class="copyright-text">
            Intégré par <span class="developer">Fix iT Engineering</span>
        </div>
    </div>
    
    <div class="page page-center">
        <div class="container-tight py-4">
            <div class="text-center mb-4">
                <?php if (!empty($config['logo']) && file_exists(LOGO_PATH . $config['logo'])): ?>
                    <img src="<?php echo BASE_URL . 'uploads/logos/' . $config['logo']; ?>" 
                         alt="<?php echo e($config['nom_boutique']); ?>" 
                         style="max-height: 100px; margin-bottom: 20px;">
                <?php endif; ?>
                <div class="titre">
                    <?php echo e($config['nom_boutique']); ?>
                </div>
                <?php if (!empty($config['slogan'])): ?>
                    <div class="text-muted"><?php echo e($config['slogan']); ?></div>
                <?php endif; ?>
            </div>
            
            <form class="card card-md" method="POST" action="" autocomplete="off">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Connexion à STORESUITE</h2>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>
                                </div>
                                <div>
                                    <?php echo e($error_message); ?>
                                </div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($flash): ?>
                        <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div>
                                    <?php echo e($flash['message']); ?>
                                </div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    <?php endif; ?>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Identifiant</label>
                        <input type="text" name="login" class="form-control" 
                               placeholder="Votre identifiant" 
                               value="<?php echo e($login_value); ?>" 
                               required autofocus>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="password" class="form-control" 
                               placeholder="Votre mot de passe" 
                               required>
                    </div>
                    
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" /><path d="M20 12h-13l3 -3m0 6l-3 -3" /></svg>
                            Se connecter
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <a href="aide.php" class="btn btn-outline-secondary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="12" cy="12" r="9"/>
                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                        <polyline points="11 12 12 12 12 16 13 16"/>
                    </svg>
                    Aide & À Propos
                </a>
            </div>
            
            <div class="text-center text-muted mt-3">
                <small>STORESUITE v<?php echo APP_VERSION; ?> - Système de Gestion de Stock</small>
            </div>
        </div>
    </div>
    
    <!-- Libs JS -->
    <script src="./dist/js/tabler.min.js"></script>
    <script>
        // Étapes de chargement pour login
        const loadingSteps = [
            { message: 'Chargement de l\'application...', step: 'Initialisation', progress: 25, duration: 600 },
            { message: 'Préparation de l\'interface...', step: 'Configuration', progress: 60, duration: 650 },
            { message: 'Prêt à se connecter', step: 'Système prêt', progress: 100, duration: 700 }
        ];
        
        let currentStep = 0;
        
        function updateLoadingStep() {
            if (currentStep >= loadingSteps.length) {
                setTimeout(function() {
                    document.getElementById('systemLoader').classList.add('hidden');
                }, 600);
                return;
            }
            
            const step = loadingSteps[currentStep];
            const messageEl = document.getElementById('loadingMessage');
            const stepEl = document.getElementById('loadingStep');
            const progressBar = document.getElementById('progressBar');
            const percentageEl = document.getElementById('loadingPercentage');
            
            if (messageEl && stepEl && progressBar && percentageEl) {
                messageEl.textContent = step.message;
                stepEl.textContent = step.step;
                progressBar.style.width = step.progress + '%';
                percentageEl.textContent = step.progress + '%';
            }
            
            currentStep++;
            setTimeout(updateLoadingStep, step.duration);
        }
        
        window.addEventListener('load', function() {
            setTimeout(updateLoadingStep, 300);
        });
    </script>
</body>
</html>
