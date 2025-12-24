<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class MiningDataExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * Query untuk data export
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Date',
            'Time',
            'Shift',
            'Blok',
            'Front',
            'Commodity',
            'Excavator',
            'Dump Truck',
            'Dump Loc',
            'Rit',
            'Tonnase',
            'Keterangan'
        ];
    }

    /**
     * Map data untuk setiap row
     */
    public function map($miningData): array
    {
        return [
            $miningData->tanggal ? Carbon::parse($miningData->tanggal)->format('d/m/Y') : '-',
            $miningData->waktu ? Carbon::parse($miningData->waktu)->format('H:i') : '-',
            $miningData->shift ?? '-',
            $miningData->blok ?? '-',
            $miningData->front ?? '-',
            $miningData->commodity ?? '-',
            $miningData->excavator ?? '-',
            $miningData->dump_truck ?? '-',
            $miningData->dump_loc ?? '-',
            $miningData->rit ?? 0,
            $miningData->tonnase ?? 0,
            $miningData->keterangan ?? '-'
        ];
    }

    /**
     * Style untuk header
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3D5A80']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }
}
