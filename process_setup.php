<?php
/**
 * ============================================================================
 * TRAITEMENT DE LA CONFIGURATION INITIALE DU SYSTÈME
 * ============================================================================
 * 
 * Importance : Ce fichier traite les données du formulaire de configuration
 *              et initialise le système pour la première utilisation
 * 
 * Actions effectuées :
 * 1. Validation des données reçues
 * 2. Upload du logo si fourni
 * 3. Mise à jour de la configuration dans la base de données
 * 4. Création du compte administrateur
 * 5. Création des dossiers nécessaires
 * 6. Redirection vers la page de connexion
 * ============================================================================
 */

// Démarrer la session
session_start();

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/setup_errors.log');

require_once __DIR__ . '/config/database.php';

// Vérifier si le système est déjà configuré
if (is_system_configured()) {
    redirect(BASE_URL . 'login.php');
    exit;
}

// Vérifier que la requête est bien en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'setup.php');
    exit;
}

// ============================================================================
// VALIDATION DES DONNÉES
// ============================================================================

$errors = [];

// Champs obligatoires
$required_fields = [
    'nom_boutique' => 'Le nom de la boutique est obligatoire',
    'adresse' => 'L\'adresse est obligatoire',
    'telephone' => 'Le téléphone est obligatoire',
    'devise' => 'La devise est obligatoire',
    'admin_nom' => 'Le nom de l\'administrateur est obligatoire',
    'admin_login' => 'L\'identifiant administrateur est obligatoire',
    'admin_password' => 'Le mot de passe administrateur est obligatoire'
];

foreach ($required_fields as $field => $error_message) {
    if (empty($_POST[$field])) {
        $errors[] = $error_message;
    }
}

// Validation du mot de passe
if (!empty($_POST['admin_password']) && strlen($_POST['admin_password']) < 6) {
    $errors[] = 'Le mot de passe doit contenir au moins 6 caractères';
}

if ($_POST['admin_password'] !== $_POST['admin_password_confirm']) {
    $errors[] = 'Les mots de passe ne correspondent pas';
}

// Vérifier si le login admin existe déjà (seulement si ce n'est pas 'admin' qui sera supprimé)
if (!empty($_POST['admin_login']) && $_POST['admin_login'] !== 'admin') {
    if (user_exists($_POST['admin_login'])) {
        $errors[] = 'Cet identifiant existe déjà';
    }
}

// Si des erreurs, retourner à la page de configuration
if (!empty($errors)) {
    $_SESSION['setup_errors'] = $errors;
    $_SESSION['setup_data'] = $_POST;
    redirect(BASE_URL . 'setup.php');
    exit;
}

// ============================================================================
// CRÉATION DES DOSSIERS NÉCESSAIRES
// ============================================================================

$folders_to_create = [
    UPLOAD_PATH,
    LOGO_PATH,
    PRODUCT_IMG_PATH,
    USER_IMG_PATH
];

foreach ($folders_to_create as $folder) {
    if (!file_exists($folder)) {
        mkdir($folder, 0755, true);
    }
}

// ============================================================================
// TRAITEMENT DE L'UPLOAD DU LOGO
// ============================================================================

$logo_filename = null;

// Vérifier d'abord si un logo rogné (base64) a été envoyé
if (!empty($_POST['logo_cropped'])) {
    // Traiter l'image base64
    $base64_string = $_POST['logo_cropped'];
    
    // Extraire les données de l'image
    if (preg_match('/^data:image\/(\w+);base64,/', $base64_string, $type)) {
        $base64_string = substr($base64_string, strpos($base64_string, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif
        
        // Vérifier le type
        if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
            $errors[] = 'Format de fichier non autorisé pour le logo';
        } else {
            $base64_string = base64_decode($base64_string);
            
            if ($base64_string === false) {
                $errors[] = 'Erreur lors du décodage du logo';
            } else {
                // Générer un nom standardisé pour le fichier
                // Nom: logo_boutique.{type}
                $logo_filename = 'logo_boutique.' . $type;
                $logo_path = LOGO_PATH . $logo_filename;
                
                // Sauvegarder le fichier
                if (!file_put_contents($logo_path, $base64_string)) {
                    $errors[] = 'Erreur lors de la sauvegarde du logo';
                    $logo_filename = null;
                }
            }
        }
    }
} elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    // Traiter l'upload classique si pas de logo rogné
    $file = $_FILES['logo'];
    
    // Vérifier la taille du fichier (5MB max)
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'Le fichier logo est trop volumineux (max 5MB)';
    }
    
    // Vérifier l'extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, ALLOWED_IMAGE_EXTENSIONS)) {
        $errors[] = 'Format de fichier non autorisé pour le logo';
    }
    
    if (empty($errors)) {
        // Générer un nom unique pour le fichier
        $logo_filename = 'logo_' . uniqid() . '.' . $file_extension;
        $logo_path = LOGO_PATH . $logo_filename;
        
        // Déplacer le fichier uploadé
        if (!move_uploaded_file($file['tmp_name'], $logo_path)) {
            $errors[] = 'Erreur lors de l\'upload du logo';
            $logo_filename = null;
        }
    }
}

// Si erreurs après upload, retourner
if (!empty($errors)) {
    $_SESSION['setup_errors'] = $errors;
    $_SESSION['setup_data'] = $_POST;
    redirect(BASE_URL . 'setup.php');
    exit;
}

// ============================================================================
// TRANSACTION DE BASE DE DONNÉES
// ============================================================================

try {
    // Démarrer une transaction
    db_begin_transaction();
    
    // ========================================================================
    // 0. S'ASSURER QU'IL N'Y A QU'UNE SEULE LIGNE DE CONFIGURATION
    // ========================================================================
    
    // Supprimer toutes les lignes de configuration sauf celle avec id_config = 1
    db_execute("DELETE FROM configuration WHERE id_config != 1");
    
    // S'assurer qu'il existe une ligne avec id_config = 1
    $check = db_query("SELECT COUNT(*) as count FROM configuration WHERE id_config = 1");
    $count = $check->fetch();
    
    if ($count['count'] == 0) {
        // Créer la ligne si elle n'existe pas
        db_execute("INSERT INTO configuration (id_config) VALUES (1)");
    }
    
    // ========================================================================
    // 1. MISE À JOUR DE LA CONFIGURATION
    // ========================================================================
    
    $config_data = [
        'nom_boutique' => trim($_POST['nom_boutique']),
        'slogan' => trim($_POST['slogan'] ?? ''),
        'logo' => $logo_filename ? 'uploads/logos/' . $logo_filename : null,
        'couleur_primaire' => $_POST['couleur_primaire'] ?? '#1a7f5a',
        'couleur_secondaire' => $_POST['couleur_secondaire'] ?? '#206bc4',
        'adresse' => trim($_POST['adresse']),
        'telephone' => trim($_POST['telephone']),
        'email' => trim($_POST['email'] ?? ''),
        'site_web' => trim($_POST['site_web'] ?? ''),
        'num_registre_commerce' => trim($_POST['num_registre_commerce'] ?? ''),
        'num_impot' => trim($_POST['num_impot'] ?? ''),
        'devise' => $_POST['devise'],
        'taux_tva' => floatval($_POST['taux_tva'] ?? 0),
        'est_configure' => 1,
        'date_configuration' => date('Y-m-d H:i:s')
    ];
    
    // Mettre à jour la configuration
    db_update('configuration', $config_data, 'id_config = ?', [1]);
    
    // ========================================================================
    // 2. SUPPRESSION DE L'ADMIN PAR DÉFAUT ET CRÉATION DU NOUVEAU
    // ========================================================================
    
    // Supprimer l'utilisateur admin par défaut
    db_delete('utilisateurs', 'login = ?', ['admin']);
    
    // Créer le nouveau compte administrateur
    $admin_data = [
        'nom_complet' => trim($_POST['admin_nom']),
        'login' => trim($_POST['admin_login']),
        'mot_de_passe' => password_hash($_POST['admin_password'], PASSWORD_DEFAULT),
        'email' => trim($_POST['admin_email'] ?? ''),
        'niveau_acces' => NIVEAU_ADMIN,
        'est_actif' => 1,
        'date_creation' => date('Y-m-d H:i:s')
    ];
    
    $admin_id = db_insert('utilisateurs', $admin_data);
    
    // ========================================================================
    // 3. CRÉATION D'UNE NOTIFICATION DE BIENVENUE
    // ========================================================================
    
    $notification_data = [
        'type_notification' => 'systeme',
        'titre' => 'Bienvenue !',
        'message' => 'Votre système de gestion de stock a été configuré avec succès. Vous pouvez maintenant commencer à ajouter vos produits et effectuer vos ventes.',
        'niveau_urgence' => 'info',
        'est_lue' => 0,
        'date_creation' => date('Y-m-d H:i:s')
    ];
    
    db_insert('notifications', $notification_data);
    
    // ========================================================================
    // 4. ENREGISTREMENT DANS LES LOGS
    // ========================================================================
    
    $log_data = [
        'id_utilisateur' => $admin_id,
        'type_action' => 'configuration_initiale',
        'description' => 'Configuration initiale du système effectuée',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'donnees_json' => json_encode([
            'nom_boutique' => $_POST['nom_boutique'],
            'admin_login' => $_POST['admin_login']
        ]),
        'date_action' => date('Y-m-d H:i:s')
    ];
    
    db_insert('logs_activites', $log_data);
    
    // Valider la transaction
    db_commit();
    
    // ========================================================================
    // 5. CRÉATION DU FICHIER .htaccess POUR SÉCURITÉ
    // ========================================================================
    
    $htaccess_content = "# Protection des dossiers d'upload\n";
    $htaccess_content .= "Options -Indexes\n";
    $htaccess_content .= "<FilesMatch \"\\.(php|php3|php4|php5|phtml)$\">\n";
    $htaccess_content .= "  Order Allow,Deny\n";
    $htaccess_content .= "  Deny from all\n";
    $htaccess_content .= "</FilesMatch>\n";
    
    file_put_contents(UPLOAD_PATH . '.htaccess', $htaccess_content);
    
    // ========================================================================
    // 6. REDIRECTION VERS LA PAGE DE SUCCÈS
    // ========================================================================
    
    $_SESSION['setup_success'] = true;
    redirect(BASE_URL . 'setup_success.php');
    
} catch (Exception $e) {
    // En cas d'erreur, annuler toutes les modifications
    db_rollback();
    
    // Supprimer le logo uploadé si existant
    if ($logo_filename && file_exists(LOGO_PATH . $logo_filename)) {
        unlink(LOGO_PATH . $logo_filename);
    }
    
    // Log l'erreur
    error_log("Erreur configuration: " . $e->getMessage());
    
    // Retourner à la page de configuration avec l'erreur
    $_SESSION['setup_errors'] = ['Une erreur est survenue lors de la configuration : ' . $e->getMessage()];
    $_SESSION['setup_data'] = $_POST;
    redirect(BASE_URL . 'setup.php');
}

// ============================================================================
// FIN DU FICHIER
// ============================================================================
