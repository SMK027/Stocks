-- Migration 008 : autoriser le même produit à apparaître plusieurs fois dans l'inventaire
-- tant que la combinaison (product_id, location_id, stock_date, expiry_date) est unique.

-- Supprimer l'ancienne contrainte unique sur (product_id, location_id)
ALTER TABLE inventory_items DROP INDEX unique_product_location;

-- Ajouter une nouvelle contrainte unique sur (product_id, location_id, stock_date, expiry_date)
-- Remarque : sous MySQL, deux lignes avec expiry_date = NULL ne violent pas cette contrainte
-- (NULL ≠ NULL dans les index UNIQUE). La logique PHP (upsert) gère ce cas via IS NULL.
ALTER TABLE inventory_items
    ADD CONSTRAINT unique_product_location_dates
    UNIQUE (product_id, location_id, stock_date, expiry_date);
