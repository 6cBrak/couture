<?php
require_once('protection_pages.php');
$page_title = 'Rapport Caisse';

// ── Vérification migration ────────────────────────────────────────────────────
$migration_ok = true;
try { db_fetch_one("SELECT 1 FROM caisse LIMIT 1"); } catch (Exception $e) { $migration_ok = false; }
if (!$migration_ok) {
    require_once('header.php');
    echo '<div class="container-xl py-5"><div class="alert alert-danger"><h4 class="alert-title">Migration requise</h4><p class="mb-0">Exécutez <strong><code>database/migration_caisse_depenses.sql</code></strong> puis rechargez la page.</p></div></div>';
    require_once('footer.php'); exit;
}

// ── Filtres ────────────────────────────────────────────────────────────────────
$periode    = $_GET['periode']     ?? 'month';
$date_debut = $_GET['date_debut']  ?? date('Y-m-01');
$date_fin   = $_GET['date_fin']    ?? date('Y-m-d');

switch ($periode) {
    case 'today': $date_debut = $date_fin = date('Y-m-d'); break;
    case 'week':  $date_debut = date('Y-m-d', strtotime('monday this week')); $date_fin = date('Y-m-d'); break;
    case 'month': $date_debut = date('Y-m-01'); $date_fin = date('Y-m-d'); break;
    case 'year':  $date_debut = date('Y-01-01'); $date_fin = date('Y-m-d'); break;
}

$dt_debut = $date_debut . ' 00:00:00';
$dt_fin   = $date_fin   . ' 23:59:59';

// ── Résumé global ─────────────────────────────────────────────────────────────
$resume = db_fetch_one("
    SELECT
        COUNT(DISTINCT c.id_caisse)                                                     AS nb_sessions,
        COALESCE(SUM(CASE WHEN m.sens='entree' THEN m.montant ELSE 0 END), 0)           AS total_entrees,
        COALESCE(SUM(CASE WHEN m.sens='sortie' THEN m.montant ELSE 0 END), 0)           AS total_sorties,
        COALESCE(SUM(CASE WHEN m.type_mouvement='vente_especes'  THEN m.montant ELSE 0 END), 0) AS ventes_especes,
        COALESCE(SUM(CASE WHEN m.type_mouvement='depense'        THEN m.montant ELSE 0 END), 0) AS total_depenses,
        COALESCE(SUM(CASE WHEN m.type_mouvement='entree_manuelle' THEN m.montant ELSE 0 END), 0) AS entrees_manuelles,
        COALESCE(SUM(CASE WHEN m.type_mouvement='sortie_manuelle' THEN m.montant ELSE 0 END), 0) AS sorties_manuelles,
        COALESCE(AVG(CASE WHEN c.ecart IS NOT NULL THEN c.ecart END), 0)                AS ecart_moyen
    FROM caisse c
    LEFT JOIN mouvements_caisse m ON m.id_caisse = c.id_caisse
    WHERE c.date_ouverture BETWEEN ? AND ?
", [$dt_debut, $dt_fin]);

// ── Sessions de la période ────────────────────────────────────────────────────
$sessions = db_fetch_all("
    SELECT c.*,
           u.nom_complet AS nom_utilisateur,
           COALESCE(SUM(CASE WHEN m.sens='entree' THEN m.montant ELSE 0 END), 0) AS total_entrees,
           COALESCE(SUM(CASE WHEN m.sens='sortie' THEN m.montant ELSE 0 END), 0) AS total_sorties,
           COALESCE(SUM(CASE WHEN m.type_mouvement='vente_especes' THEN m.montant ELSE 0 END), 0) AS ventes_especes,
           COALESCE(SUM(CASE WHEN m.type_mouvement='depense'       THEN m.montant ELSE 0 END), 0) AS depenses,
           COUNT(m.id_mouvement) AS nb_mouvements
    FROM caisse c
    LEFT JOIN utilisateurs    u ON c.id_utilisateur = u.id_utilisateur
    LEFT JOIN mouvements_caisse m ON m.id_caisse = c.id_caisse
    WHERE c.date_ouverture BETWEEN ? AND ?
    GROUP BY c.id_caisse
    ORDER BY c.date_ouverture DESC
", [$dt_debut, $dt_fin]);

// ── Évolution journalière (entrées vs sorties) ────────────────────────────────
$evolution = db_fetch_all("
    SELECT DATE(m.date_mouvement) AS jour,
           COALESCE(SUM(CASE WHEN m.sens='entree' THEN m.montant ELSE 0 END), 0) AS entrees,
           COALESCE(SUM(CASE WHEN m.sens='sortie' THEN m.montant ELSE 0 END), 0) AS sorties
    FROM mouvements_caisse m
    INNER JOIN caisse c ON m.id_caisse = c.id_caisse
    WHERE m.date_mouvement BETWEEN ? AND ?
    GROUP BY jour
    ORDER BY jour ASC
", [$dt_debut, $dt_fin]);

require_once('header.php');
?>

<style>
.rapport-card { border-radius: 14px; border: none; box-shadow: 0 3px 14px rgba(0,0,0,0.07); }
.kpi-value    { font-size: 1.9rem; font-weight: 700; letter-spacing: -0.5px; }
.table.card-table th, .table.card-table td { vertical-align: middle; color: #2f2f2f; }
.badge-statut-ouverte  { background: #d1fae5; color: #065f46; }
.badge-statut-fermee   { background: #f3f4f6; color: #6b7280; }
</style>

<div class="container-xl py-4">

    <!-- En-tête + filtres -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title fw-bold" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    Rapport Caisse
                </h2>
                <p class="text-muted mb-0">Sessions, mouvements et écarts de caisse</p>
            </div>
            <div class="col-auto">
                <a href="caisse.php" class="btn btn-outline-secondary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                    Gestion caisse
                </a>
            </div>
        </div>
    </div>

    <!-- Filtres période -->
    <div class="card rapport-card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-1 small fw-semibold">Période</label>
                    <select name="periode" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="today" <?php echo $periode=='today'?'selected':''; ?>>Aujourd'hui</option>
                        <option value="week"  <?php echo $periode=='week' ?'selected':''; ?>>Cette semaine</option>
                        <option value="month" <?php echo $periode=='month'?'selected':''; ?>>Ce mois</option>
                        <option value="year"  <?php echo $periode=='year' ?'selected':''; ?>>Cette année</option>
                        <option value="custom"<?php echo $periode=='custom'?'selected':''; ?>>Personnalisée</option>
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

    <!-- KPI -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card rapport-card text-center py-3">
                <div class="text-muted small fw-semibold mb-1">SESSIONS</div>
                <div class="kpi-value text-primary"><?php echo (int)$resume['nb_sessions']; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card rapport-card text-center py-3">
                <div class="text-muted small fw-semibold mb-1">TOTAL ENTRÉES</div>
                <div class="kpi-value text-success"><?php echo number_format($resume['total_entrees'], 2); ?></div>
                <div class="small text-muted"><?php echo e($devise); ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card rapport-card text-center py-3">
                <div class="text-muted small fw-semibold mb-1">TOTAL SORTIES</div>
                <div class="kpi-value text-danger"><?php echo number_format($resume['total_sorties'], 2); ?></div>
                <div class="small text-muted"><?php echo e($devise); ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card rapport-card text-center py-3">
                <div class="text-muted small fw-semibold mb-1">ÉCART MOYEN</div>
                <?php $ec = (float)$resume['ecart_moyen']; $ec_cls = $ec < 0 ? 'text-danger' : ($ec > 0 ? 'text-warning' : 'text-muted'); ?>
                <div class="kpi-value <?php echo $ec_cls; ?>"><?php echo ($ec >= 0 ? '+' : '') . number_format($ec, 2); ?></div>
                <div class="small text-muted"><?php echo e($devise); ?></div>
            </div>
        </div>
    </div>

    <!-- Détail des flux -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card rapport-card h-100">
                <div class="card-header bg-white border-bottom-0 pt-3"><h5 class="card-title fw-semibold mb-0">Répartition des entrées</h5></div>
                <div class="card-body">
                    <?php
                    $entrees_data = [
                        ['Ventes espèces',   $resume['ventes_especes'],    '#2fb344'],
                        ['Entrées manuelles',$resume['entrees_manuelles'],  '#4263eb'],
                    ];
                    $total_e = (float)$resume['total_entrees'] ?: 1;
                    foreach ($entrees_data as [$label, $val, $color]):
                        $pct = round($val / $total_e * 100);
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-medium small"><?php echo $label; ?></span>
                            <span class="small text-muted"><?php echo number_format($val, 2); ?> <?php echo e($devise); ?> (<?php echo $pct; ?>%)</span>
                        </div>
                        <div class="progress" style="height:7px;border-radius:4px;">
                            <div class="progress-bar" style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>;border-radius:4px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card rapport-card h-100">
                <div class="card-header bg-white border-bottom-0 pt-3"><h5 class="card-title fw-semibold mb-0">Répartition des sorties</h5></div>
                <div class="card-body">
                    <?php
                    $sorties_data = [
                        ['Dépenses',         $resume['total_depenses'],    '#e53935'],
                        ['Sorties manuelles',$resume['sorties_manuelles'], '#f59f00'],
                    ];
                    $total_s = (float)$resume['total_sorties'] ?: 1;
                    foreach ($sorties_data as [$label, $val, $color]):
                        $pct = round($val / $total_s * 100);
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-medium small"><?php echo $label; ?></span>
                            <span class="small text-muted"><?php echo number_format($val, 2); ?> <?php echo e($devise); ?> (<?php echo $pct; ?>%)</span>
                        </div>
                        <div class="progress" style="height:7px;border-radius:4px;">
                            <div class="progress-bar" style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>;border-radius:4px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique évolution -->
    <?php if (count($evolution)): ?>
    <div class="card rapport-card mb-4">
        <div class="card-header bg-white border-bottom-0 pt-3">
            <h5 class="card-title fw-semibold mb-0">Évolution journalière</h5>
        </div>
        <div class="card-body">
            <div id="chartEvolution" style="min-height:220px;"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tableau des sessions -->
    <div class="card rapport-card">
        <div class="card-header bg-white border-bottom-0 pt-3">
            <h5 class="card-title fw-semibold mb-0">Détail des sessions (<?php echo count($sessions); ?>)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover card-table">
                <thead class="table-light">
                    <tr>
                        <th>Ouverture</th>
                        <th>Fermeture</th>
                        <th class="text-end">Fond initial</th>
                        <th class="text-end">Ventes esp.</th>
                        <th class="text-end">Dépenses</th>
                        <th class="text-end">Théorique</th>
                        <th class="text-end">Réel</th>
                        <th class="text-end">Écart</th>
                        <th>Opérateur</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($sessions)): foreach ($sessions as $s): ?>
                    <tr>
                        <td class="small"><?php echo date('d/m/Y H:i', strtotime($s['date_ouverture'])); ?></td>
                        <td class="small text-muted"><?php echo $s['date_fermeture'] ? date('d/m/Y H:i', strtotime($s['date_fermeture'])) : '—'; ?></td>
                        <td class="text-end"><?php echo number_format($s['solde_ouverture'], 2); ?></td>
                        <td class="text-end text-success"><?php echo number_format($s['ventes_especes'], 2); ?></td>
                        <td class="text-end text-danger"><?php echo number_format($s['depenses'], 2); ?></td>
                        <td class="text-end"><?php echo $s['solde_theorique'] !== null ? number_format($s['solde_theorique'], 2) : '—'; ?></td>
                        <td class="text-end"><?php echo $s['solde_fermeture'] !== null ? number_format($s['solde_fermeture'], 2) : '—'; ?></td>
                        <td class="text-end">
                            <?php if ($s['ecart'] !== null):
                                $ec = (float)$s['ecart'];
                                $cls = $ec < 0 ? 'text-danger' : ($ec > 0 ? 'text-warning' : 'text-muted');
                            ?>
                            <span class="fw-semibold <?php echo $cls; ?>"><?php echo ($ec >= 0?'+':'') . number_format($ec,2); ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="small"><?php echo e($s['nom_utilisateur']); ?></td>
                        <td>
                            <?php if ($s['statut'] === 'ouverte'): ?>
                            <span class="badge badge-statut-ouverte fw-semibold" style="font-size:0.72rem;">Ouverte</span>
                            <?php else: ?>
                            <span class="badge badge-statut-fermee fw-semibold" style="font-size:0.72rem;">Fermée</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-5">Aucune session sur la période.</td></tr>
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
const opts = {
    chart: { type: 'bar', height: 220, toolbar: { show: false }, fontFamily: 'inherit' },
    series: [
        { name: 'Entrées', data: evo.map(r => parseFloat(r.entrees)) },
        { name: 'Sorties', data: evo.map(r => parseFloat(r.sorties)) },
    ],
    colors: ['#2fb344', '#e53935'],
    xaxis: { categories: evo.map(r => {
        const d = new Date(r.jour);
        return d.toLocaleDateString('fr-FR', {day:'2-digit', month:'short'});
    }), labels: { style: { fontSize: '11px' } } },
    yaxis: { labels: { formatter: v => v.toLocaleString('fr-FR', {minimumFractionDigits:0}) + ' ' + DEVISE } },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
    legend: { position: 'top' },
    tooltip: { y: { formatter: v => v.toLocaleString('fr-FR', {minimumFractionDigits:2}) + ' ' + DEVISE } },
    dataLabels: { enabled: false },
    grid: { borderColor: '#f0f0f0' },
};
new ApexCharts(document.getElementById('chartEvolution'), opts).render();
</script>
<?php endif; ?>

<?php require_once('footer.php'); ?>
