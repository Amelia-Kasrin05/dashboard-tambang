<?php

namespace App\Console\Commands;

use App\Models\Production;
use App\Models\ProductionRaw;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NormalizeProductionData extends Command
{
    protected $signature = 'production:normalize {--limit=100 : Number of records to process}';
    protected $description = 'Normalize production data from raw to productions table';

    public function handle()
    {
        $limit = $this->option('limit');

        $this->info("Starting normalization process (limit: {$limit})...");

        $rawData = ProductionRaw::whereNotNull('date')
            ->whereNotNull('time')
            ->limit($limit)
            ->get();

        $this->info("Found {$rawData->count()} records to process");

        $normalized = 0;
        $skipped = 0;
        $errors = [];

        $bar = $this->output->createProgressBar($rawData->count());
        $bar->start();

        foreach ($rawData as $raw) {
            try {
                $date = $this->parseDate($raw->date);

                if (!$date) {
                    $errors[] = "Row {$raw->id}: Invalid date '{$raw->date}'";
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Check duplicate
                $exists = Production::where('date', $date)
                    ->where('time', $raw->time)
                    ->where('shift', $raw->shift ?? '-')
                    ->where('excavator', $raw->excavator ?? '-')
                    ->exists();

                if ($exists) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                Production::create([
                    'date' => $date,
                    'time' => $raw->time ?? '00:00',
                    'shift' => $raw->shift ?? '-',
                    'blok' => $raw->blok,
                    'front' => $raw->front ?? '-',
                    'commodity' => $raw->commodity ?? '-',
                    'excavator' => $raw->excavator ?? '-',
                    'dump_truck' => (int)($raw->dump_truck ?? 0),
                    'dump_loc' => $raw->dump_loc ?? '-',
                    'rit' => (int)($raw->rit ?? 0),
                    'tonnase' => (int)($raw->tonnase ?? 0),
                ]);

                $normalized++;

            } catch (\Exception $e) {
                $errors[] = "Row {$raw->id}: {$e->getMessage()}";
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Normalized: {$normalized}");
        $this->warn("⊗ Skipped: {$skipped}");

        if (count($errors) > 0 && count($errors) <= 10) {
            $this->newLine();
            $this->error("Errors:");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        } elseif (count($errors) > 10) {
            $this->newLine();
            $this->error("Too many errors (" . count($errors) . "). Showing first 10:");
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->line("  - {$error}");
            }
        }

        return 0;
    }

    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        $dateString = trim($dateString);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return $dateString;
        }

        try {
            if (is_numeric($dateString) && $dateString > 0) {
                $days = (int)$dateString;
                if ($days > 60) {
                    $days--;
                }
                return Carbon::create(1900, 1, 1)->addDays($days - 1)->format('Y-m-d');
            }

            $formats = ['d/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd/m/y', 'd-m-y', 'Y-m-d H:i:s', 'd/m/Y H:i:s'];

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

            return Carbon::parse($dateString)->format('Y-m-d');

        } catch (\Exception $e) {
            return null;
        }
    }
}
