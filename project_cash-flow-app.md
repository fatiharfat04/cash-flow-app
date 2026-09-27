# PROJECT.md — Cash Flow (Laravel TALL Stack)
### Spesifikasi Lengkap: Backend Logic sampai Styling

Dokumen ini adalah **kontrak kerja untuk AI coding assistant**. Setiap section berdiri sendiri — kamu tidak perlu baca seluruh dokumen tiap sesi, cukup baca section yang relevan dengan fase yang sedang dikerjakan (lihat §10 untuk urutan fase).

---

## DAFTAR ISI
1. Keputusan Terkunci
2. Tech Stack & Versi
3. Struktur Folder Project
4. Database Schema (Detail Penuh)
5. Model & Relasi
6. Service Layer (Business Logic)
7. Livewire Components (Detail Penuh)
8. Routing & Middleware
9. Design System (Style Guide)
10. Step-by-Step Fase Pengerjaan
11. Rules untuk AI
12. Testing Strategy
13. Backlog (di luar MVP)
14. Definition of Done

---

## 1. Keputusan Terkunci

| Keputusan | Nilai |
|---|---|
| Mode user | Multi-user, dengan login & logout |
| Mata uang | IDR only |
| Format angka uang | Integer (rupiah penuh, tanpa desimal) |
| Database | MySQL 8 |
| Stack | TALL (Tailwind, Alpine.js, Laravel, Livewire) |
| Desain | Mobile-first, responsive |
| Chart | Wajib ada di dashboard (Chart.js) |
| Color palette | Cream sebagai primary |

---

## 2. Tech Stack & Versi

```
PHP           ^8.3
Laravel       ^11.0
Livewire      ^3.5
Alpine.js     ^3.x (bundled via Breeze Livewire stack)
Tailwind CSS  ^3.4
MySQL         8.0
Laravel Excel (maatwebsite/excel)  ^3.1
Chart.js      ^4.x (via CDN, JANGAN npm install — cukup <script> tag)
Pest PHP      ^3.x
Laravel Breeze ^2.x (livewire stack)
```

**Package tambahan yang DIIZINKAN tanpa konfirmasi ulang** (karena sudah implied oleh fitur di dokumen ini):
- `spatie/laravel-query-builder` — untuk filter transaksi yang lebih rapi (opsional, boleh manual query scope juga)

**Package apa pun di luar ini WAJIB konfirmasi ke user dulu** (rule #10 di §11).

---

## 3. Struktur Folder Project

```
app/
├── Livewire/
│   ├── Auth/                          (dari Breeze, jangan diubah strukturnya)
│   ├── Dashboard.php
│   ├── Transactions/
│   │   ├── TransactionForm.php        (dipakai untuk create & edit, mode via prop)
│   │   ├── TransactionList.php
│   │   └── TransactionFilter.php      (child component, emit event ke TransactionList)
│   ├── Categories/
│   │   └── CategoryManager.php
│   └── Budgets/
│       └── BudgetManager.php
├── Models/
│   ├── User.php
│   ├── Category.php
│   ├── Transaction.php
│   └── Budget.php
├── Services/
│   ├── TransactionService.php
│   ├── BudgetService.php
│   ├── DashboardService.php
│   └── CategoryService.php
├── Exports/
│   └── TransactionsExport.php
├── Policies/
│   ├── TransactionPolicy.php
│   ├── CategoryPolicy.php
│   └── BudgetPolicy.php
└── Enums/
    ├── TransactionType.php            (enum PHP 8.1+: Income, Expense)
    └── BudgetStatus.php               (enum: Safe, Warning, Exceeded)

resources/
├── views/
│   ├── layouts/
│   │   ├── app.blade.php              (layout utama: bottom nav mobile + sidebar desktop)
│   │   └── guest.blade.php
│   ├── livewire/
│   │   ├── dashboard.blade.php
│   │   ├── transactions/
│   │   ├── categories/
│   │   └── budgets/
│   └── components/
│       ├── card.blade.php             (Blade component: wrapper card cream)
│       ├── button.blade.php           (variant: primary/secondary/danger)
│       ├── badge.blade.php            (untuk status budget: safe/warning/exceeded)
│       └── nav-bottom.blade.php
├── css/app.css
└── js/app.js

database/
├── migrations/
├── factories/
└── seeders/

tests/
├── Feature/
│   ├── Livewire/
│   └── Exports/
└── Unit/
    └── Services/
```

---

## 4. Database Schema (Detail Penuh)

### 4.1 Tabel `users` (bawaan Breeze, tidak diubah)
```
id            bigint unsigned, PK, auto increment
name          varchar(255)
email         varchar(255), UNIQUE
password      varchar(255)
email_verified_at  timestamp, nullable
remember_token varchar(100), nullable
created_at, updated_at  timestamp
```

### 4.2 Tabel `categories`
```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
    // nullable = kategori default/global (dibuat oleh seeder, milik semua user)
    // terisi  = kategori kustom milik user tertentu
    $table->string('name', 100);
    $table->enum('type', ['income', 'expense']);
    $table->string('icon', 50)->default('tag');       // nama icon (Heroicons slug)
    $table->string('color', 7)->default('#D9BC7C');   // hex, default cream-500
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['user_id', 'name', 'type'], 'uniq_category_per_user_type');
    $table->index(['user_id', 'type']);
});
```

### 4.3 Tabel `transactions`
```php
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->constrained()->restrictOnDelete();
    // restrictOnDelete: kategori yang sudah dipakai transaksi TIDAK BOLEH dihapus
    // (harus di-soft-delete kategori atau reassign transaksi dulu)
    $table->enum('type', ['income', 'expense']);
    $table->unsignedBigInteger('amount');     // INTEGER, rupiah penuh, tidak boleh negatif
    $table->text('description')->nullable();
    $table->date('transaction_date');         // TERPISAH dari created_at
    $table->timestamps();
    $table->softDeletes();

    $table->index(['user_id', 'transaction_date']);
    $table->index(['user_id', 'type', 'transaction_date']);
    $table->index(['user_id', 'category_id']);
});
```

**Kenapa `restrictOnDelete` untuk category_id, bukan `cascadeOnDelete`?** Karena menghapus kategori tidak boleh diam-diam menghapus riwayat transaksi finansial user. Itu bug serius untuk aplikasi keuangan — data histori harus tetap ada meski kategorinya dihapus/diarsipkan.

### 4.4 Tabel `budgets`
```php
Schema::create('budgets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->date('month');                    // selalu tanggal 1, misal 2026-09-01
    $table->unsignedBigInteger('amount_limit'); // INTEGER
    $table->timestamps();

    $table->unique(['user_id', 'category_id', 'month'], 'uniq_budget_per_category_month');
});
```

### 4.5 Seeder Default Categories
```php
// database/seeders/DefaultCategorySeeder.php
$defaults = [
    ['name' => 'Gaji',          'type' => 'income',  'icon' => 'banknotes',      'color' => '#4A7C59'],
    ['name' => 'Bonus/Lainnya', 'type' => 'income',  'icon' => 'gift',           'color' => '#4A7C59'],
    ['name' => 'Makanan',       'type' => 'expense', 'icon' => 'shopping-cart',  'color' => '#B85C4A'],
    ['name' => 'Transportasi',  'type' => 'expense', 'icon' => 'truck',          'color' => '#B85C4A'],
    ['name' => 'Hiburan',       'type' => 'expense', 'icon' => 'film',           'color' => '#B85C4A'],
    ['name' => 'Tagihan',       'type' => 'expense', 'icon' => 'document-text',  'color' => '#B85C4A'],
];
// user_id = null untuk semua default categories
```

---

## 5. Model & Relasi

### 5.1 `Category.php`
```php
class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'type', 'icon', 'color'];
    protected $casts = ['type' => TransactionType::class];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function transactions(): HasMany { return $this->hasMany(Transaction::class); }
    public function budgets(): HasMany { return $this->hasMany(Budget::class); }

    public function isDefault(): bool { return is_null($this->user_id); }

    public function scopeVisibleTo(Builder $q, int $userId): Builder
    {
        return $q->where(fn($q) => $q->whereNull('user_id')->orWhere('user_id', $userId));
    }

    public function scopeOfType(Builder $q, TransactionType $type): Builder
    {
        return $q->where('type', $type);
    }
}
```

### 5.2 `Transaction.php`
```php
class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'category_id', 'type', 'amount', 'description', 'transaction_date'];
    protected $casts = [
        'type' => TransactionType::class,
        'amount' => 'integer',
        'transaction_date' => 'date',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }

    // Query Scopes — WAJIB dipakai, jangan tulis ulang logic tanggal di component
    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }
    public function scopeToday(Builder $q): Builder
    {
        return $q->whereDate('transaction_date', today());
    }
    public function scopeThisWeek(Builder $q): Builder
    {
        return $q->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }
    public function scopeThisMonth(Builder $q): Builder
    {
        return $q->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()]);
    }
    public function scopeBetweenDates(Builder $q, string $from, string $to): Builder
    {
        return $q->whereBetween('transaction_date', [$from, $to]);
    }
    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp' . number_format($this->amount, 0, ',', '.');
    }
}
```

### 5.3 `Budget.php`
```php
class Budget extends Model
{
    protected $fillable = ['user_id', 'category_id', 'month', 'amount_limit'];
    protected $casts = ['month' => 'date', 'amount_limit' => 'integer'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }

    public function scopeForMonth(Builder $q, Carbon $month): Builder
    {
        return $q->whereYear('month', $month->year)->whereMonth('month', $month->month);
    }
}
```

### 5.4 `Enums/TransactionType.php`
```php
enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match($this) {
            self::Income => 'Pemasukan',
            self::Expense => 'Pengeluaran',
        };
    }
    public function colorToken(): string
    {
        return match($this) {
            self::Income => 'income',   // lihat §9 color tokens
            self::Expense => 'expense',
        };
    }
}
```

---

## 6. Service Layer (Business Logic)

> **Rule wajib:** SEMUA kalkulasi (total, saldo, persentase budget, data chart) HARUS lewat Service class ini. Livewire Component hanya memanggil service dan menampilkan hasilnya — tidak boleh ada `+`/`-`/`array_sum` kalkulasi finansial langsung di Component atau di Blade.

### 6.1 `TransactionService.php`
```php
class TransactionService
{
    public function create(User $user, array $data): Transaction
    public function update(Transaction $transaction, array $data): Transaction
    public function delete(Transaction $transaction): bool

    public function getFiltered(User $user, array $filters): Builder
    // $filters: ['period' => 'today'|'week'|'month'|'custom', 'from' => ?, 'to' => ?,
    //            'category_id' => ?, 'type' => ?]
    // return: Builder (belum di-paginate), supaya bisa dipakai ulang untuk export

    public function getSummary(User $user, array $filters): array
    // return: ['total_income' => int, 'total_expense' => int, 'balance' => int]
}
```

### 6.2 `BudgetService.php`
```php
class BudgetService
{
    public function setBudget(User $user, int $categoryId, Carbon $month, int $amountLimit): Budget
    // upsert: create atau update jika sudah ada (unique constraint §4.4)

    public function getBudgetStatus(User $user, Carbon $month): Collection
    // return Collection of:
    // ['category' => Category, 'limit' => int, 'spent' => int, 'percentage' => float,
    //  'status' => BudgetStatus::Safe|Warning|Exceeded]
    // Warning jika percentage >= 80, Exceeded jika >= 100
}
```

### 6.3 `DashboardService.php`
```php
class DashboardService
{
    public function getSummaryCards(User $user): array
    // ['balance' => int, 'income_this_month' => int, 'expense_this_month' => int]

    public function getTrendChartData(User $user, int $days = 30): array
    // ['labels' => ['2026-09-01', ...], 'income' => [int, ...], 'expense' => [int, ...]]

    public function getCategoryBreakdown(User $user, Carbon $month): array
    // ['labels' => ['Makanan', 'Transportasi', ...], 'values' => [int, ...], 'colors' => [hex, ...]]

    public function getRecentTransactions(User $user, int $limit = 5): Collection
}
```

### 6.4 `CategoryService.php`
```php
class CategoryService
{
    public function createCustom(User $user, array $data): Category
    public function update(Category $category, array $data): Category
    // WAJIB cek: $category->isDefault() === false dan $category->user_id === auth()->id()
    // sebelum boleh update/delete — lempar AuthorizationException jika gagal
    public function delete(Category $category): bool
    // WAJIB cek category tidak punya transaksi terkait (karena FK restrictOnDelete),
    // kalau ada, lempar Exception dengan pesan jelas ke user
    public function getVisibleForUser(User $user, ?TransactionType $type = null): Collection
}
```

---

## 7. Livewire Components (Detail Penuh)

### 7.1 `TransactionForm`
```php
class TransactionForm extends Component
{
    public ?Transaction $transaction = null;  // null = mode create, terisi = mode edit
    public string $type = 'expense';
    public ?int $category_id = null;
    public ?int $amount = null;
    public ?string $description = null;
    public string $transaction_date;

    protected function rules(): array
    {
        return [
            'type' => 'required|in:income,expense',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|integer|min:1|max:999999999999',
            'description' => 'nullable|string|max:500',
            'transaction_date' => 'required|date|before_or_equal:today',
        ];
    }

    public function updatedType(): void
    {
        // reset category_id saat tipe berubah, karena kategori difilter per tipe
        $this->category_id = null;
    }

    public function getCategoriesProperty(): Collection
    // computed property, filter kategori sesuai $this->type

    public function save(): void
    // panggil TransactionService::create atau update, dispatch event 'transaction-saved'
}
```

**Validasi khusus yang WAJIB ada (jangan cuma rules() di atas):**
- `amount` tidak boleh 0 (min:1 sudah cover, tapi test-nya wajib eksplisit)
- `category_id` yang dipilih harus `visibleTo` user yang login (tidak boleh pilih kategori kustom milik user lain via manipulasi request) — validasi ini custom rule, bukan cuma `exists`

### 7.2 `TransactionList`
```php
class TransactionList extends Component
{
    use WithPagination;

    public string $period = 'month';   // today|week|month|custom
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $categoryFilter = null;
    public ?string $typeFilter = null;

    protected $listeners = ['transaction-saved' => '$refresh'];

    public function getTransactionsProperty(): LengthAwarePaginator
    public function getSummaryProperty(): array   // dari TransactionService::getSummary

    public function delete(int $transactionId): void
    // authorize via TransactionPolicy sebelum delete

    public function exportExcel()
    // return Excel::download(...) dengan filter yang sama seperti list
}
```

### 7.3 `CategoryManager`
```php
class CategoryManager extends Component
{
    public string $name = '';
    public string $type = 'expense';
    public string $icon = 'tag';
    public string $color = '#D9BC7C';
    public ?int $editingId = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:categories,name,' .
                      ($this->editingId ?? 'NULL') . ',id,user_id,' . auth()->id() . ',type,' . $this->type,
            'type' => 'required|in:income,expense',
            'icon' => 'required|string',
            'color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    public function save(): void
    public function edit(int $categoryId): void
    public function delete(int $categoryId): void
    // WAJIB tampilkan pesan error jelas jika gagal karena masih dipakai transaksi
}
```

### 7.4 `BudgetManager`
```php
class BudgetManager extends Component
{
    public Carbon $month;   // default: bulan berjalan, ada tombol next/prev month
    public array $limits = [];  // [category_id => amount_limit]

    public function getBudgetStatusProperty(): Collection
    // dari BudgetService::getBudgetStatus

    public function saveBudget(int $categoryId, int $amount): void
    public function nextMonth(): void
    public function previousMonth(): void
}
```

### 7.5 `Dashboard`
```php
class Dashboard extends Component
{
    public function getSummaryProperty(): array
    public function getTrendDataProperty(): array
    public function getBreakdownDataProperty(): array
    public function getRecentTransactionsProperty(): Collection
    public function getBudgetStatusProperty(): Collection

    // Saat mount/render, dispatch browser event berisi JSON data chart,
    // di-listen oleh Alpine.js x-data component yang inject ke Chart.js instance
    public function render()
    {
        $this->dispatch('chart-data-updated',
            trend: $this->trendData,
            breakdown: $this->breakdownData
        );
        return view('livewire.dashboard');
    }
}
```

---

## 8. Routing & Middleware

```php
// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/transactions', TransactionList::class)->name('transactions.index');
    Route::get('/categories', CategoryManager::class)->name('categories.index');
    Route::get('/budgets', BudgetManager::class)->name('budgets.index');
});
// Auth routes (login/register/logout) sudah otomatis dari Breeze Livewire
```

**Policy registration** (`AuthServiceProvider`):
```php
Transaction::class => TransactionPolicy::class,
Category::class => CategoryPolicy::class,
Budget::class => BudgetPolicy::class,
```

`TransactionPolicy::update/delete`: `return $transaction->user_id === $user->id;`
`CategoryPolicy::update/delete`: `return !$category->isDefault() && $category->user_id === $user->id;`

---

## 9. Design System (Style Guide)

### 9.1 Color Tokens
```js
// tailwind.config.js
theme: {
  extend: {
    colors: {
      cream: {
        50: '#FFFDF9', 100: '#FFF8ED', 200: '#FCEFD9',
        300: '#F5E3BE',  // primary — background utama, card, nav
        400: '#EAD2A0', 500: '#D9BC7C', 600: '#B8985A', 700: '#96784A',
      },
      income:  { DEFAULT: '#4A7C59', light: '#E8F0EA' },  // hijau muted + bg tint
      expense: { DEFAULT: '#B85C4A', light: '#F5E7E4' },  // merah bata muted + bg tint
      warning: '#C7912E',
      ink:     '#3A3128',   // teks utama di atas cream
      'ink-muted': '#6B6154',
    }
  }
}
```

### 9.2 Typography
```
Font family: 'Figtree' (default Breeze) atau 'Inter' — pilih salah satu, konsisten.
Scale (Tailwind default classes, JANGAN custom):
  text-xs   (12px) — label kecil, caption
  text-sm   (14px) — body sekunder, meta info
  text-base (16px) — body utama, SEMUA input field (wajib 16px, cegah zoom iOS)
  text-lg   (18px) — subheading
  text-xl   (20px) — heading card/section
  text-2xl  (24px) — heading halaman (mobile)
  text-3xl  (30px) — heading halaman (desktop, md: ke atas)
Font weight: font-normal (body), font-medium (label), font-semibold (heading)
```

### 9.3 Spacing & Layout
```
Base unit: Tailwind default (4px increments)
Container padding mobile: px-4
Container padding desktop: md:px-8
Card padding: p-4 (mobile), md:p-6 (desktop)
Gap antar card di grid: gap-4
Bottom nav height: h-16, fixed bottom-0, pakai safe-area-inset untuk notch
Sidebar desktop width: w-64
Max width content area: max-w-5xl mx-auto (desktop)
```

### 9.4 Komponen UI

**Button** (`components/button.blade.php`, props: `variant`)
```
primary:   bg-cream-500 text-ink hover:bg-cream-600, font-medium, rounded-lg, px-4 py-2.5
secondary: bg-white border border-cream-400 text-ink hover:bg-cream-50, rounded-lg
danger:    bg-expense text-white hover:bg-expense/90, rounded-lg
Semua button: min-height 44px (touch target), transition-colors duration-150
```

**Card** (`components/card.blade.php`)
```
bg-white (BUKAN cream — cream untuk background PAGE, card tetap putih agar ada
kontras layer), rounded-xl, shadow-sm, border border-cream-200, p-4 md:p-6
```

**Input Field**
```
w-full, rounded-lg, border-cream-300, focus:border-cream-500 focus:ring-cream-500,
text-base (16px — wajib), px-3 py-2.5
Label: text-sm font-medium text-ink-muted, mb-1
Error text: text-sm text-expense, mt-1
```

**Badge Status Budget** (`components/badge.blade.php`)
```
Safe:     bg-income-light text-income
Warning:  bg-warning/10 text-warning
Exceeded: bg-expense-light text-expense
Semua: text-xs font-medium px-2 py-1 rounded-full
```

**Transaction List Item**
```
Mobile (default): card per item, flex justify-between,
  icon kategori (kiri, bg sesuai warna kategori/10, rounded-full, 40x40px) +
  nama kategori & deskripsi (tengah) +
  amount (kanan, font-semibold, warna income/expense sesuai type)
Desktop (md: ke atas): table dengan kolom Tanggal | Kategori | Deskripsi | Tipe | Jumlah | Aksi
```

**Bottom Navigation (mobile)**
```
Fixed bottom-0, bg-white, border-t border-cream-200, h-16, grid grid-cols-5,
setiap item: icon + label text-xs, active state: text-cream-600 font-medium,
inactive: text-ink-muted
Hidden di desktop (md:hidden), diganti sidebar (hidden md:block)
```

### 9.5 Chart Styling (Chart.js)
```js
// Palet chart mengikuti color tokens, JANGAN pakai default Chart.js colors
const chartColors = {
  income: '#4A7C59',
  expense: '#B85C4A',
  grid: '#FCEFD9',      // cream-200, untuk gridlines (subtle)
  text: '#6B6154',      // ink-muted, untuk axis label
};
// Line/bar chart trend: 2 dataset (income hijau, expense merah bata)
// Pie/donut breakdown per kategori: pakai warna custom kategori (dari kolom categories.color)
// Font chart: sama dengan font body (Figtree/Inter), size 12px untuk label
```

### 9.6 Responsive Breakpoints (Tailwind default, JANGAN custom)
```
sm:  640px   — mulai transisi dari mobile murni
md:  768px   — breakpoint utama mobile -> desktop (sidebar muncul, table muncul)
lg:  1024px  — desktop penuh, max-width content diterapkan
```

---

## 10. Step-by-Step Fase Pengerjaan

### Fase 0 — Setup Project
```
Buatkan project Laravel 11 "cash-flow". Install Breeze Livewire stack
(php artisan breeze:install livewire), Tailwind CSS, maatwebsite/excel.
Setup MySQL di .env. Tambahkan color tokens dari §9.1 ke tailwind.config.js.
Buat Blade components: button, card, badge (§9.4) sebelum lanjut fase lain.
Jangan buat fitur transaksi dulu.
```

### Fase 1 — Layout & Navigasi
```
Buat layouts/app.blade.php dengan bottom nav mobile (§9.4) dan sidebar desktop.
Sesuaikan halaman login/register Breeze pakai color tokens §9.1 dan komponen §9.4.
```

### Fase 2 — Migration & Model
```
Buat migration categories, transactions, budgets persis sesuai §4.
Buat model + enum sesuai §5. Buat DefaultCategorySeeder sesuai §4.5.
Tulis test migration & relasi sebelum lanjut.
```

### Fase 3 — Service Layer
```
Buat TransactionService, CategoryService sesuai signature di §6.1 dan §6.4.
Tulis Unit test untuk getSummary() dan getFiltered() dengan berbagai kombinasi filter
SEBELUM membuat Livewire Component yang memanggilnya.
```

### Fase 4 — Kategori (CategoryManager)
```
Buat CategoryManager Livewire Component sesuai §7.3. Buat Policy sesuai §8.
Tulis test: tidak bisa hapus default, tidak bisa edit milik user lain,
tidak bisa hapus kategori yang masih dipakai transaksi.
```

### Fase 5 — Transaksi (TransactionForm + TransactionList)
```
Buat TransactionForm dan TransactionList sesuai §7.1 dan §7.2.
Gunakan TransactionService yang sudah dibuat di Fase 3, jangan tulis ulang logicnya.
Styling ikuti §9.4 (Transaction List Item: card mobile, table desktop).
Tulis test lengkap termasuk authorization (user A vs user B).
```

### Fase 6 — Budget (BudgetService + BudgetManager)
```
Buat BudgetService (§6.2) dan BudgetManager Livewire Component (§7.4).
Styling badge status ikuti §9.4. Tulis test perhitungan percentage & status.
```

### Fase 7 — Dashboard & Chart
```
Buat DashboardService (§6.3) dan Dashboard Component (§7.5).
Implementasikan Chart.js dengan styling §9.5, dijembatani Alpine.js dari
Livewire dispatch event (lihat contoh render() di §7.5).
```

### Fase 8 — Export Excel
```
Buat TransactionsExport memakai TransactionService::getFiltered() (Fase 3),
supaya filter export konsisten dengan filter yang ada di TransactionList.
```

### Fase 9 — Polish Responsive & Aksesibilitas
```
Review semua halaman terhadap checklist mobile-first (rules #6 di §11) dan
pastikan seluruh styling sesuai §9 secara konsisten (jangan ada hex color
liar di luar tokens §9.1).
```

### Fase 10 — Testing & Deployment
```
Full test suite pass. Dockerfile + docker-compose (app, mysql, nginx). README.md.
```

---

## 11. Rules untuk AI

1. Satu fase, satu commit logis.
2. Migration dulu, baru logic.
3. Validasi pakai Livewire `rules()`/`#[Validate]`, bukan Form Request.
4. Uang selalu integer, never float/decimal.
5. Semua query transaksi WAJIB scoped ke `auth()->id()`.
6. Mobile-first: desain dari breakpoint `sm` dulu.
7. Tidak ada logic bisnis (kalkulasi uang) di Blade/Livewire view — semua lewat Service (§6).
8. Setiap fitur baru wajib test (happy path + edge case).
9. `wire:key` wajib di setiap `@foreach`.
10. Jangan install package baru di luar §2 tanpa konfirmasi.
11. Jika ambigu, AI wajib bertanya, bukan menebak.
12. **Jangan lakukan apapun di luar konteks PROJECT.md.** Ide di luar dokumen ini dilaporkan sebagai saran, bukan dieksekusi. Pengecualian: bug kritis/celah keamanan/error yang menghalangi fase berjalan — boleh diperbaiki tapi wajib dilaporkan terpisah.
13. **Satu task tuntas dulu, baru lanjut.** Definition of Done (§14) harus terpenuhi untuk task berjalan sebelum mulai task berikutnya.
14. **Semua warna WAJIB dari color tokens §9.1.** Tidak ada hex code baru ditulis langsung di Blade/CSS di luar tokens ini.
15. **Semua kalkulasi uang WAJIB lewat Service class (§6).** Livewire Component hanya boleh memanggil, tidak boleh menghitung sendiri.

---

## 12. Testing Strategy

```
Unit tests (tests/Unit/Services/):
  - TransactionServiceTest: getSummary() dengan berbagai filter, getFiltered() scoping
  - BudgetServiceTest: percentage calculation, status Safe/Warning/Exceeded, edge case 0 transaksi
  - DashboardServiceTest: trend data shape, breakdown data shape

Feature tests (tests/Feature/Livewire/):
  - TransactionFormTest: create, update, validasi gagal, category cross-user rejected
  - TransactionListTest: filter periode benar, tidak bocor data user lain
  - CategoryManagerTest: tidak bisa hapus default/milik user lain, unique constraint
  - BudgetManagerTest: set budget, unique per bulan, upsert behavior
  - DashboardTest: render tanpa error dengan 0 data maupun data banyak

Feature tests (tests/Feature/Exports/):
  - TransactionsExportTest: jumlah baris sesuai filter, total baris terakhir benar
```

---

## 13. Backlog (di luar MVP — JANGAN dikerjakan sebelum Fase 0-10 selesai)

| Fitur | Alasan ditunda |
|---|---|
| Recurring transaction | Belum critical untuk validasi konsep dasar |
| Import Excel/CSV | Bukan blocker untuk mulai pakai aplikasi |
| Multi-akun (bank/e-wallet/cash) | Menambah kompleksitas model data signifikan |
| PWA/offline | Effort besar, web app mobile-first sudah cukup |
| Dark mode | Estetika, tema utama sudah cream/light |
| Multi-currency | Sudah diputuskan IDR only |

---

## 14. Definition of Done (per fitur)

1. Migration & model final, tidak ada rename kolom setelahnya.
2. Validasi ada di Livewire `rules()`, bukan manual di method.
3. Query di-scope ke `auth()->id()`.
4. `wire:key` ada di setiap `@foreach`.
5. Kalkulasi finansial lewat Service class (§6), bukan di Component/Blade.
6. Styling konsisten dengan §9 (color tokens, spacing, komponen).
7. Minimal 1 test lulus (happy path + 1 edge case).
8. Dicek tampilan di viewport 360px dan desktop (≥1024px).
9. Tidak ada hex color baru di luar §9.1.
