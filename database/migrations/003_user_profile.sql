-- Migration : ajout des champs profil utilisateur (nom, prénom)

ALTER TABLE users ADD COLUMN firstname VARCHAR(100) DEFAULT NULL AFTER username;
ALTER TABLE users ADD COLUMN lastname VARCHAR(100) DEFAULT NULL AFTER firstname;
