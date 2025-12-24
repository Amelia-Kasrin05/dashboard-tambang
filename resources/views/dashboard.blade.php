@extends('layouts.dashboard')

@section('page-title', 'Dashboard Produksi')

@section('header-actions')
    <a href="{{ route('excel.upload.form') }}" class="btn btn-success">
        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
        </svg>
        Upload Excel
    </a>
@endsection

@section('content')

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy-accent) 100%);
        color: var(--white);
        padding: 24px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .stat-label {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .stat-unit {
        font-size: 14px;
        opacity: 0.8;
    }

    .filter-section {
        background: var(--white);
        padding: 24px;
        border-radius: 8px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group label {
        font-size: 14px;
        font-weight: 500;
        color: var(--text-dark);
    }

    .form-control {
        padding: 10px 12px;
        border: 1px solid var(--gray-medium);
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--navy-accent);
    }

    .table-responsive {
        overflow-x: auto;
        background: var(--white);
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: var(--navy-dark);
        color: var(--white);
    }

    th {
        padding: 14px 16px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--gray-medium);
        font-size: 14px;
    }

    tbody tr:hover {
        background: var(--gray-light);
    }

    .pagination {
        display: flex;
        gap: 8px;
        justify-content: center;
        margin-top: 20px;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid var(--gray-medium);
        border-radius: 4px;
        text-decoration: none;
        color: var(--text-dark);
    }

    .pagination .active {
        background: var(--navy-dark);
        color: var(--white);
        border-color: var(--navy-dark);
    }

    .badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-shift-1 {
        background: #e3f2fd;
        color: #1976d2;
    }

    .badge-shift-2 {
        background: #fff3e0;
        color: #f57c00;
    }

    .badge-shift-3 {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-light);
    }

    .empty-state svg {
        width: 80px;
        height: 80px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
</style>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Rit</div>
        <div class="stat-value">{{ number_format($stats['total_rit']) }}</div>
        <div class="stat-unit">Rit</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Total Tonnase</div>
        <div class="stat-value">{{ number_format($stats['total_tonnase']) }}</div>
        <div class="stat-unit">Ton</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Avg Rit/Jam</div>
        <div class="stat-value">{{ number_format($stats['avg_rit_per_hour'], 2) }}</div>
        <div class="stat-unit">Rit/Jam</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Avg Tonnase/Jam</div>
        <div class="stat-value">{{ number_format($stats['avg_tonnase_per_hour'], 2) }}</div>
        <div class="stat-unit">Ton/Jam</div>
    </div>
</div>

<!-- Filters -->
<div class="filter-section">
    <h3 style="margin-bottom: 20px; color: var(--navy-dark); font-size: 18px; font-weight: 600;">Filter Data</h3>
    <form method="GET" action="{{ route('dashboard') }}">
        <div class="filter-grid">
            <div class="form-group">
                <label for="date_from">Tanggal Dari</label>
                <input type="date" id="date_from" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
            </div>

            <div class="form-group">
                <label for="date_to">Tanggal Sampai</label>
                <input type="date" id="date_to" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
            </div>

            <div class="form-group">
                <label for="shift">Shift</label>
                <select id="shift" name="shift" class="form-control">
                    <option value="">Semua Shift</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift }}" {{ $filters['shift'] == $shift ? 'selected' : '' }}>
                            {{ $shift }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="front">Front</label>
                <select id="front" name="front" class="form-control">
                    <option value="">Semua Front</option>
                    @foreach($fronts as $front)
                        <option value="{{ $front }}" {{ $filters['front'] == $front ? 'selected' : '' }}>
                            {{ $front }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="commodity">Commodity</label>
                <select id="commodity" name="commodity" class="form-control">
                    <option value="">Semua Commodity</option>
                    @foreach($commodities as $commodity)
                        <option value="{{ $commodity }}" {{ $filters['commodity'] == $commodity ? 'selected' : '' }}>
                            {{ $commodity }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="excavator">Excavator</label>
                <select id="excavator" name="excavator" class="form-control">
                    <option value="">Semua Excavator</option>
                    @foreach($excavators as $excavator)
                        <option value="{{ $excavator }}" {{ $filters['excavator'] == $excavator ? 'selected' : '' }}>
                            {{ $excavator }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                Terapkan Filter
            </button>

            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                Reset Filter
            </a>

            <a href="{{ route('dashboard.export', request()->all()) }}" class="btn btn-success">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export Excel
            </a>
        </div>
    </form>
</div>

<!-- Charts Section -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-bottom: 24px;">
    <!-- Line Chart: Tonnase per Tanggal -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Trend Tonnase per Tanggal</h3>
        </div>
        <canvas id="lineChart" style="max-height: 300px;"></canvas>
    </div>

    <!-- Bar Chart: Produksi per Excavator -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Produksi per Excavator (Top 10)</h3>
        </div>
        <canvas id="barChart" style="max-height: 300px;"></canvas>
    </div>

    <!-- Pie Chart: Proporsi per Front -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Proporsi per Front (Top 10)</h3>
        </div>
        <canvas id="pieChart" style="max-height: 300px;"></canvas>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Data Produksi</h3>
    </div>

    @if($miningData->count() > 0)
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Shift</th>
                        <th>Blok</th>
                        <th>Front</th>
                        <th>Commodity</th>
                        <th>Excavator</th>
                        <th>Dump Truck</th>
                        <th>Dump Loc</th>
                        <th>Rit</th>
                        <th>Tonnase</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($miningData as $data)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($data->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ $data->waktu ? \Carbon\Carbon::parse($data->waktu)->format('H:i') : '-' }}</td>
                            <td>
                                @if($data->shift == '1')
                                    <span class="badge badge-shift-1">Shift 1</span>
                                @elseif($data->shift == '2')
                                    <span class="badge badge-shift-2">Shift 2</span>
                                @elseif($data->shift == '3')
                                    <span class="badge badge-shift-3">Shift 3</span>
                                @elseif($data->shift)
                                    <span class="badge">{{ $data->shift }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $data->blok ?? '-' }}</td>
                            <td>{{ $data->front ?? '-' }}</td>
                            <td>{{ $data->commodity ?? '-' }}</td>
                            <td>{{ $data->excavator ?? '-' }}</td>
                            <td>{{ $data->dump_truck ?? '-' }}</td>
                            <td>{{ $data->dump_loc ?? '-' }}</td>
                            <td>{{ $data->rit !== null ? number_format($data->rit) : '-' }}</td>
                            <td>{{ $data->tonnase !== null ? number_format($data->tonnase, 2) : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="text-align: center; padding: 40px;">
                                <p style="color: #6c757d; margin: 0;">Belum ada data mining. Silakan upload file Excel untuk memulai.</p>
                                <a href="{{ route('excel.upload.form') }}" class="btn btn-success" style="margin-top: 16px;">Upload Excel</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">
            {{ $miningData->links() }}
        </div>
    @else
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 style="margin-bottom: 8px;">Belum Ada Data</h3>
            <p>Silakan upload file Excel atau normalisasi data terlebih dahulu.</p>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Data dari controller
    const chartData = @json($chartData);

    // Line Chart: Tonnase per Tanggal
    const lineCtx = document.getElementById('lineChart').getContext('2d');
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: chartData.line.labels,
            datasets: [{
                label: 'Tonnase (Ton)',
                data: chartData.line.data,
                borderColor: 'rgb(61, 90, 128)',
                backgroundColor: 'rgba(61, 90, 128, 0.1)',
                tension: 0.3,
                fill: true,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Tonnase: ' + context.parsed.y.toLocaleString() + ' Ton';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Bar Chart: Produksi per Excavator
    const barCtx = document.getElementById('barChart').getContext('2d');

    // Generate colors dynamically
    const excavatorColors = chartData.bar.labels.map((_, index) => {
        const colors = [
            'rgba(25, 118, 210, 0.7)',
            'rgba(245, 124, 0, 0.7)',
            'rgba(123, 31, 162, 0.7)',
            'rgba(56, 142, 60, 0.7)',
            'rgba(211, 47, 47, 0.7)',
            'rgba(0, 151, 167, 0.7)',
            'rgba(251, 192, 45, 0.7)',
            'rgba(81, 45, 168, 0.7)',
            'rgba(194, 24, 91, 0.7)',
            'rgba(0, 121, 107, 0.7)'
        ];
        return colors[index % colors.length];
    });

    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: chartData.bar.labels,
            datasets: [{
                label: 'Tonnase per Excavator',
                data: chartData.bar.data,
                backgroundColor: excavatorColors,
                borderColor: excavatorColors.map(c => c.replace('0.7', '1')),
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Tonnase: ' + context.parsed.y.toLocaleString() + ' Ton';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Pie Chart: Proporsi per Front
    const pieCtx = document.getElementById('pieChart').getContext('2d');

    // Generate colors dynamically
    const frontColors = [
        '#1976d2', '#f57c00', '#7b1fa2', '#388e3c', '#d32f2f',
        '#0097a7', '#fbc02d', '#512da8', '#c2185b', '#00796b'
    ];

    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: chartData.pie.labels,
            datasets: [{
                label: 'Tonnase per Front',
                data: chartData.pie.data,
                backgroundColor: frontColors,
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 10,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + ': ' + value.toLocaleString() + ' Ton (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
</script>
@endpush
