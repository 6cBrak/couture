<?php
/**
 * AJAX - GESTION DES CLIENTS
 * Ajouter, modifier, supprimer des clients
 */
require_once '../protection_pages.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'add_client':
            $nom = trim($_POST['nom_client'] ?? '');
            $telephone = trim($_POST['telephone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $adresse = trim($_POST['adresse'] ?? '');
            
            if (empty($nom)) {
                throw new Exception('Le nom du client est obligatoire');
            }
            
            $id = db_insert('clients', [
                'nom_client' => $nom,
                'telephone'  => $telephone,
                'email'      => $email,
                'adresse'    => $adresse,
                'est_actif'  => 1,
            ]);

            $response['success']   = true;
            $response['message']   = 'Client ajouté avec succès';
            $response['id_client'] = $id;
            break;
            
        case 'update_client':
            $id_client = intval($_POST['id_client'] ?? 0);
            $nom = trim($_POST['nom_client'] ?? '');
            $telephone = trim($_POST['telephone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $adresse = trim($_POST['adresse'] ?? '');
            
            if (!$id_client) {
                throw new Exception('ID client manquant');
            }
            
            if (empty($nom)) {
                throw new Exception('Le nom du client est obligatoire');
            }
            
            $sql = "UPDATE clients SET nom_client = ?, telephone = ?, email = ?, adresse = ? 
                    WHERE id_client = ?";
            db_execute($sql, [$nom, $telephone, $email, $adresse, $id_client]);
            
            $response['success'] = true;
            $response['message'] = 'Client modifié avec succès';
            break;
            
        case 'delete_client':
            $id_client = intval($_POST['id_client'] ?? 0);
            
            if (!$id_client) {
                throw new Exception('ID client manquant');
            }
            
            // Vérifier si le client a des ventes
            $ventes = db_fetch_one("SELECT COUNT(*) as nb FROM ventes WHERE id_client = ?", [$id_client]);
            
            if ($ventes['nb'] > 0) {
                // Désactiver au lieu de supprimer
                db_execute("UPDATE clients SET est_actif = 0 WHERE id_client = ?", [$id_client]);
                $response['message'] = 'Client désactivé (a des ventes associées)';
            } else {
                // Supprimer réellement
                db_execute("DELETE FROM clients WHERE id_client = ?", [$id_client]);
                $response['message'] = 'Client supprimé avec succès';
            }
            
            $response['success'] = true;
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
