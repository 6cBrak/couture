<?php
require_once('protection_pages.php');
$page_title = 'Rapport Dépenses';

// ── Vérification migration ────────────────────────────────────────────────────
$migration_ok = true;
try { db_fetch_one("SELECT 1 FROM categories_depenses LIMIT 1"); } catch (Exception $e) { $migration_ok = false; }
if (!$migration_ok) {
    require_once('header.php');
    echo '<div class="container-xl py-5"><div class="alert alert-danger"><h4 class="alert-title">Migration requise</h4><p class="mb-0">Exécutez <strong><code>database/migration_caisse_depenses.sql</code></strong> puis rechargez la page.</p></div></div>';
    require_once('footer.php'); exit;
}

// ── Filtres ────────────────────────────────────────────────────────────────────
$periode      = $_GET['periode']      ?? 'month';
$date_debut   = $_GET['date_debut']   ?? date('Y-m-01');
$date_fin     = $_GET['date_fin']     ?? date('Y-m-d');
$id_categorie = intval($_GET['id_categorie'] ?? 0);

switch ($periode) {
    case 'today': $date_debut = $date_fin = date('Y-m-d'); break;
    case 'week':  $date_debut = date('Y-m-d', strtotime('monday this week')); $date_fin = date('Y-m-d'); break;
    case 'month': $date_debut = date('Y-m-01'); $date_fin = date('Y-m-d'); break;
    case 'year':  $date_debut = date('Y-01-01'); $date_fin = date('Y-m-d'); break;
}

$dt_debut = $date_debut . ' 00:00:00';
$dt_fin   = $date_fin   . ' 23:59:59';

// ── Catégories (pour filtre) ───────────────────────────────────────────────────
$categories = db_fetch_all("SELECT * FROM categories_depenses WHERE est_actif = 1 ORDER BY ordre_affichage, nom_categorie");

// ── WHERE dynamique ────────────────────────────────────────────────────────────
$where_cat = $id_categorie ? "AND d.id_categorie = $id_categorie" : '';

// ── KPIs globaux ──────────────────────────────────────────────────────────────
$resume = db_fetch_one("
    SELECT
        COUNT(*)                       AS nb_depenses,
        COALESCE(SUM(montant), 0)      AS total,
        COALESCE(AVG(montant), 0)      AS moyenne,
        COALESCE(MAX(montant), 0)      AS maximum
    FROM depenses d
    WHERE d.date_depense BETWEEN ? AND ?
      AND d.statut = 'validee'
      $where_cat
", [$dt_debut, $dt_fin]);

// ── Par catégorie ─────────────────────────────────────────────────────────────
$par_categorie = db_fetch_all("
    SELECT c.nom_categorie, c.couleur, c.icone,
           COUNT(d.id_depense)    AS nb,
           SUM(d.montant)         AS total,
           AVG(d.montant)         AS moyenne,
           MAX(d.montant)         AS maximum
    FROM depenses d
    INNER JOIN categories_depenses c ON d.id_categorie = c.id_categorie
    WHERE d.date_depense BETWEEN ? AND ?
      AND d.statut = 'validee'
      $where_cat
    GROUP BY c.id_categorie
    ORDER BY total DESC
", [$dt_debut, $dt_fin]);

// ── Évolution journalière ──────────────────────────────────────────────────────
$evolution = db_fetch_all("
    SELECT DATE(d.date_depense) AS jour, SUM(d.montant) AS total
    FROM depenses d
    WHERE d.date_depense BETWEEN ? AND ?
      AND d.statut = 'validee'
      $where_cat
    GROUP BY jour
    ORDER BY jour ASC
", [$dt_debut, $dt_fin]);

// ── Top dépenses ──────────────────────────────────────────────────────────────
$top_depenses = db_fetch_all("
    SELECT d.libelle, d.montant, d.date_depense,
           c.nom_categorie, c.couleur,
           u.nom_complet AS utilisateur
    FROM depenses d
    LEFT JOIN categories_depenses c ON d.id_categorie = c.id_categorie
    LEFT JOIN utilisateurs        u ON d.id_utilisateur = u.id_utilisateur
    WHERE d.date_depense BETWEEN ? AND ?
      AND d.statut = 'validee'
      $where_cat
    ORDER BY d.montant DESC
    LIMIT 10
", [$dt_debut, $dt_fin]);

// ── Comparaison mois précédent ─────────────────────────────────────────────────
$mois_prec_debut = date('Y-m-01', strtotime($date_debut . ' -1 month'));
$mois_prec_fin   = date('Y-m-t',  strtotime($date_debut . ' -1 month'));
$prec = db_fetch_one("
    SELECT COALESCE(SUM(montant), 0) AS total
    FROM depenses
    WHERE date_depense BETWEEN ? AND ?
      AND statut = 'validee'
      $where_cat
", [$mois_prec_debut . ' 00:00:00', $mois_prec_fin . ' 23:59:59']);

$variation = ($prec['total'] > 0)
    ? round(((float)$resume['total'] - (float)$prec['total']) / (float)$prec['total'] * 100, 1)
    : null;

require_once('header.php');
?>

<style>
.rapport-card { border-radius: 14px; border: none; box-shadow: 0 3px 14px rgba(0,0,0,0.07); }
.kpi-value    { font-size: 1.85rem; font-weight: 700; letter-spacing: -0.5px; }
.cat-dot      { width: 12px; height: 12px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.table.card-table th, .table.card-table td { vertical-align: middle; color: #2f2f2f; }
.progress-sm  { height: 7px; border-radius: 4px; }
</style>

<div class="container-xl py-4">

    <!-- En-tête -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title fw-bold" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    Rapport Dépenses
                </h2>
                <p class="text-muted mb-0">Analyse des dépenses par période et catégorie</p>
            </div>
            <div class="col-auto">
                <a href="depenses.php" class="btn btn-outline-secondary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l6 -6"/><circle cx="9.5" cy="9.5" r=".5" fill="currentColor"/><circle cx="14.5" cy="14.5" r=".5" fill="currentColor"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/></svg>
                    Gestion dépenses
                </a>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card rapport-card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-1 small fw-semibold">Période</label>
                    <select name="periode" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="today"  <?php echo $periode=='today' ?'selected':''; ?>>Aujourd'hui</option>
                        <option value="week"   <?php echo $periode=='week'  ?'selected':''; ?>>Cette semaine</option>
                        <option value="month"  <?php echo $periode=='month' ?'selected':''; ?>>Ce mois</option>
                        <option value="year"   <?php echo $periode=='year'  ?'selected':''; ?>>Cette année</option>
                        <option value="custom" <?php echo $periode=='custom'?'selected':''; ?>>Personnalisée</option>
                    </select>
                </div>
                <?php if ($periode === 'custom'): ?>
                <div class="col-auto">
                    <label class="form-label mb-1 small fw-semibold">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm" value="<?php echo $date_debut; ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-1 small fw-semibold">Au</label>
                    <input type="date" name="date_fin"   class="form-control form-control-sm" value="<?php echo $date_fin; ?>">
                </div>
                <?php endif; ?>
                <div class="col-auto">
                    <label class="form-label mb-1 small fw-semibold">Catégorie</label>
                    <select name="id_categorie" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Toutes</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id_categorie']; ?>" <?php echo $id_categorie == $cat['id_categorie'] ? 'selected' : ''; ?>>
                            <?php echo e($cat['nom_categorie']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($periode === 'custom'): ?>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                </div>
                <?php endif; ?>
                <div class="col-auto ms-auto text-muted small">
                    <?php echo date('d/m/Y', strtotime($date_debut)); ?> → <?php echo date('d/m/Y', strtotime($date_fin)); ?>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card rapport-card text-center py-3">
                <div class="text-muted small fw-semibold mb-1">TOTAL DÉPENSES</div>
                <div class="kpi-value text-danger"><?php echo number_format($resume['total'], 2); ?></div>
                <div class="small text-muted"><?php echo e($devise); ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card rapport-card text-center py-3">
                <div class="text-muted small fw-semibold mb-1">NB OPÉRATIONS</div>
                <div class="kpi-value text-primary"><?php echo (int)$resume['nb_depenses']; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card rapport-card text-center py-3">
                <div class="text-muted small fw-semibold mb-1">MOYENNE / OP.</div>
                <div class="kpi-value text-secondary"><?php echo number_format($resume['moyenne'], 2); ?></div>
                <div class="small text-muted"><?php echo e($devise); ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card rapport-card text-center py-3">
                <div class="text-muted small fw-semibold mb-1">VS PÉRIODE PRÉC.</div>
                <?php if ($variation !== null): $v_cls = $variation > 0 ? 'text-danger' : 'text-success'; ?>
                <div class="kpi-value <?php echo $v_cls; ?>"><?php echo ($variation > 0 ? '+' : '') . $variation; ?>%</div>
                <div class="small text-muted"><?php echo number_format($prec['total'], 2); ?> <?php echo e($devise); ?></div>
                <?php else: ?>
                <div class="kpi-value text-muted">—</div>
                <div class="small text-muted">Pas de données</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Répartition par catégorie -->
        <div class="col-lg-5">
            <div class="card rapport-card h-100">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h5 class="card-title fw-semibold mb-0">Répartition par catégorie</h5>
                </div>
                <div class="card-body">
                    <?php if (count($par_categorie)):
                        $total_g = (float)$resume['total'] ?: 1;
                        foreach ($par_categorie as $pc):
                            $pct = round((float)$pc['total'] / $total_g * 100);
                    ?>
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="d-flex align-items-center gap-2 fw-medium small">
                                <span class="cat-dot" style="background:<?php echo $pc['couleur']; ?>;"></span>
                                <?php echo e($pc['nom_categorie']); ?>
                                <span class="text-muted">(<?php echo $pc['nb']; ?>)</span>
                            </span>
                            <span class="small fw-semibold text-danger"><?php echo number_format($pc['total'], 2); ?> <?php echo e($devise); ?></span>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar" style="width:<?php echo $pct; ?>%;background:<?php echo $pc['couleur']; ?>;border-radius:4px;"></div>
                        </div>
                        <div class="text-end text-muted" style="font-size:0.7rem;"><?php echo $pct; ?>%</div>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="text-center text-muted py-5">Aucune dépense sur la période.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Graphique évolution -->
        <div class="col-lg-7">
            <div class="card rapport-card h-100">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h5 class="card-title fw-semibold mb-0">Évolution journalière</h5>
                </div>
                <div class="card-body">
                    <?php if (count($evolution)): ?>
                    <div id="chartDepenses" style="min-height:200px;"></div>
                    <?php else: ?>
                    <div class="text-center text-muted py-5">Aucune donnée sur la période.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top 10 dépenses -->
    <div class="card rapport-card">
        <div class="card-header bg-white border-bottom-0 pt-3">
            <h5 class="card-title fw-semibold mb-0">Top dépenses de la période</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover card-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Catégorie</th>
                        <th>Libellé</th>
                        <th class="text-end">Montant</th>
                        <th>Opérateur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($top_depenses)):
                        foreach ($top_depenses as $i => $d):
                    ?>
                    <tr>
                        <td class="text-muted small"><?php echo $i + 1; ?></td>
                        <td class="small"><?php echo date('d/m/Y H:i', strtotime($d['date_depense'])); ?></td>
                        <td>
                            <span class="d-inline-flex align-items-center gap-1 fw-medium small" style="color:<?php echo $d['couleur']; ?>;">
                                <span class="cat-dot" style="background:<?php echo $d['couleur']; ?>;"></span>
                                <?php echo e($d['nom_categorie']); ?>
                            </span>
                        </td>
                        <td><?php echo e($d['libelle']); ?></td>
                        <td class="text-end text-danger fw-semibold"><?php echo number_format($d['montant'], 2); ?> <?php echo e($devise); ?></td>
                        <td class="small text-muted"><?php echo e($d['utilisateur']); ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">Aucune dépense sur la période.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (count($evolution)): ?>
<script src="<?php echo BASE_URL; ?>dist/libs/apexcharts/apexcharts.min.js"></script>
<script>
const evo = <?php echo json_encode($evolution); ?>;
const DEVISE = <?php echo json_encode($devise); ?>;
const COULEUR = <?php echo json_encode($couleur_primaire); ?>;
const opts = {
    chart: { type: 'area', height: 200, toolbar: { show: false }, fontFamily: 'inherit', sparkline: { enabled: false } },
    series: [{ name: 'Dépenses', data: evo.map(r => parseFloat(r.total)) }],
    colors: ['#e53935'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: { categories: evo.map(r => {
        const d = new Date(r.jour);
        return d.toLocaleDateString('fr-FR', {day:'2-digit', month:'short'});
    }), labels: { style: { fontSize: '11px' } } },
    yaxis: { labels: { formatter: v => v.toLocaleString('fr-FR', {minimumFractionDigits:0}) } },
    tooltip: { y: { formatter: v => v.toLocaleString('fr-FR', {minimumFractionDigits:2}) + ' ' + DEVISE } },
    dataLabels: { enabled: false },
    grid: { borderColor: '#f0f0f0' },
    markers: { size: 4, colors: ['#e53935'], strokeWidth: 2 },
};
new ApexCharts(document.getElementById('chartDepenses'), opts).render();
</script>
<?php endif; ?>

<?php require_once('footer.php'); ?>
