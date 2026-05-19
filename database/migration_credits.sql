-- ============================================================
-- MIGRATION : Gestion des ventes à crédit et suivi des paiements
-- À exécuter une seule fois sur la base de données
-- ============================================================

-- 1. Ajouter la colonne date_echeance dans la table ventes
ALTER TABLE ventes
    ADD COLUMN IF NOT EXISTS date_echeance DATE NULL AFTER mode_paiement;

-- 2. Créer la table de suivi des paiements crédit
CREATE TABLE IF NOT EXISTS paiements_credit (
    id_paiement     INT            AUTO_INCREMENT PRIMARY KEY,
    id_vente        INT            NOT NULL,
    montant         DECIMAL(15,2)  NOT NULL,
    mode_paiement   ENUM('especes','carte','mobile_money','cheque') NOT NULL DEFAULT 'especes',
    notes           VARCHAR(500)   NOT NULL DEFAULT '',
    id_utilisateur  INT            NOT NULL,
    date_paiement   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pc_vente       FOREIGN KEY (id_vente)       REFERENCES ventes(id_vente)          ON DELETE CASCADE,
    CONSTRAINT fk_pc_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Index pour les performances
CREATE INDEX IF NOT EXISTS idx_pc_id_vente ON paiements_credit(id_vente);
CREATE INDEX IF NOT EXISTS idx_ventes_mode_paiement ON ventes(mode_paiement);
CREATE INDEX IF NOT EXISTS idx_ventes_date_echeance ON ventes(date_echeance);
