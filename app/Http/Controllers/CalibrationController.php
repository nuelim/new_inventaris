<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CalibrationSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CalibrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:calibration-list|calibration-create|calibration-edit|calibration-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:calibration-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:calibration-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:calibration-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $frequency = $request->get('frequency');
        $assignedTo = $request->get('assigned_to');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $vendor = $request->get('calibration_vendor');

        $calibrations = CalibrationSchedule::query()
            ->with(['asset.assetType', 'assignedTo', 'completedBy'])
            ->when($search, function ($query, $search) {
                return $query->where('title', 'like', "%{$search}%")
                           ->orWhereHas('asset', function ($q) use ($search) {
                               $q->where('name', 'like', "%{$search}%")
                                 ->orWhere('sku', 'like', "%{$search}%");
                           })
                           ->orWhere('calibration_vendor', 'like', "%{$search}%");
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($frequency, function ($query, $frequency) {
                return $query->byFrequency($frequency);
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
            ->when($vendor, function ($query, $vendor) {
                return $query->byVendor($vendor);
            })
            ->latest('scheduled_date')
            ->paginate(15);

        $statuses = ['scheduled' => 'Terjadwal', 'in_progress' => 'Sedang Dikerjakan', 'completed' => 'Selesai', 'overdue' => 'Terlambat', 'cancelled' => 'Dibatalkan'];
        $frequencies = ['monthly' => 'Bulanan', 'quarterly' => 'Kuartalan', 'semi_annual' => 'Semester', 'annual' => 'Tahunan', 'custom' => 'Kustom'];
        $technicians = User::active()->orderBy('name')->pluck('name', 'id');

        return view('calibration.index', compact('calibrations', 'statuses', 'frequencies', 'technicians', 'search', 'status', 'frequency', 'assignedTo', 'dateFrom', 'dateTo', 'vendor'));
    }

    public function create(): View
    {
        $assets = Asset::active()
            ->whereHas('assetType', function ($query) {
                $query->where('requires_calibration', true);
            })
            ->with('assetType')
            ->orderBy('name')
            ->get();

        $frequencies = ['monthly' => 'Bulanan', 'quarterly' => 'Kuartalan', 'semi_annual' => 'Semester', 'annual' => 'Tahunan', 'custom' => 'Kustom'];
        $technicians = User::active()->orderBy('name')->pluck('name', 'id');

        return view('calibration.create', compact('assets', 'frequencies', 'technicians'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'frequency' => 'required|in:monthly,quarterly,semi_annual,annual,custom',
            'custom_frequency_days' => 'required_if:frequency,custom|integer|min:1|max:365',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'due_date' => 'required|date|after_or_equal:scheduled_date',
            'assigned_to' => 'nullable|exists:users,id',
            'calibration_vendor' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0|max:999999999.99',
            'notes' => 'nullable|string|max:1000',
            'is_recurring' => 'boolean',
        ]);

        $validated['status'] = 'scheduled';
        $validated['is_recurring'] = $request->has('is_recurring');

        CalibrationSchedule::create($validated);

        return redirect()->route('calibration.index')
                        ->with('success', 'Jadwal kalibrasi berhasil ditambahkan.');
    }

    public function show(CalibrationSchedule $calibration): View
    {
        $calibration->load([
            'asset.assetType',
            'asset.location',
            'assignedTo',
            'completedBy'
        ]);

        return view('calibration.show', compact('calibration'));
    }

    public function edit(CalibrationSchedule $calibration): View
    {
        $this->authorize('update', $calibration);

        $assets = Asset::with('assetType')->orderBy('name')->get();
        $frequencies = ['monthly' => 'Bulanan', 'quarterly' => 'Kuartalan', 'semi_annual' => 'Semester', 'annual' => 'Tahunan', 'custom' => 'Kustom'];
        $technicians = User::active()->orderBy('name')->pluck('name', 'id');

        return view('calibration.edit', compact('calibration', 'assets', 'frequencies', 'technicians'));
    }

    public function update(Request $request, CalibrationSchedule $calibration): RedirectResponse
    {
        $this->authorize('update', $calibration);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'frequency' => 'required|in:monthly,quarterly,semi_annual,annual,custom',
            'custom_frequency_days' => 'required_if:frequency,custom|integer|min:1|max:365',
            'scheduled_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:scheduled_date',
            'assigned_to' => 'nullable|exists:users,id',
            'calibration_vendor' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0|max:999999999.99',
            'notes' => 'nullable|string|max:1000',
            'is_recurring' => 'boolean',
        ]);

        $validated['is_recurring'] = $request->has('is_recurring');

        $calibration->update($validated);

        return redirect()->route('calibration.show', $calibration)
                        ->with('success', 'Jadwal kalibrasi berhasil diperbarui.');
    }

    public function destroy(CalibrationSchedule $calibration): RedirectResponse
    {
        $this->authorize('delete', $calibration);

        $calibration->delete();

        return redirect()->route('calibration.index')
                        ->with('success', 'Jadwal kalibrasi berhasil dihapus.');
    }

    public function startWork(Request $request, CalibrationSchedule $calibration): RedirectResponse
    {
        $this->authorize('update', $calibration);

        if ($calibration->status !== 'scheduled') {
            return redirect()->route('calibration.show', $calibration)
                            ->with('error', 'Kalibrasi hanya dapat dimulai dari status terjadwal.');
        }

        $calibration->update([
            'status' => 'in_progress',
            'assigned_to' => auth()->id(),
        ]);

        return redirect()->route('calibration.show', $calibration)
                        ->with('success', 'Kalibrasi sedang dikerjakan.');
    }

    public function completeWork(Request $request, CalibrationSchedule $calibration): RedirectResponse
    {
        $this->authorize('update', $calibration);

        $validated = $request->validate([
            'completion_notes' => 'required|string|max:1000',
            'certificate_number' => 'nullable|string|max:255',
            'certificate_expiry' => 'nullable|date|after:today',
            'cost' => 'nullable|numeric|min:0|max:999999999.99',
        ]);

        if ($calibration->status !== 'in_progress') {
            return redirect()->route('calibration.show', $calibration)
                            ->with('error', 'Kalibrasi harus dalam status sedang dikerjakan.');
        }

        $calibration->markAsCompleted(
            auth()->id(),
            $validated['completion_notes'],
            $validated['certificate_number'] ?? null,
            $validated['certificate_expiry'] ?? null
        );

        if (isset($validated['cost'])) {
            $calibration->update(['cost' => $validated['cost']]);
        }

        return redirect()->route('calibration.show', $calibration)
                        ->with('success', 'Kalibrasi telah selesai.');
    }

    public function calendar(): View
    {
        $calibrations = CalibrationSchedule::with(['asset', 'assignedTo'])
            ->whereMonth('scheduled_date', now()->month)
            ->whereYear('scheduled_date', now()->year)
            ->orderBy('scheduled_date')
            ->get();

        return view('calibration.calendar', compact('calibrations'));
    }

    public function overdue(): View
    {
        $calibrations = CalibrationSchedule::overdue()
            ->with(['asset.assetType', 'assignedTo'])
            ->orderBy('due_date')
            ->paginate(15);

        return view('calibration.overdue', compact('calibrations'));
    }

    public function upcoming(): View
    {
        $calibrations = CalibrationSchedule::dueSoon(7)
            ->with(['asset.assetType', 'assignedTo'])
            ->orderBy('due_date')
            ->paginate(15);

        return view('calibration.upcoming', compact('calibrations'));
    }

    public function myTasks(): View
    {
        $calibrations = CalibrationSchedule::assignedTo(auth()->id())
            ->with(['asset.assetType'])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('due_date')
            ->paginate(15);

        return view('calibration.my-tasks', compact('calibrations'));
    }

    public function certificates(): View
    {
        $certificates = CalibrationSchedule::completed()
            ->with(['asset.assetType'])
            ->whereNotNull('certificate_number')
            ->whereNotNull('certificate_expiry')
            ->orderBy('certificate_expiry', 'desc')
            ->paginate(15);

        return view('calibration.certificates', compact('certificates'));
    }

    public function expiringCertificates(): View
    {
        $certificates = CalibrationSchedule::certificateExpiringSoon(30)
            ->with(['asset.assetType'])
            ->orderBy('certificate_expiry')
            ->paginate(15);

        return view('calibration.expiring-certificates', compact('certificates'));
    }

    public function report(): View
    {
        $stats = [
            'total' => CalibrationSchedule::count(),
            'completed' => CalibrationSchedule::completed()->count(),
            'in_progress' => CalibrationSchedule::inProgress()->count(),
            'overdue' => CalibrationSchedule::overdue()->count(),
            'scheduled' => CalibrationSchedule::scheduled()->count(),
            'certificates_valid' => CalibrationSchedule::completed()
                ->whereNotNull('certificate_number')
                ->whereNotNull('certificate_expiry')
                ->where('certificate_expiry', '>=', now())
                ->count(),
        ];

        $monthlyStats = CalibrationSchedule::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $frequencyStats = CalibrationSchedule::selectRaw('frequency, COUNT(*) as count')
            ->groupBy('frequency')
            ->pluck('count', 'frequency');

        $vendorStats = CalibrationSchedule::selectRaw('calibration_vendor, COUNT(*) as count')
            ->whereNotNull('calibration_vendor')
            ->groupBy('calibration_vendor')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'calibration_vendor');

        return view('calibration.report', compact('stats', 'monthlyStats', 'frequencyStats', 'vendorStats'));
    }
}