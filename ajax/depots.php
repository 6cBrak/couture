<?php
/**
 * AJAX endpoint - Gestion des dépôts (CRUD)
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
            $nom          = trim($_POST['nom_depot']    ?? '');
            $description  = trim($_POST['description']  ?? '');
            $adresse      = trim($_POST['adresse']      ?? '');
            $type_depot   = trim($_POST['type_depot']   ?? 'depot');
            $responsable  = trim($_POST['responsable']  ?? '');
            $telephone    = trim($_POST['telephone']    ?? '');
            $est_principal = !empty($_POST['est_principal']) ? 1 : 0;

            if (empty($nom)) throw new Exception('Nom du dépôt requis.');

            $types_valides = ['magasin', 'depot', 'entrepot', 'autre'];
            if (!in_array($type_depot, $types_valides)) $type_depot = 'depot';

            if ($est_principal) {
                db_execute("UPDATE depots SET est_principal = 0");
            }

            $id = db_insert('depots', [
                'nom_depot'    => $nom,
                'description'  => $description,
                'adresse'      => $adresse,
                'type_depot'   => $type_depot,
                'responsable'  => $responsable,
                'telephone'    => $telephone,
                'est_principal'=> $est_principal,
                'est_actif'    => 1,
            ]);

            $response = ['success' => true, 'message' => 'Dépôt ajouté avec succès.', 'id' => $id];
            break;

        case 'update':
            $id           = intval($_POST['id_depot']   ?? 0);
            $nom          = trim($_POST['nom_depot']    ?? '');
            $description  = trim($_POST['description']  ?? '');
            $adresse      = trim($_POST['adresse']      ?? '');
            $type_depot   = trim($_POST['type_depot']   ?? 'depot');
            $responsable  = trim($_POST['responsable']  ?? '');
            $telephone    = trim($_POST['telephone']    ?? '');
            $est_principal = !empty($_POST['est_principal']) ? 1 : 0;

            if (!$id || empty($nom)) throw new Exception('Données incomplètes.');

            $types_valides = ['magasin', 'depot', 'entrepot', 'autre'];
            if (!in_array($type_depot, $types_valides)) $type_depot = 'depot';

            $depot = db_fetch_one("SELECT est_principal FROM depots WHERE id_depot = ?", [$id]);
            if (!$depot) throw new Exception('Dépôt introuvable.');

            if ($depot['est_principal'] == 1 && $est_principal == 0) {
                $autres = db_fetch_one("SELECT COUNT(*) as nb FROM depots WHERE est_actif = 1 AND id_depot != ?", [$id]);
                if ($autres['nb'] == 0) {
                    throw new Exception('Impossible de retirer le statut principal : aucun autre dépôt actif.');
                }
            }

            if ($est_principal && !$depot['est_principal']) {
                db_execute("UPDATE depots SET est_principal = 0");
            }

            db_execute(
                "UPDATE depots SET nom_depot=?, description=?, adresse=?, type_depot=?, responsable=?, telephone=?, est_principal=? WHERE id_depot=?",
                [$nom, $description, $adresse, $type_depot, $responsable, $telephone, $est_principal, $id]
            );

            $response = ['success' => true, 'message' => 'Dépôt modifié avec succès.'];
            break;

        case 'delete':
            $id = intval($_POST['id_depot'] ?? 0);
            if (!$id) throw new Exception('ID manquant.');

            $depot = db_fetch_one("SELECT est_principal FROM depots WHERE id_depot = ?", [$id]);
            if (!$depot) throw new Exception('Dépôt introuvable.');
            if ($depot['est_principal']) throw new Exception('Impossible de supprimer le dépôt principal.');

            $stock = db_fetch_one("SELECT COALESCE(SUM(quantite),0) as total FROM stock_par_depot WHERE id_depot = ?", [$id]);
            if ($stock['total'] > 0) {
                throw new Exception("Impossible de supprimer : ce dépôt contient {$stock['total']} unités en stock.");
            }

            // Vérifier si des caissiers sont liés à ce dépôt
            $caissiers = db_fetch_one("SELECT COUNT(*) as nb FROM utilisateurs WHERE id_depot = ?", [$id]);
            if ($caissiers && $caissiers['nb'] > 0) {
                throw new Exception("Impossible de supprimer : {$caissiers['nb']} caissier(s) sont assignés à ce dépôt.");
            }

            db_execute("UPDATE depots SET est_actif = 0 WHERE id_depot = ?", [$id]);
            $response = ['success' => true, 'message' => 'Dépôt supprimé avec succès.'];
            break;

        default:
            throw new Exception('Action non reconnue.');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
