<?php
require_once('protection_pages.php');
$page_title = 'Atelier Couture';

if (!$atelier_mode) {
    redirect('accueil.php');
}

$tab_active = $_GET['tab'] ?? 'commandes';

// ── Vérification tables ───────────────────────────────────────────────────────
$migration_ok = true;
try {
    db_fetch_one("SELECT 1 FROM commandes_atelier LIMIT 1");
    db_fetch_one("SELECT 1 FROM tailleurs LIMIT 1");
} catch (Exception $e) {
    $migration_ok = false;
}

// ── Données ───────────────────────────────────────────────────────────────────
$tailleurs_liste = [];

if ($migration_ok) {
    $tailleurs_liste = db_fetch_all(
        "SELECT id_tailleur, nom, telephone, specialite FROM tailleurs WHERE est_actif = 1 ORDER BY nom"
    );
}

include 'header.php';
?>

<div class="container-xl">
    <!-- En-tête -->
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
                    Atelier Couture
                </h2>
                <div class="text-muted mt-1">Gestion des commandes et tailleurs</div>
            </div>
            <div class="col-auto">
                <?php if ($tab_active === 'commandes'): ?>
                <a href="vente.php" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="6" cy="19" r="2"/><circle cx="17" cy="19" r="2"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/></svg>
                    Nouvelle vente
                </a>
                <?php elseif ($tab_active === 'tailleurs' && $is_admin): ?>
                <button class="btn btn-primary" id="btnNouveauTailleur">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nouveau tailleur
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$migration_ok): ?>
    <div class="alert alert-warning">
        <strong>Migration requise :</strong> Veuillez exécuter le fichier
        <code>database/migration_atelier.sql</code> pour activer le module Atelier.
    </div>
    <?php else: ?>

    <!-- Onglets -->
    <ul class="nav nav-tabs mb-4" id="atelierTabs">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab_active === 'commandes' ? 'active' : ''; ?>"
               href="atelier.php?tab=commandes">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 10h8"/><path d="M8 14h5"/></svg>
                Commandes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab_active === 'affectation' ? 'active' : ''; ?>"
               href="atelier.php?tab=affectation">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/><circle cx="12" cy="12" r="9"/></svg>
                Affectation
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab_active === 'tailleurs' ? 'active' : ''; ?>"
               href="atelier.php?tab=tailleurs">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                Tailleurs
            </a>
        </li>
    </ul>

    <!-- ===== TAB COMMANDES ===== -->
    <?php if ($tab_active === 'commandes'): ?>

    <!-- Stats rapides -->
    <div class="row g-3 mb-4" id="statsCards">
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="h3 mb-0 fw-bold text-primary" id="statTotal">—</div>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="h3 mb-0 fw-bold text-info" id="statRecu">—</div>
                    <small class="text-muted">Reçues</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="h3 mb-0 fw-bold text-warning" id="statEnCours">—</div>
                    <small class="text-muted">En cours</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="h3 mb-0 fw-bold text-success" id="statTerminee">—</div>
                    <small class="text-muted">Terminées</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="h3 mb-0 fw-bold text-azure" id="statLivre">—</div>
                    <small class="text-muted">Livrées</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-lg-2">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="h3 mb-0 fw-bold text-danger" id="statAnnulee">—</div>
                    <small class="text-muted">Annulées</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Du</label>
                    <input type="date" id="filterDateDebut" class="form-control form-control-sm" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Au</label>
                    <input type="date" id="filterDateFin" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Statut</label>
                    <select id="filterStatut" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="recu">Reçu</option>
                        <option value="en_cours">En cours</option>
                        <option value="terminee">Terminée</option>
                        <option value="livre">Livré</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">Tailleur</label>
                    <select id="filterTailleur" class="form-select form-select-sm">
                        <option value="">Tous les tailleurs</option>
                        <?php foreach ($tailleurs_liste as $t): ?>
                        <option value="<?php echo $t['id_tailleur']; ?>"><?php echo e($t['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Recherche</label>
                    <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Réf, description…">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary btn-sm w-100" id="btnFiltrer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><line x1="21" y1="21" x2="15" y2="15"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table commandes -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Référence</th>
                            <th>Description</th>
                            <th>Client</th>
                            <th>Tailleur</th>
                            <th>Montant</th>
                            <th>Acompte</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Échéance</th>
                            <th style="white-space:nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableauCommandes">
                        <tr><td colspan="10" class="text-center py-4 text-muted">Chargement…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== TAB AFFECTATION ===== -->
    <?php elseif ($tab_active === 'affectation'): ?>

    <!-- Filtres affectation -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Du</label>
                    <input type="date" id="affDateDebut" class="form-control form-control-sm" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Au</label>
                    <input type="date" id="affDateFin" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">Affectation</label>
                    <select id="affStatut" class="form-select form-select-sm">
                        <option value="">Toutes les ventes</option>
                        <option value="sans">Sans tailleur</option>
                        <option value="avec">Déjà affectées</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-sm w-100" id="btnFiltrerAff">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><line x1="21" y1="21" x2="15" y2="15"/></svg>
                        Filtrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table affectation -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Facture</th>
                            <th>Client</th>
                            <th>Confection</th>
                            <th class="text-end">Montant</th>
                            <th>Paiement</th>
                            <th>Date</th>
                            <th>Affectation</th>
                            <th style="white-space:nowrap;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tableauAffectation">
                        <tr><td colspan="8" class="text-center py-4 text-muted">Chargement…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== TAB TAILLEURS ===== -->
    <?php elseif ($tab_active === 'tailleurs'): ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-vcenter mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nom</th>
                            <th>Téléphone</th>
                            <th>Spécialité</th>
                            <th class="text-end">Solde acomptes</th>
                            <th class="text-center">Commandes</th>
                            <th style="white-space:nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableauTailleurs">
                        <tr><td colspan="6" class="text-center py-4 text-muted">Chargement…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; /* migration_ok */ ?>
</div>

<!-- ===== MODAL AFFECTATION MULTI-ARTICLE ===== -->
<div class="modal fade" id="modalAffectation" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Affecter les tailleurs — <span id="aff_facture_info" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <input type="hidden" id="aff_id_vente">

                <!-- Entête commun -->
                <div class="p-3 bg-light border-bottom">
                    <div class="row g-2 align-items-end">
                        <div class="col-sm-4">
                            <label class="form-label form-label-sm mb-1">Date d'échéance (commune)</label>
                            <input type="date" class="form-control form-control-sm" id="aff_echeance">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label form-label-sm mb-1">Notes (communes)</label>
                            <input type="text" class="form-control form-control-sm" id="aff_notes" placeholder="Optionnel…">
                        </div>
                        <div class="col-sm-4 d-flex align-items-end gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTailleurUnique" title="Appliquer le même tailleur à tous les articles">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11l3 3l8 -8"/><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9"/></svg>
                                Même tailleur pour tous
                            </button>
                        </div>
                    </div>
                    <!-- Sélecteur "même tailleur pour tous" -->
                    <div id="divTailleurUnique" class="row g-2 mt-1" style="display:none !important;">
                        <div class="col-sm-5">
                            <select class="form-select form-select-sm" id="affTailleurUnique">
                                <option value="">— Choisir un tailleur —</option>
                                <?php foreach ($tailleurs_liste as $t): ?>
                                <option value="<?php echo $t['id_tailleur']; ?>"><?php echo e($t['nom']); ?><?php echo $t['specialite'] ? ' — ' . e($t['specialite']) : ''; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <button type="button" class="btn btn-sm btn-primary w-100" id="btnAppliquerTailleurUnique">Appliquer à tous</button>
                        </div>
                    </div>
                </div>

                <!-- Tableau des articles -->
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tableArticlesAff">
                        <thead class="table-light">
                            <tr>
                                <th>Article</th>
                                <th class="text-center" style="width:60px;">Qté</th>
                                <th style="min-width:200px;">Tailleur</th>
                                <th style="min-width:130px;">Acompte tailleur</th>
                                <th style="min-width:140px;">Statut</th>
                            </tr>
                        </thead>
                        <tbody id="affArticlesTbody">
                            <tr><td colspan="5" class="text-center py-4 text-muted">Chargement…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btnConfirmerAffectation">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                    Enregistrer les affectations
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL STATUT ===== -->
<div class="modal fade" id="modalStatut" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Changer le statut</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="statut_cmd_id">
                <p class="mb-3">Commande : <strong id="statut_ref"></strong></p>
                <label class="form-label">Nouveau statut</label>
                <div class="d-flex flex-column gap-2" id="statutOptions">
                    <label class="border rounded p-2 d-flex align-items-center gap-2 cursor-pointer">
                        <input type="radio" name="nouveau_statut" value="recu"> <span class="badge bg-secondary">Reçu</span> <small class="text-muted">— Commande enregistrée, en attente</small>
                    </label>
                    <label class="border rounded p-2 d-flex align-items-center gap-2 cursor-pointer">
                        <input type="radio" name="nouveau_statut" value="en_cours"> <span class="badge bg-warning text-dark">En cours</span> <small class="text-muted">— Le tailleur a commencé</small>
                    </label>
                    <label class="border rounded p-2 d-flex align-items-center gap-2 cursor-pointer">
                        <input type="radio" name="nouveau_statut" value="terminee"> <span class="badge bg-success">Terminée</span> <small class="text-muted">— Prêt, acompte crédité au tailleur</small>
                    </label>
                    <label class="border rounded p-2 d-flex align-items-center gap-2 cursor-pointer">
                        <input type="radio" name="nouveau_statut" value="livre"> <span class="badge bg-primary">Livré</span> <small class="text-muted">— Remis au client</small>
                    </label>
                    <label class="border rounded p-2 d-flex align-items-center gap-2 cursor-pointer">
                        <input type="radio" name="nouveau_statut" value="annulee"> <span class="badge bg-danger">Annulée</span> <small class="text-muted">— Commande annulée</small>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btnConfirmerStatut">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL TAILLEUR ===== -->
<div class="modal fade" id="modalTailleur" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTailleurTitre">Nouveau tailleur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tail_id">
                <div class="mb-3">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="tail_nom" maxlength="150">
                </div>
                <div class="mb-3">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" id="tail_telephone" maxlength="30">
                </div>
                <div class="mb-3">
                    <label class="form-label">Spécialité</label>
                    <input type="text" class="form-control" id="tail_specialite" maxlength="100" placeholder="Ex: Tenue traditionnelle, costumes, robes…">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" id="tail_notes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btnSauvegarderTailleur">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL PAIEMENT TAILLEUR ===== -->
<div class="modal fade" id="modalPaiement" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payer commission — <span id="pay_nom_tailleur"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pb-2">
                <input type="hidden" id="pay_id_tailleur">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-sm-4">
                        <label class="form-label form-label-sm">Du</label>
                        <input type="date" id="payDateDebut" class="form-control form-control-sm" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label form-label-sm">Au</label>
                        <input type="date" id="payDateFin" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-sm-4">
                        <button class="btn btn-outline-primary btn-sm w-100" id="btnApercu">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2"/><path d="M22 12c-2.667 4.667 -6 7 -10 7s-7.333 -2.333 -10 -7c2.667 -4.667 6 -7 10 -7s7.333 2.333 10 7"/></svg>
                            Aperçu
                        </button>
                    </div>
                </div>

                <div id="payCommandesSection" style="display:none;">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Référence</th>
                                    <th>Facture</th>
                                    <th>Confection</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th class="text-end">Commission</th>
                                </tr>
                            </thead>
                            <tbody id="payCommandesTbody"></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-3 rounded mb-3" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                        <strong class="text-success">Total à payer</strong>
                        <strong class="text-success fs-5" id="payTotal">—</strong>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Notes (optionnel)</label>
                        <input type="text" id="payNotes" class="form-control form-control-sm" placeholder="Remarque sur ce paiement…">
                    </div>
                </div>

                <div id="payAucuneCommande" class="text-center text-muted py-3" style="display:none;">
                    Aucune commande terminée et non payée sur cette période.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="btnConfirmerPaiement" style="display:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 11 12 14 20 6"/><path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9"/></svg>
                    Confirmer le paiement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL MOUVEMENTS TAILLEUR ===== -->
<div class="modal fade" id="modalMouvements" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mouvements — <span id="mvt_nom_tailleur"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body border-bottom pb-3">
                <input type="hidden" id="mvt_id_tailleur">
                <div class="row g-2 align-items-end">
                    <div class="col-sm-4">
                        <label class="form-label form-label-sm mb-1">Du</label>
                        <input type="date" id="mvtDateDebut" class="form-control form-control-sm" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label form-label-sm mb-1">Au</label>
                        <input type="date" id="mvtDateFin" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-sm-4">
                        <button class="btn btn-primary btn-sm w-100" id="btnFiltrerMvt">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><line x1="21" y1="21" x2="15" y2="15"/></svg>
                            Filtrer
                        </button>
                    </div>
                    <div class="col-12 mt-1">
                        <div class="d-flex gap-3 small">
                            <span>Total crédits : <strong class="text-success" id="mvtTotalCredit">—</strong></span>
                            <span>Total débits : <strong class="text-danger" id="mvtTotalDebit">—</strong></span>
                            <span>Solde période : <strong id="mvtSoldePeriode">—</strong></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th class="text-end">Montant</th>
                                <th>Description</th>
                                <th>Commande</th>
                            </tr>
                        </thead>
                        <tbody id="tableMouvements">
                            <tr><td colspan="5" class="text-center py-3 text-muted">Chargement…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// ═══════════════════════════════════════════════════════════════════════════════
// ATELIER COUTURE — JS
// ═══════════════════════════════════════════════════════════════════════════════
const DEVISE = '<?php echo e($devise); ?>';
const IS_ADMIN = <?php echo $is_admin ? 'true' : 'false'; ?>;
const TAB = '<?php echo e($tab_active); ?>';

// Badges statut
const BADGES = {
    recu:     '<span class="badge bg-secondary">Reçu</span>',
    en_cours: '<span class="badge bg-warning text-dark">En cours</span>',
    terminee: '<span class="badge bg-success">Terminée</span>',
    livre:    '<span class="badge bg-primary">Livré</span>',
    annulee:  '<span class="badge bg-danger">Annulée</span>',
};

function fmt(n) { return Number(n).toLocaleString('fr-FR', {minimumFractionDigits:0, maximumFractionDigits:2}) + ' ' + DEVISE; }
function fmtDate(d) { if (!d) return '—'; const dt = new Date(d.replace(' ', 'T')); return dt.toLocaleDateString('fr-FR'); }

// ── Stats ──────────────────────────────────────────────────────────────────────
function loadStats() {
    fetch('ajax/commandes_atelier.php?action=get_stats')
        .then(r => r.json()).then(data => {
            if (!data.success) return;
            const s = data.stats;
            document.getElementById('statTotal').textContent    = s.total    || 0;
            document.getElementById('statRecu').textContent     = s.nb_recu  || 0;
            document.getElementById('statEnCours').textContent  = s.nb_en_cours || 0;
            document.getElementById('statTerminee').textContent = s.nb_terminee || 0;
            document.getElementById('statLivre').textContent    = s.nb_livre || 0;
            document.getElementById('statAnnulee').textContent  = s.nb_annulee || 0;
        });
}

// ── Charger commandes ──────────────────────────────────────────────────────────
function loadCommandes() {
    const params = new URLSearchParams({
        action:       'get_liste',
        date_debut:   document.getElementById('filterDateDebut')?.value || '',
        date_fin:     document.getElementById('filterDateFin')?.value   || '',
        statut:       document.getElementById('filterStatut')?.value    || '',
        id_tailleur:  document.getElementById('filterTailleur')?.value  || '',
        search:       document.getElementById('filterSearch')?.value    || '',
    });

    const tbody = document.getElementById('tableauCommandes');
    tbody.innerHTML = '<tr><td colspan="10" class="text-center py-3 text-muted">Chargement…</td></tr>';

    fetch('ajax/commandes_atelier.php?' + params)
        .then(r => r.json()).then(data => {
            if (!data.success) { tbody.innerHTML = `<tr><td colspan="10" class="text-center text-danger py-3">${data.message}</td></tr>`; return; }
            if (!data.commandes.length) { tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">Aucune commande trouvée.</td></tr>'; return; }

            tbody.innerHTML = data.commandes.map(c => {
                const restant = (c.montant_total - c.acompte_verse);
                return `<tr>
                    <td><span class="fw-semibold text-primary">${e(c.reference)}</span></td>
                    <td>${e(c.description)}</td>
                    <td>${c.nom_client ? e(c.nom_client) : '<span class="text-muted">—</span>'}</td>
                    <td>${c.nom_tailleur ? e(c.nom_tailleur) : '<span class="text-muted">—</span>'}</td>
                    <td class="text-end fw-semibold">${fmt(c.montant_total)}</td>
                    <td class="text-end">${fmt(c.acompte_verse)}<br><small class="text-${restant > 0 ? 'danger' : 'success'}">reste ${fmt(restant)}</small></td>
                    <td>${BADGES[c.statut] || c.statut}</td>
                    <td>${fmtDate(c.date_commande)}</td>
                    <td>${c.date_echeance ? fmtDate(c.date_echeance) : '<span class="text-muted">—</span>'}</td>
                    <td style="white-space:nowrap;">
                        <button class="btn btn-sm btn-outline-warning" onclick="ouvrirStatut(${c.id_commande}, '${e(c.reference)}', '${c.statut}')" title="Changer statut"
                            ${c.statut === 'annulee' ? 'disabled' : ''}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.933 13.041a8 8 0 1 1 -9.925 -8.788c3.899 -1 7.935 1.007 9.425 4.747"/><path d="M20 4v5h-5"/></svg>
                            Statut
                        </button>
                    </td>
                </tr>`;
            }).join('');
        });
}

function e(str) { if (!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

// ── Modal changement statut ────────────────────────────────────────────────────
function ouvrirStatut(id, ref, statutActuel) {
    document.getElementById('statut_cmd_id').value = id;
    document.getElementById('statut_ref').textContent = ref;
    document.querySelectorAll('input[name="nouveau_statut"]').forEach(r => {
        r.checked = r.value === statutActuel;
    });
    new bootstrap.Modal(document.getElementById('modalStatut')).show();
}

document.getElementById('btnConfirmerStatut')?.addEventListener('click', () => {
    const statut = document.querySelector('input[name="nouveau_statut"]:checked')?.value;
    if (!statut) { showAlertModal('Erreur', 'Veuillez sélectionner un statut.', 'warning'); return; }

    const body = new FormData();
    body.append('action',      'update_statut');
    body.append('id_commande', document.getElementById('statut_cmd_id').value);
    body.append('statut',      statut);

    fetch('ajax/commandes_atelier.php', { method: 'POST', body })
        .then(r => r.json()).then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalStatut'))?.hide();
                showAlertModal('Succès', data.message, 'success');
                loadCommandes(); loadStats();
            } else {
                showAlertModal('Erreur', data.message, 'danger');
            }
        });
});

// ── Tailleurs ─────────────────────────────────────────────────────────────────
function loadTailleurs() {
    const tbody = document.getElementById('tableauTailleurs');
    if (!tbody) return;
    fetch('ajax/tailleurs.php?action=get_liste')
        .then(r => r.json()).then(data => {
            if (!data.success) { tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">${data.message}</td></tr>`; return; }
            if (!data.tailleurs.length) { tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Aucun tailleur enregistré.</td></tr>'; return; }

            tbody.innerHTML = data.tailleurs.map(t => `<tr>
                <td class="fw-semibold">${e(t.nom)}</td>
                <td>${t.telephone ? e(t.telephone) : '<span class="text-muted">—</span>'}</td>
                <td>${t.specialite ? e(t.specialite) : '<span class="text-muted">—</span>'}</td>
                <td class="text-end fw-bold text-success">${fmt(t.solde)}</td>
                <td class="text-center"><span class="badge bg-blue-lt">${t.nb_commandes}</span></td>
                <td style="white-space:nowrap;">
                    <button class="btn btn-sm btn-outline-info me-1" onclick="voirMouvements(${t.id_tailleur})" title="Mouvements">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="12" width="6" height="8" rx="1"/><rect x="9" y="8" width="6" height="12" rx="1"/><rect x="15" y="4" width="6" height="16" rx="1"/></svg>
                    </button>
                    ${IS_ADMIN ? `
                    <button class="btn btn-sm btn-outline-success me-1" onclick="ouvrirPaiementTailleur(${t.id_tailleur}, '${e(t.nom)}')" title="Payer commission">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="7" y="9" width="14" height="10" rx="2"/><circle cx="14" cy="14" r="2"/><path d="M17 9v-2a2 2 0 0 0 -2 -2h-10a2 2 0 0 0 -2 2v6a2 2 0 0 0 2 2h2"/></svg>
                    </button>
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="ouvrirEditionTailleur(${t.id_tailleur})" title="Modifier">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7h-3a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-3"/><path d="M9 15h3l8.5 -8.5a1.5 1.5 0 0 0 -3 -3l-8.5 8.5v3"/></svg>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="supprimerTailleur(${t.id_tailleur}, '${e(t.nom)}')" title="Supprimer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3h6v3"/></svg>
                    </button>` : ''}
                </td>
            </tr>`).join('');
        });
}

document.getElementById('btnNouveauTailleur')?.addEventListener('click', () => {
    document.getElementById('modalTailleurTitre').textContent = 'Nouveau tailleur';
    document.getElementById('tail_id').value         = '';
    document.getElementById('tail_nom').value        = '';
    document.getElementById('tail_telephone').value  = '';
    document.getElementById('tail_specialite').value = '';
    document.getElementById('tail_notes').value      = '';
    new bootstrap.Modal(document.getElementById('modalTailleur')).show();
});

function ouvrirEditionTailleur(id) {
    fetch('ajax/tailleurs.php?action=get_liste')
        .then(r => r.json()).then(data => {
            const t = data.tailleurs?.find(x => x.id_tailleur == id);
            if (!t) { showAlertModal('Erreur', 'Tailleur introuvable.', 'danger'); return; }
            document.getElementById('modalTailleurTitre').textContent = 'Modifier tailleur';
            document.getElementById('tail_id').value         = t.id_tailleur;
            document.getElementById('tail_nom').value        = t.nom;
            document.getElementById('tail_telephone').value  = t.telephone || '';
            document.getElementById('tail_specialite').value = t.specialite || '';
            document.getElementById('tail_notes').value      = t.notes || '';
            new bootstrap.Modal(document.getElementById('modalTailleur')).show();
        });
}

document.getElementById('btnSauvegarderTailleur')?.addEventListener('click', () => {
    const body = new FormData();
    body.append('action',      'save');
    body.append('id_tailleur', document.getElementById('tail_id').value);
    body.append('nom',         document.getElementById('tail_nom').value);
    body.append('telephone',   document.getElementById('tail_telephone').value);
    body.append('specialite',  document.getElementById('tail_specialite').value);
    body.append('notes',       document.getElementById('tail_notes').value);

    fetch('ajax/tailleurs.php', { method: 'POST', body })
        .then(r => r.json()).then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalTailleur'))?.hide();
                showAlertModal('Succès', data.message, 'success');
                loadTailleurs();
            } else {
                showAlertModal('Erreur', data.message, 'danger');
            }
        });
});

function supprimerTailleur(id, nom) {
    showConfirmModal('Supprimer', `Supprimer le tailleur "${nom}" ?`, () => {
        const body = new FormData();
        body.append('action', 'delete'); body.append('id_tailleur', id);
        fetch('ajax/tailleurs.php', { method: 'POST', body })
            .then(r => r.json()).then(data => {
                showAlertModal(data.success ? 'Succès' : 'Erreur', data.message, data.success ? 'success' : 'danger');
                if (data.success) loadTailleurs();
            });
    });
}

function voirMouvements(id) {
    document.getElementById('mvt_id_tailleur').value = id;
    document.getElementById('tableMouvements').innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Chargement…</td></tr>';
    new bootstrap.Modal(document.getElementById('modalMouvements')).show();
    chargerMouvements(id);
}

function chargerMouvements(id) {
    const dateDebut = document.getElementById('mvtDateDebut').value;
    const dateFin   = document.getElementById('mvtDateFin').value;
    const params    = new URLSearchParams({
        action:      'get_mouvements',
        id_tailleur: id || document.getElementById('mvt_id_tailleur').value,
        date_debut:  dateDebut,
        date_fin:    dateFin,
    });

    document.getElementById('tableMouvements').innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Chargement…</td></tr>';

    fetch('ajax/tailleurs.php?' + params)
        .then(r => r.json()).then(data => {
            document.getElementById('mvt_nom_tailleur').textContent = data.tailleur?.nom || '';
            const tbody = document.getElementById('tableMouvements');

            if (!data.success || !data.mouvements.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Aucun mouvement sur cette période.</td></tr>';
                document.getElementById('mvtTotalCredit').textContent  = fmt(0);
                document.getElementById('mvtTotalDebit').textContent   = fmt(0);
                document.getElementById('mvtSoldePeriode').textContent = fmt(0);
                return;
            }

            // Calcul des totaux
            let totalCredit = 0, totalDebit = 0;
            data.mouvements.forEach(m => {
                if (m.type_mouvement === 'credit') totalCredit += parseFloat(m.montant);
                else totalDebit += parseFloat(m.montant);
            });
            const soldePeriode = totalCredit - totalDebit;

            document.getElementById('mvtTotalCredit').textContent  = fmt(totalCredit);
            document.getElementById('mvtTotalDebit').textContent   = fmt(totalDebit);
            const sp = document.getElementById('mvtSoldePeriode');
            sp.textContent = fmt(Math.abs(soldePeriode));
            sp.className   = soldePeriode >= 0 ? 'text-success' : 'text-danger';

            tbody.innerHTML = data.mouvements.map(m => `<tr>
                <td>${fmtDate(m.date_mouvement)}</td>
                <td>${m.type_mouvement === 'credit' ? '<span class="badge bg-success">Crédit</span>' : '<span class="badge bg-danger">Débit</span>'}</td>
                <td class="text-end fw-semibold text-${m.type_mouvement === 'credit' ? 'success' : 'danger'}">${m.type_mouvement === 'credit' ? '+' : '-'}${fmt(m.montant)}</td>
                <td>${e(m.description) || '—'}</td>
                <td>${m.ref_commande ? `<span class="text-primary">${e(m.ref_commande)}</span>` : '—'}</td>
            </tr>`).join('');
        });
}

document.getElementById('btnFiltrerMvt')?.addEventListener('click', () => {
    chargerMouvements();
});

// ── Filtres commandes ─────────────────────────────────────────────────────────
document.getElementById('btnFiltrer')?.addEventListener('click', loadCommandes);
document.getElementById('filterSearch')?.addEventListener('keydown', e => { if (e.key === 'Enter') loadCommandes(); });

// ── Affectation ───────────────────────────────────────────────────────────────
const BADGES_MODE = {
    especes:      '<span class="badge bg-green-lt">Espèces</span>',
    carte:        '<span class="badge bg-blue-lt">Carte</span>',
    mobile_money: '<span class="badge bg-purple-lt">Mobile Money</span>',
    cheque:       '<span class="badge bg-yellow-lt">Chèque</span>',
    credit:       '<span class="badge bg-red-lt">Crédit</span>',
};

function loadAffectation() {
    const params = new URLSearchParams({
        action:      'get_ventes',
        date_debut:  document.getElementById('affDateDebut')?.value || '',
        date_fin:    document.getElementById('affDateFin')?.value   || '',
        affectation: document.getElementById('affStatut')?.value    || '',
    });

    const tbody = document.getElementById('tableauAffectation');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-3 text-muted">Chargement…</td></tr>';

    fetch('ajax/commandes_atelier.php?' + params)
        .then(r => r.json()).then(data => {
            if (!data.success) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-3">${data.message}</td></tr>`;
                return;
            }
            if (!data.ventes.length) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">Aucune vente trouvée.</td></tr>';
                return;
            }
            tbody.innerHTML = data.ventes.map(v => {
                const nb       = parseInt(v.nb_articles)  || 0;
                const nbAff    = parseInt(v.nb_affectes)  || 0;
                const complet  = nb > 0 && nbAff >= nb;
                const partiel  = nbAff > 0 && nbAff < nb;
                const statBadge = complet
                    ? '<span class="badge bg-success">Complet</span>'
                    : partiel
                        ? `<span class="badge bg-warning text-dark">Partiel (${nbAff}/${nb})</span>`
                        : '<span class="badge bg-secondary">Non affecté</span>';
                return `<tr>
                    <td class="fw-semibold text-primary">${e(v.numero_facture)}</td>
                    <td>${v.nom_client ? e(v.nom_client) : '<span class="text-muted">Comptoir</span>'}</td>
                    <td>${v.type_confection ? `<span class="badge bg-purple-lt">${e(v.type_confection)}</span>` : '—'}</td>
                    <td class="text-end fw-semibold">${fmt(v.montant_total)}</td>
                    <td>${BADGES_MODE[v.mode_paiement] || e(v.mode_paiement)}</td>
                    <td>${fmtDate(v.date_vente)}</td>
                    <td>${statBadge}</td>
                    <td>
                        <button class="btn btn-sm ${complet ? 'btn-outline-warning' : 'btn-outline-primary'}"
                                onclick="ouvrirAffectation(${v.id_vente}, '${e(v.numero_facture)}')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                            ${complet ? 'Modifier' : 'Affecter'}
                        </button>
                    </td>
                </tr>`;
            }).join('');
        });
}

const TAILLEURS_OPTIONS = `<option value="">— Aucun —</option><?php foreach ($tailleurs_liste as $t): ?><option value="<?php echo $t['id_tailleur']; ?>"><?php echo e($t['nom']); ?><?php echo $t['specialite'] ? ' — ' . e($t['specialite']) : ''; ?></option><?php endforeach; ?>`;

function ouvrirAffectation(idVente, facture) {
    document.getElementById('aff_id_vente').value           = idVente;
    document.getElementById('aff_facture_info').textContent = facture;
    document.getElementById('aff_echeance').value           = '';
    document.getElementById('aff_notes').value              = '';
    document.getElementById('divTailleurUnique').style.setProperty('display','none','important');
    document.getElementById('affArticlesTbody').innerHTML   = '<tr><td colspan="5" class="text-center py-3 text-muted">Chargement…</td></tr>';

    new bootstrap.Modal(document.getElementById('modalAffectation')).show();

    fetch('ajax/commandes_atelier.php?action=get_details_vente&id_vente=' + idVente)
        .then(r => r.json()).then(data => {
            if (!data.success) {
                document.getElementById('affArticlesTbody').innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">${data.message}</td></tr>`;
                return;
            }
            // Regrouper visuellement par article (rowspan sur le nom)
            const rows = data.details;
            let html = '';
            let prevDetail = null, rowspan = 0, rowspanStart = 0;
            // Calculer les rowspans
            const spans = {};
            rows.forEach(d => {
                spans[d.id_detail] = (spans[d.id_detail] || 0) + 1;
            });
            const printed = {};
            rows.forEach((d, i) => {
                const isFirst = !printed[d.id_detail];
                if (isFirst) printed[d.id_detail] = true;
                html += `<tr>
                    ${isFirst ? `<td class="fw-semibold align-middle" rowspan="${spans[d.id_detail]}">${e(d.nom_produit || '—')}<br><small class="text-muted">${spans[d.id_detail]} unité${spans[d.id_detail] > 1 ? 's' : ''}</small></td>` : ''}
                    <td class="text-center text-muted" style="width:50px;">#${d.numero_unite}</td>
                    <td>
                        <select class="form-select form-select-sm aff-tailleur-sel">
                            ${TAILLEURS_OPTIONS}
                        </select>
                    </td>
                    <td style="width:160px;">
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control aff-acompte-inp"
                                   min="0" step="1" placeholder="0" value="${d.acompte_tailleur || 0}">
                            <span class="input-group-text"><?php echo e($devise); ?></span>
                        </div>
                    </td>
                    <td>
                        <select class="form-select form-select-sm aff-statut-sel${!d.id_tailleur ? ' d-none' : ''}">
                            <option value="recu"     ${(d.statut_atelier||'recu')==='recu'     ? 'selected':''}>Reçu</option>
                            <option value="en_cours" ${(d.statut_atelier||'')==='en_cours'     ? 'selected':''}>En cours</option>
                            <option value="terminee" ${(d.statut_atelier||'')==='terminee'     ? 'selected':''}>Terminée</option>
                            <option value="livre"    ${(d.statut_atelier||'')==='livre'        ? 'selected':''}>Livré</option>
                        </select>
                        ${!d.id_tailleur ? '<span class="text-muted small">—</span>' : ''}
                    </td>
                    <input type="hidden" class="aff-detail-id"    value="${d.id_detail}">
                    <input type="hidden" class="aff-num-unite"     value="${d.numero_unite}">
                    <input type="hidden" class="aff-nom-produit"   value="${e(d.nom_produit || '')}">
                </tr>`;
            });
            document.getElementById('affArticlesTbody').innerHTML = html;

            // Pré-sélectionner les tailleurs déjà affectés + gérer visibilité statut
            rows.forEach((d, i) => {
                const tr = document.querySelectorAll('#affArticlesTbody tr')[i];
                if (!tr) return;
                if (d.id_tailleur) {
                    const sel = tr.querySelector('.aff-tailleur-sel');
                    if (sel) sel.value = d.id_tailleur;
                }
                // Afficher/masquer le select statut selon tailleur
                const tSel    = tr.querySelector('.aff-tailleur-sel');
                const sSel    = tr.querySelector('.aff-statut-sel');
                const sPlaceholder = tr.querySelector('.text-muted.small');
                if (tSel && sSel) {
                    tSel.addEventListener('change', () => {
                        const hasTailleur = !!tSel.value;
                        sSel.classList.toggle('d-none', !hasTailleur);
                        if (sPlaceholder) sPlaceholder.style.display = hasTailleur ? 'none' : '';
                    });
                }
            });
        });
}

// Bouton "Même tailleur pour tous"
document.getElementById('btnTailleurUnique')?.addEventListener('click', () => {
    const div = document.getElementById('divTailleurUnique');
    const hidden = div.style.getPropertyValue('display') === 'none';
    div.style.setProperty('display', hidden ? 'flex' : 'none', hidden ? '' : 'important');
});

document.getElementById('btnAppliquerTailleurUnique')?.addEventListener('click', () => {
    const val = document.getElementById('affTailleurUnique').value;
    document.querySelectorAll('.aff-tailleur-sel').forEach(sel => { sel.value = val; });
    document.getElementById('divTailleurUnique').style.setProperty('display','none','important');
});

document.getElementById('btnConfirmerAffectation')?.addEventListener('click', () => {
    const idVente    = document.getElementById('aff_id_vente').value;
    const echeance   = document.getElementById('aff_echeance').value;
    const notes      = document.getElementById('aff_notes').value;

    const rows     = document.getElementById('affArticlesTbody').querySelectorAll('tr');
    const articles = [];
    rows.forEach(row => {
        const idDetail  = row.querySelector('.aff-detail-id')?.value;
        const tailleur  = row.querySelector('.aff-tailleur-sel')?.value;
        const acompte   = row.querySelector('.aff-acompte-inp')?.value;
        const nomProd   = row.querySelector('.aff-nom-produit')?.value;
        const numUnite  = row.querySelector('.aff-num-unite')?.value;
        const statut    = row.querySelector('.aff-statut-sel')?.value || 'recu';
        if (idDetail) articles.push({
            id_detail: idDetail, id_tailleur: tailleur || '',
            acompte_tailleur: acompte || 0, nom_produit: nomProd,
            numero_unite: numUnite, statut: statut
        });
    });

    if (!articles.length) { showAlertModal('Erreur', 'Aucun article trouvé.', 'danger'); return; }

    const body = new FormData();
    body.append('action',       'affecter');
    body.append('id_vente',     idVente);
    body.append('date_echeance',echeance);
    body.append('notes',        notes);
    body.append('articles',     JSON.stringify(articles));

    fetch('ajax/commandes_atelier.php', { method: 'POST', body })
        .then(r => r.json()).then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalAffectation'))?.hide();
                showAlertModal('Succès', data.message, 'success');
                loadAffectation();
            } else {
                showAlertModal('Erreur', data.message, 'danger');
            }
        });
});

document.getElementById('btnFiltrerAff')?.addEventListener('click', loadAffectation);

// ── Paiement tailleur ─────────────────────────────────────────────────────────
function ouvrirPaiementTailleur(id, nom) {
    document.getElementById('pay_id_tailleur').value  = id;
    document.getElementById('pay_nom_tailleur').textContent = nom;
    document.getElementById('payCommandesSection').style.display = 'none';
    document.getElementById('payAucuneCommande').style.display   = 'none';
    document.getElementById('btnConfirmerPaiement').style.display = 'none';
    document.getElementById('payNotes').value = '';
    new bootstrap.Modal(document.getElementById('modalPaiement')).show();
}

document.getElementById('btnApercu')?.addEventListener('click', function() {
    const id      = document.getElementById('pay_id_tailleur').value;
    const debut   = document.getElementById('payDateDebut').value;
    const fin     = document.getElementById('payDateFin').value;
    const tbody   = document.getElementById('payCommandesTbody');
    document.getElementById('payCommandesSection').style.display = 'none';
    document.getElementById('payAucuneCommande').style.display   = 'none';
    document.getElementById('btnConfirmerPaiement').style.display = 'none';

    fetch(`ajax/tailleurs.php?action=get_commandes_paiement&id_tailleur=${id}&date_debut=${debut}&date_fin=${fin}`)
        .then(r => r.json()).then(data => {
            if (!data.success) { showAlertModal('Erreur', data.message, 'danger'); return; }
            if (!data.commandes || data.commandes.length === 0) {
                document.getElementById('payAucuneCommande').style.display = 'block';
                return;
            }
            tbody.innerHTML = data.commandes.map(c => `<tr>
                <td><span class="badge bg-blue-lt">${e(c.reference)}</span></td>
                <td>${c.numero_facture ? e(c.numero_facture) : '—'}</td>
                <td>${c.type_confection ? e(c.type_confection) : '<span class="text-muted">—</span>'}</td>
                <td>${c.nom_client ? e(c.nom_client) : '<span class="text-muted">Comptoir</span>'}</td>
                <td>${fmtDate(c.date_commande)}</td>
                <td class="text-end fw-semibold text-success">${fmt(c.acompte_tailleur)}</td>
            </tr>`).join('');
            document.getElementById('payTotal').textContent = fmt(data.total);
            document.getElementById('payCommandesSection').style.display = 'block';
            document.getElementById('btnConfirmerPaiement').style.display = 'inline-flex';
        });
});

document.getElementById('btnConfirmerPaiement')?.addEventListener('click', function() {
    const id    = document.getElementById('pay_id_tailleur').value;
    const debut = document.getElementById('payDateDebut').value;
    const fin   = document.getElementById('payDateFin').value;
    const notes = document.getElementById('payNotes').value;

    showConfirmModal({
        title: 'Confirmer le paiement',
        message: 'Cette action va enregistrer le paiement comme dépense en caisse et marquer les commandes comme payées. Continuer ?',
        type: 'warning',
        confirmText: 'Oui, payer',
        onConfirm: () => {
            const body = new FormData();
            body.append('action',       'payer');
            body.append('id_tailleur',  id);
            body.append('date_debut',   debut);
            body.append('date_fin',     fin);
            body.append('notes',        notes);

            fetch('ajax/tailleurs.php', { method: 'POST', body })
                .then(r => r.json()).then(data => {
                    if (!data.success) { showAlertModal('Erreur', data.message, 'danger'); return; }
                    bootstrap.Modal.getInstance(document.getElementById('modalPaiement'))?.hide();
                    loadTailleurs();
                    showAlertModal('Paiement enregistré', 'Commission de ' + fmt(data.montant) + ' enregistrée en dépense avec succès.', 'success');
                    window.open('imprimer_paiement_tailleur.php?id=' + data.id_paiement, '_blank');
                });
        }
    });
});

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (TAB === 'commandes')   { loadStats(); loadCommandes(); }
    if (TAB === 'affectation') { loadAffectation(); }
    if (TAB === 'tailleurs')   { loadTailleurs(); }
});
</script>
