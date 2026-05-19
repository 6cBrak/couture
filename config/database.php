<?php
/**
 * ============================================================================
 * FICHIER DE CONNEXION À LA BASE DE DONNÉES
 * ============================================================================
 * 
 * Importance : Ce fichier établit la connexion sécurisée à la base de données
 *              en utilisant PDO (PHP Data Objects) pour une meilleure sécurité
 * 
 * Avantages de PDO :
 * - Requêtes préparées (protection contre les injections SQL)
 * - Gestion des erreurs améliorée
 * - Support de plusieurs types de bases de données
 * - Meilleures performances
 * 
 * Ce fichier doit être inclus dans chaque page nécessitant un accès à la BDD
 * ============================================================================
 */

// Inclusion du fichier de configuration
require_once __DIR__ . '/config.php';

try {
    // ========================================================================
    // CRÉATION DE LA CONNEXION PDO
    // ========================================================================
    
    // Construction de la chaîne DSN (Data Source Name)
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    // Options de configuration PDO pour améliorer la sécurité et les performances
    $options = [
        // Mode d'erreur : exceptions (permet de capturer les erreurs facilement)
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        
        // Mode de récupération par défaut : tableau associatif
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        
        // Désactive l'émulation des requêtes préparées (plus sécurisé)
        PDO::ATTR_EMULATE_PREPARES => false,
        
        // Utilise des connexions persistantes pour de meilleures performances
        PDO::ATTR_PERSISTENT => false,
        
        // Encodage UTF-8 pour supporter tous les caractères
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];
    
    // Création de l'instance PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // ========================================================================
    // VÉRIFICATION SI LE SYSTÈME EST CONFIGURÉ
    // ========================================================================
    
    /**
     * Fonction pour vérifier si le système a été configuré
     * (si la table configuration contient des données valides)
     */
    function is_system_configured() {
        global $pdo;
        try {
            // Vérifier qu'il n'y a qu'une seule ligne de configuration
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM configuration");
            $count_result = $stmt->fetch();
            
            // S'il y a plus d'une ligne, supprimer les lignes en trop
            if ($count_result['count'] > 1) {
                $pdo->exec("DELETE FROM configuration WHERE id_config != 1");
            }
            
            // Vérifier si la configuration existe et est complète
            $stmt = $pdo->query("SELECT est_configure, nom_boutique, adresse, telephone FROM configuration WHERE id_config = 1");
            $result = $stmt->fetch();
            
            // Si pas de résultat, le système n'est pas configuré
            if (!$result) {
                return false;
            }
            
            // Le système est configuré si:
            // 1. est_configure = 1 OU
            // 2. Les champs obligatoires sont tous remplis
            $hasRequiredFields = !empty($result['nom_boutique']) 
                && !empty($result['adresse']) 
                && !empty($result['telephone']);
            
            $isMarkedConfigured = isset($result['est_configure']) && $result['est_configure'] == 1;
            
            // Si les champs sont remplis mais est_configure n'est pas à 1, le mettre à jour
            if ($hasRequiredFields && !$isMarkedConfigured) {
                $pdo->exec("UPDATE configuration SET est_configure = 1 WHERE id_config = 1");
                return true;
            }
            
            // Retourner true si est_configure=1 OU si tous les champs requis sont remplis
            return $isMarkedConfigured || $hasRequiredFields;
                
        } catch (PDOException $e) {
            error_log("Erreur is_system_configured: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Fonction pour obtenir la configuration du système
     * @return array|null Configuration ou null si non configuré
     */
    function get_system_config() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT * FROM configuration WHERE id_config = 1");
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur get_system_config: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Fonction pour exécuter une requête préparée simple
     * @param string $sql Requête SQL avec des placeholders (?)
     * @param array $params Paramètres à binder
     * @return PDOStatement
     */
    function db_query($sql, $params = []) {
        global $pdo;
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erreur SQL: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Fonction pour exécuter une requête sans retour de résultat
     * @param string $sql Requête SQL avec des placeholders (?)
     * @param array $params Paramètres à binder
     * @return int Nombre de lignes affectées
     */
    function db_execute($sql, $params = []) {
        global $pdo;
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Erreur SQL: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Fonction pour récupérer une seule ligne
     * @param string $sql Requête SQL
     * @param array $params Paramètres
     * @return array|false
     */
    function db_fetch_one($sql, $params = []) {
        $stmt = db_query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fonction pour récupérer toutes les lignes
     * @param string $sql Requête SQL
     * @param array $params Paramètres
     * @return array
     */
    function db_fetch_all($sql, $params = []) {
        $stmt = db_query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fonction pour compter le nombre de lignes
     * @param string $table Nom de la table
     * @param string $where Condition WHERE (optionnel)
     * @param array $params Paramètres pour la condition
     * @return int
     */
    function db_count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as total FROM $table";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $result = db_fetch_one($sql, $params);
        return (int)$result['total'];
    }
    
    /**
     * Fonction pour insérer des données
     * @param string $table Nom de la table
     * @param array $data Données à insérer (clé => valeur)
     * @return int ID de la ligne insérée
     */
    function db_insert($table, $data) {
        global $pdo;
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        
        $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        db_query($sql, array_values($data));
        
        return $pdo->lastInsertId();
    }
    
    /**
     * Fonction pour mettre à jour des données
     * @param string $table Nom de la table
     * @param array $data Données à mettre à jour
     * @param string $where Condition WHERE
     * @param array $whereParams Paramètres pour WHERE
     * @return int Nombre de lignes affectées
     */
    function db_update($table, $data, $where, $whereParams = []) {
        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "$key = ?";
        }
        $setClause = implode(', ', $sets);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        $params = array_merge(array_values($data), $whereParams);
        
        $stmt = db_query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Fonction pour supprimer des données
     * @param string $table Nom de la table
     * @param string $where Condition WHERE
     * @param array $params Paramètres
     * @return int Nombre de lignes supprimées
     */
    function db_delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = db_query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Fonction pour commencer une transaction
     */
    function db_begin_transaction() {
        global $pdo;
        $pdo->beginTransaction();
    }
    
    /**
     * Fonction pour valider une transaction
     */
    function db_commit() {
        global $pdo;
        $pdo->commit();
    }
    
    /**
     * Fonction pour annuler une transaction
     */
    function db_rollback() {
        global $pdo;
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
    
    // ========================================================================
    // FONCTIONS SPÉCIFIQUES À L'APPLICATION
    // ========================================================================
    
    /**
     * Récupère le nombre de notifications non lues
     * @return int
     */
    function get_notifications_count() {
        return db_count('notifications', 'est_lue = 0');
    }
    
    /**
     * Récupère le nombre de produits en alerte stock
     * @return int
     */
    function get_products_alert_count() {
        return db_count('vue_produits_alertes');
    }
    
    /**
     * Vérifie si un utilisateur existe avec ce login
     * @param string $login
     * @return bool
     */
    function user_exists($login) {
        $count = db_count('utilisateurs', 'login = ? AND est_actif = 1', [$login]);
        return $count > 0;
    }
    
    /**
     * Récupère un utilisateur par son login
     * @param string $login
     * @return array|false
     */
    function get_user_by_login($login) {
        // Permettre la connexion par login ou email
        return db_fetch_one(
            "SELECT * FROM utilisateurs WHERE (login = ? OR email = ?) AND est_actif = 1 LIMIT 1",
            [$login, $login]
        );
    }
    
    /**
     * Récupère un utilisateur par son ID
     * @param int $id
     * @return array|false
     */
    function get_user_by_id($id) {
        return db_fetch_one(
            "SELECT * FROM utilisateurs WHERE id_utilisateur = ?",
            [$id]
        );
    }
    
    /**
     * Met à jour la date de dernière connexion
     * @param int $userId
     */
    function update_last_login($userId) {
        db_update(
            'utilisateurs',
            ['date_derniere_connexion' => date('Y-m-d H:i:s')],
            'id_utilisateur = ?',
            [$userId]
        );
    }
    
    /**
     * Vérifie si une transaction est en cours
     * @return bool
     */
    function db_in_transaction() {
        global $pdo;
        return $pdo->inTransaction();
    }
    
    // NOTE: Les fonctions utilitaires (is_logged_in, get_user_id, e, format_montant, format_date,
    // generate_csrf_token, verify_csrf_token, set_flash_message, get_flash_message, redirect, die_error)
    // sont définies dans config/config.php pour éviter les redéclarations.
    
} catch (PDOException $e) {
    // ========================================================================
    // GESTION DES ERREURS DE CONNEXION
    // ========================================================================
    
    // Message d'erreur générique pour l'utilisateur (ne pas exposer les détails)
    $error_message = "Impossible de se connecter à la base de données.";
    
    // Log l'erreur détaillée pour le développeur
    error_log("Erreur de connexion PDO: " . $e->getMessage());
    
    // En mode développement, afficher les détails
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        die_error($error_message . "<br><br><strong>Détails techniques :</strong><br>" . $e->getMessage());
    } else {
        die_error($error_message . "<br>Veuillez contacter l'administrateur système.");
    }
}

// ============================================================================
// FIN DU FICHIER DE CONNEXION
// ============================================================================
