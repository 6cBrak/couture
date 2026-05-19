<?php
/**
 * Script de configuration directe — à supprimer après utilisation
 */
require_once __DIR__ . '/config/database.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_boutique = trim($_POST['nom_boutique'] ?? 'Ma Boutique');
    $admin_nom    = trim($_POST['admin_nom'] ?? '');
    $admin_login  = trim($_POST['admin_login'] ?? '');
    $admin_pass   = $_POST['admin_pass'] ?? '';

    if (!$admin_nom || !$admin_login || strlen($admin_pass) < 6) {
        $message = 'Tous les champs sont obligatoires (mot de passe min. 6 caractères).';
    } else {
        try {
            // Marquer comme configuré
            db_execute("UPDATE configuration SET nom_boutique=?, est_configure=1 WHERE id_config=1", [$nom_boutique]);

            // Supprimer les anciens admins par défaut si existants
            db_execute("DELETE FROM utilisateurs WHERE login='admin' AND mot_de_passe=''");

            // Vérifier si le login existe déjà
            $exists = db_count('utilisateurs', 'login = ?', [$admin_login]);
            if ($exists) {
                // Mettre à jour le mot de passe si l'utilisateur existe
                db_update('utilisateurs',
                    ['mot_de_passe' => password_hash($admin_pass, PASSWORD_DEFAULT), 'nom_complet' => $admin_nom, 'niveau_acces' => 1, 'est_actif' => 1],
                    'login = ?', [$admin_login]
                );
            } else {
                db_insert('utilisateurs', [
                    'nom_complet'  => $admin_nom,
                    'login'        => $admin_login,
                    'mot_de_passe' => password_hash($admin_pass, PASSWORD_DEFAULT),
                    'niveau_acces' => 1,
                    'est_actif'    => 1,
                    'date_creation' => date('Y-m-d H:i:s'),
                ]);
            }

            $success = true;
        } catch (Exception $e) {
            $message = 'Erreur : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Configuration directe</title>
    <style>
        body { font-family: sans-serif; max-width: 480px; margin: 60px auto; padding: 20px; }
        input { display:block; width:100%; padding:10px; margin:8px 0 16px; box-sizing:border-box; font-size:15px; border:1px solid #ccc; border-radius:4px; }
        button { background:#206bc4; color:#fff; border:none; padding:12px 24px; font-size:16px; border-radius:4px; cursor:pointer; width:100%; }
        .error { background:#f8d7da; color:#721c24; padding:12px; border-radius:4px; margin-bottom:16px; }
        .success { background:#d1e7dd; color:#0a3622; padding:20px; border-radius:4px; text-align:center; }
        .success a { display:inline-block; margin-top:12px; padding:10px 24px; background:#0a3622; color:#fff; text-decoration:none; border-radius:4px; }
    </style>
</head>
<body>
<?php if ($success): ?>
    <div class="success">
        <h2>Configuration réussie !</h2>
        <p>L'administrateur a été créé. <strong>Supprime ce fichier après connexion.</strong></p>
        <a href="login.php">Se connecter</a>
    </div>
<?php else: ?>
    <h2>Configuration directe</h2>
    <?php if ($message): ?><div class="error"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <form method="POST">
        <label>Nom de la boutique</label>
        <input name="nom_boutique" value="Ma Boutique" required>
        <label>Nom complet de l'admin</label>
        <input name="admin_nom" required>
        <label>Login admin</label>
        <input name="admin_login" required>
        <label>Mot de passe (min. 6 caractères)</label>
        <input name="admin_pass" type="password" required minlength="6">
        <button type="submit">Configurer et créer l'admin</button>
    </form>
<?php endif; ?>
</body>
</html>
