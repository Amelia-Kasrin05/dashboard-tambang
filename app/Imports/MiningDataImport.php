<?php

namespace App\Imports;

use App\Models\MiningData;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;

/**
 * Import Excel untuk Mining Data PT Semen Padang
 *
 * Format Excel yang diharapkan (dengan header row):
 * - tanggal, shift, lokasi, material, volume_bcm, tonnase, equipment_type, equipment_code, rit, fuel_usage, keterangan
 */
class MiningDataImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    protected $userId;
    protected $uploadId;

    public function __construct($userId, $uploadId)
    {
        $this->userId = $userId;
        $this->uploadId = $uploadId;
    }

    /**
     * Convert each row to MiningData model
     */
    public function model(array $row)
    {
        // Normalize keys to lowercase
        $row = array_change_key_case($row, CASE_LOWER);

        // Parse tanggal dari berbagai format
        $tanggal = $this->parseDate($row['tanggal'] ?? $row['date'] ?? null);

        // Skip jika tanggal tidak valid
        if (!$tanggal) {
            return null;
        }

        // Parse waktu (time)
        $waktu = $this->parseTime($row['time'] ?? null);

        return new MiningData([
            'user_id' => $this->userId,
            'upload_id' => $this->uploadId,
            'tanggal' => $tanggal,
            'waktu' => $waktu,
            'shift' => $row['shift'] ?? null,
            'blok' => $row['blok'] ?? null,
            'front' => $row['front'] ?? null,
            'commodity' => $row['commodity'] ?? null,
            'excavator' => $row['excavator'] ?? null,
            'dump_truck' => $row['dump_truck'] ?? null,
            'dump_loc' => $row['dump_loc'] ?? null,
            'rit' => $this->parseInt($row['rit'] ?? null),
            'tonnase' => $this->parseDecimal($row['tonnase'] ?? null),
            'keterangan' => $row['keterangan'] ?? null,
        ]);
    }

    /**
     * Parse date dari berbagai format
     */
    protected function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Jika sudah format Y-m-d
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value;
            }

            // Excel serial number
            if (is_numeric($value) && $value > 0) {
                $days = (int)$value;
                if ($days > 60) {
                    $days--; // Excel leap year bug
                }
                return Carbon::create(1900, 1, 1)->addDays($days - 1)->format('Y-m-d');
            }

            // Parse berbagai format tanggal
            $formats = [
                'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d',
                'd/m/y', 'd-m-y', 'Y-m-d H:i:s', 'd/m/Y H:i:s',
            ];

            foreach ($formats as $format) {
                try {
                    $parsed = Carbon::createFromFormat($format, trim($value));
                    if ($parsed) {
                        return $parsed->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Fallback: parse otomatis
            return Carbon::parse($value)->format('Y-m-d');

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse time dari berbagai format
     */
    protected function parseTime($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Format: 07:00-08:00 atau 07:00–08:00
            if (str_contains($value, '-') || str_contains($value, '–')) {
                $parts = preg_split('/[-–]/', $value);
                $value = trim($parts[0]); // Ambil waktu mulai
            }

            // Jika sudah format H:i
            if (preg_match('/^\d{1,2}:\d{2}/', $value)) {
                return Carbon::createFromFormat('H:i', trim($value))->format('H:i:s');
            }

            // Parse otomatis
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse decimal number
     */
    protected function parseDecimal($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // Remove non-numeric characters except dot and comma
        $cleaned = preg_replace('/[^\d.,]/', '', $value);

        // Replace comma with dot
        $cleaned = str_replace(',', '.', $cleaned);

        return is_numeric($cleaned) ? (float)$cleaned : null;
    }

    /**
     * Parse integer
     */
    protected function parseInt($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        $cleaned = preg_replace('/[^\d]/', '', $value);
        return $cleaned !== '' ? (int)$cleaned : null;
    }

    /**
     * Batch insert untuk performa
     */
    public function batchSize(): int
    {
        return 500;
    }

    /**
     * Chunk reading untuk file besar
     */
    public function chunkSize(): int
    {
        return 500;
    }
}
