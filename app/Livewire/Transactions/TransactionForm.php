<?php

namespace App\Livewire\Transactions;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\CategoryService;
use App\Services\TransactionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Form tambah / ubah transaksi (§7.1 PROJECT.md).
 * Dipakai sebagai child component di dalam modal pada TransactionList.
 */
class TransactionForm extends Component
{
    /** null = mode create, terisi = mode edit. #[Locked] supaya tidak bisa
     *  diganti dari sisi client (wajib dicek lagi lewat TransactionPolicy). */
    #[Locked]
    public ?Transaction $transaction = null;

    public string $type = 'expense';

    public ?int $category_id = null;

    public ?int $amount = null;

    public ?string $description = null;

    public string $transaction_date = '';

    /** State modal — hanya UI, bukan logika bisnis. */
    public bool $open = false;

    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(): void
    {
        $this->transaction_date = today()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'type' => 'required|in:income,expense',
            'category_id' => [
                'required',
                'exists:categories,id',
                // WAJIB: kategori harus visibleTo user yang login, bukan cuma exists (§7.1)
                $this->categoryMustBeVisibleToCurrentUser(),
            ],
            'amount' => 'required|integer|min:1|max:999999999999',
            'description' => 'nullable|string|max:500',
            'transaction_date' => 'required|date|before_or_equal:today',
        ];
    }

    /**
     * Custom rule: kategori harus kategori default atau milik user yang login,
     * tidak soft-deleted, dan tipenya harus sama dengan tipe transaksi.
     */
    private function categoryMustBeVisibleToCurrentUser(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $category = Category::query()
                ->whereKey($value)
                ->whereNull('deleted_at')
                ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', auth()->id()))
                ->first();

            if ($category === null) {
                $fail('Kategori tidak ditemukan atau bukan milik Anda.');

                return;
            }

            if ($category->type->value !== $this->type) {
                $fail('Kategori "'.$category->name.'" tidak sesuai dengan tipe transaksi yang dipilih.');
            }
        };
    }

    public function getCategoriesProperty(): Collection
    {
        return app(CategoryService::class)
            ->getVisibleForUser(auth()->user(), TransactionType::from($this->type));
    }

    public function updatedType(): void
    {
        // reset category_id saat tipe berubah, karena kategori difilter per tipe
        $this->category_id = null;
        $this->resetValidation(['category_id']);
    }

    /**
     * Livewire tidak bisa memasukkan string kosong ke properti ?int,
     * jadi dinormalkan dulu supaya validasi "required" bekerja.
     */
    public function updatedCategoryId(mixed $value): void
    {
        if ($value === '' || $value === null) {
            $this->category_id = null;
        }
    }

    public function updatedAmount(mixed $value): void
    {
        if ($value === '' || $value === null) {
            $this->amount = null;
        }
    }

    #[On('open-create')]
    public function openCreate(): void
    {
        $this->resetForm();
        $this->open = true;
    }

    #[On('edit-transaction')]
    public function openEdit(int $transactionId): void
    {
        $transaction = Transaction::find($transactionId);

        if ($transaction === null) {
            $this->setNotice('error', 'Transaksi tidak ditemukan.');

            return;
        }

        if (! Gate::check('update', $transaction)) {
            $this->setNotice('error', 'Anda tidak punya akses untuk mengubah transaksi ini.');

            return;
        }

        $this->transaction = $transaction;
        $this->type = $transaction->type->value;
        $this->category_id = $transaction->category_id;
        $this->amount = $transaction->amount;
        $this->description = $transaction->description;
        $this->transaction_date = $transaction->transaction_date->toDateString();
        $this->notice = null;
        $this->resetValidation();
        $this->open = true;
    }

    public function save(TransactionService $service): void
    {
        $validated = $this->validate();

        $isEdit = $this->transaction !== null;

        try {
            if ($isEdit) {
                Gate::authorize('update', $this->transaction);
                $service->update($this->transaction, $validated);
            } else {
                $service->create(auth()->user(), $validated);
            }
        } catch (AuthorizationException $exception) {
            $this->setNotice('error', $exception->getMessage());

            return;
        }

        $this->resetForm();
        $this->setNotice('success', $isEdit ? 'Transaksi berhasil diubah.' : 'Transaksi berhasil ditambahkan.');
        $this->dispatch('transaction-saved');
    }

    public function close(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->transaction = null;
        $this->type = 'expense';
        $this->category_id = null;
        $this->amount = null;
        $this->description = null;
        $this->transaction_date = today()->toDateString();
        $this->open = false;
        $this->notice = null;
        $this->noticeType = 'success';
        $this->resetValidation();
    }

    private function setNotice(string $type, string $message): void
    {
        $this->noticeType = $type;
        $this->notice = $message;
    }

    public function render()
    {
        return view('livewire.transactions.transaction-form');
    }
}
