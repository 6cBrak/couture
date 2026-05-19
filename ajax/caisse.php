<?php
/**
 * AJAX - GESTION DE LA CAISSE
 * Actions disponibles :
 *   get_status       : état courant de la caisse (solde, session ouverte ?)
 *   ouvrir           : ouvrir une nouvelle session de caisse
 *   fermer           : fermer la session courante
 *   get_mouvements   : liste des mouvements de la session courante
 *   ajouter_entree   : entrée manuelle de liquidités
 *   ajouter_sortie   : sortie manuelle de liquidités
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => ''];

// ── Helpers locaux ────────────────────────────────────────────────────────────

/**
 * Retourne la session de caisse ouverte pour l'utilisateur donné, ou null.
 * Chaque utilisateur/vendeur gère sa propre caisse liée à son dépôt.
 */
function get_caisse_ouverte(int $uid = 0) {
    if ($uid) {
        return db_fetch_one(
            "SELECT c.*, u.nom_complet AS nom_utilisateur
             FROM caisse c
             LEFT JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
             WHERE c.statut = 'ouverte' AND c.id_utilisateur = ?
             ORDER BY c.date_ouverture DESC LIMIT 1",
            [$uid]
        );
    }
    return db_fetch_one(
        "SELECT c.*, u.nom_complet AS nom_utilisateur
         FROM caisse c
         LEFT JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
         WHERE c.statut = 'ouverte'
         ORDER BY c.date_ouverture DESC LIMIT 1"
    );
}

/**
 * Calcule le solde courant d'une session de caisse.
 * solde = solde_ouverture + SUM(entrées) - SUM(sorties)
 */
function calcul_solde_caisse(int $id_caisse): float {
    $row = db_fetch_one(
        "SELECT
            COALESCE(SUM(CASE WHEN sens = 'entree' THEN montant ELSE 0 END), 0) AS total_entrees,
            COALESCE(SUM(CASE WHEN sens = 'sortie' THEN montant ELSE 0 END), 0) AS total_sorties
         FROM mouvements_caisse
         WHERE id_caisse = ?",
        [$id_caisse]
    );
    return round((float)($row['total_entrees'] ?? 0) - (float)($row['total_sorties'] ?? 0), 2);
}

// ── Dispatch ──────────────────────────────────────────────────────────────────

try {
    switch ($action) {

        // ── État courant ──────────────────────────────────────────────────────
        case 'get_status':
            $caisse = get_caisse_ouverte($user_id);
            if (!$caisse) {
                $response['success']     = true;
                $response['ouverte']     = false;
                $response['solde']       = 0;
                $response['caisse']      = null;
                break;
            }
            $solde = calcul_solde_caisse((int)$caisse['id_caisse']);

            // Stats rapides du jour
            $stats = db_fetch_one(
                "SELECT
                    COALESCE(SUM(CASE WHEN type_mouvement = 'vente_especes'  THEN montant ELSE 0 END), 0) AS ventes_especes,
                    COALESCE(SUM(CASE WHEN type_mouvement = 'depense'        THEN montant ELSE 0 END), 0) AS total_depenses,
                    COALESCE(SUM(CASE WHEN sens = 'entree' AND type_mouvement != 'ouverture' THEN montant ELSE 0 END), 0) AS total_entrees,
                    COALESCE(SUM(CASE WHEN sens = 'sortie' THEN montant ELSE 0 END), 0) AS total_sorties
                 FROM mouvements_caisse
                 WHERE id_caisse = ?",
                [$caisse['id_caisse']]
            );

            $response['success'] = true;
            $response['ouverte'] = true;
            $response['solde']   = $solde;
            $response['caisse']  = $caisse;
            $response['stats']   = $stats;
            $response['devise']  = $devise;
            break;

        // ── Ouvrir une session ────────────────────────────────────────────────
        case 'ouvrir':
            $existante = get_caisse_ouverte($user_id);
            if ($existante) throw new Exception('Une session de caisse est déjà ouverte.');

            $solde_ouverture = floatval($_POST['solde_ouverture'] ?? 0);
            if ($solde_ouverture < 0) throw new Exception('Le solde d\'ouverture ne peut pas être négatif.');
            $notes = trim($_POST['notes'] ?? '');

            db_begin_transaction();
            try {
                $id_caisse = db_insert('caisse', [
                    'date_ouverture'  => date('Y-m-d H:i:s'),
                    'solde_ouverture' => $solde_ouverture,
                    'id_utilisateur'  => $user_id,
                    'statut'          => 'ouverte',
                    'notes'           => $notes,
                ]);

                // Mouvement d'ouverture
                db_insert('mouvements_caisse', [
                    'id_caisse'      => $id_caisse,
                    'type_mouvement' => 'ouverture',
                    'montant'        => $solde_ouverture,
                    'sens'           => 'entree',
                    'description'    => 'Ouverture de caisse',
                    'id_utilisateur' => $user_id,
                    'date_mouvement' => date('Y-m-d H:i:s'),
                ]);

                db_commit();

                log_activity('CAISSE_OUVERTURE',
                    "Ouverture caisse avec " . number_format($solde_ouverture, 2) . " " . $devise,
                    ['id_caisse' => $id_caisse, 'solde_ouverture' => $solde_ouverture]
                );

                $response['success']   = true;
                $response['message']   = 'Caisse ouverte avec succès';
                $response['id_caisse'] = $id_caisse;
                $response['solde']     = $solde_ouverture;
            } catch (Exception $e) {
                db_rollback();
                throw $e;
            }
            break;

        // ── Fermer une session ────────────────────────────────────────────────
        case 'fermer':

            $caisse = get_caisse_ouverte($user_id);
            if (!$caisse) throw new Exception('Aucune session de caisse ouverte.');

            $id_caisse       = (int)$caisse['id_caisse'];
            $solde_fermeture = floatval($_POST['solde_fermeture'] ?? 0);
            if ($solde_fermeture < 0) throw new Exception('Le solde de fermeture ne peut pas être négatif.');
            $notes = trim($_POST['notes'] ?? '');

            $solde_theorique = calcul_solde_caisse($id_caisse);
            $ecart           = round($solde_fermeture - $solde_theorique, 2);

            db_begin_transaction();
            try {
                // Mouvement de fermeture (enregistrement du solde réel)
                db_insert('mouvements_caisse', [
                    'id_caisse'      => $id_caisse,
                    'type_mouvement' => 'fermeture',
                    'montant'        => $solde_fermeture,
                    'sens'           => 'sortie',
                    'description'    => 'Fermeture de caisse — solde réel : ' . number_format($solde_fermeture, 2) . ' ' . $devise,
                    'id_utilisateur' => $user_id,
                    'date_mouvement' => date('Y-m-d H:i:s'),
                ]);

                db_execute(
                    "UPDATE caisse
                     SET statut = 'fermee', date_fermeture = ?, solde_fermeture = ?,
                         solde_theorique = ?, ecart = ?, notes = CONCAT(COALESCE(notes,''), ?)
                     WHERE id_caisse = ?",
                    [
                        date('Y-m-d H:i:s'),
                        $solde_fermeture,
                        $solde_theorique,
                        $ecart,
                        $notes ? "\n" . $notes : '',
                        $id_caisse,
                    ]
                );

                db_commit();

                log_activity('CAISSE_FERMETURE',
                    "Fermeture caisse — théorique: " . number_format($solde_theorique, 2) .
                    " | réel: " . number_format($solde_fermeture, 2) .
                    " | écart: " . number_format($ecart, 2) . " " . $devise,
                    ['id_caisse' => $id_caisse, 'ecart' => $ecart]
                );

                $response['success']         = true;
                $response['message']         = 'Caisse fermée avec succès';
                $response['solde_theorique'] = $solde_theorique;
                $response['ecart']           = $ecart;
            } catch (Exception $e) {
                db_rollback();
                throw $e;
            }
            break;

        // ── Entrée manuelle ───────────────────────────────────────────────────
        case 'ajouter_entree':
            $caisse = get_caisse_ouverte($user_id);
            if (!$caisse) throw new Exception('Aucune session de caisse ouverte.');

            $montant     = floatval($_POST['montant']     ?? 0);
            $description = trim($_POST['description']    ?? '');
            if ($montant <= 0)       throw new Exception('Le montant doit être supérieur à 0.');
            if ($description === '') throw new Exception('La description est obligatoire.');

            db_insert('mouvements_caisse', [
                'id_caisse'      => (int)$caisse['id_caisse'],
                'type_mouvement' => 'entree_manuelle',
                'montant'        => $montant,
                'sens'           => 'entree',
                'description'    => $description,
                'id_utilisateur' => $user_id,
                'date_mouvement' => date('Y-m-d H:i:s'),
            ]);

            $nouveau_solde = calcul_solde_caisse((int)$caisse['id_caisse']);

            log_activity('CAISSE_ENTREE', "Entrée manuelle : " . $montant . " " . $devise . " — " . $description, []);

            $response['success']      = true;
            $response['message']      = 'Entrée enregistrée';
            $response['nouveau_solde'] = $nouveau_solde;
            break;

        // ── Sortie manuelle ───────────────────────────────────────────────────
        case 'ajouter_sortie':
            $caisse = get_caisse_ouverte($user_id);
            if (!$caisse) throw new Exception('Aucune session de caisse ouverte.');

            $montant     = floatval($_POST['montant']     ?? 0);
            $description = trim($_POST['description']    ?? '');
            if ($montant <= 0)       throw new Exception('Le montant doit être supérieur à 0.');
            if ($description === '') throw new Exception('La description est obligatoire.');

            $solde_actuel = calcul_solde_caisse((int)$caisse['id_caisse']);
            if ($montant > $solde_actuel + 0.01) {
                throw new Exception('Solde insuffisant — disponible : ' . number_format($solde_actuel, 2) . ' ' . $devise);
            }

            db_insert('mouvements_caisse', [
                'id_caisse'      => (int)$caisse['id_caisse'],
                'type_mouvement' => 'sortie_manuelle',
                'montant'        => $montant,
                'sens'           => 'sortie',
                'description'    => $description,
                'id_utilisateur' => $user_id,
                'date_mouvement' => date('Y-m-d H:i:s'),
            ]);

            $nouveau_solde = calcul_solde_caisse((int)$caisse['id_caisse']);

            log_activity('CAISSE_SORTIE', "Sortie manuelle : " . $montant . " " . $devise . " — " . $description, []);

            $response['success']       = true;
            $response['message']       = 'Sortie enregistrée';
            $response['nouveau_solde'] = $nouveau_solde;
            break;

        // ── Liste des mouvements ──────────────────────────────────────────────
        case 'get_mouvements':
            $id_caisse = intval($_GET['id_caisse'] ?? 0);
            if (!$id_caisse) {
                $caisse_row = get_caisse_ouverte($user_id);
                $id_caisse  = $caisse_row ? (int)$caisse_row['id_caisse'] : 0;
            }
            if (!$id_caisse) throw new Exception('Aucune caisse trouvée.');

            $mouvements = db_fetch_all(
                "SELECT m.*, u.nom_complet AS utilisateur
                 FROM mouvements_caisse m
                 LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur
                 WHERE m.id_caisse = ?
                 ORDER BY m.date_mouvement DESC",
                [$id_caisse]
            );

            $solde = calcul_solde_caisse($id_caisse);

            $response['success']    = true;
            $response['mouvements'] = $mouvements;
            $response['solde']      = $solde;
            $response['devise']     = $devise;
            break;

        // ── Historique des sessions ───────────────────────────────────────────
        case 'get_historique':
            if (!$is_admin) throw new Exception('Action réservée aux administrateurs.');

            $sessions = db_fetch_all(
                "SELECT c.*, u.nom_complet AS nom_utilisateur,
                    (SELECT COUNT(*) FROM mouvements_caisse WHERE id_caisse = c.id_caisse) AS nb_mouvements
                 FROM caisse c
                 LEFT JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
                 ORDER BY c.date_ouverture DESC
                 LIMIT 30"
            );

            $response['success']  = true;
            $response['sessions'] = $sessions;
            $response['devise']   = $devise;
            break;

        default:
            throw new Exception('Action non reconnue : ' . htmlspecialchars($action));
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
