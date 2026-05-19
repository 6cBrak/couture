<?php
/**
 * ENDPOINT - RÉINITIALISATION DES DONNÉES
 * Supprime les ventes, produits, clients ou utilisateurs
 */
require_once __DIR__ . '/../protection_pages.php';
require_admin(); // Uniquement les admins

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $type = trim($_POST['type'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($type)) {
        throw new Exception('Type de réinitialisation non spécifié');
    }
    
    if (empty($password)) {
        throw new Exception('Le mot de passe est requis');
    }
    
    // Vérifier le mot de passe de l'utilisateur actuel
    $user = get_user_by_id($user_id);
    if (!$user || !password_verify($password, $user['mot_de_passe'])) {
        throw new Exception('Mot de passe incorrect');
    }
    
    // Vérifier que le type est valide
    $types_valides = ['ventes', 'produits', 'clients', 'utilisateurs', 'caisse', 'depenses', 'tailleurs', 'complet'];
    if (!in_array($type, $types_valides)) {
        throw new Exception('Type de réinitialisation invalide');
    }
    
    db_begin_transaction();
    
    switch ($type) {
        case 'ventes':
            // Supprimer toutes les ventes et leurs détails
            db_execute("DELETE FROM details_vente");
            db_execute("DELETE FROM ventes");
            log_activity('REINIT', 'Suppression de toutes les ventes', ['type' => 'ventes']);
            $response['message'] = 'Toutes les ventes ont été supprimées avec succès.';
            break;
            
        case 'produits':
            // Supprimer tous les produits et réinitialiser le stock
            db_execute("DELETE FROM mouvements_stock");
            db_execute("DELETE FROM produits");
            log_activity('REINIT', 'Suppression de tous les produits', ['type' => 'produits']);
            $response['message'] = 'Tous les produits ont été supprimés avec succès.';
            break;
            
        case 'clients':
            // Supprimer tous les clients SAUF le client par défaut
            $client_defaut = db_fetch_one("SELECT id_client FROM clients WHERE nom_client LIKE '%Client%' OR nom_client LIKE '%comptoir%' LIMIT 1");
            $id_client_defaut = $client_defaut ? $client_defaut['id_client'] : null;
            
            if ($id_client_defaut) {
                db_execute("DELETE FROM clients WHERE id_client != ?", [$id_client_defaut]);
            } else {
                db_execute("DELETE FROM clients");
            }
            
            log_activity('REINIT', 'Suppression de tous les clients', ['type' => 'clients']);
            $response['message'] = 'Tous les clients ont été supprimés (sauf le client par défaut).';
            break;
            
        case 'utilisateurs':
            // Supprimer tous les utilisateurs SAUF l'utilisateur actuel
            db_execute("DELETE FROM utilisateurs WHERE id_utilisateur != ?", [$user_id]);
            log_activity('REINIT', 'Suppression de tous les utilisateurs', ['type' => 'utilisateurs']);
            $response['message'] = 'Tous les utilisateurs ont été supprimés (sauf vous-même).';
            break;
            
        case 'caisse':
            db_execute("DELETE FROM mouvements_caisse");
            db_execute("DELETE FROM caisse");
            log_activity('REINIT', 'Vidage historique caisse', ['type' => 'caisse']);
            $response['message'] = 'L\'historique de caisse a été vidé avec succès.';
            break;

        case 'depenses':
            db_execute("DELETE FROM depenses");
            log_activity('REINIT', 'Suppression de toutes les dépenses', ['type' => 'depenses']);
            $response['message'] = 'Toutes les dépenses ont été supprimées avec succès.';
            break;

        case 'tailleurs':
            db_execute("DELETE FROM mouvements_tailleur");
            db_execute("DELETE FROM paiements_tailleur");
            db_execute("DELETE FROM commandes_atelier");
            db_execute("DELETE FROM tailleurs");
            log_activity('REINIT', 'Suppression de tous les tailleurs et commandes atelier', ['type' => 'tailleurs']);
            $response['message'] = 'Tailleurs, commandes et paiements atelier supprimés avec succès.';
            break;

        case 'complet':
            // Réinitialisation COMPLÈTE du système
            // Supprimer toutes les données
            db_execute("DELETE FROM details_vente");
            db_execute("DELETE FROM ventes");
            db_execute("DELETE FROM mouvements_stock");
            db_execute("DELETE FROM produits");
            db_execute("DELETE FROM clients");
            db_execute("DELETE FROM utilisateurs WHERE id_utilisateur != ?", [$user_id]);
            db_execute("DELETE FROM categories");
            
            // Réinitialiser la configuration
            db_execute("UPDATE configuration SET 
                nom_boutique = 'Ma Boutique',
                adresse = '',
                telephone = '',
                email = '',
                devise = 'USD',
                couleur_primaire = '#0066cc',
                couleur_secondaire = '#004d99',
                logo = NULL
                WHERE id_config = 1");
            
            // Récréer le client par défaut
            db_execute("INSERT INTO clients (nom_client, type_client) VALUES (?, ?)", 
                ['Client Comptoir', 'particulier']);
            
            // Récréer les catégories par défaut
            $categories = [
                ['Électronique', 'Téléphones, ordinateurs, accessoires', '#3498db', 1],
                ['Électroménager', 'Réfrigérateurs, télévisions, cuisinières', '#e74c3c', 2],
                ['Meubles', 'Tables, chaises, armoires', '#9b59b6', 3],
                ['Vêtements', 'Habits, chaussures, accessoires', '#1abc9c', 4],
                ['Alimentation', 'Produits alimentaires', '#f39c12', 5],
            ];
            
            foreach ($categories as $cat) {
                db_execute("INSERT INTO categories (nom_categorie, description, couleur, ordre_affichage, est_actif) VALUES (?, ?, ?, ?, 1)",
                    [$cat[0], $cat[1], $cat[2], $cat[3]]);
            }
            
            log_activity('REINIT', 'RÉINITIALISATION COMPLÈTE du système', ['type' => 'complet']);
            $response['message'] = 'Le système a été complètement réinitialisé. Tous les paramètres ont été restaurés par défaut.';
            break;
    }
    
    db_commit();
    $response['success'] = true;
    
} catch (Exception $e) {
    if (db_in_transaction()) {
        db_rollback();
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
