# Guide IA - STORESUITE

## Vue d'ensemble
Système de gestion de commerce de détail basé sur PHP fonctionnant sur XAMPP (Windows). Interface en français avec gestion des stocks, ventes, facturation et utilisateurs. Architecture monolithique avec endpoints AJAX. Base de données : MySQL (storesuite).

## Architecture critique

### Structure des fichiers
- **Fichiers PHP racine** : Pages de fonctionnalités (vente.php, facture.php, listes.php, etc.)
- **ajax/** : Endpoints backend pour opérations CRUD
- **config/** : Système central (database.php, config.php)
- **protection_pages.php** : Middleware d'authentification - à inclure en haut de chaque page protégée
- **header.php/footer.php** : Composants de mise en page partagés

### Couche base de données
**PDO global** via `config/database.php` :
- `$pdo` : Instance PDO (déjà initialisée)
- Fonctions helper : `db_query()`, `db_fetch_one()`, `db_fetch_all()`, `db_insert()`, `db_update()`, `db_delete()`
- Transactions : `db_begin_transaction()`, `db_commit()`, `db_rollback()`
- Toujours utiliser les requêtes préparées via les helpers

**Tables clés** :
- `utilisateurs` : `nom_complet` (PAS nom_utilisateur), `password_hash` (PAS password), `niveau_acces`
- `ventes` : inclut `montant_ht`, `montant_tva` (TVA 16%), `montant_total`
- `ventes_details` : lignes de vente (alias `details_vente` dans les vues)
- `produits`, `clients`, `categories`, `mouvements_stock`

### Flux d'authentification
```php
// Chaque page protégée commence par :
require_once('protection_pages.php');

// Cela définit les variables globales :
$user_id, $user_name, $user_niveau, $is_admin
$nom_boutique, $logo_boutique, $devise, $couleur_primaire, $couleur_secondaire
```
- Timeout de session : 2 heures (`SESSION_LIFETIME`)
- Vérification admin : `is_admin()` ou variable globale `$is_admin`
- Imposer permissions : fonction `require_admin()`

### Modèle de structure de page
```php
<?php
require_once('protection_pages.php');
$page_title = 'Titre de la page'; // Utilisé par header.php
require_once('header.php');
?>
<!-- Contenu de la page -->
<?php require_once('footer.php'); ?>
```

## Conventions de développement

### Système de modals
**NE JAMAIS utiliser `alert()` ou `confirm()` natifs**. Utiliser les modals modernes de `assets/js/modals.js` :
```javascript
// Confirmation
showConfirmModal({
    title: 'Confirmer?',
    message: 'Action irréversible.',
    onConfirm: () => { /* action */ }
});

// Alerte
showAlertModal({
    title: 'Succès',
    message: 'Opération réussie',
    type: 'success' // success, error, warning, info
});
```

### Modèle d'endpoint AJAX
```php
<?php
require_once __DIR__ . '/../protection_pages.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];
try {
    // Validation
    if (empty($_POST['field'])) throw new Exception('Champ requis');
    
    // Logique métier avec transactions
    db_begin_transaction();
    // ... opérations db ...
    db_commit();
    
    $response = ['success' => true, 'message' => 'Succès'];
} catch (Exception $e) {
    if (db_in_transaction()) db_rollback();
    $response['message'] = $e->getMessage();
}
echo json_encode($response);
```

### Modèle AJAX Frontend
```javascript
fetch('ajax/endpoint.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({field: value})
})
.then(r => r.json())
.then(data => {
    if (data.success) {
        showAlertModal({title: 'Succès', message: data.message, type: 'success'});
    } else {
        showAlertModal({title: 'Erreur', message: data.message, type: 'error'});
    }
});
```

### Fonctions utilitaires
- `e($string)` : Échappement HTML anti-XSS - **à utiliser pour TOUTES les sorties**
- `redirect($url)` : Redirection header avec exit
- `format_montant($amount, $devise)` : Formatage monétaire
- `set_flash_message($msg, $type)` : Messages post-redirection
- `log_activity($type, $description, $data)` : Journalisation d'audit

### Calcul TVA (taxe)
TVA de 16% standard. Toujours calculer :
```php
$montant_ht = $total_price;
$montant_tva = round($montant_ht * 0.16, 2);
$montant_total = $montant_ht + $montant_tva;
```

## Tâches courantes

### Ajouter une nouvelle page de fonctionnalité
1. Créer `feature.php` à la racine
2. Commencer par `require_once('protection_pages.php');`
3. Définir `$page_title` avant `require_once('header.php');`
4. Utiliser les classes Bootstrap 5 (déjà chargées)
5. Inclure `require_once('footer.php');` à la fin

### Créer un endpoint AJAX
1. Créer dans le répertoire `ajax/`
2. Toujours inclure `../protection_pages.php`
3. Retourner JSON avec clés `success` et `message`
4. Utiliser transactions pour opérations multi-tables
5. Appels frontend via API `fetch()`

### Modifications du schéma de base de données
1. Créer migration SQL dans dossier `database/`
2. Documenter dans fichiers markdown (suivre modèle dans `CORRECTIONS_APPLIQUEES.md`)
3. Utiliser clauses `IF NOT EXISTS` pour sécurité
4. Tester avec données d'exemple avant déploiement

### Problèmes de noms de colonnes (bug historique)
- Table `utilisateurs` utilise `nom_complet` (PAS `nom_utilisateur`)
- Table `utilisateurs` utilise `password_hash` (PAS `password`)
- Toujours vérifier les noms de colonnes avant d'écrire des requêtes

## Configuration de l'environnement

**Stack XAMPP** :
- URL : `http://localhost/STORESuite/`
- Base de données : `http://localhost/phpmyadmin` (storesuite)
- Fuseau horaire : `Africa/Lubumbashi`
- Authentification basée sur session PHP

**Fichiers clés à lire en premier** :
- [config/database.php](config/database.php) : Tous les helpers DB
- [protection_pages.php](protection_pages.php) : Auth + variables globales
- [assets/js/modals.js](assets/js/modals.js) : API des modals
- Docs de session récentes : `SESSION_TRAVAIL.md`, `ETAT_PROJET_9_JAN_2026.md`

## Workflow de test
1. Vérifier que le schéma de base de données correspond au code (vérifier noms de colonnes)
2. Tester endpoints AJAX avec journalisation console
3. Utiliser l'onglet Network des DevTools du navigateur pour débogage
4. Consulter `SESSION_TRAVAIL.md` pour problèmes connus
5. Exécuter migrations SQL via l'onglet SQL de phpMyAdmin

## Particularités du projet
- Langue française partout (noms de variables, UI, messages)
- Pas d'ORM - PDO brut avec fonctions helper
- Système de modals construit from scratch (pas de modals Bootstrap)
- Connexion base de données unique partagée globalement (`$pdo`)
- Pages de fonctionnalités à la racine, pas dans sous-répertoires
- Workflow des ventes : `vente_professionnel.php` → `ajax/valider_vente.php` → `facture_impression.php`
