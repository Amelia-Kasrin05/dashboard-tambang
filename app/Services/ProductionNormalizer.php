<?php

namespace App\Services;

use App\Models\Production;
use App\Models\ProductionRaw;
use App\Models\ProductionUpload;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProductionNormalizer
{
    /**
     * Normalisasi data dari productions_raw ke productions
     */
    public function normalize($uploadBatch = null)
    {
        $query = ProductionRaw::whereNotNull('date')
            ->whereNotNull('time');

        if ($uploadBatch) {
            $query->where('upload_batch', $uploadBatch);
        }

        $rawData = $query->get();

        $normalized = 0;
        $skipped = 0;

        foreach ($rawData as $raw) {
            try {
                // Parse date
                $date = $this->parseDate($raw->date);

                if (!$date) {
                    $skipped++;
                    continue;
                }

                // Clean data
                $cleanedData = $this->cleanData($raw, $date);

                // Cek duplikasi - gunakan whereNull untuk nilai null
                $query = Production::where('date', $cleanedData['date']);

                // Check time
                if ($cleanedData['time'] === null) {
                    $query->whereNull('time');
                } else {
                    $query->where('time', $cleanedData['time']);
                }

                // Check shift
                if ($cleanedData['shift'] === null) {
                    $query->whereNull('shift');
                } else {
                    $query->where('shift', $cleanedData['shift']);
                }

                // Check excavator
                if ($cleanedData['excavator'] === null) {
                    $query->whereNull('excavator');
                } else {
                    $query->where('excavator', $cleanedData['excavator']);
                }

                $exists = $query->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                Production::create($cleanedData);
                $normalized++;

            } catch (\Exception $e) {
                Log::error("Normalization error for raw ID {$raw->id}: " . $e->getMessage());
                $skipped++;
                continue;
            }
        }

        // Update production_uploads table
        if ($uploadBatch) {
            ProductionUpload::where('upload_batch', $uploadBatch)
                ->increment('normalized_rows', $normalized);
        }

        return [
            'normalized' => $normalized,
            'skipped' => $skipped,
            'total' => $rawData->count(),
        ];
    }

    /**
     * Clean dan validasi semua data
     */
    private function cleanData(ProductionRaw $raw, string $date): array
    {
        return [
            'date' => $date,
            'time' => $this->cleanString($raw->time),
            'shift' => $this->cleanString($raw->shift),
            'blok' => $this->cleanString($raw->blok),
            'front' => $this->cleanString($raw->front),
            'commodity' => $this->cleanString($raw->commodity),
            'excavator' => $this->cleanString($raw->excavator),
            'dump_truck' => $this->cleanInt($raw->dump_truck),
            'dump_loc' => $this->cleanString($raw->dump_loc),
            'rit' => $this->cleanInt($raw->rit),
            'tonnase' => $this->cleanInt($raw->tonnase),
        ];
    }

    /**
     * Helper: Clean string - remove whitespace, null handling
     */
    private function cleanString($value): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // Trim whitespace
        $cleaned = trim($value);

        // Remove excessive spaces
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);

        // Handle "null", "NULL", "-", "n/a" as null
        if (in_array(strtolower($cleaned), ['null', 'n/a', 'na', '-', ''])) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Helper: Clean integer - extract numeric value
     */
    private function cleanInt($value): ?int
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // If already integer
        if (is_int($value)) {
            return $value;
        }

        // Extract numeric part from string
        $cleaned = preg_replace('/[^0-9.]/', '', $value);

        // Return null if no numeric value found
        if ($cleaned === '' || $cleaned === '.') {
            return null;
        }

        return (int) $cleaned;
    }

    /**
     * Parse berbagai format tanggal dari Excel
     */
    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        // Trim whitespace
        $dateString = trim($dateString);

        // Jika sudah format Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return $dateString;
        }

        // Coba parse dengan Carbon (support banyak format)
        try {
            // Excel serial number (numeric)
            if (is_numeric($dateString) && $dateString > 0) {
                // Excel date epoch dimulai dari 1900-01-01
                // Tapi ada bug di Excel yang menghitung 1900 sebagai tahun kabisat
                $days = (int)$dateString;

                if ($days > 60) {
                    // Setelah Feb 29, 1900 (bug Excel)
                    $days--;
                }

                return Carbon::create(1900, 1, 1)
                    ->addDays($days - 1)
                    ->format('Y-m-d');
            }

            // Format lainnya
            $formats = [
                'd/m/Y',
                'd-m-Y',
                'm/d/Y',
                'Y/m/d',
                'd/m/y',
                'd-m-y',
                'Y-m-d H:i:s',
                'd/m/Y H:i:s',
            ];

            foreach ($formats as $format) {
                try {
                    $parsed = Carbon::createFromFormat($format, $dateString);
                    if ($parsed) {
                        return $parsed->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Fallback: parse otomatis
            $parsed = Carbon::parse($dateString);
            if ($parsed) {
                return $parsed->format('Y-m-d');
            }

        } catch (\Exception $e) {
            // Log error untuk debugging
            Log::warning("Failed to parse date: {$dateString}", ['error' => $e->getMessage()]);
        }

        return null;
    }
}
