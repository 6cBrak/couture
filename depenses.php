<?php
require_once('protection_pages.php');
$page_title = 'Gestion des Dépenses';

// ── Vérification migration ────────────────────────────────────────────────────
$migration_ok = true;
try {
    db_fetch_one("SELECT 1 FROM categories_depenses LIMIT 1");
} catch (Exception $e) {
    $migration_ok = false;
}

// ── Données (seulement si tables présentes) ───────────────────────────────────
$categories    = [];
$caisse_ouverte = null;
$solde_caisse  = 0;
$depenses_mois = [];
$total_mois    = 0;
$par_cat_arr   = [];

if ($migration_ok) {
    $categories = db_fetch_all(
        "SELECT * FROM categories_depenses WHERE est_actif = 1 ORDER BY ordre_affichage, nom_categorie"
    );

    $caisse_ouverte = db_fetch_one(
        "SELECT id_caisse FROM caisse WHERE statut = 'ouverte' AND id_utilisateur = ? ORDER BY date_ouverture DESC LIMIT 1",
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
        $solde_caisse = round((float)$row['e'] - (float)$row['s'], 2);
    }

    $debut_mois = date('Y-m-01');
    $fin_mois   = date('Y-m-d');
    $dep_where  = $is_admin ? '' : ' AND d.id_utilisateur = ?';
    $dep_params = $is_admin
        ? [$debut_mois . ' 00:00:00', $fin_mois . ' 23:59:59']
        : [$debut_mois . ' 00:00:00', $fin_mois . ' 23:59:59', $user_id];

    $depenses_mois = db_fetch_all(
        "SELECT d.*, c.nom_categorie, c.couleur, c.icone,
                u.nom_complet AS utilisateur, dep.nom_depot
         FROM depenses d
         LEFT JOIN categories_depenses c ON d.id_categorie = c.id_categorie
         LEFT JOIN utilisateurs        u ON d.id_utilisateur = u.id_utilisateur
         LEFT JOIN depots            dep ON u.id_depot = dep.id_depot
         WHERE d.date_depense BETWEEN ? AND ? AND d.statut = 'validee'$dep_where
         ORDER BY d.date_depense DESC",
        $dep_params
    );
    $total_mois = array_sum(array_column($depenses_mois, 'montant'));

    $par_cat = [];
    foreach ($depenses_mois as $d) {
        $k = $d['id_categorie'];
        if (!isset($par_cat[$k])) $par_cat[$k] = ['nom' => $d['nom_categorie'], 'couleur' => $d['couleur'], 'total' => 0, 'nb' => 0];
        $par_cat[$k]['total'] += $d['montant'];
        $par_cat[$k]['nb']++;
    }
    $par_cat_arr = array_values($par_cat);
    usort($par_cat_arr, fn($a,$b) => $b['total'] <=> $a['total']);
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
.dep-card {
    border-radius: 16px;
    border: none;
    box-shadow: 0 4px 16px rgba(0,0,0,0.07);
    transition: box-shadow 0.3s;
}
.dep-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
.cat-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
}
.table.card-table th, .table.card-table td { vertical-align: middle; color: #2f2f2f; }
.progress-sm { height: 6px; border-radius: 3px; }
.filter-bar { background: #f8f9fa; border-radius: 12px; padding: 1rem 1.25rem; }
</style>

<div class="container-xl py-4">

    <!-- En-tête -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title fw-bold" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="28" height="28" viewBox="0 0 24 24" stroke-width="2" stroke="<?php echo $couleur_primaire; ?>" fill="none" style="-webkit-text-fill-color: initial;">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M9 14l6 -6"/><circle cx="9.5" cy="9.5" r=".5" fill="currentColor"/>
                        <circle cx="14.5" cy="14.5" r=".5" fill="currentColor"/>
                        <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/>
                    </svg>
                    Gestion des Dépenses
                </h2>
                <p class="text-muted mb-0">Enregistrez et suivez toutes vos dépenses par catégorie</p>
            </div>
            <div class="col-auto d-flex gap-2">
                <?php if ($is_admin): ?>
                <button class="btn btn-outline-secondary" onclick="modalCategories()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="6" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                    Catégories
                </button>
                <?php endif; ?>
                <button class="btn btn-primary" onclick="modalAjouterDepense()" <?php echo !$caisse_ouverte ? 'disabled title="Ouvrez la caisse d\'abord"' : ''; ?>>
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nouvelle dépense
                </button>
            </div>
        </div>
    </div>

    <?php if (!$caisse_ouverte): ?>
    <div class="alert alert-warning d-flex align-items-center mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-warning" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>
        <div>La caisse est fermée. <a href="caisse.php" class="alert-link">Ouvrir la caisse</a> pour pouvoir enregistrer des dépenses.</div>
    </div>
    <?php endif; ?>

    <!-- Widgets résumé -->
    <div class="row g-3 mb-4">
        <!-- Solde caisse -->
        <div class="col-md-3">
            <div class="card dep-card">
                <div class="card-body">
                    <div class="text-muted small fw-semibold mb-1">SOLDE CAISSE</div>
                    <?php if ($caisse_ouverte): ?>
                    <div class="h3 fw-bold <?php echo $solde_caisse < 0 ? 'text-danger' : 'text-success'; ?>"><?php echo number_format($solde_caisse, 2); ?> <small class="fs-6"><?php echo e($devise); ?></small></div>
                    <div class="text-muted small">Disponible pour dépenses</div>
                    <?php else: ?>
                    <div class="h3 fw-bold text-muted">—</div>
                    <div class="text-muted small">Caisse fermée</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Total mois -->
        <div class="col-md-3">
            <div class="card dep-card">
                <div class="card-body">
                    <div class="text-muted small fw-semibold mb-1">DÉPENSES DU MOIS</div>
                    <div class="h3 fw-bold text-danger"><?php echo number_format($total_mois, 2); ?> <small class="fs-6"><?php echo e($devise); ?></small></div>
                    <div class="text-muted small"><?php echo count($depenses_mois); ?> dépense<?php echo count($depenses_mois) > 1 ? 's' : ''; ?> enregistrée<?php echo count($depenses_mois) > 1 ? 's' : ''; ?></div>
                </div>
            </div>
        </div>
        <!-- Catégorie la plus dépensée -->
        <div class="col-md-3">
            <div class="card dep-card">
                <div class="card-body">
                    <div class="text-muted small fw-semibold mb-1">CATÉGORIE PRINCIPALE</div>
                    <?php if (count($par_cat_arr)): $top = $par_cat_arr[0]; ?>
                    <div class="h3 fw-bold" style="color: <?php echo $top['couleur']; ?>"><?php echo e($top['nom']); ?></div>
                    <div class="text-muted small"><?php echo number_format($top['total'], 2); ?> <?php echo e($devise); ?> — <?php echo $top['nb']; ?> op.</div>
                    <?php else: ?>
                    <div class="h3 fw-bold text-muted">—</div>
                    <div class="text-muted small">Aucune dépense ce mois</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Nombre catégories -->
        <div class="col-md-3">
            <div class="card dep-card">
                <div class="card-body">
                    <div class="text-muted small fw-semibold mb-1">CATÉGORIES ACTIVES</div>
                    <div class="h3 fw-bold text-primary"><?php echo count($categories); ?></div>
                    <div class="text-muted small">Types de dépenses configurés</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Tableau des dépenses -->
        <div class="col-lg-8">
            <div class="card dep-card">
                <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="card-title mb-0 fw-semibold">Dépenses</h4>
                    </div>
                    <!-- Filtres -->
                    <div class="filter-bar mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-sm-3">
                                <label class="form-label mb-1 small fw-semibold">Du</label>
                                <input type="date" class="form-control form-control-sm" id="filtreDebut" value="<?php echo $debut_mois; ?>">
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label mb-1 small fw-semibold">Au</label>
                                <input type="date" class="form-control form-control-sm" id="filtreFin" value="<?php echo $fin_mois; ?>">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label mb-1 small fw-semibold">Catégorie</label>
                                <select class="form-select form-select-sm" id="filtreCategorie">
                                    <option value="">Toutes</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id_categorie']; ?>"><?php echo e($cat['nom_categorie']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-2">
                                <button class="btn btn-primary btn-sm w-100" onclick="chargerDepenses()">Filtrer</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover card-table">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Catégorie</th>
                                <th>Libellé</th>
                                <th class="text-end">Montant</th>
                                <th>Dépôt</th>
                                <th>Opérateur</th>
                                <?php if ($is_admin): ?><th></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="tbodyDepenses">
                            <?php if (count($depenses_mois)): foreach ($depenses_mois as $dep): ?>
                            <tr>
                                <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($dep['date_depense'])); ?></td>
                                <td>
                                    <span class="cat-badge" style="background: <?php echo $dep['couleur']; ?>22; color: <?php echo $dep['couleur']; ?>;">
                                        <?php echo e($dep['nom_categorie']); ?>
                                    </span>
                                </td>
                                <td><?php echo e($dep['libelle']); ?><?php if($dep['notes']): ?><br><small class="text-muted"><?php echo e($dep['notes']); ?></small><?php endif; ?></td>
                                <td class="text-end text-danger fw-semibold"><?php echo number_format($dep['montant'], 2); ?></td>
                                <td class="text-muted small"><?php echo e($dep['nom_depot'] ?? '—'); ?></td>
                                <td class="text-muted small"><?php echo e($dep['utilisateur']); ?></td>
                                <?php if ($is_admin): ?>
                                <td><button class="btn btn-ghost-danger btn-sm" onclick="annulerDepense(<?php echo $dep['id_depense']; ?>, '<?php echo addslashes($dep['libelle']); ?>')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="<?php echo $is_admin ? 6 : 5; ?>" class="text-center text-muted py-5">Aucune dépense ce mois.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <span class="text-muted small" id="nbDepenses"><?php echo count($depenses_mois); ?> dépense(s)</span>
                    <span class="fw-bold text-danger" id="totalDepenses">Total : <?php echo number_format($total_mois, 2); ?> <?php echo e($devise); ?></span>
                </div>
            </div>
        </div>

        <!-- Répartition par catégorie -->
        <div class="col-lg-4">
            <div class="card dep-card">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h4 class="card-title mb-0 fw-semibold">Répartition par catégorie</h4>
                    <div class="text-muted small">Mois en cours</div>
                </div>
                <div class="card-body" id="repartitionCategories">
                    <?php if ($total_mois > 0 && count($par_cat_arr)): ?>
                    <?php foreach ($par_cat_arr as $pc): $pct = $total_mois > 0 ? round($pc['total'] / $total_mois * 100) : 0; ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-medium small"><?php echo e($pc['nom']); ?></span>
                            <span class="small text-muted"><?php echo number_format($pc['total'], 2); ?> <?php echo e($devise); ?> (<?php echo $pct; ?>%)</span>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar" style="width: <?php echo $pct; ?>%; background-color: <?php echo $pc['couleur']; ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">Aucune dépense ce mois.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Liste des catégories -->
            <div class="card dep-card mt-4">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h4 class="card-title mb-0 fw-semibold">Catégories</h4>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($categories as $cat): ?>
                    <div class="list-group-item d-flex align-items-center gap-2 px-3 py-2">
                        <span class="badge rounded-circle p-2" style="background-color: <?php echo $cat['couleur']; ?>22;">
                            <span style="color: <?php echo $cat['couleur']; ?>; font-size: 0.85rem;">●</span>
                        </span>
                        <span class="fw-medium flex-fill"><?php echo e($cat['nom_categorie']); ?></span>
                        <?php if ($is_admin): ?>
                        <button class="btn btn-ghost-secondary btn-sm py-0 px-1" onclick="editCategorie(<?php echo htmlspecialchars(json_encode($cat)); ?>)" title="Modifier">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a1.5 1.5 0 0 0 -4 -4l-10.5 10.5v4"/><line x1="13.5" y1="6.5" x2="17.5" y2="10.5"/></svg>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouvelle Dépense -->
<div class="modal fade" id="modalNouvelleDepense" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); color: white;">
                <h5 class="modal-title">Nouvelle Dépense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                    Solde disponible : <strong id="soldeCaisseModal"><?php echo number_format($solde_caisse, 2); ?> <?php echo e($devise); ?></strong>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Catégorie <span class="text-danger">*</span></label>
                    <select class="form-select" id="depCategorie">
                        <option value="">— Sélectionner —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id_categorie']; ?>"><?php echo e($cat['nom_categorie']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Libellé <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="depLibelle" placeholder="Description de la dépense…">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Montant (<?php echo e($devise); ?>) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="depMontant" min="0.01" step="0.01" placeholder="0.00">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes (optionnel)</label>
                    <textarea class="form-control" id="depNotes" rows="2" placeholder="Précisions, numéro de reçu…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmAjouterDepense()">Enregistrer la dépense</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Catégories (admin) -->
<div class="modal fade" id="modalGestionCategories" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title" id="titreModalCat">Gestion des catégories</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="catId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="catNom" placeholder="Ex: Transport, Loyer…">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control" id="catDesc" placeholder="Description courte…">
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Couleur</label>
                        <input type="color" class="form-control form-control-color w-100" id="catCouleur" value="#6c757d">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Ordre d'affichage</label>
                        <input type="number" class="form-control" id="catOrdre" min="0" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmSaveCategorie()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
const DEVISE = <?php echo json_encode($devise); ?>;
const IS_ADMIN = <?php echo $is_admin ? 'true' : 'false'; ?>;

function fmt(n) {
    return parseFloat(n).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function modalAjouterDepense() {
    // Rafraîchir le solde
    fetch('ajax/caisse.php?action=get_status')
        .then(r => r.json())
        .then(data => {
            const el = document.getElementById('soldeCaisseModal');
            if (el) el.textContent = fmt(data.solde || 0) + ' ' + DEVISE;
        });
    document.getElementById('depCategorie').value = '';
    document.getElementById('depLibelle').value = '';
    document.getElementById('depMontant').value = '';
    document.getElementById('depNotes').value = '';
    new bootstrap.Modal(document.getElementById('modalNouvelleDepense')).show();
}

function confirmAjouterDepense() {
    const cat     = document.getElementById('depCategorie').value;
    const libelle = document.getElementById('depLibelle').value.trim();
    const montant = parseFloat(document.getElementById('depMontant').value);
    const notes   = document.getElementById('depNotes').value.trim();

    if (!cat)    { showModal('warning', 'Veuillez sélectionner une catégorie.'); return; }
    if (!libelle){ showModal('warning', 'Le libellé est obligatoire.'); return; }
    if (!montant || montant <= 0) { showModal('warning', 'Veuillez saisir un montant valide.'); return; }

    const fd = new FormData();
    fd.append('action', 'ajouter');
    fd.append('id_categorie', cat);
    fd.append('libelle', libelle);
    fd.append('montant', montant);
    fd.append('notes', notes);

    fetch('ajax/depenses.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalNouvelleDepense')).hide();
                showModal('success', data.message);
                setTimeout(() => location.reload(), 1200);
            } else {
                showModal('error', data.message || 'Erreur lors de l\'enregistrement.');
            }
        });
}

function annulerDepense(id, libelle) {
    showModal('confirm', `Annuler la dépense "${libelle}" ?`, () => {
        const fd = new FormData();
        fd.append('action', 'annuler');
        fd.append('id_depense', id);
        fetch('ajax/depenses.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showModal('success', data.message); setTimeout(() => location.reload(), 1000); }
                else showModal('error', data.message || 'Erreur');
            });
    });
}

function chargerDepenses() {
    const debut = document.getElementById('filtreDebut').value;
    const fin   = document.getElementById('filtreFin').value;
    const cat   = document.getElementById('filtreCategorie').value;

    fetch(`ajax/depenses.php?action=get_liste&date_debut=${debut}&date_fin=${fin}&id_categorie=${cat}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showModal('error', data.message); return; }
            const tbody = document.getElementById('tbodyDepenses');
            const cols  = IS_ADMIN ? 6 : 5;
            if (!data.depenses.length) {
                tbody.innerHTML = `<tr><td colspan="${cols}" class="text-center text-muted py-5">Aucune dépense sur la période.</td></tr>`;
                document.getElementById('totalDepenses').textContent = 'Total : 0.00 ' + DEVISE;
                document.getElementById('nbDepenses').textContent = '0 dépense(s)';
                return;
            }
            tbody.innerHTML = data.depenses.map(d => {
                const date = new Date(d.date_depense.replace(' ','T'));
                const ds = date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit'});
                const annulerBtn = IS_ADMIN ? `<td><button class="btn btn-ghost-danger btn-sm" onclick="annulerDepense(${d.id_depense}, '${d.libelle.replace(/'/g,"\\'")}')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button></td>` : '';
                const notes = d.notes ? `<br><small class="text-muted">${d.notes}</small>` : '';
                return `<tr>
                    <td class="text-muted small">${ds}</td>
                    <td><span class="cat-badge" style="background:${d.couleur}22;color:${d.couleur};">${d.nom_categorie}</span></td>
                    <td>${d.libelle}${notes}</td>
                    <td class="text-end text-danger fw-semibold">${fmt(d.montant)}</td>
                    <td class="text-muted small">${d.nom_depot || '—'}</td>
                    <td class="text-muted small">${d.utilisateur || '—'}</td>
                    ${annulerBtn}
                </tr>`;
            }).join('');
            document.getElementById('totalDepenses').textContent = 'Total : ' + fmt(data.total) + ' ' + DEVISE;
            document.getElementById('nbDepenses').textContent = data.depenses.length + ' dépense(s)';
        });
}

function modalCategories() {
    document.getElementById('catId').value = '';
    document.getElementById('catNom').value = '';
    document.getElementById('catDesc').value = '';
    document.getElementById('catCouleur').value = '#6c757d';
    document.getElementById('catOrdre').value = 0;
    document.getElementById('titreModalCat').textContent = 'Nouvelle catégorie';
    new bootstrap.Modal(document.getElementById('modalGestionCategories')).show();
}

function editCategorie(cat) {
    document.getElementById('catId').value     = cat.id_categorie;
    document.getElementById('catNom').value    = cat.nom_categorie;
    document.getElementById('catDesc').value   = cat.description || '';
    document.getElementById('catCouleur').value = cat.couleur;
    document.getElementById('catOrdre').value  = cat.ordre_affichage;
    document.getElementById('titreModalCat').textContent = 'Modifier catégorie';
    new bootstrap.Modal(document.getElementById('modalGestionCategories')).show();
}

function confirmSaveCategorie() {
    const fd = new FormData();
    fd.append('action', 'save_categorie');
    fd.append('id_categorie',    document.getElementById('catId').value);
    fd.append('nom_categorie',   document.getElementById('catNom').value);
    fd.append('description',     document.getElementById('catDesc').value);
    fd.append('couleur',         document.getElementById('catCouleur').value);
    fd.append('ordre_affichage', document.getElementById('catOrdre').value);
    fetch('ajax/depenses.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { bootstrap.Modal.getInstance(document.getElementById('modalGestionCategories')).hide(); showModal('success', data.message); setTimeout(() => location.reload(), 1000); }
            else showModal('error', data.message || 'Erreur');
        });
}
</script>

<?php require_once('footer.php'); ?>
