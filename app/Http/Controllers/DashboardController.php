<?php

namespace App\Http\Controllers;

use App\Models\MiningData;
use App\Models\ExcelUpload;
use App\Models\ActivityLog;
use App\Exports\MiningDataExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

/**
 * Dashboard Controller - PT Semen Padang
 * Main dashboard dengan data mining
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        // Filter parameters (USER ISOLATION applied)
        $filters = [
            'date_from' => $request->input('date_from', Carbon::now()->subDays(30)->format('Y-m-d')),
            'date_to' => $request->input('date_to', Carbon::now()->format('Y-m-d')),
            'shift' => $request->input('shift'),
            'front' => $request->input('front'),
            'commodity' => $request->input('commodity'),
            'excavator' => $request->input('excavator'),
        ];

        // Query builder for mining data (USER ISOLATION)
        $query = MiningData::where('user_id', $userId);

        // Apply filters
        if ($filters['date_from']) {
            $query->where('tanggal', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->where('tanggal', '<=', $filters['date_to']);
        }
        if ($filters['shift']) {
            $query->where('shift', $filters['shift']);
        }
        if ($filters['front']) {
            $query->where('front', $filters['front']);
        }
        if ($filters['commodity']) {
            $query->where('commodity', $filters['commodity']);
        }
        if ($filters['excavator']) {
            $query->where('excavator', $filters['excavator']);
        }

        // Get KPI statistics
        $totalRit = (clone $query)->sum('rit') ?? 0;
        $totalTonnase = (clone $query)->sum('tonnase') ?? 0;

        // Calculate unique hours for average calculations
        $uniqueHours = (clone $query)
            ->selectRaw('COUNT(DISTINCT CONCAT(tanggal, " ", HOUR(waktu))) as unique_hours')
            ->whereNotNull('waktu')
            ->value('unique_hours');

        // If no time data, count unique dates instead
        if (!$uniqueHours || $uniqueHours == 0) {
            $uniqueHours = (clone $query)
                ->selectRaw('COUNT(DISTINCT tanggal) as unique_days')
                ->value('unique_days');
        }

        // Final safety check: ensure $uniqueHours is never 0
        $uniqueHours = $uniqueHours > 0 ? $uniqueHours : 1;

        $stats = [
            'total_rit' => $totalRit,
            'total_tonnase' => round($totalTonnase, 2),
            'avg_rit_per_hour' => round($totalRit / $uniqueHours, 2),
            'avg_tonnase_per_hour' => round($totalTonnase / $uniqueHours, 2),
            'total_records' => (clone $query)->count(),
        ];

        // Chart data
        $chartData = $this->getChartData($userId, $filters);

        // Get mining data with pagination
        $miningData = $query->latest('tanggal')
            ->paginate(50);

        // Get filter options (only this user's data)
        $shifts = MiningData::where('user_id', $userId)
            ->distinct()
            ->pluck('shift')
            ->filter();

        $fronts = MiningData::where('user_id', $userId)
            ->distinct()
            ->pluck('front')
            ->filter();

        $commodities = MiningData::where('user_id', $userId)
            ->distinct()
            ->pluck('commodity')
            ->filter();

        $excavators = MiningData::where('user_id', $userId)
            ->distinct()
            ->pluck('excavator')
            ->filter();

        // Upload history - Recent 10 uploads (USER ISOLATION)
        $uploadHistory = ExcelUpload::where('user_id', $userId)
            ->where('status', 'completed')
            ->latest('created_at')
            ->take(10)
            ->get();

        // Recent activity logs
        $recentActivities = ActivityLog::where('user_id', $userId)
            ->latest('created_at')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'miningData',
            'stats',
            'filters',
            'shifts',
            'fronts',
            'commodities',
            'excavators',
            'uploadHistory',
            'recentActivities',
            'chartData'
        ));
    }

    /**
     * Get data untuk chart visualisasi
     * (USER ISOLATION applied)
     */
    private function getChartData($userId, $filters)
    {
        // Base query with filters
        $baseQuery = MiningData::where('user_id', $userId)
            ->whereBetween('tanggal', [
                $filters['date_from'],
                $filters['date_to']
            ]);

        if ($filters['shift']) {
            $baseQuery->where('shift', $filters['shift']);
        }
        if ($filters['front']) {
            $baseQuery->where('front', $filters['front']);
        }
        if ($filters['commodity']) {
            $baseQuery->where('commodity', $filters['commodity']);
        }
        if ($filters['excavator']) {
            $baseQuery->where('excavator', $filters['excavator']);
        }

        // Line Chart: Tonnase trend per tanggal
        $dateRange = (clone $baseQuery)
            ->selectRaw('tanggal, SUM(tonnase) as total_tonnase')
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->get();

        // Bar Chart: Produksi per excavator
        $excavatorData = (clone $baseQuery)
            ->select('excavator', DB::raw('SUM(tonnase) as total'))
            ->whereNotNull('excavator')
            ->groupBy('excavator')
            ->orderByDesc('total')
            ->take(10)
            ->get();

        // Pie Chart: Proporsi per front atau commodity
        $frontData = (clone $baseQuery)
            ->select('front', DB::raw('SUM(tonnase) as total'))
            ->whereNotNull('front')
            ->groupBy('front')
            ->orderByDesc('total')
            ->take(10)
            ->get();

        return [
            'line' => [
                'labels' => $dateRange->pluck('tanggal')->map(function ($date) {
                    return Carbon::parse($date)->format('d/m');
                })->toArray(),
                'data' => $dateRange->pluck('total_tonnase')->map(function ($value) {
                    return round($value ?? 0, 2);
                })->toArray(),
            ],
            'bar' => [
                'labels' => $excavatorData->pluck('excavator')->toArray(),
                'data' => $excavatorData->pluck('total')->map(function ($value) {
                    return round($value ?? 0, 2);
                })->toArray(),
            ],
            'pie' => [
                'labels' => $frontData->pluck('front')->toArray(),
                'data' => $frontData->pluck('total')->map(function ($value) {
                    return round($value ?? 0, 2);
                })->toArray(),
            ],
        ];
    }

    /**
     * Export filtered data to Excel
     */
    public function export(Request $request)
    {
        $userId = auth()->id();

        // Build query dengan filter yang sama seperti index
        $query = MiningData::where('user_id', $userId);

        // Apply filters
        if ($request->filled('date_from')) {
            $query->where('tanggal', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('tanggal', '<=', $request->date_to);
        }
        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }
        if ($request->filled('front')) {
            $query->where('front', $request->front);
        }
        if ($request->filled('commodity')) {
            $query->where('commodity', $request->commodity);
        }
        if ($request->filled('excavator')) {
            $query->where('excavator', $request->excavator);
        }

        // Order by tanggal
        $query->orderBy('tanggal', 'desc');

        // Generate filename
        $filename = 'MiningData_' . auth()->user()->name . '_' . now()->format('YmdHis') . '.xlsx';

        // Export
        return Excel::download(new MiningDataExport($query), $filename);
    }
}
