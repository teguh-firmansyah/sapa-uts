<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TemplateSheet implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function array(): array
    {
        return [
            ['Ahmad Fadillah', 'ahmad.fadillah@sapa.sch.id', '2025001', 'X RPL 1', '081234567890', ''],
        ];
    }

    public function headings(): array
    {
        return ['nama', 'email', 'nisn', 'nama_kelas', 'no_telepon', 'password'];
    }

    public function title(): string
    {
        return 'Template';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => [
                    'italic' => true,
                    'color' => ['argb' => 'FF94A3B8'],
                ],
            ],
        ];
    }
}