-- Migration 006 : récapitulatif journalier par email
ALTER TABLE users ADD COLUMN daily_digest TINYINT(1) NOT NULL DEFAULT 0;
