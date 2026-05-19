<?php
/**
 * ============================================================================
 * DÉCONNEXION - LOGOUT
 * ============================================================================
 * 
 * Importance : Ferme la session utilisateur de manière sécurisée
 * 
 * Fonctionnement :
 * 1. Enregistre l'action de déconnexion dans les logs
 * 2. Détruit toutes les données de session
 * 3. Supprime les cookies de session
 * 4. Redirige vers la page de connexion
 * 
 * Sécurité : Nettoie complètement la session pour éviter les accès non autorisés
 * 
 * ============================================================================
 */

session_start();

// Enregistrer la déconnexion dans les logs avant de détruire la session
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/config/database.php';
    log_activity('deconnexion', 'Déconnexion de ' . ($_SESSION['user_name'] ?? 'utilisateur'));
}

// Détruire toutes les variables de session
$_SESSION = [];

// Détruire le cookie de session si il existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion avec un message
session_start();
$_SESSION['flash_message'] = 'Vous avez été déconnecté avec succès.';
$_SESSION['flash_type'] = 'success';

header("Location: login.php");
exit;
?>