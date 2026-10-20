<?php

namespace App\Exports;

use App\Models\SchoolClass;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClassListSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return collect(
            SchoolClass::orderBy('grade')
                ->orderBy('major')
                ->orderBy('sequence')
                ->get()
        );
    }

    public function headings(): array
    {
        return ['Nama Kelas (salin persis ke kolom nama_kelas)', 'Tahun Ajaran'];
    }

    /**
     * @param mixed $class
     * @return array
     */
    public function map($class): array
    {
        return [
            $class->name ?? '',
            $class->academic_year ?? '',
        ];
    }

    public function title(): string
    {
        return 'Daftar Kelas';
    }
}