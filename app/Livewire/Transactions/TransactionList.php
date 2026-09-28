<?php

namespace App\Livewire\Transactions;

use App\Exports\TransactionsExport;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\CategoryService;
use App\Services\TransactionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Daftar transaksi + ringkasan + filter + export (§7.2 PROJECT.md).
 */
#[Layout('layouts.app')]
#[Title('Transaksi — Cash Flow')]
class TransactionList extends Component
{
    use WithPagination;

    public string $period = 'month';   // today|week|month|custom

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?int $categoryFilter = null;

    public ?string $typeFilter = null;

    public ?string $notice = null;

    public string $noticeType = 'success';

    protected $listeners = ['transaction-saved' => '$refresh'];

    protected function rules(): array
    {
        $required = $this->period === 'custom' ? 'required' : 'nullable';

        return [
            'period' => 'required|in:today,week,month,custom',
            'dateFrom' => $required.'|date',
            'dateTo' => $required.'|date|after_or_equal:dateFrom',
            'categoryFilter' => 'nullable|integer|exists:categories,id',
            'typeFilter' => 'nullable|in:income,expense',
        ];
    }

    /**
     * Livewire tidak bisa memasukkan string kosong ke properti bertipe,
     * jadi dinormalkan dulu supaya validasi & filter tetap bekerja.
     */
    public function updatedCategoryFilter(mixed $value): void
    {
        if ($value === '' || $value === null) {
            $this->categoryFilter = null;
        }
    }

    public function updatedTypeFilter(mixed $value): void
    {
        if ($value === '' || $value === null) {
            $this->typeFilter = null;
        }
    }

    public function updatedDateFrom(mixed $value): void
    {
        if ($value === '') {
            $this->dateFrom = null;
        }
    }

    public function updatedDateTo(mixed $value): void
    {
        if ($value === '') {
            $this->dateTo = null;
        }
    }

    public function filters(): array
    {
        return [
            'period' => $this->period,
            'from' => $this->dateFrom,
            'to' => $this->dateTo,
            'category_id' => $this->categoryFilter,
            'type' => $this->typeFilter,
        ];
    }

    public function getTransactionsProperty(): LengthAwarePaginator
    {
        return app(TransactionService::class)
            ->getFiltered(auth()->user(), $this->filters())
            ->paginate(10);
    }

    public function getSummaryProperty(): array
    {
        return app(TransactionService::class)->getSummary(auth()->user(), $this->filters());
    }

    /** Untuk dropdown filter kategori — hanya kategori yang visibleTo user login. */
    public function getCategoriesProperty(): Collection
    {
        return app(CategoryService::class)->getVisibleForUser(auth()->user());
    }

    public function applyFilter(): void
    {
        $this->validate();
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->reset(['period', 'dateFrom', 'dateTo', 'categoryFilter', 'typeFilter']);
        $this->resetValidation();
        $this->resetPage();
    }

    public function delete(int $transactionId): void
    {
        $transaction = Transaction::find($transactionId);

        if ($transaction === null) {
            $this->setNotice('error', 'Transaksi tidak ditemukan.');

            return;
        }

        try {
            Gate::authorize('delete', $transaction);
            app(TransactionService::class)->delete($transaction);
        } catch (AuthorizationException $exception) {
            $this->setNotice('error', $exception->getMessage());

            return;
        }

        $this->setNotice('success', 'Transaksi berhasil dihapus.');
    }

    public function openCreate(): void
    {
        $this->dispatch('open-create');
    }

    public function edit(int $transactionId): void
    {
        $this->dispatch('edit-transaction', transactionId: $transactionId);
    }

    public function exportExcel()
    {
        return Excel::download(
            new TransactionsExport(auth()->user(), app(TransactionService::class), $this->filters()),
            'transaksi-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    private function setNotice(string $type, string $message): void
    {
        $this->noticeType = $type;
        $this->notice = $message;
    }

    public function render()
    {
        return view('livewire.transactions.transaction-list');
    }
}
