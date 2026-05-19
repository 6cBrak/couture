<?php
/**
 * ============================================================================
 * FICHIER DE CONFIGURATION GLOBALE DU SYSTÈME
 * ============================================================================
 * 
 * Importance : Ce fichier contient toutes les constantes et paramètres
 *              essentiels au fonctionnement du système
 * 
 * Contenu :
 * - Paramètres de connexion à la base de données
 * - Constantes de chemin et URL
 * - Paramètres de sécurité
 * - Configuration des sessions
 * - Paramètres de l'application
 * 
 * ⚠️ SÉCURITÉ : Ne jamais commit ce fichier avec des vrais identifiants
 * ============================================================================
 */

// ============================================================================
// CONFIGURATION DE LA BASE DE DONNÉES
// ============================================================================
// Variables d'environnement Docker prioritaires, sinon valeurs XAMPP locales
define('DB_HOST',    getenv('DB_HOST') ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME') ?: 'c1790820c_shopdemo_tailor');
define('DB_USER',    getenv('DB_USER') ?: 'root');
define('DB_PASS',    getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ============================================================================
// CHEMINS ET URLs DU SYSTÈME
// ============================================================================
// Chemin absolu vers le dossier racine de l'application
define('ROOT_PATH', dirname(__DIR__));

// URL de base du site — détectée automatiquement (local ou production)
$_base_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_base_port = $_SERVER['SERVER_PORT'] ?? 80;
$_base_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ? 'https'
    : ((($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http');
$_base_script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$_base_root = rtrim(preg_replace('#/(ajax|config|database|dist|tests)(/.*)?$#', '', $_base_script), '/');
define('BASE_URL', $_base_scheme . '://' . $_base_host . $_base_root . '/');

// Chemins vers les dossiers importants
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('LOGO_PATH', UPLOAD_PATH . 'logos/');
define('PRODUCT_IMG_PATH', UPLOAD_PATH . 'produits/');
define('USER_IMG_PATH', UPLOAD_PATH . 'utilisateurs/');

// ============================================================================
// CONFIGURATION DE SÉCURITÉ
// ============================================================================
// Clé secrète pour le hashage (CHANGER EN PRODUCTION!)
define('SECRET_KEY', 'VotreCleSuperSecreteAChanger2026!@#');

// Durée de validité d'une session (en secondes) - 2 heures par défaut
define('SESSION_LIFETIME', 7200);

// Nombre maximum de tentatives de connexion avant blocage
define('MAX_LOGIN_ATTEMPTS', 5);

// Durée du blocage après trop de tentatives (en minutes)
define('LOGIN_BLOCK_DURATION', 15);

// ============================================================================
// CONFIGURATION DE L'APPLICATION
// ============================================================================
// Nom de l'application
define('APP_NAME', 'STORESUITE');
define('APP_VERSION', '2.0.0');
define('DEVISE', 'XOF');                     // Devise par défaut

// Fuseau horaire par défaut
date_default_timezone_set('Africa/Ouagadougou');

// Configuration PHP pour affichage des erreurs (DÉSACTIVER EN PRODUCTION)
if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ============================================================================
// NIVEAUX D'ACCÈS UTILISATEUR
// ============================================================================
define('NIVEAU_ADMIN', 1);         // Administrateur - Accès complet
define('NIVEAU_VENDEUR', 2);       // Vendeur/Caissier - Accès limité

// ============================================================================
// STATUTS DES VENTES
// ============================================================================
define('VENTE_EN_COURS', 'en_cours');
define('VENTE_VALIDEE', 'validee');
define('VENTE_ANNULEE', 'annulee');

// ============================================================================
// TYPES DE MOUVEMENTS DE STOCK
// ============================================================================
define('MOUVEMENT_ENTREE', 'entree');
define('MOUVEMENT_SORTIE', 'sortie');
define('MOUVEMENT_AJUSTEMENT', 'ajustement');
define('MOUVEMENT_RETOUR', 'retour');

// ============================================================================
// CONFIGURATION DES UPLOADS
// ============================================================================
// Taille maximale des fichiers uploadés (en octets) - 5MB par défaut
define('MAX_FILE_SIZE', 5242880);

// Extensions autorisées pour les images
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ============================================================================
// CONFIGURATION SUPPLÉMENTAIRE
// ============================================================================
// Configuration TVA — valeur par défaut (fallback).
// Le taux effectif est lu depuis la table configuration (colonne taux_tva)
// et exposé via $taux_tva dans protection_pages.php.
define('TVA_STANDARD', 0.00);

// Configuration de la pagination
define('ITEMS_PER_PAGE', 20);                // Nombre d'éléments par page

// ============================================================================
// FONCTIONS UTILITAIRES GLOBALES
// ============================================================================

/**
 * Fonction pour échapper les données HTML
 * Prévient les attaques XSS
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Fonction pour rediriger vers une page
 */
function redirect($url, $code = 302) {
    header("Location: $url", true, $code);
    exit;
}

/**
 * Fonction pour afficher un message d'erreur et arrêter l'exécution
 */
function die_error($message) {
    die("<div style='background:#f8d7da;color:#721c24;padding:20px;border:1px solid #f5c6cb;border-radius:5px;margin:20px;'>
        <strong>Erreur :</strong> $message
    </div>");
}

/**
 * Fonction pour formater les montants avec la devise
 */
function format_montant($montant, $devise = 'XOF') {
    return number_format($montant, 2, ',', ' ') . ' ' . $devise;
}

/**
 * Fonction pour formater les dates
 */
function format_date($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Génère un token CSRF pour sécuriser les formulaires
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est administrateur
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['niveau_acces']) && $_SESSION['niveau_acces'] == NIVEAU_ADMIN;
}

/**
 * Obtient l'ID de l'utilisateur connecté
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtient le nom de l'utilisateur connecté
 */
function get_user_name() {
    return $_SESSION['user_name'] ?? 'Utilisateur';
}

/**
 * Enregistre une activité dans les logs
 */
function log_activity($type_action, $description, $donnees = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs_activites (id_utilisateur, type_action, description, ip_address, user_agent, donnees_json)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            get_user_id(),
            $type_action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $donnees ? json_encode($donnees) : null
        ]);
    } catch (Exception $e) {
        // En cas d'erreur, on ne bloque pas l'application
        error_log("Erreur log_activity: " . $e->getMessage());
    }
}

// ============================================================================
// MESSAGE FLASH (pour afficher des messages après redirection)
// ============================================================================
function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type; // success, error, warning, info
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// ============================================================================
// FIN DU FICHIER DE CONFIGURATION
// ============================================================================
