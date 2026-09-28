# Cash Flow

Aplikasi pencatat **arus kas pribadi**: transaksi masuk/keluar, kategori,
limit budget per bulan, dan dashboard dengan grafik. Dibangun sesuai
`project_cash-flow-app.md` (TALL stack), mata uang IDR disimpan sebagai
**integer rupiah** — tanpa float/decimal.

## Tech stack

| Komponen | Versi |
|---|---|
| PHP | ^8.3 |
| Laravel | 11 |
| Livewire | 3 (Breeze Livewire stack + Volt) |
| Tailwind CSS | 3.4 |
| Alpine.js | 3 (bundling Livewire) |
| MySQL | 8.x |
| Laravel Excel (maatwebsite/excel) | ^3.1 |
| Chart.js | 4 — **via CDN** (`<script>` tag, bukan npm install) |
| Pest | 3 |

## Fitur

- Autentikasi Breeze (login, register, verifikasi email, profil + foto).
- Transaksi: tambah/ubah/hapus, filter `hari ini | minggu | bulan | rentang tanggal`,
  ringkasan pemasukan/pengeluaran/saldo, export Excel (`.xlsx`) sesuai filter aktif.
- Kategori: 6 kategori bawaan untuk semua user + kategori custom per user
  (hapus/diubah hanya untuk kategori milik sendiri).
- Budget: limit per kategori per bulan, progress bar + status `Aman` / `Waspada` / `Lewat`.
- Dashboard: kartu ringkasan, grafik tren 30 hari (line) dan pengeluaran per
  kategori (doughnut), 5 transaksi terbaru, dan status budget.

## Struktur penting

```
app/Enums/        TransactionType, BudgetStatus
app/Exports/      TransactionsExport (Excel)
app/Livewire/     Dashboard, Transactions/, Categories/, Budgets/, Layout/
app/Models/       User, Category, Transaction, Budget + Policy
app/Services/     TransactionService, CategoryService, BudgetService, DashboardService
app/Support/      Money (format tampilan rupiah)
database/         migrations, factories, seeders (DefaultCategorySeeder)
resources/views/  layouts, livewire/*, components
tests/            Feature/Livewire, Feature/Exports, Unit/Services
```

**Aturan yang dipakai:** seluruh kalkulasi uang ada di `app/Services/*`
(Livewire component & Blade hanya memanggil), semua query di-scope ke
`auth()->id()`, warna hanya dari token `tailwind.config.js` (§9.1).

---

## Development lokal

Prasyarat: PHP 8.3 (+ ext `pdo_mysql`, `intl`, `mbstring`, `zip`), Composer 2,
Node 20+, MySQL 8.

```bash
composer install
cp .env.example .env
php artisan key:generate
# sesuaikan DB_* di .env dengan MySQL kamu

php artisan migrate --seed      # + 6 kategori bawaan
npm install
npm run build                   # atau: npm run dev

php artisan serve               # http://127.0.0.1:8000
```

### Menjalankan test

Test memakai database terpisah (`cash_flow_test`) — lihat `phpunit.xml`.

```bash
php artisan test                # seluruh suite
php artisan test --filter=Dashboard
```

Pastikan database `cash_flow_test` sudah dibuat:
`mysql -u root -e "CREATE DATABASE cash_flow_test CHARACTER SET utf8mb4;"`

---

## Deploy dengan Docker

Tiga service sesuai spec: **app** (PHP-FPM), **mysql**, **nginx**.

```bash
docker compose up -d --build

# sekali saja saat pertama kali
docker compose exec app php artisan migrate --seed

# buka
# http://localhost:8080          (ganti port lewat variabel APP_PORT)
```

`migrate --seed` membuat 6 kategori bawaan + akun demo
**`test@example.com` / `password`**. Hapus akun itu (atau jangan pakai `--seed`)
sebelum dipakai di server publik.

Perintah lain di dalam container:

```bash
docker compose exec app php artisan tinker
docker compose exec app php artisan about
docker compose logs -f nginx
docker compose down             # berhenti
docker compose down -v          # berhenti + hapus data DB & volume aplikasi
```

> Test suite (Pest) hanya ada di mode development (`composer install` biasa) —
> jalankan di mesin dev, bukan di image produksi yang memakai `--no-dev`.

### Konfigurasi

| File | Isi |
|---|---|
| `docker/app.env` | environment container aplikasi (DB, locale, log) |
| `docker/db.env` | kredensial MySQL — **harus sama** dengan `docker/app.env` |
| `docker/nginx.conf` | root `public/`, `try_files` → `index.php`, `fastcgi_pass app:9000` |
| `APP_PORT` | port host untuk nginx (default `8080`) |

Hal-hal yang perlu diperhatikan saat deploy sungguhan:

1. **Ganti password** di `docker/app.env` dan `docker/db.env`.
2. `APP_KEY` dibuat otomatis oleh entry point container pada boot pertama dan
   disimpan di volume `app_data` (`storage/app/.app_key`) — tidak perlu diisi manual.
3. `APP_URL` di `docker/app.env` harus disesuaikan dengan domain.
4. Aset dibuild saat image dibuat (tahap `node:22-alpine` di `Dockerfile`),
   jadi `npm install`/`npm run build` tidak perlu dijalankan di server.
5. Chart.js dimuat dari `https://cdn.jsdelivr.net/npm/chart.js@4` — server
   pengunjung harus punya akses internet agar grafik tampil.

---

## Pengerjaan per fase

Implementasi mengikuti urutan fase di `project_cash-flow-app.md`:

| Fase | Isi |
|---|---|
| 0 | Setup project + color tokens §9.1 + komponen `button`/`card`/`badge` |
| 1 | Layout & navigasi (bottom nav mobile, sidebar desktop) |
| 2 | Migration, model, enum, seeder, policy, factory |
| 3 | Service layer (perhitungan uang) |
| 4 | Manajemen kategori |
| 5 | Form & daftar transaksi + export Excel |
| 6 | Manajemen budget |
| 7 | Dashboard + Chart.js (Alpine menjembatani `dispatch`) |
| 8 | Test export Excel |
| 9 | Polish responsive & aksesibilitas |
| 10 | Test suite penuh, Docker, README |
