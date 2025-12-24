<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MiningData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * API Controller untuk Chart Data - PT Semen Padang
 * Semua endpoint menerapkan USER ISOLATION (hanya data user yang login)
 */
class ChartDataController extends Controller
{
    /**
     * Daily Production - 30 hari terakhir
     * GET /mining/api/daily-production
     */
    public function dailyProduction(Request $request)
    {
        $userId = auth()->id();
        $days = $request->input('days', 30);

        $data = MiningData::where('user_id', $userId)
            ->where('tanggal', '>=', Carbon::now()->subDays($days))
            ->select(
                'tanggal',
                DB::raw('SUM(tonnase) as total_tonnase'),
                DB::raw('SUM(volume_bcm) as total_volume'),
                DB::raw('SUM(rit) as total_rit'),
                DB::raw('COUNT(*) as total_records')
            )
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'period' => "{$days} hari terakhir",
            'user_id' => $userId,
        ]);
    }

    /**
     * Weekly Production - 12 minggu terakhir
     * GET /mining/api/weekly-production
     */
    public function weeklyProduction(Request $request)
    {
        $userId = auth()->id();
        $weeks = $request->input('weeks', 12);

        $data = MiningData::where('user_id', $userId)
            ->where('tanggal', '>=', Carbon::now()->subWeeks($weeks))
            ->select(
                DB::raw('YEARWEEK(tanggal, 1) as year_week'),
                DB::raw('MIN(tanggal) as week_start'),
                DB::raw('MAX(tanggal) as week_end'),
                DB::raw('SUM(tonnase) as total_tonnase'),
                DB::raw('SUM(volume_bcm) as total_volume'),
                DB::raw('SUM(rit) as total_rit'),
                DB::raw('SUM(fuel_usage) as total_fuel'),
                DB::raw('COUNT(*) as total_records')
            )
            ->groupBy('year_week')
            ->orderBy('year_week', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'period' => "{$weeks} minggu terakhir",
            'user_id' => $userId,
        ]);
    }

    /**
     * Monthly Production - 12 bulan terakhir
     * GET /mining/api/monthly-production
     */
    public function monthlyProduction(Request $request)
    {
        $userId = auth()->id();
        $months = $request->input('months', 12);

        $data = MiningData::where('user_id', $userId)
            ->where('tanggal', '>=', Carbon::now()->subMonths($months))
            ->select(
                DB::raw('YEAR(tanggal) as year'),
                DB::raw('MONTH(tanggal) as month'),
                DB::raw('DATE_FORMAT(tanggal, "%Y-%m") as year_month'),
                DB::raw('SUM(tonnase) as total_tonnase'),
                DB::raw('SUM(volume_bcm) as total_volume'),
                DB::raw('SUM(volume_lcm) as total_volume_lcm'),
                DB::raw('SUM(rit) as total_rit'),
                DB::raw('SUM(fuel_usage) as total_fuel'),
                DB::raw('AVG(tonnase) as avg_tonnase'),
                DB::raw('COUNT(*) as total_records')
            )
            ->groupBy('year', 'month', 'year_month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'period' => "{$months} bulan terakhir",
            'user_id' => $userId,
        ]);
    }

    /**
     * Equipment Statistics - Statistik per equipment
     * GET /mining/api/equipment-stats
     */
    public function equipmentStats(Request $request)
    {
        $userId = auth()->id();
        $dateFrom = $request->input('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));

        $data = MiningData::where('user_id', $userId)
            ->whereBetween('tanggal', [$dateFrom, $dateTo])
            ->whereNotNull('equipment_code')
            ->select(
                'equipment_type',
                'equipment_code',
                DB::raw('SUM(tonnase) as total_tonnase'),
                DB::raw('SUM(rit) as total_rit'),
                DB::raw('SUM(fuel_usage) as total_fuel'),
                DB::raw('SUM(jam_operasi) as total_jam_operasi'),
                DB::raw('SUM(jam_breakdown) as total_jam_breakdown'),
                DB::raw('AVG(tonnase) as avg_tonnase_per_trip'),
                DB::raw('COUNT(*) as total_records')
            )
            ->groupBy('equipment_type', 'equipment_code')
            ->orderBy('total_tonnase', 'desc')
            ->get();

        // Calculate efficiency metrics
        $data->each(function ($item) {
            $item->efficiency = $item->total_jam_operasi > 0
                ? round(($item->total_jam_operasi / ($item->total_jam_operasi + $item->total_jam_breakdown)) * 100, 2)
                : 0;

            $item->fuel_efficiency = $item->total_fuel > 0 && $item->total_tonnase > 0
                ? round($item->total_tonnase / $item->total_fuel, 2)
                : 0;
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'period' => "Dari {$dateFrom} hingga {$dateTo}",
            'user_id' => $userId,
        ]);
    }

    /**
     * Material Breakdown - Komposisi material
     * GET /mining/api/material-breakdown
     */
    public function materialBreakdown(Request $request)
    {
        $userId = auth()->id();
        $dateFrom = $request->input('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));

        // Material breakdown
        $materials = MiningData::where('user_id', $userId)
            ->whereBetween('tanggal', [$dateFrom, $dateTo])
            ->whereNotNull('material')
            ->select(
                'material',
                DB::raw('SUM(tonnase) as total_tonnase'),
                DB::raw('SUM(volume_bcm) as total_volume'),
                DB::raw('COUNT(*) as total_records')
            )
            ->groupBy('material')
            ->orderBy('total_tonnase', 'desc')
            ->get();

        // Lokasi breakdown
        $lokasi = MiningData::where('user_id', $userId)
            ->whereBetween('tanggal', [$dateFrom, $dateTo])
            ->whereNotNull('lokasi')
            ->select(
                'lokasi',
                DB::raw('SUM(tonnase) as total_tonnase'),
                DB::raw('SUM(volume_bcm) as total_volume'),
                DB::raw('COUNT(*) as total_records')
            )
            ->groupBy('lokasi')
            ->orderBy('total_tonnase', 'desc')
            ->get();

        // Shift breakdown
        $shifts = MiningData::where('user_id', $userId)
            ->whereBetween('tanggal', [$dateFrom, $dateTo])
            ->whereNotNull('shift')
            ->select(
                'shift',
                DB::raw('SUM(tonnase) as total_tonnase'),
                DB::raw('AVG(tonnase) as avg_tonnase'),
                DB::raw('COUNT(*) as total_records')
            )
            ->groupBy('shift')
            ->orderBy('shift', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'materials' => $materials,
                'lokasi' => $lokasi,
                'shifts' => $shifts,
            ],
            'period' => "Dari {$dateFrom} hingga {$dateTo}",
            'user_id' => $userId,
        ]);
    }

    /**
     * Dashboard Summary - KPI untuk gauge charts
     * GET /mining/api/dashboard-summary
     */
    public function dashboardSummary(Request $request)
    {
        $userId = auth()->id();
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));

        $summary = MiningData::where('user_id', $userId)
            ->whereBetween('tanggal', [$dateFrom, $dateTo])
            ->select(
                DB::raw('SUM(tonnase) as total_tonnase'),
                DB::raw('SUM(volume_bcm) as total_volume_bcm'),
                DB::raw('SUM(volume_lcm) as total_volume_lcm'),
                DB::raw('SUM(rit) as total_rit'),
                DB::raw('SUM(fuel_usage) as total_fuel'),
                DB::raw('COUNT(DISTINCT tanggal) as total_days'),
                DB::raw('COUNT(DISTINCT equipment_code) as total_equipment'),
                DB::raw('COUNT(*) as total_records')
            )
            ->first();

        // Calculate averages
        $avgPerDay = $summary->total_days > 0
            ? round($summary->total_tonnase / $summary->total_days, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_tonnase' => round($summary->total_tonnase ?? 0, 2),
                'total_volume_bcm' => round($summary->total_volume_bcm ?? 0, 2),
                'total_volume_lcm' => round($summary->total_volume_lcm ?? 0, 2),
                'total_rit' => $summary->total_rit ?? 0,
                'total_fuel' => round($summary->total_fuel ?? 0, 2),
                'total_days' => $summary->total_days ?? 0,
                'total_equipment' => $summary->total_equipment ?? 0,
                'total_records' => $summary->total_records ?? 0,
                'avg_tonnase_per_day' => $avgPerDay,
            ],
            'period' => "Dari {$dateFrom} hingga {$dateTo}",
            'user_id' => $userId,
        ]);
    }
}
