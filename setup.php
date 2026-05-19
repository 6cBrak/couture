<?php
/**
 * Configuration initiale du système
 * Cette page s'affiche uniquement si le système n'est pas encore configuré
 */

session_start();

// Charger la configuration de base (sans protection de page)
require_once 'config/config.php';
require_once 'config/database.php';

// Vérifier si le système est déjà configuré
if (is_system_configured()) {
    redirect('index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Initiale - Store Suite</title>
    <link href="./assets/css/tabler.min.css" rel="stylesheet"/>
    <link href="./assets/css/style.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css"/>
    <style>
        .btn-primary, .btn-success, .btn-secondary {
            color: #ffffff !important;
        }
        .btn-primary:hover, .btn-success:hover, .btn-secondary:hover {
            color: #ffffff !important;
        }
        
        /* Footer moderne minimaliste */
        .setup-footer {
            margin-top: 4rem;
            padding: 2.5rem 0 1.5rem;
            text-align: center;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 16px;
            border-top: 3px solid #e2e8f0;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.02);
        }
        
        .footer-tagline {
            font-size: 1rem;
            color: #334155;
            font-weight: 600;
            margin-bottom: 1rem;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #1e40af 0%, #1a5aa8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .footer-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }
        
        .footer-separator {
            opacity: 0.5;
            font-weight: 300;
        }
        
        .footer-text {
            transition: all 0.2s ease;
        }
        
        .footer-text:hover {
            color: #1a5aa8;
            transform: translateY(-1px);
        }
        
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
            color: #1a5aa8;
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
</head>
<body>
    <div class="page page-center">
        <div class="container-xl py-4">
            <div class="setup-container">
                <div class="setup-header">
                    <h1>Bienvenue dans Store Suite</h1>
                    <p>Configuration initiale de votre système de gestion de stock</p>
                </div>

                <div class="step-indicator">
                    <span class="step active" id="step1">1</span>
                    <span class="step" id="step2">2</span>
                    <span class="step" id="step3">3</span>
                </div>

                <?php if (!empty($_SESSION['setup_errors'])): ?>
                <div class="alert alert-danger mb-3">
                    <strong>Erreur lors de la configuration :</strong>
                    <ul class="mb-0 mt-1">
                        <?php foreach ($_SESSION['setup_errors'] as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['setup_errors']); ?>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form id="setupForm" method="POST" action="process_setup.php" enctype="multipart/form-data" novalidate>
                            
                            <!-- ÉTAPE 1: Informations de base -->
                            <div class="setup-step" id="setupStep1">
                                <h2 class="card-title mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    Configuration de la boutique
                                </h2>
                                
                                <div class="mb-3">
                                    <label class="form-label required">
                                        Nom de la boutique
                                        <span class="info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Ce nom apparaîtra sur toutes les factures et rapports">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                        </span>
                                    </label>
                                    <input type="text" class="form-control" name="nom_boutique" id="nom_boutique" required placeholder="Ex: Super Électro Shop">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Slogan (optionnel)</label>
                                    <input type="text" class="form-control" name="slogan" placeholder="Ex: Votre partenaire technologique">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label required">Adresse complète</label>
                                    <textarea class="form-control" name="adresse" rows="2" required placeholder="Avenue, numéro, quartier, ville"></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">Téléphone</label>
                                        <input type="text" class="form-control" name="telephone" required placeholder="+226 XXX XXX XXX">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" placeholder="contact@votreboutique.com">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            Numéro d'enregistrement
                                            <span class="info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="RCCM, registre de commerce, numéro d'identification">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                            </span>
                                        </label>
                                        <input type="text" class="form-control" name="num_registre_commerce" placeholder="RCCM/CD/XXX/...">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            Numéro fiscal
                                            <span class="info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Numéro d'impôt ou TVA de votre entreprise">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                            </span>
                                        </label>
                                        <input type="text" class="form-control" name="num_impot" placeholder="N° Impôt ou TVA">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">
                                            Devise
                                            <span class="info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Devise principale utilisée pour les transactions">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                            </span>
                                        </label>
                                        <select class="form-select" name="devise" required>
                                            <option value="XOF" selected>XOF - Franc CFA</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            Site web
                                            <span class="info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Adresse de votre site internet (optionnel)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                            </span>
                                        </label>
                                        <input type="url" class="form-control" name="site_web" placeholder="https://www.votresite.com">
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                                        Suivant
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 6 15 12 9 18" /></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- ÉTAPE 2: Personnalisation visuelle -->
                            <div class="setup-step" id="setupStep2" style="display:none;">
                                <h2 class="card-title mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" /></svg>
                                    Personnalisation visuelle
                                </h2>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Logo de la boutique
                                        <span class="info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Ce logo apparaîtra sur les factures et rapports. Format JPG, PNG ou GIF (max 5MB). Vous pourrez rogner l'image après sélection.">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                        </span>
                                    </label>
                                    <input type="file" class="form-control" id="logoInput" accept="image/*" onchange="selectLogo(this)">
                                    <input type="hidden" name="logo_cropped" id="logoCroppedData">
                                    <div id="logoPreviewContainer" style="display:none; margin-top:10px;">
                                        <img id="logoPreview" class="logo-preview" alt="Aperçu du logo">
                                        <button type="button" class="btn btn-sm btn-warning mt-2" onclick="reopenCrop()">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="8" y="8" width="12" height="12" rx="1" /><path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2" /></svg>
                                            Recadrer le logo
                                        </button>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            Couleur primaire
                                            <span class="info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Couleur principale utilisée pour : en-tête, menu de navigation, boutons principaux et liens actifs">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                            </span>
                                        </label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" name="couleur_primaire" id="couleur1" value="#206bc4" onchange="updateColorPreview('couleur1', 'preview1')">
                                            <input type="text" class="form-control" id="couleur1_text" value="#206bc4" readonly>
                                        </div>
                                        <div class="color-preview" id="preview1" style="background:#206bc4;"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            Couleur secondaire
                                            <span class="info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Couleur secondaire utilisée pour : survol des boutons, badges, éléments d'accentuation et fond de certains widgets">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                            </span>
                                        </label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" name="couleur_secondaire" id="couleur2" value="#1a5aa8" onchange="updateColorPreview('couleur2', 'preview2')">
                                            <input type="text" class="form-control" id="couleur2_text" value="#1a5aa8" readonly>
                                        </div>
                                        <div class="color-preview" id="preview2" style="background:#1a5aa8;"></div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep(1)">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="15 6 9 12 15 18" /></svg>
                                        Précédent
                                    </button>
                                    <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                                        Suivant
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 6 15 12 9 18" /></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- ÉTAPE 3: Création du compte administrateur -->
                            <div class="setup-step" id="setupStep3" style="display:none;">
                                <h2 class="card-title mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
                                    Compte administrateur
                                </h2>

                                <div class="alert alert-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>
                                    <strong>Important :</strong> Créez votre compte administrateur principal. Conservez bien ces identifiants en lieu sûr !
                                </div>

                                <div class="mb-3">
                                    <label class="form-label required">Nom complet</label>
                                    <input type="text" class="form-control" name="admin_nom" required placeholder="Ex: Jean Dupont">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label required">Identifiant de connexion</label>
                                    <input type="text" class="form-control" name="admin_login" required placeholder="Ex: admin">
                                    <small class="form-hint">Utilisé pour se connecter au système</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label required">Mot de passe</label>
                                    <input type="password" class="form-control" name="admin_password" id="admin_password" required minlength="6" placeholder="Minimum 6 caractères">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label required">Confirmer le mot de passe</label>
                                    <input type="password" class="form-control" name="admin_password_confirm" id="admin_password_confirm" required minlength="6" placeholder="Retapez le mot de passe">
                                    <div id="passwordError" class="text-danger" style="display:none;">Les mots de passe ne correspondent pas</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email administrateur</label>
                                    <input type="email" class="form-control" name="admin_email" placeholder="admin@votreboutique.com">
                                </div>

                                <hr class="my-4">

                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="15 6 9 12 15 18" /></svg>
                                        Précédent
                                    </button>
                                    <button type="submit" class="btn btn-success" id="submitBtn">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 11 12 14 20 6" /><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9" /></svg>
                                        Terminer la configuration
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Footer moderne -->
                <footer class="setup-footer">
                    <div class="footer-tagline">Votre solution intelligente de gestion commerciale et facturation</div>
                    <div class="footer-content">
                        <span class="footer-text">Store Suite v2.0</span>
                        <span class="footer-separator">•</span>
                        <span class="footer-text">© 2026</span>
                    </div>
                </footer>
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

    <script src="./assets/js/tabler.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        let cropper = null;
        let currentImageFile = null;
        
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
        
        // Navigation entre les étapes
        function nextStep(stepNumber) {
            // Validation basique avant de passer à l'étape suivante
            if (stepNumber === 2) {
                const nomBoutique = document.getElementById('nom_boutique').value;
                if (!nomBoutique.trim()) {
                    alert('Veuillez entrer le nom de la boutique');
                    return;
                }
            }
            
            showStep(stepNumber);
        }

        function prevStep(stepNumber) {
            showStep(stepNumber);
        }

        function showStep(stepNumber) {
            // Cacher toutes les étapes
            document.querySelectorAll('.setup-step').forEach(el => el.style.display = 'none');
            
            // Afficher l'étape demandée
            document.getElementById('setupStep' + stepNumber).style.display = 'block';
            
            // Mettre à jour les indicateurs
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + stepNumber).classList.add('active');
        }

        // Mise à jour de l'aperçu des couleurs
        function updateColorPreview(inputId, previewId) {
            const color = document.getElementById(inputId).value;
            document.getElementById(previewId).style.background = color;
            document.getElementById(inputId + '_text').value = color;
        }

        // Validation manuelle du formulaire (novalidate désactive la validation native)
        document.getElementById('setupForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const adminNom = document.querySelector('[name="admin_nom"]').value.trim();
            const adminLogin = document.querySelector('[name="admin_login"]').value.trim();
            const password = document.getElementById('admin_password').value;
            const passwordConfirm = document.getElementById('admin_password_confirm').value;

            if (!adminNom || !adminLogin || !password) {
                alert('Veuillez remplir tous les champs obligatoires.');
                return;
            }

            if (password.length < 6) {
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return;
            }

            if (password !== passwordConfirm) {
                document.getElementById('passwordError').style.display = 'block';
                alert('Les mots de passe ne correspondent pas !');
                return;
            }

            document.getElementById('passwordError').style.display = 'none';

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Configuration en cours...';

            this.submit();
        });

        // Initialiser les tooltips Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof bootstrap !== 'undefined') {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        });
    </script>
</body>
</html>
