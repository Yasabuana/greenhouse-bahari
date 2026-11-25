-- -----------------------------------------------------------------------------
-- Sistem Monitoring Multi-Greenhouse — setup database MariaDB
-- -----------------------------------------------------------------------------
-- Jalankan sebagai root MariaDB:
--   sudo mariadb < deploy/mariadb/setup.sql
--
-- GANTI password 'ubah_password_ini' sebelum dijalankan, lalu samakan
-- dengan DB_PASSWORD pada file .env aplikasi.
-- -----------------------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `greenhouse_bahari`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'greenhouse'@'localhost' IDENTIFIED BY 'ubah_password_ini';
ALTER USER 'greenhouse'@'localhost' IDENTIFIED BY 'ubah_password_ini';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
    ON `greenhouse_bahari`.* TO 'greenhouse'@'localhost';

FLUSH PRIVILEGES;

-- Verifikasi
SELECT User, Host FROM mysql.user WHERE User = 'greenhouse';
SHOW DATABASES LIKE 'greenhouse_bahari';
