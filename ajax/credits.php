<?php
/**
 * AJAX - GESTION DES VENTES À CRÉDIT
 * add_payment : enregistrer un paiement sur une vente à crédit
 * get_historique : récupérer les paiements d'une vente
 */
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {

        // ── Ajouter un paiement ──────────────────────────────────────────
        case 'add_payment':
            $id_vente     = intval($_POST['id_vente']     ?? 0);
            $montant      = floatval($_POST['montant']     ?? 0);
            $mode         = trim($_POST['mode_paiement']  ?? 'especes');
            $notes        = trim($_POST['notes']          ?? '');

            if (!$id_vente)  throw new Exception('Vente introuvable');
            if ($montant <= 0) throw new Exception('Le montant doit être supérieur à 0');

            $modes_valides = ['especes', 'carte', 'mobile_money', 'cheque'];
            if (!in_array($mode, $modes_valides)) throw new Exception('Mode de paiement invalide');

            // Récupérer la vente avec le total déjà payé
            $vente = db_fetch_one("
                SELECT v.id_vente, v.montant_total, v.numero_facture,
                       COALESCE(SUM(p.montant), 0) AS total_paye
                FROM ventes v
                LEFT JOIN paiements_credit p ON p.id_vente = v.id_vente
                WHERE v.id_vente = ? AND v.mode_paiement = 'credit'
                GROUP BY v.id_vente
            ", [$id_vente]);

            if (!$vente) throw new Exception('Vente crédit introuvable');

            $solde_restant = round($vente['montant_total'] - $vente['total_paye'], 2);

            if ($montant > $solde_restant + 0.01) {
                throw new Exception("Montant supérieur au solde restant (" . number_format($solde_restant, 2) . " " . $devise . ")");
            }

            db_begin_transaction();
            try {
                db_insert('paiements_credit', [
                    'id_vente'      => $id_vente,
                    'montant'       => $montant,
                    'mode_paiement' => $mode,
                    'notes'         => $notes,
                    'id_utilisateur'=> $user_id,
                    'date_paiement' => date('Y-m-d H:i:s'),
                ]);

                $nouveau_total_paye = round($vente['total_paye'] + $montant, 2);
                db_execute("UPDATE ventes SET montant_paye = ? WHERE id_vente = ?",
                    [$nouveau_total_paye, $id_vente]);

                db_commit();

                $nouveau_solde = round($vente['montant_total'] - $nouveau_total_paye, 2);

                log_activity('CREDIT_PAYMENT',
                    "Paiement crédit " . $vente['numero_facture'] . " : " . $montant . " " . $devise,
                    ['id_vente' => $id_vente, 'montant' => $montant]
                );

                $response['success']     = true;
                $response['message']     = 'Paiement enregistré avec succès';
                $response['nouveau_solde'] = $nouveau_solde;
                $response['solde']        = $nouveau_solde;
                $response['est_solde']    = ($nouveau_solde <= 0.01);

            } catch (Exception $e) {
                db_rollback();
                throw $e;
            }
            break;

        // ── Historique des paiements d'une vente ────────────────────────
        case 'get_historique':
            $id_vente = intval($_POST['id_vente'] ?? $_GET['id_vente'] ?? 0);
            if (!$id_vente) throw new Exception('ID vente manquant');

            $vente = db_fetch_one("
                SELECT v.*, c.nom_client, c.telephone as tel_client,
                       u.nom_complet as vendeur
                FROM ventes v
                LEFT JOIN clients      c ON v.id_client  = c.id_client
                LEFT JOIN utilisateurs u ON v.id_vendeur = u.id_utilisateur
                WHERE v.id_vente = ?
            ", [$id_vente]);

            if (!$vente) throw new Exception('Vente introuvable');

            $paiements = db_fetch_all("
                SELECT p.*, u.nom_complet AS utilisateur
                FROM paiements_credit p
                LEFT JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
                WHERE p.id_vente = ?
                ORDER BY p.date_paiement ASC
            ", [$id_vente]);

            $total_paye = array_sum(array_column($paiements, 'montant'));
            $solde      = round($vente['montant_total'] - $total_paye, 2);

            $response['success']    = true;
            $response['vente']      = $vente;
            $response['paiements']  = $paiements;
            $response['total_paye'] = $total_paye;
            $response['solde']      = $solde;
            $response['devise']     = $devise;
            break;

        default:
            throw new Exception('Action non reconnue : ' . htmlspecialchars($action));
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
