<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaynoExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithTitle
{
    private array $rows;
    private array $headings;
    private string $sheetTitle;

    public function __construct(array $rows, array $headings, string $sheetTitle)
    {
        $this->rows = $rows;
        $this->headings = array_values($headings);
        $this->sheetTitle = $sheetTitle;
    }

    public function collection(): Collection
    {
        return collect($this->rows)->map(function ($row): array {
            return array_values((array) $row);
        });
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr($this->sheetTitle, 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F28C00'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $lastColumn = $sheet->getHighestColumn();
                $lastRow = max(1, $sheet->getHighestRow());
                $range = 'A1:' . $lastColumn . $lastRow;

                // Автофильтр Excel по всем заголовкам и строкам.
                $sheet->setAutoFilter($range);

                // Закрепляем строку заголовков.
                $sheet->freezePane('A2');

                // Высота строки заголовков.
                $sheet->getRowDimension(1)->setRowHeight(32);

                // Перенос текста и выравнивание данных.
                if ($lastRow >= 2) {
                    $sheet->getStyle('A2:' . $lastColumn . $lastRow)
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }

                // Тонкие границы ячеек.
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D9D9D9'],
                        ],
                    ],
                ]);

                // Максимальная разумная ширина, чтобы длинные комментарии
                // не растягивали лист на чрезмерную ширину.
                foreach (range(1, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn)) as $columnIndex) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
                    $currentWidth = $sheet->getColumnDimension($columnLetter)->getWidth();

                    if ($currentWidth > 55) {
                        $sheet->getColumnDimension($columnLetter)->setWidth(55);
                    }
                }
            },
        ];
    }
}
