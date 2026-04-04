-- Migration 004: Ajout de la colonne is_casse aux éléments d'inventaire
ALTER TABLE inventory_items ADD COLUMN is_casse TINYINT(1) NOT NULL DEFAULT 0;
