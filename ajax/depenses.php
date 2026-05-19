<?php
/**
 * AJAX - GESTION DES DÉPENSES
 * Actions :
 *   get_categories     : liste des catégories actives
 *   get_solde_caisse   : solde de la caisse ouverte (pour validation côté client)
 *   ajouter            : enregistrer une dépense (vérifie la disponibilité caisse)
 *   annuler            : annuler une dépense (admin)
 *   get_liste          : liste des dépenses (filtres optionnels)
 *   save_categorie     : créer/modifier une catégorie (admin)
 *   delete_categorie   : désactiver une catégorie (admin)
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => ''];

// ── Helper : caisse ouverte ───────────────────────────────────────────────────
function get_caisse_ouverte_dep(int $uid = 0) {
    if ($uid) {
        return db_fetch_one(
            "SELECT id_caisse, solde_ouverture FROM caisse WHERE statut = 'ouverte' AND id_utilisateur = ? ORDER BY date_ouverture DESC LIMIT 1",
            [$uid]
        );
    }
    return db_fetch_one(
        "SELECT id_caisse, solde_ouverture FROM caisse WHERE statut = 'ouverte' ORDER BY date_ouverture DESC LIMIT 1"
    );
}

function calcul_solde_caisse_dep(int $id_caisse): float {
    $row = db_fetch_one(
        "SELECT
            COALESCE(SUM(CASE WHEN sens = 'entree' THEN montant ELSE 0 END), 0) AS e,
            COALESCE(SUM(CASE WHEN sens = 'sortie' THEN montant ELSE 0 END), 0) AS s
         FROM mouvements_caisse WHERE id_caisse = ?",
        [$id_caisse]
    );
    return round((float)($row['e'] ?? 0) - (float)($row['s'] ?? 0), 2);
}

// ── Dispatch ──────────────────────────────────────────────────────────────────
try {
    switch ($action) {

        // ── Catégories ────────────────────────────────────────────────────────
        case 'get_categories':
            $categories = db_fetch_all(
                "SELECT * FROM categories_depenses WHERE est_actif = 1 ORDER BY ordre_affichage, nom_categorie"
            );
            $response['success']    = true;
            $response['categories'] = $categories;
            break;

        // ── Solde disponible ──────────────────────────────────────────────────
        case 'get_solde_caisse':
            $caisse = get_caisse_ouverte_dep($user_id);
            if (!$caisse) {
                $response['success'] = true;
                $response['ouverte'] = false;
                $response['solde']   = 0;
                break;
            }
            $solde = calcul_solde_caisse_dep((int)$caisse['id_caisse']);
            $response['success'] = true;
            $response['ouverte'] = true;
            $response['solde']   = $solde;
            $response['devise']  = $devise;
            break;

        // ── Ajouter une dépense ───────────────────────────────────────────────
        case 'ajouter':
            $id_categorie = intval($_POST['id_categorie'] ?? 0);
            $libelle      = trim($_POST['libelle']        ?? '');
            $montant      = floatval($_POST['montant']    ?? 0);
            $notes        = trim($_POST['notes']          ?? '');

            if (!$id_categorie) throw new Exception('Veuillez sélectionner une catégorie.');
            if ($libelle === '') throw new Exception('Le libellé est obligatoire.');
            if ($montant <= 0)   throw new Exception('Le montant doit être supérieur à 0.');

            // Vérifier que la catégorie existe
            $cat = db_fetch_one("SELECT id_categorie FROM categories_depenses WHERE id_categorie = ? AND est_actif = 1", [$id_categorie]);
            if (!$cat) throw new Exception('Catégorie introuvable ou inactive.');

            // Vérifier la caisse et le solde disponible
            $caisse = get_caisse_ouverte_dep($user_id);
            if (!$caisse) {
                throw new Exception('Aucune caisse ouverte. Veuillez ouvrir la caisse avant d\'enregistrer une dépense.');
            }

            $id_caisse    = (int)$caisse['id_caisse'];
            $solde_actuel = calcul_solde_caisse_dep($id_caisse);

            if ($montant > $solde_actuel + 0.01) {
                throw new Exception(
                    'Solde insuffisant — disponible en caisse : ' . number_format($solde_actuel, 2) . ' ' . $devise
                );
            }

            db_begin_transaction();
            try {
                // Enregistrer la dépense
                $id_depense = db_insert('depenses', [
                    'id_categorie'   => $id_categorie,
                    'libelle'        => $libelle,
                    'montant'        => $montant,
                    'id_caisse'      => $id_caisse,
                    'id_utilisateur' => $user_id,
                    'notes'          => $notes,
                    'date_depense'   => date('Y-m-d H:i:s'),
                    'statut'         => 'validee',
                ]);

                // Imputer la sortie sur la caisse
                db_insert('mouvements_caisse', [
                    'id_caisse'      => $id_caisse,
                    'type_mouvement' => 'depense',
                    'montant'        => $montant,
                    'sens'           => 'sortie',
                    'description'    => $libelle,
                    'reference'      => 'DEP-' . str_pad($id_depense, 6, '0', STR_PAD_LEFT),
                    'id_utilisateur' => $user_id,
                    'date_mouvement' => date('Y-m-d H:i:s'),
                ]);

                db_commit();

                $nouveau_solde = calcul_solde_caisse_dep($id_caisse);

                log_activity('DEPENSE_AJOUT',
                    "Dépense enregistrée : " . $libelle . " — " . number_format($montant, 2) . " " . $devise,
                    ['id_depense' => $id_depense, 'montant' => $montant]
                );

                $response['success']       = true;
                $response['message']       = 'Dépense enregistrée avec succès';
                $response['id_depense']    = $id_depense;
                $response['nouveau_solde'] = $nouveau_solde;
            } catch (Exception $e) {
                db_rollback();
                throw $e;
            }
            break;

        // ── Annuler une dépense ───────────────────────────────────────────────
        case 'annuler':
            if (!$is_admin) throw new Exception('Action réservée aux administrateurs.');

            $id_depense = intval($_POST['id_depense'] ?? 0);
            if (!$id_depense) throw new Exception('ID dépense manquant.');

            $depense = db_fetch_one(
                "SELECT * FROM depenses WHERE id_depense = ? AND statut = 'validee'",
                [$id_depense]
            );
            if (!$depense) throw new Exception('Dépense introuvable ou déjà annulée.');

            db_begin_transaction();
            try {
                // Marquer comme annulée
                db_execute("UPDATE depenses SET statut = 'annulee' WHERE id_depense = ?", [$id_depense]);

                // Re-créditer la caisse si la session est encore ouverte
                if ($depense['id_caisse']) {
                    $caisse_dep = db_fetch_one(
                        "SELECT id_caisse FROM caisse WHERE id_caisse = ? AND statut = 'ouverte'",
                        [$depense['id_caisse']]
                    );
                    if ($caisse_dep) {
                        db_insert('mouvements_caisse', [
                            'id_caisse'      => (int)$depense['id_caisse'],
                            'type_mouvement' => 'entree_manuelle',
                            'montant'        => $depense['montant'],
                            'sens'           => 'entree',
                            'description'    => 'Annulation dépense : ' . $depense['libelle'],
                            'reference'      => 'DEP-' . str_pad($id_depense, 6, '0', STR_PAD_LEFT),
                            'id_utilisateur' => $user_id,
                            'date_mouvement' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }

                db_commit();

                log_activity('DEPENSE_ANNULATION',
                    "Annulation dépense #" . $id_depense . " : " . $depense['libelle'],
                    ['id_depense' => $id_depense]
                );

                $response['success'] = true;
                $response['message'] = 'Dépense annulée';
            } catch (Exception $e) {
                db_rollback();
                throw $e;
            }
            break;

        // ── Liste des dépenses ────────────────────────────────────────────────
        case 'get_liste':
            $date_debut   = $_GET['date_debut']    ?? date('Y-m-01');
            $date_fin     = $_GET['date_fin']       ?? date('Y-m-d');
            $id_categorie = intval($_GET['id_categorie'] ?? 0);
            $statut       = $_GET['statut']         ?? 'validee';

            $where  = ['d.date_depense BETWEEN ? AND ?'];
            $params = [$date_debut . ' 00:00:00', $date_fin . ' 23:59:59'];

            if ($id_categorie) { $where[] = 'd.id_categorie = ?'; $params[] = $id_categorie; }
            if ($statut !== '')  { $where[] = 'd.statut = ?';       $params[] = $statut; }

            // Non-admin voit seulement ses propres dépenses
            if (!$is_admin) {
                $where[]  = 'd.id_utilisateur = ?';
                $params[] = $user_id;
            }

            $sql = "SELECT d.*, c.nom_categorie, c.couleur, c.icone,
                           u.nom_complet AS utilisateur, dep.nom_depot
                    FROM depenses d
                    LEFT JOIN categories_depenses c ON d.id_categorie = c.id_categorie
                    LEFT JOIN utilisateurs        u ON d.id_utilisateur = u.id_utilisateur
                    LEFT JOIN depots           dep ON u.id_depot = dep.id_depot
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY d.date_depense DESC";

            $depenses   = db_fetch_all($sql, $params);
            $total      = array_sum(array_column(
                array_filter($depenses, fn($r) => $r['statut'] === 'validee'),
                'montant'
            ));

            // Totaux par catégorie
            $par_categorie = [];
            foreach ($depenses as $d) {
                if ($d['statut'] !== 'validee') continue;
                $key = $d['id_categorie'];
                if (!isset($par_categorie[$key])) {
                    $par_categorie[$key] = [
                        'nom_categorie' => $d['nom_categorie'],
                        'couleur'       => $d['couleur'],
                        'total'         => 0,
                        'count'         => 0,
                    ];
                }
                $par_categorie[$key]['total'] += $d['montant'];
                $par_categorie[$key]['count']++;
            }

            $response['success']       = true;
            $response['depenses']      = $depenses;
            $response['total']         = $total;
            $response['par_categorie'] = array_values($par_categorie);
            $response['devise']        = $devise;
            break;

        // ── Sauvegarder une catégorie (admin) ─────────────────────────────────
        case 'save_categorie':
            if (!$is_admin) throw new Exception('Action réservée aux administrateurs.');

            $id           = intval($_POST['id_categorie']   ?? 0);
            $nom          = trim($_POST['nom_categorie']    ?? '');
            $description  = trim($_POST['description']      ?? '');
            $couleur      = trim($_POST['couleur']          ?? '#6c757d');
            $icone        = trim($_POST['icone']            ?? 'receipt');
            $ordre        = intval($_POST['ordre_affichage'] ?? 0);

            if ($nom === '') throw new Exception('Le nom de la catégorie est obligatoire.');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $couleur)) $couleur = '#6c757d';

            $data = [
                'nom_categorie'   => $nom,
                'description'     => $description,
                'couleur'         => $couleur,
                'icone'           => $icone,
                'ordre_affichage' => $ordre,
            ];

            if ($id) {
                db_execute(
                    "UPDATE categories_depenses SET nom_categorie=?, description=?, couleur=?, icone=?, ordre_affichage=? WHERE id_categorie=?",
                    [$nom, $description, $couleur, $icone, $ordre, $id]
                );
                $response['message'] = 'Catégorie mise à jour';
            } else {
                $id = db_insert('categories_depenses', $data);
                $response['message'] = 'Catégorie créée';
            }

            $response['success']     = true;
            $response['id_categorie'] = $id;
            break;

        // ── Supprimer/désactiver une catégorie (admin) ────────────────────────
        case 'delete_categorie':
            if (!$is_admin) throw new Exception('Action réservée aux administrateurs.');

            $id = intval($_POST['id_categorie'] ?? 0);
            if (!$id) throw new Exception('ID catégorie manquant.');

            // Vérifier si utilisée
            $usage = db_fetch_one("SELECT COUNT(*) AS n FROM depenses WHERE id_categorie = ? AND statut = 'validee'", [$id]);
            if ($usage && $usage['n'] > 0) {
                // Désactiver seulement
                db_execute("UPDATE categories_depenses SET est_actif = 0 WHERE id_categorie = ?", [$id]);
                $response['message'] = 'Catégorie désactivée (elle contient des dépenses)';
            } else {
                db_execute("DELETE FROM categories_depenses WHERE id_categorie = ?", [$id]);
                $response['message'] = 'Catégorie supprimée';
            }

            $response['success'] = true;
            break;

        default:
            throw new Exception('Action non reconnue : ' . htmlspecialchars($action));
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
