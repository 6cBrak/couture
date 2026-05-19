<?php
require_once('protection_pages.php');
$page_title = 'Gestion de la Caisse';

// ── Vérification migration ────────────────────────────────────────────────────
$migration_ok = true;
try {
    db_fetch_one("SELECT 1 FROM caisse LIMIT 1");
} catch (Exception $e) {
    $migration_ok = false;
}

// ── Données ───────────────────────────────────────────────────────────────────
$caisse_ouverte = null;
$solde_courant  = 0;
$historique     = [];

if ($migration_ok) {
    // Chaque utilisateur voit SA caisse (liée à son dépôt)
    $caisse_ouverte = db_fetch_one(
        "SELECT c.*, u.nom_complet AS nom_utilisateur, d.nom_depot
         FROM caisse c
         LEFT JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
         LEFT JOIN depots d ON u.id_depot = d.id_depot
         WHERE c.statut = 'ouverte' AND c.id_utilisateur = ?
         ORDER BY c.date_ouverture DESC LIMIT 1",
        [$user_id]
    );

    if ($caisse_ouverte) {
        $row = db_fetch_one(
            "SELECT
                COALESCE(SUM(CASE WHEN sens='entree' THEN montant ELSE 0 END),0) AS e,
                COALESCE(SUM(CASE WHEN sens='sortie' THEN montant ELSE 0 END),0) AS s
             FROM mouvements_caisse WHERE id_caisse = ?",
            [$caisse_ouverte['id_caisse']]
        );
        $solde_courant = round((float)$row['e'] - (float)$row['s'], 2);
    }

    if ($is_admin) {
        $historique = db_fetch_all(
            "SELECT c.*, u.nom_complet AS nom_utilisateur
             FROM caisse c LEFT JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
             ORDER BY c.date_ouverture DESC LIMIT 10"
        );
    }
}

require_once('header.php');

if (!$migration_ok): ?>
<div class="container-xl py-5">
    <div class="alert alert-danger d-flex gap-3 align-items-start">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg flex-shrink-0 mt-1" width="28" height="28" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>
        <div>
            <h4 class="alert-title">Migration requise</h4>
            <p class="mb-2">Les tables de la caisse et des dépenses n'existent pas encore dans la base de données.</p>
            <p class="mb-0">Exécutez le fichier <strong><code>database/migration_caisse_depenses.sql</code></strong> dans votre gestionnaire de base de données (phpMyAdmin, MySQL Workbench…) puis rechargez la page.</p>
        </div>
    </div>
</div>
<?php require_once('footer.php'); exit; endif; ?>

<style>
.caisse-card {
    border-radius: 16px;
    border: none;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    transition: box-shadow 0.3s;
}
.caisse-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,0.13); }
.solde-display {
    font-size: 2.5rem;
    font-weight: 700;
    letter-spacing: -1px;
}
.badge-type { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.mvt-row-entree { border-left: 3px solid #2fb344; }
.mvt-row-sortie  { border-left: 3px solid #e53935; }
.table.card-table th, .table.card-table td { vertical-align: middle; color: #2f2f2f; }
</style>

<div class="container-xl py-4">

    <!-- En-tête -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title fw-bold" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="28" height="28" viewBox="0 0 24 24" stroke-width="2" stroke="<?php echo $couleur_primaire; ?>" fill="none" stroke-linecap="round" stroke-linejoin="round" style="-webkit-text-fill-color: initial;">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <rect x="2" y="6" width="20" height="12" rx="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <path d="M6 12h.01M18 12h.01"/>
                    </svg>
                    Gestion de la Caisse
                </h2>
                <p class="text-muted mb-0">Suivi des entrées, sorties et solde de la caisse</p>
            </div>
            <div class="col-auto">
                <?php if (!$caisse_ouverte): ?>
                <button class="btn btn-success" onclick="ouvrirCaisse()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Ouvrir ma caisse
                </button>
                <?php else: ?>
                <button class="btn btn-danger" onclick="fermerCaisse()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Fermer ma caisse
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($caisse_ouverte): ?>
    <!-- Solde courant -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card caisse-card h-100" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>);">
                <div class="card-body text-white">
                    <div class="d-flex align-items-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="white" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                        <span class="fw-semibold opacity-90">Solde disponible</span>
                    </div>
                    <div class="solde-display" id="soldeCourant"><?php echo number_format($solde_courant, 2); ?></div>
                    <div class="mt-1 opacity-75 fw-medium"><?php echo e($devise); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card caisse-card h-100">
                <div class="card-body">
                    <div class="text-muted small fw-semibold mb-2">SESSION EN COURS</div>
                    <div><strong>Ouverte le :</strong> <?php echo date('d/m/Y à H:i', strtotime($caisse_ouverte['date_ouverture'])); ?></div>
                    <div class="mt-1"><strong>Par :</strong> <?php echo e($caisse_ouverte['nom_utilisateur']); ?></div>
                    <div class="mt-1"><strong>Fond initial :</strong> <?php echo number_format($caisse_ouverte['solde_ouverture'], 2); ?> <?php echo e($devise); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card caisse-card h-100">
                <div class="card-body">
                    <div class="text-muted small fw-semibold mb-3">ACTIONS RAPIDES</div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-success btn-sm" onclick="modalEntree()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Entrée manuelle
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="modalSortie()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Sortie manuelle
                        </button>
                        <a href="depenses.php" class="btn btn-outline-warning btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l6 -6"/><circle cx="9.5" cy="9.5" r=".5" fill="currentColor"/><circle cx="14.5" cy="14.5" r=".5" fill="currentColor"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/></svg>
                            Enregistrer une dépense
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mouvements de la session -->
    <div class="card caisse-card">
        <div class="card-header bg-white border-bottom-0 pt-3">
            <h4 class="card-title mb-0 fw-semibold">Mouvements de la session</h4>
        </div>
        <div class="table-responsive">
            <table class="table table-hover card-table" id="tableMouvements">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Référence</th>
                        <th class="text-end">Entrée</th>
                        <th class="text-end">Sortie</th>
                        <th>Opérateur</th>
                    </tr>
                </thead>
                <tbody id="tbodyMouvements">
                    <tr><td colspan="7" class="text-center py-4 text-muted">Chargement…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php else: ?>
    <!-- Caisse fermée -->
    <div class="card caisse-card">
        <div class="card-body text-center py-5">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-3" width="60" height="60" viewBox="0 0 24 24" stroke-width="1.5" stroke="#adb5bd" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
            <h4 class="text-muted">Caisse fermée</h4>
            <p class="text-muted mb-4">Aucune session de caisse n'est actuellement ouverte.</p>
            <?php if ($is_admin): ?>
            <button class="btn btn-success btn-lg" onclick="ouvrirCaisse()">
                Ouvrir la caisse maintenant
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historique des sessions (admin) -->
    <?php if ($is_admin && count($historique)): ?>
    <div class="card caisse-card mt-4">
        <div class="card-header bg-white border-bottom-0 pt-3">
            <h4 class="card-title mb-0 fw-semibold">Historique des sessions</h4>
        </div>
        <div class="table-responsive">
            <table class="table table-hover card-table">
                <thead class="table-light">
                    <tr>
                        <th>Ouverture</th>
                        <th>Fermeture</th>
                        <th class="text-end">Fond initial</th>
                        <th class="text-end">Solde théorique</th>
                        <th class="text-end">Solde réel</th>
                        <th class="text-end">Écart</th>
                        <th>Opérateur</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historique as $sess): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($sess['date_ouverture'])); ?></td>
                        <td><?php echo $sess['date_fermeture'] ? date('d/m/Y H:i', strtotime($sess['date_fermeture'])) : '—'; ?></td>
                        <td class="text-end"><?php echo number_format($sess['solde_ouverture'], 2); ?></td>
                        <td class="text-end"><?php echo $sess['solde_theorique'] !== null ? number_format($sess['solde_theorique'], 2) : '—'; ?></td>
                        <td class="text-end"><?php echo $sess['solde_fermeture'] !== null ? number_format($sess['solde_fermeture'], 2) : '—'; ?></td>
                        <td class="text-end">
                            <?php if ($sess['ecart'] !== null):
                                $ec = (float)$sess['ecart'];
                                $cls = $ec < 0 ? 'text-danger' : ($ec > 0 ? 'text-success' : 'text-muted');
                            ?>
                            <span class="<?php echo $cls; ?> fw-semibold"><?php echo ($ec >= 0 ? '+' : '') . number_format($ec, 2); ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><?php echo e($sess['nom_utilisateur']); ?></td>
                        <td>
                            <?php if ($sess['statut'] === 'ouverte'): ?>
                            <span class="badge bg-success-lt text-success">Ouverte</span>
                            <?php else: ?>
                            <span class="badge bg-secondary-lt text-secondary">Fermée</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Ouvrir Caisse -->
<div class="modal fade" id="modalOuvrirCaisse" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); color: white;">
                <h5 class="modal-title">Ouverture de la caisse</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Fond de caisse initial (<?php echo e($devise); ?>)</label>
                    <input type="number" class="form-control form-control-lg" id="soldeOuverture" min="0" step="0.01" value="0" placeholder="0.00">
                    <small class="text-muted">Montant d'espèces déjà présentes dans la caisse.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes (optionnel)</label>
                    <textarea class="form-control" id="notesOuverture" rows="2" placeholder="Remarques éventuelles…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" onclick="confirmOuvrirCaisse()">Ouvrir la caisse</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Fermer Caisse -->
<div class="modal fade" id="modalFermerCaisse" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Fermeture de la caisse</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    Solde théorique : <strong id="soldeTheorique"><?php echo number_format($solde_courant, 2); ?> <?php echo e($devise); ?></strong>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Solde réel compté (<?php echo e($devise); ?>)</label>
                    <input type="number" class="form-control form-control-lg" id="soldeFermeture" min="0" step="0.01" value="<?php echo $solde_courant; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes (optionnel)</label>
                    <textarea class="form-control" id="notesFermeture" rows="2" placeholder="Remarques éventuelles…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" onclick="confirmFermerCaisse()">Fermer la caisse</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Entrée manuelle -->
<div class="modal fade" id="modalEntreeManuelle" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Entrée manuelle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Montant (<?php echo e($devise); ?>)</label>
                    <input type="number" class="form-control" id="montantEntree" min="0.01" step="0.01" placeholder="0.00">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="descEntree" placeholder="Ex: Apport de liquidités, remboursement…">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" onclick="confirmEntree()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sortie manuelle -->
<div class="modal fade" id="modalSortieManuelle" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Sortie manuelle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    Solde disponible : <strong id="soldeDispoSortie"><?php echo number_format($solde_courant, 2); ?> <?php echo e($devise); ?></strong>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Montant (<?php echo e($devise); ?>)</label>
                    <input type="number" class="form-control" id="montantSortie" min="0.01" step="0.01" placeholder="0.00">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="descSortie" placeholder="Ex: Retrait, avance…">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" onclick="confirmSortie()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
const DEVISE = <?php echo json_encode($devise); ?>;
const IS_ADMIN = <?php echo $is_admin ? 'true' : 'false'; ?>;
const CAISSE_OUVERTE = <?php echo $caisse_ouverte ? 'true' : 'false'; ?>;

function fmt(n) {
    return parseFloat(n).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function ouvrirCaisse() {
    new bootstrap.Modal(document.getElementById('modalOuvrirCaisse')).show();
}

function fermerCaisse() {
    new bootstrap.Modal(document.getElementById('modalFermerCaisse')).show();
}

function modalEntree() {
    document.getElementById('montantEntree').value = '';
    document.getElementById('descEntree').value = '';
    new bootstrap.Modal(document.getElementById('modalEntreeManuelle')).show();
}

function modalSortie() {
    document.getElementById('montantSortie').value = '';
    document.getElementById('descSortie').value = '';
    new bootstrap.Modal(document.getElementById('modalSortieManuelle')).show();
}

function confirmOuvrirCaisse() {
    const solde = parseFloat(document.getElementById('soldeOuverture').value) || 0;
    const notes = document.getElementById('notesOuverture').value;
    const fd = new FormData();
    fd.append('action', 'ouvrir');
    fd.append('solde_ouverture', solde);
    fd.append('notes', notes);
    fetch('ajax/caisse.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { location.reload(); }
            else { showModal('error', data.message || 'Erreur'); }
        });
}

function confirmFermerCaisse() {
    const solde = parseFloat(document.getElementById('soldeFermeture').value) || 0;
    const notes = document.getElementById('notesFermeture').value;
    const fd = new FormData();
    fd.append('action', 'fermer');
    fd.append('solde_fermeture', solde);
    fd.append('notes', notes);
    fetch('ajax/caisse.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { location.reload(); }
            else { showModal('error', data.message || 'Erreur'); }
        });
}

function confirmEntree() {
    const montant = parseFloat(document.getElementById('montantEntree').value);
    const desc    = document.getElementById('descEntree').value.trim();
    if (!montant || montant <= 0) { showModal('warning', 'Veuillez saisir un montant valide.'); return; }
    if (!desc) { showModal('warning', 'La description est obligatoire.'); return; }
    const fd = new FormData();
    fd.append('action', 'ajouter_entree');
    fd.append('montant', montant);
    fd.append('description', desc);
    fetch('ajax/caisse.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalEntreeManuelle')).hide();
                document.getElementById('soldeCourant').textContent = fmt(data.nouveau_solde);
                document.getElementById('soldeDispoSortie').textContent = fmt(data.nouveau_solde) + ' ' + DEVISE;
                chargerMouvements();
                showModal('success', data.message);
            } else { showModal('error', data.message || 'Erreur'); }
        });
}

function confirmSortie() {
    const montant = parseFloat(document.getElementById('montantSortie').value);
    const desc    = document.getElementById('descSortie').value.trim();
    if (!montant || montant <= 0) { showModal('warning', 'Veuillez saisir un montant valide.'); return; }
    if (!desc) { showModal('warning', 'La description est obligatoire.'); return; }
    const fd = new FormData();
    fd.append('action', 'ajouter_sortie');
    fd.append('montant', montant);
    fd.append('description', desc);
    fetch('ajax/caisse.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalSortieManuelle')).hide();
                document.getElementById('soldeCourant').textContent = fmt(data.nouveau_solde);
                document.getElementById('soldeDispoSortie').textContent = fmt(data.nouveau_solde) + ' ' + DEVISE;
                chargerMouvements();
                showModal('success', data.message);
            } else { showModal('error', data.message || 'Erreur'); }
        });
}

const LABELS = {
    ouverture:              'Ouverture',
    vente_especes:          'Vente espèces',
    remboursement_especes:  'Remboursement',
    entree_manuelle:        'Entrée manuelle',
    depense:                'Dépense',
    sortie_manuelle:        'Sortie manuelle',
    fermeture:              'Fermeture',
};
const BADGES = {
    ouverture:    'bg-blue-lt text-blue',
    vente_especes:'bg-green-lt text-green',
    depense:      'bg-red-lt text-red',
    fermeture:    'bg-secondary-lt text-secondary',
    entree_manuelle:    'bg-teal-lt text-teal',
    sortie_manuelle:    'bg-orange-lt text-orange',
    remboursement_especes: 'bg-purple-lt text-purple',
};

function chargerMouvements() {
    if (!CAISSE_OUVERTE) return;
    fetch('ajax/caisse.php?action=get_mouvements')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('tbodyMouvements');
            if (!data.success || !data.mouvements.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Aucun mouvement</td></tr>';
                return;
            }
            tbody.innerHTML = data.mouvements.map(m => {
                const d = new Date(m.date_mouvement.replace(' ', 'T'));
                const dateStr = d.toLocaleDateString('fr-FR') + ' ' + d.toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit'});
                const badge = BADGES[m.type_mouvement] || 'bg-secondary-lt';
                const label = LABELS[m.type_mouvement] || m.type_mouvement;
                const entree = m.sens === 'entree' ? `<span class="text-success fw-semibold">+ ${fmt(m.montant)}</span>` : '—';
                const sortie = m.sens === 'sortie' ? `<span class="text-danger fw-semibold">- ${fmt(m.montant)}</span>` : '—';
                const rowCls = m.sens === 'entree' ? 'mvt-row-entree' : 'mvt-row-sortie';
                return `<tr class="${rowCls}">
                    <td class="text-muted small">${dateStr}</td>
                    <td><span class="badge badge-type ${badge}">${label}</span></td>
                    <td>${m.description ? m.description.substring(0,80) : '—'}</td>
                    <td class="text-muted small">${m.reference || '—'}</td>
                    <td class="text-end">${entree}</td>
                    <td class="text-end">${sortie}</td>
                    <td class="text-muted small">${m.utilisateur || '—'}</td>
                </tr>`;
            }).join('');
        });
}

document.addEventListener('DOMContentLoaded', chargerMouvements);
</script>

<?php require_once('footer.php'); ?>
