<?php
/**
 * ============================================================================
 * INDEX - PAGE D'ACCUEIL / REDIRECTION PRINCIPALE
 * ============================================================================
 * 
 * Importance : Point d'entrée du système STORESUITE
 * 
 * Fonctionnement :
 * 1. Affiche un écran de chargement élégant
 * 2. Vérifie si le système est configuré
 *    - Si NON → Redirige vers setup.php
 * 3. Vérifie si l'utilisateur est connecté
 *    - Si OUI → Redirige vers accueil.php (tableau de bord)
 *    - Si NON → Redirige vers login.php
 * 
 * ============================================================================
 */

// Inclusion de la configuration et connexion base de données
require_once __DIR__ . '/config/database.php';

// ============================================================================
// VÉRIFICATION 1 : SYSTÈME CONFIGURÉ ?
// ============================================================================
if (!is_system_configured()) {
    // Système non configuré → Rediriger vers la page de configuration
    redirect(BASE_URL . 'setup.php');
    exit;
}

// ============================================================================
// RÉCUPÉRATION DES PARAMÈTRES POUR LE LOADER
// ============================================================================
$nom_boutique = 'STORESUITE';
$logo_boutique = '';
$couleur_primaire = '#0ea5e9'; // Bleu cyan moderne
$couleur_secondaire = '#0284c7'; // Bleu profond

// Récupérer les paramètres de la boutique depuis la base de données
try {
    $config = db_fetch_one("SELECT nom_boutique, logo, couleur_primaire, couleur_secondaire FROM parametres WHERE id = 1");
    if ($config) {
        $nom_boutique = $config['nom_boutique'] ?? 'STORESUITE';
        $logo_boutique = $config['logo'] ?? '';
        $couleur_primaire = $config['couleur_primaire'] ?? '#0ea5e9';
        $couleur_secondaire = $config['couleur_secondaire'] ?? '#0284c7';
    }
} catch (Exception $e) {
    // Utiliser les valeurs par défaut si erreur
}

// ============================================================================
// DÉTERMINER LA PAGE DE DESTINATION
// ============================================================================
$destination = BASE_URL . 'login.php'; // Par défaut
if (is_logged_in()) {
    $destination = BASE_URL . 'accueil.php';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Démarrage - <?php echo e($nom_boutique); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/loader.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        #systemLoader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            color: white;
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
<body>
    <div id="systemLoader">
        <div class="system-loader-content">
            <!-- Logo -->
            <div class="system-logo">
                <?php if (!empty($logo_boutique) && file_exists('uploads/logos/' . $logo_boutique)): ?>
                    <img src="<?php echo BASE_URL . 'uploads/logos/' . e($logo_boutique); ?>" alt="Logo">
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z" />
                        <path d="M9 11v-5a3 3 0 0 1 6 0v5" />
                    </svg>
                <?php endif; ?>
            </div>
            
            <!-- Nom du système -->
            <div class="system-name"><?php echo strtoupper(e($nom_boutique)); ?></div>
            <div class="system-subtitle">SYSTÈME DE GESTION</div>
            
            <!-- Spinner -->
            <div class="loading-spinner">
                <div class="spinner-circle"></div>
                <div class="spinner-circle"></div>
            </div>
            
            <!-- Status de chargement -->
            <div class="loading-status">
                <div class="loading-message" id="loadingMessage">Démarrage du système...</div>
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
    
    <script>
        // Étapes de chargement du système
        const loadingSteps = [
            { message: 'Démarrage du système...', step: 'Initialisation des composants', progress: 15, duration: 800 },
            { message: 'Chargement des modules...', step: 'Configuration des services', progress: 35, duration: 900 },
            { message: 'Vérification de la connexion...', step: 'Authentification en cours', progress: 60, duration: 850 },
            { message: 'Préparation de l\'interface...', step: 'Chargement des ressources', progress: 85, duration: 800 },
            { message: 'Bienvenue !', step: 'Système prêt', progress: 100, duration: 1000 }
        ];
        
        let currentStep = 0;
        
        function updateLoadingStep() {
            if (currentStep >= loadingSteps.length) {
                // Redirection après la dernière étape
                setTimeout(function() {
                    window.location.href = '<?php echo $destination; ?>';
                }, 1000);
                return;
            }
            
            const step = loadingSteps[currentStep];
            const messageEl = document.getElementById('loadingMessage');
            const stepEl = document.getElementById('loadingStep');
            const progressBar = document.getElementById('progressBar');
            const percentageEl = document.getElementById('loadingPercentage');
            
            // Mise à jour des textes avec animation
            messageEl.textContent = step.message;
            stepEl.textContent = step.step;
            progressBar.style.width = step.progress + '%';
            percentageEl.textContent = step.progress + '%';
            
            currentStep++;
            setTimeout(updateLoadingStep, step.duration);
        }
        
        // Démarrer le chargement après que la page soit prête
        window.addEventListener('load', function() {
            setTimeout(updateLoadingStep, 500);
        });
    </script>
</body>
</html>