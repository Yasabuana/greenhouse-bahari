# Sistem Monitoring Multi-Greenhouse — Bahari Farm

Aplikasi monitoring greenhouse berbasis **Laravel 12** yang menerima data dari **5 node Arduino (LoRa)**
di **2 greenhouse**, menyimpannya ke database, lalu menyajikannya lewat dashboard real-time,
halaman detail node, serta grafik riwayat.

Target produksi: **Raspberry Pi 5 + MariaDB**, dengan bridge penerima LoRa yang mengirim data
melalui HTTP ke endpoint `/api/sensor`.

---

## 1. Fitur

| Halaman | Rute | Isi |
| --- | --- | --- |
| Dashboard | `/` | Ringkasan 2 greenhouse: 2 nilai udara (SHT31) + 8 nilai media tanam (NPK 8-in-1), jumlah node online, last seen, auto-refresh 10 detik |
| Detail Node | `/nodes` | Tab per greenhouse, kartu tiap node dengan status online/offline, 10 parameter per node, auto-refresh 15 detik |
| Riwayat & Grafik | `/history` | Filter greenhouse / sumber (rata-rata atau node) / parameter / rentang (24 jam, 7 hari, 30 hari), grafik Chart.js + tabel terpaginasi |

Endpoint JSON pendukung:

- `GET /api/dashboard` — data yang sama dengan dashboard (dipakai auto-refresh).
- `GET /api/nodes` — status & bacaan terakhir tiap node.
- `POST /api/sensor` — ingestion satu batch 10 parameter (dipakai bridge LoRa).

Aturan status: node dianggap **online** bila mengirim data dalam `online_threshold_minutes`
(default **15 menit**). Rata-rata dashboard hanya memakai batch terakhir dari node yang online,
sehingga nilai basi tidak mendistorsi kondisi terkini.

---

## 2. Topologi & Parameter

| Greenhouse | Lokasi | Node |
| --- | --- | --- |
| `GH1` | Blok A | `N1`, `N2`, `N3` |
| `GH2` | Blok B | `N1`, `N2` |

Konfigurasi terpusat di `config/greenhouse.php` (menambah greenhouse/node cukup di file ini).

| Group | Parameter | Key | Satuan | Rentang valid |
| --- | --- | --- | --- | --- |
| soil | Nitrogen | `n` | mg/kg | 0–1000 |
| soil | Fosfor | `p` | mg/kg | 0–1000 |
| soil | Kalium | `k` | mg/kg | 0–1000 |
| soil | Kelembapan Tanah | `soil_moisture` | % | 0–100 |
| soil | Suhu Tanah | `soil_temp` | °C | -10–70 |
| soil | pH Tanah | `ph` | pH | 0–14 |
| soil | EC Tanah | `ec` | uS/cm | 0–50000 |
| soil | Salinitas | `salinity` | mg/L | 0–50000 |
| air | Suhu Udara | `air_temp` | °C | -20–80 |
| air | Kelembapan Udara | `air_humidity` | % | 0–100 |

---

## 3. Struktur Kode Penting

```
app/
  Services/SensorDataService.php               # agregasi dashboard, node, riwayat
  Http/Controllers/                            # DashboardController, NodeController, HistoryController
  Http/Controllers/Api/SensorController.php    # ingestion batch -> 10 baris sensor_logs
  Http/Requests/StoreSensorReadingRequest.php  # validasi gh/node + rentang 10 parameter
  Models/SensorLog.php                         # scope forGreenhouse/forNode/forParameter/recent
config/greenhouse.php                          # topologi + metadata parameter + ambang online
database/migrations/2026_11_25_000000_create_sensor_logs_table.php
database/seeders/SensorLogSeeder.php           # 1.200 baris data contoh 24 jam
resources/views/                               # layouts/app, dashboard, nodes, history, komponen
resources/js/app.js                            # Alpine (clock, auto-refresh) + Chart.js
deploy/                                        # setup MariaDB + contoh nginx
tests/Feature/                                 # 18 test (ingestion, dashboard, node, riwayat)
```

Skema `sensor_logs` memakai model *long/narrow* (satu baris per parameter) dengan index gabungan
`(greenhouse_id, node_id, parameter, created_at)` agar query time-series tetap cepat.

---

## 4. Setup Lokal (Windows / Linux, tanpa MariaDB)

```bash
composer install
npm install
copy .env.example .env      # Linux/macOS: cp .env.example .env
php artisan key:generate
```

Untuk pengembangan cepat gunakan SQLite:

```ini
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Lalu:

```bash
php artisan migrate --seed
npm run dev          # atau: npm run build
php artisan serve
```

Buka http://127.0.0.1:8000.

> Catatan: `.env` di repo ini sudah memakai SQLite dan sudah di-seed, sehingga dashboard
> langsung menampilkan data contoh 24 jam terakhir.

---

## 5. Deployment Raspberry Pi 5 + MariaDB

### 5.1 Siapkan MariaDB

Otomatis (Debian/Raspberry Pi OS):

```bash
cd /var/www/greenhouse-bahari
sudo DB_PASSWORD='password_kuat' bash deploy/setup-mariadb.sh
```

Manual:

```bash
sudo mariadb < deploy/mariadb/setup.sql   # ubah password di dalam file dulu
```

Keduanya membuat database `greenhouse_bahari` + user `greenhouse@localhost` dengan hak
`SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES`.

### 5.2 Konfigurasi aplikasi

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=http://ip-raspberry-pi

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=greenhouse_bahari
DB_USERNAME=greenhouse
DB_PASSWORD=password_kuat
```

```bash
php artisan key:generate --force
php artisan migrate --force          # tanpa --seed di produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci && npm run build
sudo chown -R www-data:www-data storage bootstrap/cache
```

### 5.3 Nginx + PHP-FPM

Contoh konfigurasi tersedia di `deploy/nginx/greenhouse.conf`:

```bash
sudo cp deploy/nginx/greenhouse.conf /etc/nginx/sites-available/greenhouse
sudo ln -s /etc/nginx/sites-available/greenhouse /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

Untuk HTTPS di jaringan lokal, gunakan `mkcert` atau sertifikat internal, lalu tambahkan
`listen 443 ssl;` beserta path sertifikat.

---

## 6. Kontrak API Ingestion

`POST /api/sensor` — `Content-Type: application/json`, tanpa CSRF (rute stateless di `routes/api.php`).

```json
{
  "gh": "GH1",
  "node": "N1",
  "n": 45.0,
  "p": 22.0,
  "k": 38.0,
  "soil_moisture": 64.5,
  "soil_temp": 27.2,
  "ph": 6.5,
  "ec": 1280,
  "salinity": 620,
  "air_temp": 30.4,
  "air_humidity": 71.2
}
```

Respons `201 Created`:

```json
{
  "status": "ok",
  "message": "Data sensor diterima.",
  "data": {
    "greenhouse_id": "GH1",
    "node_id": "N1",
    "recorded_at": "2026-11-25T10:15:00+07:00",
    "stored": 10,
    "parameters": { "n": 45.0, "p": 22.0, "k": 38.0, "soil_moisture": 64.5, "soil_temp": 27.2, "ph": 6.5, "ec": 1280.0, "salinity": 620.0, "air_temp": 30.4, "air_humidity": 71.2 }
  }
}
```

Error validasi (`422`) memakai format standar Laravel:

```json
{ "message": "Node N3 tidak terdaftar pada greenhouse GH2.", "errors": { "node": ["..."] } }
```

Uji cepat dari terminal:

```bash
curl -X POST http://127.0.0.1:8000/api/sensor \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"gh":"GH1","node":"N1","n":45,"p":22,"k":38,"soil_moisture":64.5,"soil_temp":27.2,"ph":6.5,"ec":1280,"salinity":620,"air_temp":30.4,"air_humidity":71.2}'
```

Semua parameter dalam satu request memakai **satu timestamp** sehingga terbaca sebagai satu batch.
Node divalidasi terhadap greenhouse-nya (`StoreSensorReadingRequest::after`).

---

## 7. Pengujian

Test memakai SQLite in-memory (dikonfigurasi `force="true"` pada `phpunit.xml`, jadi tidak
terpengaruh `.env`):

```bash
php artisan test
```

Cakupan (18 test, 118 assertion):

- `SensorIngestionTest` — 10 parameter tersimpan 1 batch, node salah greenhouse ditolak,
  nilai di luar rentang ditolak, parameter kurang ditolak, semua node GH1+GH2 diterima.
- `DashboardTest` — halaman tampil, rata-rata hanya node online, memakai batch terbaru,
  kondisi tanpa data mengembalikan `null` + "Belum ada data".
- `NodePageTest` — 5 node tampil, node basi ditandai offline, nilai kosong `null`.
- `HistoryTest` — grafik + tabel, filter per node, rentang 7d/30d, fallback filter tidak valid,
  paginasi 15 baris.

---

## 8. Tuning & Perawatan

| Kebutuhan | Lokasi |
| --- | --- |
| Ubah ambang online | `config/greenhouse.php` → `online_threshold_minutes` |
| Tambah greenhouse / node | `config/greenhouse.php` → `greenhouses` (sekaligus update rule `gh`/`node` di `StoreSensorReadingRequest`) |
| Ubah rentang validasi sensor | `app/Http/Requests/StoreSensorReadingRequest.php` |
| Ubah jumlah baris tabel riwayat | `HistoryController` → `paginate(15)` |

Pembersihan data lama (opsional, jalankan manual/cron):

```sql
DELETE FROM sensor_logs WHERE created_at < NOW() - INTERVAL 180 DAY;
```

Dengan frekuensi kirim 5 menit: 5 node × 10 parameter × 288 batch/hari ≈ **14.400 baris/hari**
(±5,3 juta baris/tahun) — lakukan arsip/pemangkasan berkala agar MariaDB di Raspberry Pi tetap ringan.

---

## 9. Troubleshooting

| Gejala | Penyebab & solusi |
| --- | --- |
| Dashboard kosong / "Belum ada data" | Belum ada data masuk atau semua node > 15 menit. Kirim payload uji via `curl` di atas. |
| `npm run build` gagal | Vite 6 butuh Node `>=18`. Jika Node terlalu baru/bermasalah, jalankan `npm ci` lalu ulangi; untuk Vite 7 dibutuhkan Node `^20.19` atau `>=22.12`. |
| `SQLSTATE[HY000] [1045]` | Password/kredensial MariaDB tidak cocok dengan `.env`; jalankan `php artisan config:clear`. |
| Aset tidak berubah di produksi | `npm run build` lalu `php artisan view:clear`. |
| Node selalu offline | Cek timestamp server (`APP_TIMEZONE=Asia/Jakarta`) dan jeda kirim bridge < ambang online. |

---

## 10. Lisensi

Kode aplikasi ini dikembangkan untuk Bahari Farm. Framework Laravel berlisensi MIT.
