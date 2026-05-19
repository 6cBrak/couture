<?php
/**
 * AJAX endpoint - Gestion des fournisseurs (CRUD)
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $nom = $_POST['nom_fournisseur'] ?? '';
            $contact = $_POST['contact'] ?? '';
            $telephone = $_POST['telephone'] ?? '';
            $email = $_POST['email'] ?? '';
            $adresse = $_POST['adresse'] ?? '';
            
            if (empty($nom)) {
                throw new Exception('Nom du fournisseur requis');
            }
            
            $id = db_insert('fournisseurs', [
                'nom_fournisseur' => $nom,
                'contact' => $contact,
                'telephone' => $telephone,
                'email' => $email,
                'adresse' => $adresse
            ]);
            
            if ($id) {
                $response = ['success' => true, 'message' => 'Fournisseur ajouté avec succès', 'id' => $id];
            } else {
                throw new Exception('Erreur lors de l\'ajout');
            }
            break;
            
        case 'update':
            $id = $_POST['id_fournisseur'] ?? 0;
            $nom = $_POST['nom_fournisseur'] ?? '';
            $contact = $_POST['contact'] ?? '';
            $telephone = $_POST['telephone'] ?? '';
            $email = $_POST['email'] ?? '';
            $adresse = $_POST['adresse'] ?? '';
            
            if (empty($id) || empty($nom)) {
                throw new Exception('Données incomplètes');
            }
            
            $updated = db_update('fournisseurs', [
                'nom_fournisseur' => $nom,
                'contact' => $contact,
                'telephone' => $telephone,
                'email' => $email,
                'adresse' => $adresse
            ], ['id_fournisseur' => $id]);
            
            if ($updated !== false) {
                $response = ['success' => true, 'message' => 'Fournisseur modifié avec succès'];
            } else {
                throw new Exception('Erreur lors de la modification');
            }
            break;
            
        case 'delete':
            $id = $_POST['id_fournisseur'] ?? 0;
            
            if (empty($id)) {
                throw new Exception('ID manquant');
            }
            
            // Vérifier si le fournisseur est utilisé
            $usage = db_fetch_one("
                SELECT COUNT(*) as nb FROM produits WHERE id_fournisseur_principal = ?
            ", [$id]);
            
            if ($usage['nb'] > 0) {
                throw new Exception('Impossible de supprimer : ' . $usage['nb'] . ' produit(s) lié(s) à ce fournisseur');
            }
            
            $deleted = db_update('fournisseurs', ['est_actif' => 0], ['id_fournisseur' => $id]);
            
            if ($deleted !== false) {
                $response = ['success' => true, 'message' => 'Fournisseur supprimé avec succès'];
            } else {
                throw new Exception('Erreur lors de la suppression');
            }
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
