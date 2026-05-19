<?php
/**
 * AJAX - GESTION DES COMMANDES ATELIER COUTURE
 * Actions :
 *   get_liste      : liste des commandes (filtres optionnels)
 *   get_one        : détail d'une commande
 *   save           : créer ou modifier une commande
 *   update_statut  : changer le statut (terminee → crédit tailleur automatique)
 *   delete         : annuler une commande
 *   get_stats      : statistiques du tableau de bord
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

if (!$atelier_mode) {
    echo json_encode(['success' => false, 'message' => 'Module Atelier non activé.']);
    exit;
}

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => ''];

// ── Génère une référence unique ────────────────────────────────────────────────
function generate_ref_commande(): string {
    $prefix = 'ATL-' . date('Ymd') . '-';
    $last = db_fetch_one(
        "SELECT reference FROM commandes_atelier WHERE reference LIKE ? ORDER BY id_commande DESC LIMIT 1",
        [$prefix . '%']
    );
    $num = 1;
    if ($last) {
        $parts = explode('-', $last['reference']);
        $num   = intval(end($parts)) + 1;
    }
    return $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
}

try {
    switch ($action) {

        // ── Liste des commandes ────────────────────────────────────────────────
        case 'get_liste':
            $statut       = $_GET['statut']        ?? '';
            $id_tailleur  = intval($_GET['id_tailleur']  ?? 0);
            $date_debut   = $_GET['date_debut']    ?? date('Y-m-01');
            $date_fin     = $_GET['date_fin']       ?? date('Y-m-d');
            $search       = trim($_GET['search']   ?? '');

            $where  = ['c.date_commande BETWEEN ? AND ?'];
            $params = [$date_debut . ' 00:00:00', $date_fin . ' 23:59:59'];

            if ($statut !== '')      { $where[] = 'c.statut = ?';       $params[] = $statut; }
            if ($id_tailleur)        { $where[] = 'c.id_tailleur = ?';  $params[] = $id_tailleur; }
            if ($search !== '')      { $where[] = '(c.reference LIKE ? OR c.description LIKE ? OR cl.nom_client LIKE ?)';
                                       $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

            // Non-admin : seulement ses commandes
            if (!$is_admin) {
                $where[]  = 'c.id_utilisateur = ?';
                $params[] = $user_id;
            }

            $sql = "SELECT c.*,
                           t.nom AS nom_tailleur,
                           cl.nom_client, cl.telephone AS tel_client,
                           u.nom_complet AS utilisateur
                    FROM commandes_atelier c
                    LEFT JOIN tailleurs     t  ON c.id_tailleur  = t.id_tailleur
                    LEFT JOIN clients       cl ON c.id_client    = cl.id_client
                    LEFT JOIN utilisateurs  u  ON c.id_utilisateur = u.id_utilisateur
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY c.date_commande DESC";

            $commandes = db_fetch_all($sql, $params);

            $response['success']   = true;
            $response['commandes'] = $commandes;
            $response['total']     = count($commandes);
            $response['devise']    = $devise;
            break;

        // ── Détail d'une commande ──────────────────────────────────────────────
        case 'get_one':
            $id = intval($_GET['id_commande'] ?? 0);
            if (!$id) throw new Exception('ID commande manquant.');

            $cmd = db_fetch_one(
                "SELECT c.*,
                        t.nom AS nom_tailleur,
                        cl.nom_client, cl.telephone AS tel_client,
                        u.nom_complet AS utilisateur
                 FROM commandes_atelier c
                 LEFT JOIN tailleurs    t  ON c.id_tailleur  = t.id_tailleur
                 LEFT JOIN clients      cl ON c.id_client    = cl.id_client
                 LEFT JOIN utilisateurs u  ON c.id_utilisateur = u.id_utilisateur
                 WHERE c.id_commande = ?",
                [$id]
            );
            if (!$cmd) throw new Exception('Commande introuvable.');

            $response['success']  = true;
            $response['commande'] = $cmd;
            break;

        // ── Créer / modifier une commande ──────────────────────────────────────
        case 'save':
            $id            = intval($_POST['id_commande']  ?? 0);
            $description   = trim($_POST['description']    ?? '');
            $id_tailleur   = intval($_POST['id_tailleur']  ?? 0);
            $id_client     = intval($_POST['id_client']    ?? 0) ?: null;
            $montant_total = floatval($_POST['montant_total'] ?? 0);
            $acompte_verse = floatval($_POST['acompte_verse'] ?? 0);
            $date_echeance = trim($_POST['date_echeance']  ?? '') ?: null;
            $notes         = trim($_POST['notes']          ?? '');

            if ($description === '')  throw new Exception('La description est obligatoire.');
            if (!$id_tailleur)        throw new Exception('Veuillez sélectionner un tailleur.');
            if ($montant_total <= 0)  throw new Exception('Le montant total doit être supérieur à 0.');
            if ($acompte_verse < 0)   throw new Exception('L\'acompte ne peut pas être négatif.');
            if ($acompte_verse > $montant_total) throw new Exception('L\'acompte ne peut pas dépasser le montant total.');

            $tailleur = db_fetch_one("SELECT id_tailleur FROM tailleurs WHERE id_tailleur = ? AND est_actif = 1", [$id_tailleur]);
            if (!$tailleur) throw new Exception('Tailleur introuvable ou inactif.');

            $data = [
                'description'   => $description,
                'id_tailleur'   => $id_tailleur,
                'id_client'     => $id_client,
                'montant_total' => $montant_total,
                'acompte_verse' => $acompte_verse,
                'date_echeance' => $date_echeance,
                'notes'         => $notes,
            ];

            if ($id) {
                // Modification : pas de changement de statut ici
                $existing = db_fetch_one("SELECT statut FROM commandes_atelier WHERE id_commande = ?", [$id]);
                if (!$existing) throw new Exception('Commande introuvable.');
                if (in_array($existing['statut'], ['annulee'])) throw new Exception('Impossible de modifier une commande annulée.');

                db_execute(
                    "UPDATE commandes_atelier SET description=?, id_tailleur=?, id_client=?, montant_total=?, acompte_verse=?, date_echeance=?, notes=? WHERE id_commande=?",
                    [$description, $id_tailleur, $id_client, $montant_total, $acompte_verse, $date_echeance, $notes, $id]
                );
                $response['message']    = 'Commande mise à jour';
                $response['id_commande'] = $id;
            } else {
                $data['reference']      = generate_ref_commande();
                $data['statut']         = 'recu';
                $data['id_utilisateur'] = $user_id;
                $data['date_commande']  = date('Y-m-d H:i:s');

                $id = db_insert('commandes_atelier', $data);
                log_activity('ATELIER_COMMANDE_CREATION',
                    'Nouvelle commande : ' . $data['description'],
                    ['id_commande' => $id, 'reference' => $data['reference']]
                );
                $response['message']    = 'Commande enregistrée';
                $response['id_commande'] = $id;
                $response['reference']  = $data['reference'];
            }
            $response['success'] = true;
            break;

        // ── Changer le statut d'une commande ───────────────────────────────────
        case 'update_statut':
            $id         = intval($_POST['id_commande'] ?? 0);
            $new_statut = trim($_POST['statut']        ?? '');

            $allowed = ['recu', 'en_cours', 'terminee', 'livre', 'annulee'];
            if (!$id)                               throw new Exception('ID commande manquant.');
            if (!in_array($new_statut, $allowed))   throw new Exception('Statut invalide.');

            $cmd = db_fetch_one("SELECT * FROM commandes_atelier WHERE id_commande = ?", [$id]);
            if (!$cmd) throw new Exception('Commande introuvable.');
            if ($cmd['statut'] === 'annulee') throw new Exception('Impossible de modifier une commande annulée.');
            if ($cmd['statut'] === $new_statut) {
                $response['success'] = true;
                $response['message'] = 'Statut inchangé.';
                break;
            }

            db_begin_transaction();
            try {
                db_execute("UPDATE commandes_atelier SET statut = ? WHERE id_commande = ?", [$new_statut, $id]);

                // Quand la commande passe à "terminee" → créditer l'acompte tailleur à son solde
                if ($new_statut === 'terminee' && $cmd['statut'] !== 'terminee' && $cmd['id_tailleur'] && (float)$cmd['acompte_tailleur'] > 0) {
                    db_execute(
                        "UPDATE tailleurs SET solde = solde + ? WHERE id_tailleur = ?",
                        [$cmd['acompte_tailleur'], $cmd['id_tailleur']]
                    );
                    db_insert('mouvements_tailleur', [
                        'id_tailleur'    => (int)$cmd['id_tailleur'],
                        'type_mouvement' => 'credit',
                        'montant'        => $cmd['acompte_tailleur'],
                        'description'    => 'Acompte commande terminée : ' . $cmd['reference'],
                        'id_commande'    => $id,
                        'id_utilisateur' => $user_id,
                        'date_mouvement' => date('Y-m-d H:i:s'),
                    ]);
                }

                // Si on annule et que la commande était "terminee" → débiter l'acompte tailleur
                if ($new_statut === 'annulee' && $cmd['statut'] === 'terminee' && $cmd['id_tailleur'] && (float)$cmd['acompte_tailleur'] > 0) {
                    db_execute(
                        "UPDATE tailleurs SET solde = solde - ? WHERE id_tailleur = ?",
                        [$cmd['acompte_tailleur'], $cmd['id_tailleur']]
                    );
                    db_insert('mouvements_tailleur', [
                        'id_tailleur'    => (int)$cmd['id_tailleur'],
                        'type_mouvement' => 'debit',
                        'montant'        => $cmd['acompte_tailleur'],
                        'description'    => 'Annulation commande terminée : ' . $cmd['reference'],
                        'id_commande'    => $id,
                        'id_utilisateur' => $user_id,
                        'date_mouvement' => date('Y-m-d H:i:s'),
                    ]);
                }

                db_commit();

                log_activity('ATELIER_STATUT_MAJ',
                    'Statut commande ' . $cmd['reference'] . ' : ' . $cmd['statut'] . ' → ' . $new_statut,
                    ['id_commande' => $id]
                );

                $response['success'] = true;
                $response['message'] = 'Statut mis à jour';
            } catch (Exception $e) {
                db_rollback();
                throw $e;
            }
            break;

        // ── Stats tableau de bord ──────────────────────────────────────────────
        case 'get_stats':
            $where_user  = $is_admin ? '' : 'AND c.id_utilisateur = ' . intval($user_id);

            $stats = db_fetch_one(
                "SELECT
                    COUNT(*) AS total,
                    SUM(c.statut = 'recu')     AS nb_recu,
                    SUM(c.statut = 'en_cours') AS nb_en_cours,
                    SUM(c.statut = 'terminee') AS nb_terminee,
                    SUM(c.statut = 'livre')    AS nb_livre,
                    SUM(c.statut = 'annulee')  AS nb_annulee,
                    COALESCE(SUM(CASE WHEN c.statut NOT IN ('annulee') THEN c.montant_total ELSE 0 END), 0) AS ca_total,
                    COALESCE(SUM(CASE WHEN c.statut NOT IN ('annulee') THEN c.acompte_verse ELSE 0 END), 0) AS acomptes_total
                 FROM commandes_atelier c
                 WHERE 1=1 $where_user"
            );

            $response['success'] = true;
            $response['stats']   = $stats;
            $response['devise']  = $devise;
            break;

        // ── Liste des ventes pour affectation ─────────────────────────────────
        case 'get_ventes':
            $date_debut  = $_GET['date_debut'] ?? date('Y-m-01');
            $date_fin    = $_GET['date_fin']   ?? date('Y-m-d');
            $affectation = $_GET['affectation'] ?? '';

            $where  = ["v.date_vente BETWEEN ? AND ?", "v.statut = 'validee'", "v.is_confection = 1"];
            $params = [$date_debut . ' 00:00:00', $date_fin . ' 23:59:59'];

            if (!$is_admin) { $where[] = 'v.id_vendeur = ?'; $params[] = $user_id; }

            $ventes = db_fetch_all(
                "SELECT v.id_vente, v.numero_facture, v.montant_total, v.montant_paye,
                        v.mode_paiement, v.date_vente, v.type_confection,
                        cl.nom_client, u.nom_complet AS vendeur,
                        (SELECT COUNT(*) FROM details_vente dv WHERE dv.id_vente = v.id_vente) AS nb_articles,
                        (SELECT COUNT(*) FROM commandes_atelier ca WHERE ca.id_vente = v.id_vente AND ca.statut != 'annulee') AS nb_affectes
                 FROM ventes v
                 LEFT JOIN clients      cl ON v.id_client  = cl.id_client
                 LEFT JOIN utilisateurs u  ON v.id_vendeur = u.id_utilisateur
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY v.date_vente DESC LIMIT 200",
                $params
            );

            // Filtre affectation en PHP
            if ($affectation === 'avec') {
                $ventes = array_values(array_filter($ventes, fn($v) => (int)$v['nb_affectes'] > 0));
            } elseif ($affectation === 'sans') {
                $ventes = array_values(array_filter($ventes, fn($v) => (int)$v['nb_affectes'] === 0));
            }

            $response['success'] = true;
            $response['ventes']  = $ventes;
            $response['devise']  = $devise;
            break;

        // ── Articles d'une vente, expandus par unité ─────────────────────────
        case 'get_details_vente':
            $id_vente = intval($_GET['id_vente'] ?? 0);
            if (!$id_vente) throw new Exception('Vente non spécifiée.');

            // Articles de la vente (fallback sur p.nom_produit si dv.nom_produit est vide)
            $details_raw = db_fetch_all(
                "SELECT dv.id_detail,
                        COALESCE(NULLIF(dv.nom_produit,''), p.nom_produit, '—') AS nom_produit,
                        dv.quantite
                 FROM details_vente dv
                 LEFT JOIN produits p ON p.id_produit = dv.id_produit
                 WHERE dv.id_vente = ?
                 ORDER BY dv.id_detail ASC",
                [$id_vente]
            );

            // Affectations existantes indexées par (id_detail, numero_unite)
            $commandes_ex = db_fetch_all(
                "SELECT ca.id_commande, ca.id_detail_vente, ca.numero_unite,
                        ca.id_tailleur, ca.acompte_tailleur, ca.statut AS statut_atelier,
                        t.nom AS nom_tailleur
                 FROM commandes_atelier ca
                 LEFT JOIN tailleurs t ON ca.id_tailleur = t.id_tailleur
                 WHERE ca.id_vente = ? AND ca.statut != 'annulee'",
                [$id_vente]
            );
            $idx_commandes = [];
            foreach ($commandes_ex as $c) {
                $idx_commandes[$c['id_detail_vente'] . '_' . $c['numero_unite']] = $c;
            }

            // Expansion : une ligne par unité
            $rows = [];
            foreach ($details_raw as $d) {
                $qte = max(1, intval($d['quantite']));
                for ($u = 1; $u <= $qte; $u++) {
                    $key = $d['id_detail'] . '_' . $u;
                    $ca  = $idx_commandes[$key] ?? null;
                    $rows[] = [
                        'id_detail'       => $d['id_detail'],
                        'nom_produit'     => $d['nom_produit'],
                        'quantite_total'  => $qte,
                        'numero_unite'    => $u,
                        'id_commande'     => $ca['id_commande']     ?? null,
                        'id_tailleur'     => $ca['id_tailleur']     ?? null,
                        'nom_tailleur'    => $ca['nom_tailleur']    ?? null,
                        'acompte_tailleur'=> $ca['acompte_tailleur']?? 0,
                        'statut_atelier'  => $ca['statut_atelier']  ?? null,
                    ];
                }
            }

            $response['success'] = true;
            $response['details'] = $rows;
            break;

        // ── Affecter les tailleurs (multi-article) ────────────────────────────
        case 'affecter':
            $id_vente      = intval($_POST['id_vente']      ?? 0);
            $date_echeance = trim($_POST['date_echeance']   ?? '') ?: null;
            $notes         = trim($_POST['notes']           ?? '');
            $articles_json = trim($_POST['articles']        ?? '[]');

            if (!$id_vente) throw new Exception('Vente non spécifiée.');

            $articles = json_decode($articles_json, true);
            if (!is_array($articles)) throw new Exception('Format des articles invalide.');

            $vente = db_fetch_one(
                "SELECT v.*, cl.nom_client FROM ventes v LEFT JOIN clients cl ON v.id_client = cl.id_client WHERE v.id_vente = ? AND v.statut = 'validee'",
                [$id_vente]
            );
            if (!$vente) throw new Exception('Vente introuvable.');

            $nb_crees = 0; $nb_maj = 0;

            db_begin_transaction();
            try {
                $statuts_valides = ['recu', 'en_cours', 'terminee', 'livre'];

                foreach ($articles as $art) {
                    $id_detail_vente  = intval($art['id_detail']      ?? 0);
                    $numero_unite     = intval($art['numero_unite']    ?? 1);
                    $id_tailleur      = intval($art['id_tailleur']     ?? 0);
                    $acompte_tailleur = max(0, floatval($art['acompte_tailleur'] ?? 0));
                    $nom_produit      = trim($art['nom_produit']       ?? '');
                    $nouveau_statut   = in_array($art['statut'] ?? '', $statuts_valides) ? $art['statut'] : 'recu';

                    if (!$id_detail_vente) continue;

                    if (!$id_tailleur) {
                        // Pas de tailleur → annuler l'affectation existante pour cette unité
                        $existant = db_fetch_one(
                            "SELECT id_commande FROM commandes_atelier WHERE id_detail_vente = ? AND numero_unite = ? AND statut != 'annulee'",
                            [$id_detail_vente, $numero_unite]
                        );
                        if ($existant) {
                            db_execute(
                                "UPDATE commandes_atelier SET statut = 'annulee' WHERE id_commande = ?",
                                [$existant['id_commande']]
                            );
                        }
                        continue;
                    }

                    $tailleur = db_fetch_one("SELECT id_tailleur FROM tailleurs WHERE id_tailleur = ? AND est_actif = 1", [$id_tailleur]);
                    if (!$tailleur) continue;

                    $existant = db_fetch_one(
                        "SELECT id_commande, statut, acompte_tailleur AS ancien_acompte FROM commandes_atelier WHERE id_detail_vente = ? AND numero_unite = ? AND statut != 'annulee'",
                        [$id_detail_vente, $numero_unite]
                    );

                    if ($existant) {
                        $ancien_statut  = $existant['statut'];
                        $ancien_acompte = (float)$existant['ancien_acompte'];

                        db_execute(
                            "UPDATE commandes_atelier SET id_tailleur=?, acompte_tailleur=?, statut=?, date_echeance=?, notes=?, nom_produit=? WHERE id_commande=?",
                            [$id_tailleur, $acompte_tailleur, $nouveau_statut, $date_echeance, $notes, $nom_produit, $existant['id_commande']]
                        );

                        // Si passage à "terminee" : créditer l'acompte tailleur
                        if ($nouveau_statut === 'terminee' && $ancien_statut !== 'terminee' && $acompte_tailleur > 0) {
                            db_execute("UPDATE tailleurs SET solde = solde + ? WHERE id_tailleur = ?", [$acompte_tailleur, $id_tailleur]);
                            db_insert('mouvements_tailleur', [
                                'id_tailleur'    => $id_tailleur,
                                'type_mouvement' => 'credit',
                                'montant'        => $acompte_tailleur,
                                'description'    => 'Commission — ' . ($nom_produit ?: 'Article') . ' #' . $numero_unite . ' (' . $vente['numero_facture'] . ')',
                                'id_commande'    => $existant['id_commande'],
                                'id_utilisateur' => $user_id,
                                'date_mouvement' => date('Y-m-d H:i:s'),
                            ]);
                        }

                        // Si retour arrière depuis "terminee" : débiter l'ancien acompte
                        if ($ancien_statut === 'terminee' && $nouveau_statut !== 'terminee' && $ancien_acompte > 0) {
                            db_execute("UPDATE tailleurs SET solde = solde - ? WHERE id_tailleur = ?", [$ancien_acompte, $id_tailleur]);
                            db_insert('mouvements_tailleur', [
                                'id_tailleur'    => $id_tailleur,
                                'type_mouvement' => 'debit',
                                'montant'        => $ancien_acompte,
                                'description'    => 'Annulation commission — ' . ($nom_produit ?: 'Article') . ' #' . $numero_unite . ' (' . $vente['numero_facture'] . ')',
                                'id_commande'    => $existant['id_commande'],
                                'id_utilisateur' => $user_id,
                                'date_mouvement' => date('Y-m-d H:i:s'),
                            ]);
                        }

                        $nb_maj++;
                    } else {
                        $ref_prefix = 'ATL-' . date('Ymd') . '-';
                        $last_atl   = db_fetch_one("SELECT reference FROM commandes_atelier WHERE reference LIKE ? ORDER BY id_commande DESC LIMIT 1", [$ref_prefix . '%']);
                        $num_atl    = $last_atl ? intval(substr($last_atl['reference'], -3)) + 1 : 1;
                        $ref_atl    = $ref_prefix . str_pad($num_atl, 3, '0', STR_PAD_LEFT);

                        $acompte_verse = ($vente['mode_paiement'] === 'credit') ? (float)$vente['montant_paye'] : (float)$vente['montant_total'];

                        db_insert('commandes_atelier', [
                            'reference'        => $ref_atl,
                            'id_vente'         => $id_vente,
                            'id_detail_vente'  => $id_detail_vente,
                            'numero_unite'     => $numero_unite,
                            'nom_produit'      => $nom_produit,
                            'quantite'         => 1,
                            'description'      => $vente['numero_facture'] . ' — ' . $nom_produit . ' #' . $numero_unite,
                            'id_client'        => $vente['id_client'] ?: null,
                            'id_tailleur'      => $id_tailleur,
                            'montant_total'    => (float)$vente['montant_total'],
                            'acompte_verse'    => $acompte_verse,
                            'acompte_tailleur' => $acompte_tailleur,
                            'statut'           => $nouveau_statut,
                            'date_commande'    => date('Y-m-d H:i:s'),
                            'date_echeance'    => $date_echeance,
                            'notes'            => $notes ?: ('Vente ' . $vente['numero_facture']),
                            'id_utilisateur'   => $user_id,
                        ]);
                        $id_nouvelle = $pdo->lastInsertId();

                        // Si créée directement à "terminee", créditer l'acompte
                        if ($nouveau_statut === 'terminee' && $acompte_tailleur > 0) {
                            db_execute("UPDATE tailleurs SET solde = solde + ? WHERE id_tailleur = ?", [$acompte_tailleur, $id_tailleur]);
                            db_insert('mouvements_tailleur', [
                                'id_tailleur'    => $id_tailleur,
                                'type_mouvement' => 'credit',
                                'montant'        => $acompte_tailleur,
                                'description'    => 'Commission — ' . ($nom_produit ?: 'Article') . ' #' . $numero_unite . ' (' . $vente['numero_facture'] . ')',
                                'id_commande'    => $id_nouvelle,
                                'id_utilisateur' => $user_id,
                                'date_mouvement' => date('Y-m-d H:i:s'),
                            ]);
                        }

                        $nb_crees++;
                    }
                }

                db_commit();
            } catch (Exception $ex) {
                db_rollback();
                throw $ex;
            }

            log_activity('ATELIER_AFFECTATION', 'Affectations vente ' . $vente['numero_facture'] . " : {$nb_crees} créées, {$nb_maj} mises à jour", ['id_vente' => $id_vente]);
            $response['success'] = true;
            $response['message'] = ($nb_crees + $nb_maj) . ' affectation(s) enregistrée(s)';
            break;

        default:
            throw new Exception('Action non reconnue : ' . htmlspecialchars($action));
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
