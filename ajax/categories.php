<?php
/**
 * AJAX - GESTION DES CATÉGORIES
 * Ajouter, modifier, supprimer des catégories
 */
require_once '../protection_pages.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'add_category':
            $nom = trim($_POST['nom_categorie'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($nom)) {
                throw new Exception('Le nom de la catégorie est obligatoire');
            }
            
            $sql = "INSERT INTO categories (nom_categorie, description, est_actif) VALUES (?, ?, 1)";
            db_execute($sql, [$nom, $description]);
            
            $response['success'] = true;
            $response['message'] = 'Catégorie ajoutée avec succès';
            break;
            
        case 'update_category':
            $id_categorie = intval($_POST['id_categorie'] ?? 0);
            $nom = trim($_POST['nom_categorie'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (!$id_categorie) {
                throw new Exception('ID catégorie manquant');
            }
            
            if (empty($nom)) {
                throw new Exception('Le nom de la catégorie est obligatoire');
            }
            
            $sql = "UPDATE categories SET nom_categorie = ?, description = ? WHERE id_categorie = ?";
            db_execute($sql, [$nom, $description, $id_categorie]);
            
            $response['success'] = true;
            $response['message'] = 'Catégorie modifiée avec succès';
            break;
            
        case 'delete_category':
            $id_categorie = intval($_POST['id_categorie'] ?? 0);
            
            if (!$id_categorie) {
                throw new Exception('ID catégorie manquant');
            }
            
            // Vérifier si la catégorie a des produits
            $produits = db_fetch_one("SELECT COUNT(*) as nb FROM produits WHERE id_categorie = ? AND est_actif = 1", [$id_categorie]);
            
            if ($produits['nb'] > 0) {
                throw new Exception('Impossible de supprimer : cette catégorie contient des produits actifs');
            }
            
            db_execute("DELETE FROM categories WHERE id_categorie = ?", [$id_categorie]);
            
            $response['success'] = true;
            $response['message'] = 'Catégorie supprimée avec succès';
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
