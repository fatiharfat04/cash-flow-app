<?php

namespace App\Livewire\Budgets;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Services\BudgetService;
use App\Services\CategoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pengelola limit budget per kategori pengeluaran (§7.4 PROJECT.md).
 */
#[Layout('layouts.app')]
#[Title('Budget — Cash Flow')]
class BudgetManager extends Component
{
    public Carbon $month;

    /** [category_id => amount_limit] */
    public array $limits = [];

    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(): void
    {
        $this->month = now()->startOfMonth();
        $this->syncLimitsFromStatus();
    }

    public function getBudgetStatusProperty(): Collection
    {
        return app(BudgetService::class)->getBudgetStatus(auth()->user(), $this->month);
    }

    protected function rules(): array
    {
        return [
            'limits' => 'array',
            'limits.*' => 'required|integer|min:1|max:999999999999',
        ];
    }

    /** Semua kategori pengeluaran yang visibleTo user login (untuk form limit). */
    public function getCategoriesProperty(): Collection
    {
        return app(CategoryService::class)->getVisibleForUser(auth()->user(), TransactionType::Expense);
    }

    public function nextMonth(): void
    {
        $this->month = $this->month->copy()->addMonthNoOverflow()->startOfMonth();
        $this->syncLimitsFromStatus();
    }

    public function previousMonth(): void
    {
        $this->month = $this->month->copy()->subMonthNoOverflow()->startOfMonth();
        $this->syncLimitsFromStatus();
    }

    public function saveBudget(int $categoryId, int $amount): void
    {
        $category = Category::query()
            ->visibleTo(auth()->id())
            ->ofType(TransactionType::Expense)
            ->find($categoryId);

        if ($category === null) {
            $this->setNotice('error', 'Kategori tidak ditemukan atau bukan milik Anda.');

            return;
        }

        // Dinormalkan ke properti dulu supaya bisa divalidasi lewat rules() (§14 DoD #2)
        $this->limits[$category->id] = $amount;

        $this->validate(['limits.'.$category->id => $this->rules()['limits.*']]);

        app(BudgetService::class)->setBudget(auth()->user(), $category->id, $this->month, $amount);

        $this->syncLimitsFromStatus();
        $this->setNotice('success', 'Limit "'.$category->name.'" untuk '.$this->month->translatedFormat('F Y').' berhasil disimpan.');
    }

    /** Isi $limits dari limit yang sudah tersimpan di bulan aktif. */
    private function syncLimitsFromStatus(): void
    {
        $this->limits = $this->budgetStatus
            ->mapWithKeys(fn (array $row) => [$row['category']->id => $row['limit']])
            ->all();
    }

    private function setNotice(string $type, string $message): void
    {
        $this->noticeType = $type;
        $this->notice = $message;
    }

    public function render()
    {
        return view('livewire.budgets.budget-manager');
    }
}
