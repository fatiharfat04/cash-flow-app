<?php

namespace App\Exports;

use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithEvents, ShouldAutoSize
{
    protected array $summary = [
        'total_income' => 0,
        'total_expense' => 0,
        'balance' => 0,
    ];

    public function __construct(
        private readonly User $user,
        private readonly TransactionService $transactionService,
        private readonly array $filters = [],
    ) {
    }

    public function query()
    {
        $this->summary = $this->transactionService->getSummary($this->user, $this->filters);

        return $this->transactionService->getFiltered($this->user, $this->filters);
    }

    public function headings(): array
    {
        return ['Tanggal', 'Tipe', 'Kategori', 'Deskripsi', 'Jumlah'];
    }

    public function map($transaction): array
    {
        return [
            $transaction->transaction_date->format('Y-m-d'),
            $transaction->type->label(),
            $transaction->category?->name ?? '-',
            $transaction->description ?? '-',
            (int) $transaction->amount,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'E' => ['numberFormat' => ['formatCode' => '#,##0']],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $row = $sheet->getHighestRow() + 2;

                $sheet->setCellValue('A'.$row, 'TOTAL');
                $sheet->setCellValue(
                    'D'.$row,
                    'Pemasukan: Rp'.number_format($this->summary['total_income'], 0, ',', '.')
                    .' | Pengeluaran: Rp'.number_format($this->summary['total_expense'], 0, ',', '.')
                );
                $sheet->setCellValue('E'.$row, $this->summary['balance']);

                $sheet->getStyle('A'.$row.':E'.$row)->getFont()->setBold(true);
                $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }

    public function title(): string
    {
        return 'Transaksi';
    }
}
