<?php
/**
 * AJAX - GESTION DES TAILLEURS (Module Atelier Couture)
 * Actions :
 *   get_liste      : liste des tailleurs actifs
 *   save           : créer ou modifier un tailleur (admin)
 *   delete         : désactiver un tailleur (admin)
 *   get_mouvements : historique des mouvements de solde d'un tailleur
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

if (!$atelier_mode) {
    echo json_encode(['success' => false, 'message' => 'Module Atelier non activé.']);
    exit;
}

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {

        case 'get_liste':
            $tailleurs = db_fetch_all(
                "SELECT t.*,
                        (SELECT COUNT(*) FROM commandes_atelier c WHERE c.id_tailleur = t.id_tailleur AND c.statut NOT IN ('annulee')) AS nb_commandes
                 FROM tailleurs t
                 WHERE t.est_actif = 1
                 ORDER BY t.nom"
            );
            $response['success']   = true;
            $response['tailleurs'] = $tailleurs;
            break;

        case 'save':
            if (!$is_admin) throw new Exception('Action réservée aux administrateurs.');

            $id         = intval($_POST['id_tailleur'] ?? 0);
            $nom        = trim($_POST['nom'] ?? '');
            $telephone  = trim($_POST['telephone'] ?? '');
            $specialite = trim($_POST['specialite'] ?? '');
            $notes      = trim($_POST['notes'] ?? '');

            if ($nom === '') throw new Exception('Le nom du tailleur est obligatoire.');

            $data = [
                'nom'        => $nom,
                'telephone'  => $telephone,
                'specialite' => $specialite,
                'notes'      => $notes,
            ];

            if ($id) {
                db_execute(
                    "UPDATE tailleurs SET nom=?, telephone=?, specialite=?, notes=? WHERE id_tailleur=?",
                    [$nom, $telephone, $specialite, $notes, $id]
                );
                $response['message']    = 'Tailleur mis à jour';
                $response['id_tailleur'] = $id;
            } else {
                $id = db_insert('tailleurs', $data);
                $response['message']    = 'Tailleur créé';
                $response['id_tailleur'] = $id;
            }
            $response['success'] = true;
            break;

        case 'delete':
            if (!$is_admin) throw new Exception('Action réservée aux administrateurs.');

            $id = intval($_POST['id_tailleur'] ?? 0);
            if (!$id) throw new Exception('ID tailleur manquant.');

            $usage = db_fetch_one(
                "SELECT COUNT(*) AS n FROM commandes_atelier WHERE id_tailleur = ? AND statut NOT IN ('annulee')",
                [$id]
            );
            if ($usage && $usage['n'] > 0) {
                db_execute("UPDATE tailleurs SET est_actif = 0 WHERE id_tailleur = ?", [$id]);
                $response['message'] = 'Tailleur désactivé (commandes en cours associées)';
            } else {
                db_execute("DELETE FROM tailleurs WHERE id_tailleur = ?", [$id]);
                $response['message'] = 'Tailleur supprimé';
            }
            $response['success'] = true;
            break;

        case 'get_mouvements':
            $id         = intval($_GET['id_tailleur'] ?? 0);
            $date_debut = $_GET['date_debut'] ?? date('Y-m-01');
            $date_fin   = $_GET['date_fin']   ?? date('Y-m-d');
            if (!$id) throw new Exception('ID tailleur manquant.');

            $tailleur = db_fetch_one("SELECT * FROM tailleurs WHERE id_tailleur = ?", [$id]);
            if (!$tailleur) throw new Exception('Tailleur introuvable.');

            $mouvements = db_fetch_all(
                "SELECT m.*, u.nom_complet AS utilisateur,
                        c.reference AS ref_commande
                 FROM mouvements_tailleur m
                 LEFT JOIN utilisateurs u         ON m.id_utilisateur = u.id_utilisateur
                 LEFT JOIN commandes_atelier c    ON m.id_commande    = c.id_commande
                 WHERE m.id_tailleur = ?
                   AND m.date_mouvement BETWEEN ? AND ?
                 ORDER BY m.date_mouvement DESC",
                [$id, $date_debut . ' 00:00:00', $date_fin . ' 23:59:59']
            );

            $response['success']    = true;
            $response['tailleur']   = $tailleur;
            $response['mouvements'] = $mouvements;
            break;

        case 'get_commandes_paiement':

            $id           = intval($_GET['id_tailleur'] ?? 0);
            $date_debut   = $_GET['date_debut'] ?? date('Y-m-01');
            $date_fin     = $_GET['date_fin']   ?? date('Y-m-d');
            if (!$id) throw new Exception('ID tailleur manquant.');

            $tailleur = db_fetch_one("SELECT * FROM tailleurs WHERE id_tailleur = ?", [$id]);
            if (!$tailleur) throw new Exception('Tailleur introuvable.');

            $commandes = db_fetch_all(
                "SELECT ca.id_commande, ca.reference, ca.acompte_tailleur,
                        ca.id_paiement_tailleur, ca.date_commande,
                        v.numero_facture, v.type_confection,
                        cl.nom_client
                 FROM commandes_atelier ca
                 LEFT JOIN ventes  v  ON ca.id_vente  = v.id_vente
                 LEFT JOIN clients cl ON ca.id_client = cl.id_client
                 WHERE ca.id_tailleur = ?
                   AND ca.statut IN ('terminee', 'livre')
                   AND ca.id_paiement_tailleur IS NULL
                   AND ca.date_commande BETWEEN ? AND ?
                 ORDER BY ca.date_commande ASC",
                [$id, $date_debut . ' 00:00:00', $date_fin . ' 23:59:59']
            );

            $total = array_sum(array_column($commandes, 'acompte_tailleur'));

            $response['success']   = true;
            $response['tailleur']  = $tailleur;
            $response['commandes'] = $commandes;
            $response['total']     = $total;
            break;

        case 'payer':

            $id           = intval($_POST['id_tailleur'] ?? 0);
            $date_debut   = $_POST['date_debut'] ?? '';
            $date_fin     = $_POST['date_fin']   ?? '';
            $notes        = trim($_POST['notes'] ?? '');
            if (!$id)          throw new Exception('ID tailleur manquant.');
            if (!$date_debut)  throw new Exception('Date de début manquante.');
            if (!$date_fin)    throw new Exception('Date de fin manquante.');

            $tailleur = db_fetch_one("SELECT * FROM tailleurs WHERE id_tailleur = ?", [$id]);
            if (!$tailleur) throw new Exception('Tailleur introuvable.');

            // Récupérer les commandes à payer
            $commandes = db_fetch_all(
                "SELECT ca.id_commande, ca.reference, ca.acompte_tailleur,
                        v.numero_facture, v.type_confection,
                        cl.nom_client, ca.date_commande
                 FROM commandes_atelier ca
                 LEFT JOIN ventes  v  ON ca.id_vente  = v.id_vente
                 LEFT JOIN clients cl ON ca.id_client = cl.id_client
                 WHERE ca.id_tailleur = ?
                   AND ca.statut IN ('terminee', 'livre')
                   AND ca.id_paiement_tailleur IS NULL
                   AND ca.date_commande BETWEEN ? AND ?
                 ORDER BY ca.date_commande ASC",
                [$id, $date_debut . ' 00:00:00', $date_fin . ' 23:59:59']
            );

            if (empty($commandes)) throw new Exception('Aucune commande terminée et non payée sur cette période.');

            $montant     = array_sum(array_column($commandes, 'acompte_tailleur'));
            $nb          = count($commandes);
            $libelle_dep = 'Commission tailleur : ' . $tailleur['nom'] . ' (' . $date_debut . ' → ' . $date_fin . ')';

            db_begin_transaction();
            try {
                // Créer ou récupérer la catégorie "Tailleur/Atelier"
                $cat = db_fetch_one(
                    "SELECT id_categorie FROM categories_depenses WHERE nom_categorie = 'Atelier Couture' AND est_actif = 1 LIMIT 1"
                );
                if (!$cat) {
                    $id_cat = db_insert('categories_depenses', [
                        'nom_categorie'    => 'Atelier Couture',
                        'description'      => 'Commissions et paiements tailleurs',
                        'couleur'          => '#8b5cf6',
                        'icone'            => 'scissors',
                        'est_actif'        => 1,
                        'ordre_affichage'  => 99,
                    ]);
                } else {
                    $id_cat = $cat['id_categorie'];
                }

                // Caisse ouverte du vendeur (non-admin uniquement)
                $caisse = null;
                try {
                    if (!$is_admin) {
                        $caisse = db_fetch_one(
                            "SELECT id_caisse FROM caisse WHERE statut = 'ouverte' AND id_utilisateur = ? ORDER BY date_ouverture DESC LIMIT 1",
                            [$user_id]
                        );
                    }
                } catch (Exception $e) { /* table caisse absente */ }

                // Enregistrer la dépense (id_caisse = null pour admin, caisse vendeur sinon)
                $id_depense = db_insert('depenses', [
                    'id_categorie'   => $id_cat,
                    'libelle'        => $libelle_dep,
                    'montant'        => $montant,
                    'id_caisse'      => $caisse ? $caisse['id_caisse'] : null,
                    'id_utilisateur' => $user_id,
                    'notes'          => $notes,
                    'date_depense'   => date('Y-m-d H:i:s'),
                    'statut'         => 'validee',
                ]);
                // Mouvement caisse uniquement si une caisse vendeur est ouverte
                if ($caisse) {
                    db_insert('mouvements_caisse', [
                        'id_caisse'      => $caisse['id_caisse'],
                        'type_mouvement' => 'depense',
                        'montant'        => $montant,
                        'sens'           => 'sortie',
                        'description'    => $libelle_dep,
                        'reference'      => 'TAL-' . $id . '-' . date('Ymd'),
                        'id_utilisateur' => $user_id,
                        'date_mouvement' => date('Y-m-d H:i:s'),
                    ]);
                }

                // Enregistrer le paiement
                $id_paiement = db_insert('paiements_tailleur', [
                    'id_tailleur'   => $id,
                    'montant'       => $montant,
                    'periode_debut' => $date_debut,
                    'periode_fin'   => $date_fin,
                    'nb_commandes'  => $nb,
                    'notes'         => $notes,
                    'id_utilisateur'=> $user_id,
                    'id_depense'    => $id_depense,
                    'date_paiement' => date('Y-m-d H:i:s'),
                ]);

                // Marquer les commandes comme payées + débit solde tailleur
                $ids_commandes = array_column($commandes, 'id_commande');
                foreach ($ids_commandes as $id_cmd) {
                    db_execute(
                        "UPDATE commandes_atelier SET id_paiement_tailleur = ? WHERE id_commande = ?",
                        [$id_paiement, $id_cmd]
                    );
                }

                // Débit du solde tailleur
                db_execute(
                    "UPDATE tailleurs SET solde = solde - ? WHERE id_tailleur = ?",
                    [$montant, $id]
                );

                // Mouvement débit tailleur
                db_insert('mouvements_tailleur', [
                    'id_tailleur'    => $id,
                    'type_mouvement' => 'debit',
                    'montant'        => $montant,
                    'description'    => 'Paiement commission (' . $nb . ' commande' . ($nb > 1 ? 's' : '') . ')',
                    'id_commande'    => null,
                    'id_utilisateur' => $user_id,
                    'date_mouvement' => date('Y-m-d H:i:s'),
                ]);

                db_commit();

                $response['success']      = true;
                $response['message']      = 'Paiement enregistré avec succès';
                $response['id_paiement']  = $id_paiement;
                $response['montant']      = $montant;
                $response['nb_commandes'] = $nb;
                $response['tailleur']     = $tailleur['nom'];
                $response['periode_debut']= $date_debut;
                $response['periode_fin']  = $date_fin;
                $response['commandes']    = $commandes;
                $response['caisse_ok']    = $caisse !== null;
            } catch (Exception $ex) {
                db_rollback();
                throw $ex;
            }
            break;

        default:
            throw new Exception('Action non reconnue : ' . htmlspecialchars($action));
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
