<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:maintenance-list|maintenance-create|maintenance-edit|maintenance-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:maintenance-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:maintenance-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:maintenance-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $type = $request->get('type');
        $assignedTo = $request->get('assigned_to');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $maintenances = MaintenanceSchedule::query()
            ->with(['asset.assetType', 'assignedTo', 'completedBy'])
            ->when($search, function ($query, $search) {
                return $query->where('title', 'like', "%{$search}%")
                           ->orWhereHas('asset', function ($q) use ($search) {
                               $q->where('name', 'like', "%{$search}%")
                                 ->orWhere('sku', 'like', "%{$search}%");
                           });
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($type, function ($query, $type) {
                return $query->byType($type);
            })
            ->when($assignedTo, function ($query, $assignedTo) {
                return $query->assignedTo($assignedTo);
            })
            ->when($dateFrom, function ($query, $dateFrom) {
                return $query->where('scheduled_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query, $dateTo) {
                return $query->where('scheduled_date', '<=', $dateTo);
            })
            ->latest('scheduled_date')
            ->paginate(15);

        $statuses = ['scheduled' => 'Terjadwal', 'in_progress' => 'Sedang Dikerjakan', 'completed' => 'Selesai', 'overdue' => 'Terlambat', 'cancelled' => 'Dibatalkan'];
        $types = ['routine' => 'Rutin', 'corrective' => 'Korektif', 'preventive' => 'Preventif', 'emergency' => 'Darurat'];
        $technicians = User::active()->orderBy('name')->pluck('name', 'id');

        return view('maintenance.index', compact('maintenances', 'statuses', 'types', 'technicians', 'search', 'status', 'type', 'assignedTo', 'dateFrom', 'dateTo'));
    }

    public function create(): View
    {
        $assets = Asset::active()->with('assetType')->orderBy('name')->get();
        $types = ['routine' => 'Rutin', 'corrective' => 'Korektif', 'preventive' => 'Preventif', 'emergency' => 'Darurat'];
        $frequencies = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'quarterly' => 'Kuartalan', 'semi_annual' => 'Semester', 'annual' => 'Tahunan', 'custom' => 'Kustom'];
        $technicians = User::active()->orderBy('name')->pluck('name', 'id');

        return view('maintenance.create', compact('assets', 'types', 'frequencies', 'technicians'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:routine,corrective,preventive,emergency',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,semi_annual,annual,custom',
            'custom_frequency_days' => 'required_if:frequency,custom|integer|min:1|max:365',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'due_date' => 'required|date|after_or_equal:scheduled_date',
            'assigned_to' => 'nullable|exists:users,id',
            'cost' => 'nullable|numeric|min:0|max:999999999.99',
            'notes' => 'nullable|string|max:1000',
            'is_recurring' => 'boolean',
        ]);

        $validated['status'] = 'scheduled';
        $validated['is_recurring'] = $request->has('is_recurring');

        MaintenanceSchedule::create($validated);

        return redirect()->route('maintenance.index')
                        ->with('success', 'Jadwal maintenance berhasil ditambahkan.');
    }

    public function show(MaintenanceSchedule $maintenance): View
    {
        $maintenance->load([
            'asset.assetType',
            'asset.location',
            'assignedTo',
            'completedBy'
        ]);

        return view('maintenance.show', compact('maintenance'));
    }

    public function edit(MaintenanceSchedule $maintenance): View
    {
        $this->authorize('update', $maintenance);

        $assets = Asset::with('assetType')->orderBy('name')->get();
        $types = ['routine' => 'Rutin', 'corrective' => 'Korektif', 'preventive' => 'Preventif', 'emergency' => 'Darurat'];
        $frequencies = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'quarterly' => 'Kuartalan', 'semi_annual' => 'Semester', 'annual' => 'Tahunan', 'custom' => 'Kustom'];
        $technicians = User::active()->orderBy('name')->pluck('name', 'id');

        return view('maintenance.edit', compact('maintenance', 'assets', 'types', 'frequencies', 'technicians'));
    }

    public function update(Request $request, MaintenanceSchedule $maintenance): RedirectResponse
    {
        $this->authorize('update', $maintenance);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:routine,corrective,preventive,emergency',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,semi_annual,annual,custom',
            'custom_frequency_days' => 'required_if:frequency,custom|integer|min:1|max:365',
            'scheduled_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:scheduled_date',
            'assigned_to' => 'nullable|exists:users,id',
            'cost' => 'nullable|numeric|min:0|max:999999999.99',
            'notes' => 'nullable|string|max:1000',
            'is_recurring' => 'boolean',
        ]);

        $validated['is_recurring'] = $request->has('is_recurring');

        $maintenance->update($validated);

        return redirect()->route('maintenance.show', $maintenance)
                        ->with('success', 'Jadwal maintenance berhasil diperbarui.');
    }

    public function destroy(MaintenanceSchedule $maintenance): RedirectResponse
    {
        $this->authorize('delete', $maintenance);

        $maintenance->delete();

        return redirect()->route('maintenance.index')
                        ->with('success', 'Jadwal maintenance berhasil dihapus.');
    }

    public function startWork(Request $request, MaintenanceSchedule $maintenance): RedirectResponse
    {
        $this->authorize('update', $maintenance);

        if ($maintenance->status !== 'scheduled') {
            return redirect()->route('maintenance.show', $maintenance)
                            ->with('error', 'Maintenance hanya dapat dimulai dari status terjadwal.');
        }

        $maintenance->update([
            'status' => 'in_progress',
            'assigned_to' => auth()->id(),
        ]);

        return redirect()->route('maintenance.show', $maintenance)
                        ->with('success', 'Maintenance sedang dikerjakan.');
    }

    public function completeWork(Request $request, MaintenanceSchedule $maintenance): RedirectResponse
    {
        $this->authorize('update', $maintenance);

        $validated = $request->validate([
            'completion_notes' => 'required|string|max:1000',
            'cost' => 'nullable|numeric|min:0|max:999999999.99',
        ]);

        if ($maintenance->status !== 'in_progress') {
            return redirect()->route('maintenance.show', $maintenance)
                            ->with('error', 'Maintenance harus dalam status sedang dikerjakan.');
        }

        $maintenance->markAsCompleted(auth()->id(), $validated['completion_notes']);

        if (isset($validated['cost'])) {
            $maintenance->update(['cost' => $validated['cost']]);
        }

        return redirect()->route('maintenance.show', $maintenance)
                        ->with('success', 'Maintenance telah selesai.');
    }

    public function calendar(): View
    {
        $maintenances = MaintenanceSchedule::with(['asset', 'assignedTo'])
            ->whereMonth('scheduled_date', now()->month)
            ->whereYear('scheduled_date', now()->year)
            ->orderBy('scheduled_date')
            ->get();

        return view('maintenance.calendar', compact('maintenances'));
    }

    public function overdue(): View
    {
        $maintenances = MaintenanceSchedule::overdue()
            ->with(['asset.assetType', 'assignedTo'])
            ->orderBy('due_date')
            ->paginate(15);

        return view('maintenance.overdue', compact('maintenances'));
    }

    public function upcoming(): View
    {
        $maintenances = MaintenanceSchedule::dueSoon(7)
            ->with(['asset.assetType', 'assignedTo'])
            ->orderBy('due_date')
            ->paginate(15);

        return view('maintenance.upcoming', compact('maintenances'));
    }

    public function myTasks(): View
    {
        $maintenances = MaintenanceSchedule::assignedTo(auth()->id())
            ->with(['asset.assetType'])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('due_date')
            ->paginate(15);

        return view('maintenance.my-tasks', compact('maintenances'));
    }

    public function report(): View
    {
        $stats = [
            'total' => MaintenanceSchedule::count(),
            'completed' => MaintenanceSchedule::completed()->count(),
            'in_progress' => MaintenanceSchedule::inProgress()->count(),
            'overdue' => MaintenanceSchedule::overdue()->count(),
            'scheduled' => MaintenanceSchedule::scheduled()->count(),
        ];

        $monthlyStats = MaintenanceSchedule::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $typeStats = MaintenanceSchedule::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        return view('maintenance.report', compact('stats', 'monthlyStats', 'typeStats'));
    }
}