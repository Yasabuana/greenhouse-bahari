#!/usr/bin/env bash
#
# Setup database MariaDB untuk Sistem Monitoring Multi-Greenhouse.
# Diuji pada Raspberry Pi OS (Debian 12) + MariaDB 10.11/11.x.
#
# Pemakaian:
#   sudo DB_PASSWORD='password_kuat' bash deploy/setup-mariadb.sh
#
set -euo pipefail

DB_NAME="${DB_NAME:-greenhouse_bahari}"
DB_USER="${DB_USER:-greenhouse}"
DB_HOST="${DB_HOST:-localhost}"

if [[ "${EUID}" -ne 0 ]]; then
    echo "Script ini harus dijalankan dengan sudo." >&2
    exit 1
fi

if [[ -z "${DB_PASSWORD:-}" ]]; then
    read -r -s -p "Password untuk user ${DB_USER}: " DB_PASSWORD
    echo
fi

if [[ -z "${DB_PASSWORD}" ]]; then
    echo "DB_PASSWORD tidak boleh kosong." >&2
    exit 1
fi

if ! command -v mariadb >/dev/null 2>&1; then
    echo "MariaDB client tidak ditemukan. Install dulu: sudo apt install mariadb-server" >&2
    exit 1
fi

echo "==> Membuat database ${DB_NAME} dan user ${DB_USER}@${DB_HOST}"

mariadb <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASSWORD}';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
    ON \`${DB_NAME}\`.* TO '${DB_USER}'@'${DB_HOST}';

FLUSH PRIVILEGES;
SQL

echo "==> Menguji koneksi sebagai ${DB_USER}"
MYSQL_PWD="${DB_PASSWORD}" mariadb \
    --host="${DB_HOST}" \
    --user="${DB_USER}" \
    --database="${DB_NAME}" \
    --execute="SELECT DATABASE() AS database_aktif, VERSION() AS versi_mariadb;"

cat <<INFO

Database siap digunakan.

Tambahkan konfigurasi berikut ke file .env aplikasi:

DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}

Langkah selanjutnya:

    php artisan migrate --seed
    php artisan config:clear

INFO
