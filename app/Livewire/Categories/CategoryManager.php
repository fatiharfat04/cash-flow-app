<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Kategori — Cash Flow')]
class CategoryManager extends Component
{
    public string $name = '';

    public string $type = 'expense';

    public string $icon = 'tag';

    public string $color = '#D9BC7C';

    public ?int $editingId = null;

    public ?string $notice = null;

    public string $noticeType = 'success';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:categories,name,'.
                ($this->editingId ?? 'NULL').',id,user_id,'.auth()->id().',type,'.$this->type,
            'type' => 'required|in:income,expense',
            'icon' => 'required|string',
            'color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    public function getCategoriesProperty()
    {
        return app(CategoryService::class)
            ->getVisibleForUser(auth()->user())
            ->groupBy(fn (Category $category) => $category->type->value);
    }

    public function getAvailableIconsProperty(): array
    {
        return [
            'tag', 'banknotes', 'gift', 'shopping-cart', 'shopping-bag', 'truck',
            'film', 'document-text', 'home', 'user', 'chart-pie', 'heart', 'bolt',
            'briefcase', 'currency-dollar', 'credit-card', 'phone', 'wifi',
            'musical-note', 'book-open', 'academic-cap', 'wallet', 'cake', 'globe-alt',
        ];
    }

    public function save(CategoryService $service): void
    {
        $validated = $this->validate();

        $isEdit = $this->editingId !== null;

        try {
            if ($isEdit) {
                $service->update(Category::findOrFail($this->editingId), $validated);
            } else {
                $service->createCustom(auth()->user(), $validated);
            }
        } catch (AuthorizationException $exception) {
            $this->setNotice('error', $exception->getMessage());

            return;
        }

        $this->resetForm();
        $this->setNotice('success', $isEdit ? 'Kategori berhasil diubah.' : 'Kategori berhasil ditambahkan.');
    }

    public function edit(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        if ($category->isDefault() || $category->user_id !== auth()->id()) {
            $this->setNotice('error', 'Kategori default atau milik user lain tidak bisa diubah.');

            return;
        }

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->type = $category->type->value;
        $this->icon = $category->icon;
        $this->color = $category->color;
        $this->notice = null;
        $this->resetValidation();
    }

    public function delete(int $categoryId, CategoryService $service): void
    {
        try {
            $service->delete(Category::findOrFail($categoryId));
        } catch (AuthorizationException $exception) {
            $this->setNotice('error', $exception->getMessage());

            return;
        } catch (\Exception $exception) {
            $this->setNotice('error', $exception->getMessage());

            return;
        }

        if ($this->editingId === $categoryId) {
            $this->resetForm();
        }

        $this->setNotice('success', 'Kategori berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'name',
            'type',
            'icon',
            'color',
            'editingId',
            'notice',
            'noticeType',
        ]);

        $this->type = 'expense';
        $this->icon = 'tag';
        $this->color = '#D9BC7C';
        $this->resetValidation();
    }

    private function setNotice(string $type, string $message): void
    {
        $this->noticeType = $type;
        $this->notice = $message;
    }

    public function render()
    {
        return view('livewire.categories.category-manager');
    }
}
