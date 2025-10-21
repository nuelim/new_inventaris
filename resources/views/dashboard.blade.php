@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css" rel="stylesheet">
<style>
    .dashboard-card {
        transition: transform 0.2s;
    }
    .dashboard-card:hover {
        transform: translateY(-2px);
    }
    .chart-container {
        position: relative;
        height: 300px;
        margin-bottom: 20px;
    }
    .kpi-card {
        border-left: 4px solid;
    }
    .kpi-primary { border-left-color: #0d6efd; }
    .kpi-success { border-left-color: #198754; }
    .kpi-warning { border-left-color: #ffc107; }
    .kpi-danger { border-left-color: #dc3545; }
    .kpi-info { border-left-color: #0dcaf0; }
    .kpi-secondary { border-left-color: #6c757d; }
    .kpi-dark { border-left-color: #212529; }
    .kpi-light { border-left-color: #f8f9fa; }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-tachometer-alt me-2"></i>Dashboard Inventaris Aset</h1>
    <div>
        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
            <i class="fas fa-filter me-1"></i>Filter
        </button>
    </div>
</div>

<!-- Filters -->
<div class="collapse mb-4" id="filterCollapse">
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="date_range" class="form-label">Rentang Tanggal</label>
                        <select class="form-select" id="date_range" name="date_range">
                            <option value="7" {{ $dateRange == '7' ? 'selected' : '' }}>7 Hari</option>
                            <option value="30" {{ $dateRange == '30' ? 'selected' : '' }}>30 Hari</option>
                            <option value="90" {{ $dateRange == '90' ? 'selected' : '' }}>90 Hari</option>
                            <option value="365" {{ $dateRange == '365' ? 'selected' : '' }}>1 Tahun</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="location_id" class="form-label">Lokasi</label>
                        <select class="form-select" id="location_id" name="location_id">
                            <option value="">Semua Lokasi</option>
                            @foreach($locations as $id => $name)
                            <option value="{{ $id }}" {{ $locationId == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="asset_type_id" class="form-label">Tipe Aset</label>
                        <select class="form-select" id="asset_type_id" name="asset_type_id">
                            <option value="">Semua Tipe</option>
                            @foreach($assetTypes as $id => $name)
                            <option value="{{ $id }}" {{ $assetTypeId == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card kpi-card kpi-primary h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Total Aset</h6>
                        <h3 class="mb-0">{{ number_format($kpis['total_assets']) }}</h3>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-box fa-2x text-primary opacity-25"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted">{{ number_format($kpis['active_assets']) }} aktif</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card kpi-card kpi-success h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Total Nilai Aset</h6>
                        <h3 class="mb-0">Rp {{ number_format($kpis['total_value'], 0, ',', '.') }}</h3>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-rupiah-sign fa-2x text-success opacity-25"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted">Semua aset aktif</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card kpi-card kpi-warning h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Maintenance Terlambat</h6>
                        <h3 class="mb-0">{{ number_format($kpis['maintenance_overdue']) }}</h3>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-tools fa-2x text-warning opacity-25"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted">{{ number_format($kpis['maintenance_due_soon']) }} akan jatuh tempo</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card kpi-card kpi-danger h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Kalibrasi Terlambat</h6>
                        <h3 class="mb-0">{{ number_format($kpis['calibration_overdue']) }}</h3>
                    </div>
                    <div class="ms-3">
                        <i class="fas fa-balance-scale fa-2x text-danger opacity-25"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted">{{ number_format($kpis['calibration_due_soon']) }} akan jatuh tempo</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row mb-4">
    <div class="col-lg-6 mb-3">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Distribusi Status Aset</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="assetStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Aset per Kategori</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="assetTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row mb-4">
    <div class="col-lg-6 mb-3">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Distribusi Lokasi</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="locationChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Trend Maintenance</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="maintenanceTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alerts and Activities -->
<div class="row mb-4">
    <div class="col-lg-6 mb-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Peringatan</h5>
                <a href="{{ route('maintenance.overdue') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body">
                @if($overdueMaintenances->count() > 0 || $overdueCalibrations->count() > 0)
                    @if($overdueMaintenances->count() > 0)
                    <div class="alert alert-warning mb-2">
                        <h6><i class="fas fa-tools me-2"></i>Maintenance Terlambat</h6>
                        @foreach($overdueMaintenances->take(3) as $maintenance)
                        <div class="small mb-1">
                            <strong>{{ $maintenance->asset->name }}</strong> - {{ $maintenance->asset->sku }}
                            <br><span class="text-muted">{{ $maintenance->due_date->format('d/m/Y') }}</span>
                        </div>
                        @endforeach
                        @if($overdueMaintenances->count() > 3)
                        <div class="small text-muted">... dan {{ $overdueMaintenances->count() - 3 }} lagi</div>
                        @endif
                    </div>
                    @endif

                    @if($overdueCalibrations->count() > 0)
                    <div class="alert alert-danger mb-0">
                        <h6><i class="fas fa-balance-scale me-2"></i>Kalibrasi Terlambat</h6>
                        @foreach($overdueCalibrations->take(3) as $calibration)
                        <div class="small mb-1">
                            <strong>{{ $calibration->asset->name }}</strong> - {{ $calibration->asset->sku }}
                            <br><span class="text-muted">{{ $calibration->due_date->format('d/m/Y') }}</span>
                        </div>
                        @endforeach
                        @if($overdueCalibrations->count() > 3)
                        <div class="small text-muted">... dan {{ $overdueCalibrations->count() - 3 }} lagi</div>
                        @endif
                    </div>
                    @endif
                @else
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-check-circle fa-3x mb-2"></i>
                        <p>Tidak ada peringatan saat ini</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Aktivitas Terkini</h5>
                <a href="#" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @forelse($recentActivities as $activity)
                    <div class="list-group-item px-0">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="small">
                                    <strong>{{ $activity->user->name ?? 'System' }}</strong>
                                    {{ $activity->description ?? $activity->event }}
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    {{ $activity->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-history fa-3x mb-2"></i>
                        <p>Belum ada aktivitas</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('assets.create') }}" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="fas fa-plus fa-2x mb-2"></i>
                            Tambah Aset
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('maintenance.create') }}" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="fas fa-tools fa-2x mb-2"></i>
                            Jadwal Maintenance
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('calibration.create') }}" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="fas fa-balance-scale fa-2x mb-2"></i>
                            Jadwal Kalibrasi
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('reports.index') }}" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i>
                            Laporan
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="fas fa-list fa-2x mb-2"></i>
                            Daftar Aset
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('maintenance.calendar') }}" class="btn btn-outline-dark w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="fas fa-calendar fa-2x mb-2"></i>
                            Kalender
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
// Asset Status Chart
const assetStatusCtx = document.getElementById('assetStatusChart').getContext('2d');
new Chart(assetStatusCtx, {
    type: 'doughnut',
    data: {
        labels: @json(array_keys($charts['asset_status'])),
        datasets: [{
            data: @json(array_values($charts['asset_status'])),
            backgroundColor: [
                '#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d', '#0dcaf0'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Asset Type Chart
const assetTypeCtx = document.getElementById('assetTypeChart').getContext('2d');
new Chart(assetTypeCtx, {
    type: 'bar',
    data: {
        labels: @json(array_keys($charts['asset_types'])),
        datasets: [{
            label: 'Jumlah Aset',
            data: @json(array_values($charts['asset_types'])),
            backgroundColor: '#0d6efd'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Location Chart
const locationCtx = document.getElementById('locationChart').getContext('2d');
new Chart(locationCtx, {
    type: 'bar',
    data: {
        labels: @json(array_keys($charts['locations'])),
        datasets: [{
            label: 'Jumlah Aset',
            data: @json(array_values($charts['locations'])),
            backgroundColor: '#198754'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Maintenance Trend Chart
const maintenanceTrendCtx = document.getElementById('maintenanceTrendChart').getContext('2d');
new Chart(maintenanceTrendCtx, {
    type: 'line',
    data: {
        labels: @json(array_keys($charts['maintenance_trend'])),
        datasets: [{
            label: 'Maintenance',
            data: @json(array_values($charts['maintenance_trend'])),
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
@endsection