<?php
/**
 * ============================================================================
 * PROTECTION DES PAGES - MIDDLEWARE D'AUTHENTIFICATION
 * ============================================================================
 * 
 * Importance : Fichier à inclure en haut de chaque page protégée
 *              pour vérifier que l'utilisateur est bien connecté
 * 
 * Fonctionnement :
 * 1. Vérifie si une session existe
 * 2. Vérifie si l'utilisateur est connecté
 * 3. Si non connecté → Redirige vers login.php
 * 4. Vérifie la durée de la session (timeout)
 * 5. Récupère les informations utilisateur et configuration
 * 
 * Usage : require_once('protection_pages.php'); en haut de chaque page protégée
 * 
 * ============================================================================
 */

// Démarrer la session si pas encore démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la configuration
require_once __DIR__ . '/config/database.php';

// ============================================================================
// VÉRIFICATION 1 : UTILISATEUR CONNECTÉ ?
// ============================================================================
if (!is_logged_in()) {
    // Sauvegarder la page demandée pour redirection après connexion
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Message flash
    set_flash_message('Veuillez vous connecter pour accéder à cette page.', 'warning');
    
    // Redirection vers login
    redirect(BASE_URL . 'login.php');
    exit;
}

// ============================================================================
// VÉRIFICATION 2 : TIMEOUT DE SESSION
// ============================================================================
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    // Session expirée
    session_unset();
    session_destroy();
    
    session_start();
    set_flash_message('Votre session a expiré. Veuillez vous reconnecter.', 'warning');
    
    redirect(BASE_URL . 'login.php');
    exit;
}

// Mettre à jour le timestamp de dernière activité
$_SESSION['last_activity'] = time();

// ============================================================================
// RÉCUPÉRATION DES INFORMATIONS GLOBALES
// ============================================================================

// Récupérer les informations de l'utilisateur connecté
$id_utilisateur = get_user_id();
$user_data = get_user_by_id($id_utilisateur);

if (!$user_data) {
    // Utilisateur introuvable (compte peut-être supprimé)
    session_destroy();
    redirect(BASE_URL . 'login.php');
    exit;
}

// Vérifier si le compte est toujours actif
if ($user_data['est_actif'] != 1) {
    session_destroy();
    session_start();
    set_flash_message('Votre compte a été désactivé. Contactez l\'administrateur.', 'error');
    redirect(BASE_URL . 'login.php');
    exit;
}

// Récupérer la configuration du système
$config = get_system_config();

if (!$config || !$config['est_configure']) {
    // Système non configuré
    redirect(BASE_URL . 'setup.php');
    exit;
}

// ============================================================================
// VARIABLES GLOBALES DISPONIBLES DANS TOUTES LES PAGES PROTÉGÉES
// ============================================================================

// Informations utilisateur
$user_id = $user_data['id_utilisateur'];
$user_name = $user_data['nom_complet'];
$user_login = $user_data['login'];
$user_email = $user_data['email'];
$user_niveau = $user_data['niveau_acces'];
$is_admin = ($user_niveau == NIVEAU_ADMIN);

// Configuration système
$nom_boutique = $config['nom_boutique'];
$logo_boutique = $config['logo'];
$couleur_primaire = $config['couleur_primaire'];
$couleur_secondaire = $config['couleur_secondaire'];
$devise = $config['devise'];
$taux_tva = isset($config['taux_tva']) ? (float)$config['taux_tva'] : 0.00;
$atelier_mode = (int)($config['atelier_mode'] ?? 0);

// Compter les notifications non lues
$notifications_count = get_notifications_count();

// Compter les produits en alerte
$products_alert_count = get_products_alert_count();

// ============================================================================
// FONCTIONS UTILITAIRES POUR VÉRIFICATION DES PERMISSIONS
// ============================================================================

/**
 * Vérifie si l'utilisateur actuel est administrateur
 * Si non, redirige vers page d'accès refusé
 */
function require_admin() {
    global $is_admin;
    if (!$is_admin) {
        redirect(BASE_URL . 'acces_refusé.php');
        exit;
    }
}

/**
 * Vérifie un niveau d'accès minimum requis
 * @param int $niveau_requis Niveau minimum requis
 */
function require_niveau($niveau_requis) {
    global $user_niveau;
    if ($user_niveau > $niveau_requis) {
        redirect(BASE_URL . 'acces_refusé.php');
        exit;
    }
}

// ============================================================================
// FIN DE LA PROTECTION - PAGE SÉCURISÉE
// ============================================================================
?>