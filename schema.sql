CREATE DATABASE IF NOT EXISTS born_here
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE born_here;

-- Caches public data only (Wikipedia, Wikidata, weather, charts).
-- Personal details like names and birthdays are never stored here.
CREATE TABLE IF NOT EXISTS cache_entries (
    cache_key VARCHAR(255) NOT NULL PRIMARY KEY,
    payload MEDIUMTEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;
