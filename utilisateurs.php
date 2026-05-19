<?php
/**
 * PAGE GESTION DES UTILISATEURS - STORE SUITE
 * Admin uniquement
 */
require_once 'protection_pages.php';
require_admin();

$page_title = 'Gestion des Utilisateurs';

$utilisateurs = db_fetch_all('
    SELECT u.*, d.nom_depot
    FROM utilisateurs u
    LEFT JOIN depots d ON u.id_depot = d.id_depot
    ORDER BY u.date_creation DESC
');
$depots_liste = db_fetch_all('SELECT id_depot, nom_depot FROM depots WHERE est_actif = 1 ORDER BY est_principal DESC, nom_depot ASC');

include 'header.php';
?>

<style>
.user-avatar {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>);
    color: white;
    font-weight: 600;
    font-size: 1rem;
}

.status-badge {
    padding: 0.35rem 0.65rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}
</style>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/>
                    </svg>
                    Gestion des Utilisateurs
                </h2>
                <div class="text-muted mt-1">Gérez les accès et les droits des utilisateurs</div>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddUser">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Nouvel utilisateur
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Liste des utilisateurs (<?php echo count($utilisateurs); ?>)</h3>
                    <div class="col-auto ms-auto">
                        <input type="text" class="form-control" id="searchUser" placeholder="Rechercher...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Identifiant</th>
                                <th>Dépôt</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Date création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersList">
                            <?php foreach ($utilisateurs as $user): ?>
                            <tr data-user-id="<?php echo $user['id_utilisateur']; ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <?php echo strtoupper(substr($user['nom_complet'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo e($user['nom_complet']); ?></div>
                                            <?php if (!empty($user['email'])): ?>
                                            <div class="text-muted small"><?php echo e($user['email']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code class="text-dark bg-light px-2 py-1 rounded"><?php echo e($user['login']); ?></code>
                                </td>
                                <td>
                                    <?php if (!empty($user['nom_depot'])): ?>
                                    <span class="badge bg-secondary-lt text-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path d="M5 21v-14l8 -4v18"/><path d="M19 21v-10l-6 -4"/><path d="M9 9l0 .01"/><path d="M9 12l0 .01"/><path d="M9 15l0 .01"/><path d="M9 18l0 .01"/></svg>
                                        <?php echo e($user['nom_depot']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['niveau_acces'] == 1): ?>
                                    <span class="badge" style="background: linear-gradient(135deg, <?php echo $couleur_primaire; ?>, <?php echo $couleur_secondaire; ?>); color: white;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/>
                                        </svg>
                                        Administrateur
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-success text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                        </svg>
                                        Vendeur
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['est_actif'] == 1): ?>
                                    <span class="status-badge bg-success-lt text-success">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2l4 -4"/></svg>
                                        Actif
                                    </span>
                                    <?php else: ?>
                                    <span class="status-badge bg-danger-lt text-danger">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        Inactif
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></div>
                                    <div class="text-muted small"><?php echo date('H:i', strtotime($user['date_creation'])); ?></div>
                                </td>
                                <td style="white-space:nowrap;">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-icon btn-ghost-primary" onclick="editUser(<?php echo $user['id_utilisateur']; ?>)" data-bs-toggle="tooltip" title="Modifier">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/>
                                                <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/>
                                                <path d="M16 5l3 3"/>
                                            </svg>
                                        </button>
                                        <?php if ($user['est_actif'] == 1): ?>
                                        <button class="btn btn-icon btn-ghost-warning" onclick="toggleUserStatus(<?php echo $user['id_utilisateur']; ?>, 0)" data-bs-toggle="tooltip" title="Désactiver">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <line x1="3" y1="3" x2="21" y2="21"/>
                                                <path d="M10.584 10.587a2 2 0 0 0 2.828 2.83"/>
                                                <path d="M9.363 5.365a9.466 9.466 0 0 1 2.637 -.365c4 0 7.333 2.333 10 7c-.778 1.361 -1.612 2.524 -2.503 3.488m-2.14 1.861c-1.631 1.1 -3.415 1.651 -5.357 1.651c-4 0 -7.333 -2.333 -10 -7c1.369 -2.395 2.913 -4.175 4.632 -5.341"/>
                                            </svg>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-icon btn-ghost-success" onclick="toggleUserStatus(<?php echo $user['id_utilisateur']; ?>, 1)" data-bs-toggle="tooltip" title="Activer">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <circle cx="12" cy="12" r="2"/>
                                                <path d="M22 12c-2.667 4.667 -6 7 -10 7s-7.333 -2.333 -10 -7c2.667 -4.667 6 -7 10 -7s7.333 2.333 10 7"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($user['id_utilisateur'] != $_SESSION['id_utilisateur']): ?>
                                        <button class="btn btn-icon btn-ghost-danger" onclick="deleteUser(<?php echo $user['id_utilisateur']; ?>)" data-bs-toggle="tooltip" title="Supprimer">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <line x1="4" y1="7" x2="20" y2="7"/>
                                                <line x1="10" y1="11" x2="10" y2="17"/>
                                                <line x1="14" y1="11" x2="14" y2="17"/>
                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                                            </svg>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-icon" disabled data-bs-toggle="tooltip" title="Vous ne pouvez pas vous supprimer">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon text-muted" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <rect x="5" y="11" width="14" height="10" rx="2"/>
                                                <circle cx="12" cy="16" r="1"/>
                                                <path d="M8 11v-4a4 4 0 0 1 8 0v4"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajout/Modification Utilisateur -->
<div class="modal fade" id="modalAddUser" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                        <line x1="19" y1="7" x2="19" y2="10"/>
                        <line x1="19" y1="14" x2="19" y2="14.01"/>
                    </svg>
                    <span id="modalTitleText">Nouvel utilisateur</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUser" onsubmit="saveUser(event)">
                <div class="modal-body">
                    <input type="hidden" id="userId" name="id_utilisateur">
                    <input type="hidden" id="isEdit" name="is_edit" value="0">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <span class="text-danger">*</span> Nom complet
                                <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Nom affiché dans l'interface et dans les logs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                </span>
                            </label>
                            <input type="text" class="form-control" id="userNom" name="nom_complet" required placeholder="Ex: Jean Dupont">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Email (optionnel)
                                <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Adresse email de l'utilisateur">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                </span>
                            </label>
                            <input type="email" class="form-control" id="userEmail" name="email" placeholder="utilisateur@email.com">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <span class="text-danger">*</span> Identifiant de connexion
                                <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Nom d'utilisateur pour se connecter au système">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                </span>
                            </label>
                            <input type="text" class="form-control" id="userLogin" name="login" required placeholder="Ex: jdupont">
                            <small class="form-hint">Sans espaces, lettres et chiffres uniquement</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <span class="text-danger" id="passwordRequired">*</span> Mot de passe
                                <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Minimum 6 caractères. Laissez vide pour ne pas changer lors de la modification">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                </span>
                            </label>
                            <input type="password" class="form-control" id="userPassword" name="mot_de_passe" minlength="6" placeholder="Minimum 6 caractères">
                            <small class="form-hint" id="passwordHint">Laissez vide pour conserver l'actuel</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <span class="text-danger">*</span> Rôle
                            <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Administrateur : accès complet. Vendeur : accès limité aux ventes et produits">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                            </span>
                        </label>
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item">
                                <input type="radio" name="est_admin" value="0" class="form-selectgroup-input" checked>
                                <span class="form-selectgroup-label">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-success" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                    </svg>
                                    <span>
                                        <strong>Vendeur</strong>
                                        <span class="d-block text-muted">Peut gérer les ventes et produits</span>
                                    </span>
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="est_admin" value="1" class="form-selectgroup-input">
                                <span class="form-selectgroup-label">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2 text-danger" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/>
                                    </svg>
                                    <span>
                                        <strong>Administrateur</strong>
                                        <span class="d-block text-muted">Accès complet au système</span>
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div id="depotSection" class="mb-3">
                        <label class="form-label fw-semibold">
                            <span class="text-danger" id="depotRequired">*</span> Dépôt assigné
                            <span class="text-muted ms-1" data-bs-toggle="tooltip" title="Obligatoire pour les caissiers/vendeurs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                            </span>
                        </label>
                        <select class="form-select" id="userDepot" name="id_depot">
                            <option value="">— Sélectionner un dépôt —</option>
                            <?php foreach ($depots_liste as $dep): ?>
                            <option value="<?php echo $dep['id_depot']; ?>"><?php echo e($dep['nom_depot']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">Le caissier sera rattaché à ce dépôt pour ses ventes et sa caisse.</small>
                    </div>

                    <div class="alert alert-info">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                        <strong>Attention :</strong> Les administrateurs ont accès à toutes les fonctionnalités, y compris la gestion des utilisateurs et des paramètres système.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l5 5l10 -10"/>
                        </svg>
                        <span id="btnSubmitText">Créer l'utilisateur</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Recherche utilisateur
document.getElementById('searchUser').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#usersList tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

// Afficher/masquer le dépôt selon le rôle sélectionné
function toggleDepotField() {
    const isAdmin = document.querySelector('input[name="est_admin"]:checked')?.value === '1';
    const depotSection = document.getElementById('depotSection');
    const depotRequired = document.getElementById('depotRequired');
    if (isAdmin) {
        depotSection.style.opacity = '0.5';
        depotRequired.style.display = 'none';
    } else {
        depotSection.style.opacity = '1';
        depotRequired.style.display = 'inline';
    }
}
document.querySelectorAll('input[name="est_admin"]').forEach(r => r.addEventListener('change', toggleDepotField));
toggleDepotField();

function saveUser(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const isEdit = document.getElementById('isEdit').value === '1';
    const isAdmin = document.querySelector('input[name="est_admin"]:checked')?.value === '1';

    if (!isEdit && !formData.get('mot_de_passe')) {
        alert('Le mot de passe est obligatoire pour un nouvel utilisateur');
        return;
    }

    if (!isAdmin && !formData.get('id_depot')) {
        alert('Le dépôt est obligatoire pour un vendeur/caissier');
        return;
    }

    fetch('ajax/utilisateurs.php', { method: 'POST', body: formData })
        .then(async response => {
            try {
                return await response.json();
            } catch (err) {
                return { success: false, message: 'Réponse serveur invalide' };
            }
        })
        .then(data => {
            const modalElement = document.getElementById('modalAddUser');
            const modalInstance = bootstrap.Modal.getInstance(modalElement);

            const showResult = () => {
                if (data.success) {
                    alert(data.message || 'Opération réussie');
                    location.reload();
                } else {
                    alert(data.message || 'Une erreur est survenue');
                }
            };

            if (modalInstance && modalElement.classList.contains('show')) {
                modalElement.addEventListener('hidden.bs.modal', () => {
                    setTimeout(showResult, 50);
                }, { once: true });
                modalInstance.hide();
            } else {
                showResult();
            }
        })
        .catch(error => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalAddUser'));
            if (modal) modal.hide();

            alert('Erreur lors de l\'enregistrement: ' + error);
        });
}

function editUser(id) {
    fetch('ajax/utilisateurs.php?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (!data.success) return;

            const user = data.user;
            document.getElementById('userId').value = user.id_utilisateur;
            document.getElementById('isEdit').value = '1';
            document.getElementById('userNom').value = user.nom_complet;
            document.getElementById('userEmail').value = user.email || '';
            document.getElementById('userLogin').value = user.login;
            document.getElementById('userPassword').value = '';
            document.getElementById('userPassword').removeAttribute('required');

            const isAdminValue = (user.niveau_acces == 1) ? '1' : '0';
            document.querySelector('input[name="est_admin"][value="' + isAdminValue + '"]').checked = true;
            document.getElementById('userDepot').value = user.id_depot || '';
            toggleDepotField();

            document.getElementById('modalTitleText').textContent = 'Modifier l\'utilisateur';
            document.getElementById('btnSubmitText').textContent = 'Mettre à jour';
            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('passwordHint').style.display = 'block';

            const modal = new bootstrap.Modal(document.getElementById('modalAddUser'));
            modal.show();
        });
}

function toggleUserStatus(id, status) {
    const action = status === 1 ? 'activer' : 'désactiver';
    if (!confirm(`Voulez-vous vraiment ${action} cet utilisateur ?`)) return;

    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('id_utilisateur', id);
    formData.append('est_actif', status);

    fetch('ajax/utilisateurs.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Opération réussie');
                location.reload();
            } else {
                alert(data.message || 'Une erreur est survenue');
            }
        });
}

function deleteUser(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id_utilisateur', id);

    fetch('ajax/utilisateurs.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Opération réussie');
                location.reload();
            } else {
                alert(data.message || 'Une erreur est survenue');
            }
        });
}

document.getElementById('modalAddUser').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formUser').reset();
    document.getElementById('userId').value = '';
    document.getElementById('isEdit').value = '0';
    document.getElementById('userPassword').setAttribute('required', '');
    document.getElementById('modalTitleText').textContent = 'Nouvel utilisateur';
    document.getElementById('btnSubmitText').textContent = 'Créer l\'utilisateur';
    document.getElementById('passwordRequired').style.display = 'inline';
    document.getElementById('passwordHint').style.display = 'none';
    document.getElementById('userDepot').value = '';
    toggleDepotField();
});

document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'footer.php'; ?>
