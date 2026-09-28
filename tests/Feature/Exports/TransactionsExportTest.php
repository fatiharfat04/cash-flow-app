<?php

use App\Exports\TransactionsExport;
use App\Livewire\Transactions\TransactionList;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelFormat;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Menjalankan export betulan lalu membaca hasilnya dengan PhpSpreadsheet,
 * supaya jumlah baris & baris TOTAL benar-benar terverifikasi (§10 Fase 8).
 */
function exportSheet($component): Worksheet
{
    return exportSheetFromContent(base64_decode($component->effects['download']['content']));
}

function exportSheetFromContent(string $content): Worksheet
{
    $path = sys_get_temp_dir().'/cash-flow-export-'.uniqid().'.xlsx';

    file_put_contents($path, $content);

    $sheet = IOFactory::load($path)->getActiveSheet();

    unlink($path);

    return $sheet;
}

function exportRows(Worksheet $sheet): array
{
    $rows = [];

    for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
        $value = $sheet->getCell('A'.$row)->getValue();

        if ($value === null || $value === '') {
            continue;
        }

        $rows[$row] = [
            'date' => (string) $value,
            'type' => (string) $sheet->getCell('B'.$row)->getValue(),
            'category' => (string) $sheet->getCell('C'.$row)->getValue(),
            'description' => (string) $sheet->getCell('D'.$row)->getValue(),
            'amount' => $sheet->getCell('E'.$row)->getValue(),
        ];
    }

    return $rows;
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->categories = seedDefaultCategories();
});

function exportTx(User $user, array $overrides = []): Transaction
{
    return Transaction::create(array_merge([
        'user_id' => $user->id,
        'category_id' => Category::whereNull('user_id')->where('type', 'expense')->firstOrFail()->id,
        'type' => 'expense',
        'amount' => 10000,
        'description' => 'Transaksi umum',
        'transaction_date' => today()->toDateString(),
    ], $overrides));
}

it('offers an excel download of the current filter', function () {
    exportTx($this->user);

    $component = Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->call('exportExcel')
        ->assertFileDownloaded();

    $name = $component->effects['download']['name'];

    expect(str_starts_with($name, 'transaksi-'))->toBeTrue()
        ->and(str_ends_with($name, '.xlsx'))->toBeTrue();
});

it('exports only the rows that match the active filter', function () {
    exportTx($this->user, ['amount' => 10000, 'description' => 'Hari ini satu']);
    exportTx($this->user, ['amount' => 20000, 'description' => 'Hari ini dua']);
    exportTx($this->user, ['amount' => 30000, 'description' => 'Hari ini tiga']);
    exportTx($this->user, ['amount' => 999999, 'description' => 'Minggu lalu', 'transaction_date' => today()->subWeek()->toDateString()]);

    $other = User::factory()->create();
    exportTx($other, ['amount' => 555555, 'description' => 'Milik orang lain']);

    $sheet = exportSheet(
        Livewire::actingAs($this->user)
            ->test(TransactionList::class)
            ->set('period', 'today')
            ->call('exportExcel')
    );

    $rows = exportRows($sheet);

    // baris 1 = judul, 4 baris data, 1 baris kosong, 1 baris TOTAL
    expect($rows)->toHaveCount(4)
        ->and(array_column($rows, 'description'))
        ->toContain('Hari ini satu', 'Hari ini dua', 'Hari ini tiga')
        ->not->toContain('Minggu lalu', 'Milik orang lain')
        ->and(array_column($rows, 'amount'))
        ->not->toContain(999999, 555555);
});

it('writes a heading row and rows shaped like the headings', function () {
    exportTx($this->user);

    $sheet = exportSheet(
        Livewire::actingAs($this->user)
            ->test(TransactionList::class)
            ->call('exportExcel')
    );

    expect($sheet->getCell('A1')->getValue())->toBe('Tanggal')
        ->and($sheet->getCell('B1')->getValue())->toBe('Tipe')
        ->and($sheet->getCell('C1')->getValue())->toBe('Kategori')
        ->and($sheet->getCell('D1')->getValue())->toBe('Deskripsi')
        ->and($sheet->getCell('E1')->getValue())->toBe('Jumlah');

    $rows = exportRows($sheet);

    expect($rows[2]['date'])->toBe(today()->toDateString())
        ->and($rows[2]['type'])->toBe('Pengeluaran')
        ->and($rows[2]['category'])->toBe($this->categories['expense']->name)
        ->and($rows[2]['amount'])->toBe(10000);
});

it('writes a final total row that matches the filtered summary', function () {
    exportTx($this->user, [
        'type' => 'income',
        'category_id' => $this->categories['income']->id,
        'amount' => 500000,
        'description' => 'Gaji',
    ]);
    exportTx($this->user, ['amount' => 200000, 'description' => 'Belanja']);
    exportTx($this->user, ['amount' => 50000, 'description' => 'Kopi', 'transaction_date' => today()->subWeek()->toDateString()]);

    $sheet = exportSheet(
        Livewire::actingAs($this->user)
            ->test(TransactionList::class)
            ->set('period', 'today')
            ->call('exportExcel')
    );

    $lastRow = $sheet->getHighestDataRow();

    // 1 judul + 2 data + 1 kosong + 1 TOTAL
    expect($lastRow)->toBe(5)
        ->and($sheet->getCell('A'.$lastRow)->getValue())->toBe('TOTAL')
        ->and($sheet->getCell('E'.$lastRow)->getValue())->toBe(300000)
        ->and($sheet->getCell('D'.$lastRow)->getValue())
        ->toContain('Pemasukan: Rp500.000')
        ->toContain('Pengeluaran: Rp200.000');
});

it('exports nothing but the heading and total when the filter has no rows', function () {
    $sheet = exportSheet(
        Livewire::actingAs($this->user)
            ->test(TransactionList::class)
            ->call('exportExcel')
    );

    expect($sheet->getHighestDataRow())->toBe(3)
        ->and($sheet->getCell('A3')->getValue())->toBe('TOTAL')
        ->and($sheet->getCell('E3')->getValue())->toBe(0);
});

it('keeps the export scoped to the current user', function () {
    $other = User::factory()->create();
    exportTx($other, ['amount' => 700000, 'description' => 'Milik orang lain']);
    exportTx($this->user, ['amount' => 45000, 'description' => 'Milik saya']);

    $sheet = exportSheet(
        Livewire::actingAs($this->user)
            ->test(TransactionList::class)
            ->call('exportExcel')
    );

    $descriptions = array_column(exportRows($sheet), 'description');

    expect($descriptions)->toContain('Milik saya')
        ->not->toContain('Milik orang lain')
        ->and($sheet->getCell('E'.$sheet->getHighestDataRow())->getValue())->toBe(-45000);
});

it('exports the excel file through the export class directly', function () {
    exportTx($this->user, ['amount' => 123456]);

    $response = \Maatwebsite\Excel\Facades\Excel::download(
        new TransactionsExport($this->user, app(\App\Services\TransactionService::class), ['period' => 'month']),
        'transaksi-uji.xlsx',
        ExcelFormat::XLSX
    );

    $sheet = IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

    expect($sheet->getHighestDataRow())->toBe(4)
        ->and((int) $sheet->getCell('E4')->getValue())->toBe(-123456);
});
