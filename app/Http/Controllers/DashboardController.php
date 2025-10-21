<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Location;
use App\Models\MaintenanceSchedule;
use App\Models\CalibrationSchedule;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $dateRange = $request->get('date_range', '7');
        $locationId = $request->get('location_id');
        $assetTypeId = $request->get('asset_type_id');

        // KPI Cards
        $kpis = $this->getKPIs($locationId, $assetTypeId);

        // Charts Data
        $charts = $this->getChartData($dateRange, $locationId, $assetTypeId);

        // Recent Activities
        $recentActivities = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Upcoming Schedules
        $upcomingMaintenances = MaintenanceSchedule::dueSoon(7)
            ->with(['asset.assetType', 'assignedTo'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $upcomingCalibrations = CalibrationSchedule::dueSoon(7)
            ->with(['asset.assetType', 'assignedTo'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Overdue Items
        $overdueMaintenances = MaintenanceSchedule::overdue()
            ->with(['asset.assetType', 'assignedTo'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $overdueCalibrations = CalibrationSchedule::overdue()
            ->with(['asset.assetType', 'assignedTo'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Filter Options
        $locations = Location::active()->orderBy('name')->pluck('name', 'id');
        $assetTypes = AssetType::active()->orderBy('name')->pluck('name', 'id');

        return view('dashboard', compact(
            'kpis',
            'charts',
            'recentActivities',
            'upcomingMaintenances',
            'upcomingCalibrations',
            'overdueMaintenances',
            'overdueCalibrations',
            'locations',
            'assetTypes',
            'dateRange',
            'locationId',
            'assetTypeId'
        ));
    }

    private function getKPIs($locationId = null, $assetTypeId = null): array
    {
        $assetQuery = Asset::query();
        $maintenanceQuery = MaintenanceSchedule::query();
        $calibrationQuery = CalibrationSchedule::query();

        if ($locationId) {
            $assetQuery->where('location_id', $locationId);
            $maintenanceQuery->whereHas('asset', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
            $calibrationQuery->whereHas('asset', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }

        if ($assetTypeId) {
            $assetQuery->where('asset_type_id', $assetTypeId);
            $maintenanceQuery->whereHas('asset', function ($q) use ($assetTypeId) {
                $q->where('asset_type_id', $assetTypeId);
            });
            $calibrationQuery->whereHas('asset', function ($q) use ($assetTypeId) {
                $q->where('asset_type_id', $assetTypeId);
            });
        }

        return [
            'total_assets' => $assetQuery->count(),
            'active_assets' => $assetQuery->where('status', 'active')->count(),
            'total_value' => $assetQuery->sum('current_value'),
            'maintenance_overdue' => $maintenanceQuery->overdue()->count(),
            'calibration_overdue' => $calibrationQuery->overdue()->count(),
            'maintenance_due_soon' => $maintenanceQuery->dueSoon(7)->count(),
            'calibration_due_soon' => $calibrationQuery->dueSoon(7)->count(),
            'assets_under_warranty' => $assetQuery->where('warranty_expiry', '>=', now())->count(),
            'assets_warranty_expiring' => $assetQuery->warrantyExpiringSoon(30)->count(),
        ];
    }

    private function getChartData($dateRange, $locationId = null, $assetTypeId = null): array
    {
        // Asset Status Distribution
        $assetStatusQuery = Asset::query();
        if ($locationId) $assetStatusQuery->where('location_id', $locationId);
        if ($assetTypeId) $assetStatusQuery->where('asset_type_id', $assetTypeId);
        
        $assetStatusData = $assetStatusQuery
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Asset Type Distribution
        $assetTypeQuery = Asset::query();
        if ($locationId) $assetTypeQuery->where('location_id', $locationId);
        
        $assetTypeData = $assetTypeQuery
            ->join('asset_types', 'assets.asset_type_id', '=', 'asset_types.id')
            ->selectRaw('asset_types.name, COUNT(*) as count')
            ->groupBy('asset_types.id', 'asset_types.name')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'asset_types.name')
            ->toArray();

        // Location Distribution
        $locationQuery = Asset::query();
        if ($assetTypeId) $locationQuery->where('asset_type_id', $assetTypeId);
        
        $locationData = $locationQuery
            ->join('locations', 'assets.location_id', '=', 'locations.id')
            ->selectRaw('locations.name, COUNT(*) as count')
            ->groupBy('locations.id', 'locations.name')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'locations.name')
            ->toArray();

        // Maintenance Trend
        $days = (int) $dateRange;
        $maintenanceTrendData = MaintenanceSchedule::selectRaw('DATE(scheduled_date) as date, COUNT(*) as count')
            ->whereBetween('scheduled_date', [now()->subDays($days), now()->addDays($days)])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Calibration Trend
        $calibrationTrendData = CalibrationSchedule::selectRaw('DATE(scheduled_date) as date, COUNT(*) as count')
            ->whereBetween('scheduled_date', [now()->subDays($days), now()->addDays($days)])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Asset Value by Category
        $valueByCategoryQuery = Asset::query();
        if ($locationId) $valueByCategoryQuery->where('location_id', $locationId);
        if ($assetTypeId) $valueByCategoryQuery->where('asset_type_id', $assetTypeId);

        $valueByCategoryData = $valueByCategoryQuery
            ->join('asset_types', 'assets.asset_type_id', '=', 'asset_types.id')
            ->selectRaw('asset_types.category, SUM(assets.current_value) as total_value')
            ->groupBy('asset_types.category')
            ->orderByDesc('total_value')
            ->pluck('total_value', 'asset_types.category')
            ->toArray();

        // Asset Condition Distribution
        $conditionQuery = Asset::query();
        if ($locationId) $conditionQuery->where('location_id', $locationId);
        if ($assetTypeId) $conditionQuery->where('asset_type_id', $assetTypeId);

        $conditionData = $conditionQuery
            ->selectRaw('condition, COUNT(*) as count')
            ->groupBy('condition')
            ->pluck('count', 'condition')
            ->toArray();

        return [
            'asset_status' => $assetStatusData,
            'asset_types' => $assetTypeData,
            'locations' => $locationData,
            'maintenance_trend' => $maintenanceTrendData,
            'calibration_trend' => $calibrationTrendData,
            'value_by_category' => $valueByCategoryData,
            'asset_condition' => $conditionData,
        ];
    }

    public function reports(): View
    {
        return view('reports.index');
    }

    public function assetReport(Request $request): View
    {
        $assets = Asset::query()
            ->with(['assetType', 'location', 'supplier'])
            ->when($request->get('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->get('asset_type_id'), function ($query, $typeId) {
                $query->where('asset_type_id', $typeId);
            })
            ->when($request->get('location_id'), function ($query, $locationId) {
                $query->where('location_id', $locationId);
            })
            ->orderBy('sku')
            ->get();

        $summary = [
            'total_assets' => $assets->count(),
            'total_value' => $assets->sum('current_value'),
            'active_assets' => $assets->where('status', 'active')->count(),
            'assets_by_type' => $assets->groupBy('assetType.name')->map->count(),
            'assets_by_location' => $assets->groupBy('location.name')->map->count(),
            'assets_by_status' => $assets->groupBy('status')->map->count(),
        ];

        return view('reports.assets', compact('assets', 'summary'));
    }

    public function maintenanceReport(Request $request): View
    {
        $maintenances = MaintenanceSchedule::query()
            ->with(['asset.assetType', 'asset.location', 'assignedTo', 'completedBy'])
            ->when($request->get('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->get('type'), function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->get('date_from'), function ($query, $dateFrom) {
                $query->where('scheduled_date', '>=', $dateFrom);
            })
            ->when($request->get('date_to'), function ($query, $dateTo) {
                $query->where('scheduled_date', '<=', $dateTo);
            })
            ->orderBy('scheduled_date')
            ->get();

        $summary = [
            'total_maintenances' => $maintenances->count(),
            'completed_maintenances' => $maintenances->where('status', 'completed')->count(),
            'overdue_maintenances' => $maintenances->where('status', 'overdue')->count(),
            'total_cost' => $maintenances->sum('cost'),
            'maintenances_by_type' => $maintenances->groupBy('type')->map->count(),
            'maintenances_by_status' => $maintenances->groupBy('status')->map->count(),
        ];

        return view('reports.maintenance', compact('maintenances', 'summary'));
    }

    public function calibrationReport(Request $request): View
    {
        $calibrations = CalibrationSchedule::query()
            ->with(['asset.assetType', 'asset.location', 'assignedTo', 'completedBy'])
            ->when($request->get('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->get('frequency'), function ($query, $frequency) {
                $query->where('frequency', $frequency);
            })
            ->when($request->get('date_from'), function ($query, $dateFrom) {
                $query->where('scheduled_date', '>=', $dateFrom);
            })
            ->when($request->get('date_to'), function ($query, $dateTo) {
                $query->where('scheduled_date', '<=', $dateTo);
            })
            ->orderBy('scheduled_date')
            ->get();

        $summary = [
            'total_calibrations' => $calibrations->count(),
            'completed_calibrations' => $calibrations->where('status', 'completed')->count(),
            'overdue_calibrations' => $calibrations->where('status', 'overdue')->count(),
            'total_cost' => $calibrations->sum('cost'),
            'calibrations_by_frequency' => $calibrations->groupBy('frequency')->map->count(),
            'calibrations_by_status' => $calibrations->groupBy('status')->map->count(),
            'valid_certificates' => $calibrations->where('status', 'completed')
                ->whereNotNull('certificate_number')
                ->whereNotNull('certificate_expiry')
                ->where('certificate_expiry', '>=', now())
                ->count(),
        ];

        return view('reports.calibration', compact('calibrations', 'summary'));
    }

    public function valueReport(Request $request): View
    {
        $assets = Asset::query()
            ->with(['assetType', 'location'])
            ->when($request->get('location_id'), function ($query, $locationId) {
                $query->where('location_id', $locationId);
            })
            ->when($request->get('asset_type_id'), function ($query, $typeId) {
                $query->where('asset_type_id', $typeId);
            })
            ->orderBy('current_value', 'desc')
            ->get();

        $summary = [
            'total_assets' => $assets->count(),
            'total_purchase_value' => $assets->sum('purchase_price'),
            'total_current_value' => $assets->sum('current_value'),
            'total_depreciation' => $assets->sum('purchase_price') - $assets->sum('current_value'),
            'value_by_location' => $assets->groupBy('location.name')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_value' => $group->sum('current_value'),
                ];
            }),
            'value_by_category' => $assets->groupBy('assetType.category')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_value' => $group->sum('current_value'),
                ];
            }),
            'top_valuable_assets' => $assets->take(20),
        ];

        return view('reports.value', compact('assets', 'summary'));
    }

    public function exportAssetReport(Request $request)
    {
        // Implementation for exporting asset reports to PDF/Excel
        return response()->download('path/to/generated/report.xlsx');
    }

    public function exportMaintenanceReport(Request $request)
    {
        // Implementation for exporting maintenance reports to PDF/Excel
        return response()->download('path/to/generated/report.xlsx');
    }

    public function exportCalibrationReport(Request $request)
    {
        // Implementation for exporting calibration reports to PDF/Excel
        return response()->download('path/to/generated/report.xlsx');
    }
}