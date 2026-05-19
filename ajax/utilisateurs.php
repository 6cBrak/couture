<?php
/**
 * AJAX - GESTION DES UTILISATEURS
 * Ajouter, modifier, supprimer, activer/désactiver des utilisateurs
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

// Vérifier que c'est un admin
if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé. Seuls les administrateurs peuvent gérer les utilisateurs.']);
    exit;
}

// Action GET : récupérer un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $id = intval($_GET['id'] ?? 0);
    
    if ($action === 'get' && $id > 0) {
        try {
            $user = db_fetch_one("SELECT * FROM utilisateurs WHERE id_utilisateur = ?", [$id]);
            if ($user) {
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Action invalide']);
    }
    exit;
}

// Actions POST
$response = ['success' => false, 'message' => ''];

// Logging pour debug
error_log("POST Data: " . print_r($_POST, true));

try {
    $action = $_POST['action'] ?? '';
    $isEdit = ($_POST['is_edit'] ?? '0') === '1';
    
    error_log("Action: $action, IsEdit: " . ($isEdit ? 'YES' : 'NO'));
    
    // Création ou modification d'utilisateur
    if (empty($action) && !$isEdit) {
        // CRÉATION D'UN NOUVEL UTILISATEUR
        error_log("Entering CREATION block");
        $nom = trim($_POST['nom_complet'] ?? '');
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['mot_de_passe'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $est_admin_input = intval($_POST['est_admin'] ?? 0);
        $niveau_acces = ($est_admin_input == 1) ? 1 : 2;
        $id_depot = !empty($_POST['id_depot']) ? intval($_POST['id_depot']) : null;

        if (empty($nom) || empty($login) || empty($password)) {
            throw new Exception('Nom, identifiant et mot de passe sont obligatoires');
        }

        if (strlen($password) < 6) {
            throw new Exception('Le mot de passe doit contenir au moins 6 caractères');
        }

        if ($niveau_acces == NIVEAU_VENDEUR && !$id_depot) {
            throw new Exception('Un dépôt doit être assigné au vendeur/caissier');
        }

        // Vérifier si le login existe déjà
        $existing = db_fetch_one("SELECT id_utilisateur FROM utilisateurs WHERE login = ?", [$login]);
        if ($existing) {
            throw new Exception('Cet identifiant de connexion existe déjà');
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO utilisateurs (nom_complet, login, mot_de_passe, email, niveau_acces, id_depot, est_actif, date_creation)
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
        db_execute($sql, [$nom, $login, $password_hash, $email, $niveau_acces, $id_depot]);
        
        $response['success'] = true;
        $response['message'] = 'Utilisateur créé avec succès';
        
    } elseif ($isEdit) {
        // MODIFICATION D'UN UTILISATEUR EXISTANT
        $id_utilisateur = intval($_POST['id_utilisateur'] ?? 0);
        $nom = trim($_POST['nom_complet'] ?? '');
        $login = trim($_POST['login'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $est_admin_input = intval($_POST['est_admin'] ?? 0);
        $niveau_acces = ($est_admin_input == 1) ? 1 : 2;
        $id_depot = !empty($_POST['id_depot']) ? intval($_POST['id_depot']) : null;
        $password = $_POST['mot_de_passe'] ?? '';

        if (!$id_utilisateur) throw new Exception('ID utilisateur manquant');
        if (empty($nom) || empty($login)) throw new Exception('Nom et identifiant sont obligatoires');

        if ($niveau_acces == NIVEAU_VENDEUR && !$id_depot) {
            throw new Exception('Un dépôt doit être assigné au vendeur/caissier');
        }

        // Vérifier si le login existe déjà (sauf pour cet utilisateur)
        $existing = db_fetch_one("SELECT id_utilisateur FROM utilisateurs WHERE login = ? AND id_utilisateur != ?", [$login, $id_utilisateur]);
        if ($existing) throw new Exception('Cet identifiant de connexion est déjà utilisé par un autre utilisateur');

        if (!empty($password)) {
            if (strlen($password) < 6) throw new Exception('Le mot de passe doit contenir au moins 6 caractères');
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE utilisateurs SET nom_complet=?, login=?, mot_de_passe=?, email=?, niveau_acces=?, id_depot=? WHERE id_utilisateur=?";
            db_execute($sql, [$nom, $login, $password_hash, $email, $niveau_acces, $id_depot, $id_utilisateur]);
        } else {
            $sql = "UPDATE utilisateurs SET nom_complet=?, login=?, email=?, niveau_acces=?, id_depot=? WHERE id_utilisateur=?";
            db_execute($sql, [$nom, $login, $email, $niveau_acces, $id_depot, $id_utilisateur]);
        }
        
        $response['success'] = true;
        $response['message'] = 'Utilisateur modifié avec succès';
        
    } elseif ($action === 'delete') {
        // SUPPRESSION D'UN UTILISATEUR
        $id_utilisateur = intval($_POST['id_utilisateur'] ?? 0);
        
        if (!$id_utilisateur) {
            throw new Exception('ID utilisateur manquant');
        }
        
        // Empêcher la suppression de soi-même
        if ($id_utilisateur == $user_id) {
            throw new Exception('Vous ne pouvez pas supprimer votre propre compte');
        }
        
        // Vérifier si l'utilisateur a des ventes
        $ventes = db_fetch_one("SELECT COUNT(*) as nb FROM ventes WHERE id_vendeur = ?", [$id_utilisateur]);
        
        if ($ventes && $ventes['nb'] > 0) {
            // Désactiver au lieu de supprimer pour préserver l'historique
            db_execute("UPDATE utilisateurs SET est_actif = 0 WHERE id_utilisateur = ?", [$id_utilisateur]);
            $response['message'] = 'Utilisateur désactivé (il a des ventes associées, suppression impossible)';
        } else {
            // Supprimer réellement
            db_execute("DELETE FROM utilisateurs WHERE id_utilisateur = ?", [$id_utilisateur]);
            $response['message'] = 'Utilisateur supprimé avec succès';
        }
        
        $response['success'] = true;
        
    } elseif ($action === 'toggle_status') {
        // ACTIVER/DÉSACTIVER UN UTILISATEUR
        $id_utilisateur = intval($_POST['id_utilisateur'] ?? 0);
        $nouveau_statut = intval($_POST['est_actif'] ?? 1);
        
        if (!$id_utilisateur) {
            throw new Exception('ID utilisateur manquant');
        }
        
        if ($id_utilisateur == $user_id && $nouveau_statut == 0) {
            throw new Exception('Vous ne pouvez pas désactiver votre propre compte');
        }
        
        db_execute("UPDATE utilisateurs SET est_actif = ? WHERE id_utilisateur = ?", [$nouveau_statut, $id_utilisateur]);
        
        $response['success'] = true;
        $response['message'] = $nouveau_statut ? 'Utilisateur activé avec succès' : 'Utilisateur désactivé';
        
    } else {
        throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
