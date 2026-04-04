-- Migration 007 : déplacement des dates de stock/expiration de products vers inventory_items

-- 1. Ajouter les colonnes dans inventory_items
ALTER TABLE inventory_items
    ADD COLUMN stock_date  DATE DEFAULT NULL COMMENT 'Date de mise en stock',
    ADD COLUMN expiry_date DATE DEFAULT NULL COMMENT 'Date limite de consommation';

-- 2. Migrer les données existantes (copie depuis products)
UPDATE inventory_items ii
INNER JOIN products p ON p.id = ii.product_id
SET ii.stock_date  = p.stock_date,
    ii.expiry_date = p.expiry_date;

-- 3. Supprimer les colonnes dans products
ALTER TABLE products
    DROP COLUMN IF EXISTS stock_date,
    DROP COLUMN IF EXISTS expiry_date;
