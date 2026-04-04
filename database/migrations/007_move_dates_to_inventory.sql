-- Migration 007 : déplacement des dates de stock/expiration de products vers inventory_items

-- 1. Ajouter les colonnes dans inventory_items
ALTER TABLE inventory_items
    ADD COLUMN IF NOT EXISTS stock_date  DATE DEFAULT NULL COMMENT 'Date de mise en stock',
    ADD COLUMN IF NOT EXISTS expiry_date DATE DEFAULT NULL COMMENT 'Date limite de consommation';

-- 2. Migrer les données existantes (copie depuis products) — seulement si les colonnes existent encore
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'products'
      AND COLUMN_NAME  = 'stock_date'
);
SET @migrate_sql = IF(@col_exists > 0,
    'UPDATE inventory_items ii INNER JOIN products p ON p.id = ii.product_id SET ii.stock_date = p.stock_date, ii.expiry_date = p.expiry_date',
    'SELECT 1 -- colonnes absentes de products, migration de données ignorée'
);
PREPARE _stmt FROM @migrate_sql;
EXECUTE _stmt;
DEALLOCATE PREPARE _stmt;

-- 3. Supprimer les colonnes dans products (si elles existent encore)
ALTER TABLE products
    DROP COLUMN IF EXISTS stock_date,
    DROP COLUMN IF EXISTS expiry_date;
