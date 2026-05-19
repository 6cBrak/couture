-- ============================================================================
-- MIGRATION : LIAISON CAISSIER ↔ DÉPÔT
-- Ajouter la colonne id_depot dans la table utilisateurs
-- Exécuter ce script une seule fois dans phpMyAdmin ou MySQL Workbench
-- ============================================================================

ALTER TABLE `utilisateurs`
    ADD COLUMN `id_depot` INT NULL COMMENT 'Dépôt assigné (obligatoire pour les caissiers/vendeurs)'
        AFTER `niveau_acces`,
    ADD CONSTRAINT `fk_user_depot`
        FOREIGN KEY (`id_depot`) REFERENCES `depots`(`id_depot`)
        ON DELETE SET NULL ON UPDATE CASCADE;

CREATE INDEX IF NOT EXISTS `idx_user_depot` ON `utilisateurs`(`id_depot`);
