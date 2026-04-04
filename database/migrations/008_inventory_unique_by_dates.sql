-- Migration 008 : autoriser le même produit à apparaître plusieurs fois dans l'inventaire
-- tant que la combinaison (product_id, location_id, stock_date, expiry_date) est unique.

-- 1. Ajouter d'abord le nouvel index (couvre product_id en première position,
--    ce qui satisfait la contrainte de foreign key existante sur product_id)
ALTER TABLE inventory_items
    ADD CONSTRAINT unique_product_location_dates
    UNIQUE (product_id, location_id, stock_date, expiry_date);

-- 2. Supprimer l'ancien index seulement maintenant que le nouveau est en place
ALTER TABLE inventory_items DROP INDEX unique_product_location;
