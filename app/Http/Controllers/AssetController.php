<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Location;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\Facades\DNS1DFacade;
use Milon\Barcode\Facades\DNS2DFacade;

class AssetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:asset-list|asset-create|asset-edit|asset-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:asset-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:asset-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:asset-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $search = $request->get('search');
        $assetType = $request->get('asset_type_id');
        $location = $request->get('location_id');
        $status = $request->get('status');
        $condition = $request->get('condition');
        $warranty = $request->get('warranty');

        $assets = Asset::query()
            ->with(['assetType', 'location', 'supplier'])
            ->when($search, function ($query, $search) {
                return $query->search($search);
            })
            ->when($assetType, function ($query, $assetType) {
                return $query->byType($assetType);
            })
            ->when($location, function ($query, $location) {
                return $query->byLocation($location);
            })
            ->when($status, function ($query, $status) {
                return $query->byStatus($status);
            })
            ->when($condition, function ($query, $condition) {
                return $query->byCondition($condition);
            })
            ->when($warranty === 'expiring', function ($query) {
                return $query->warrantyExpiringSoon(30);
            })
            ->when($warranty === 'expired', function ($query) {
                return $query->where('warranty_expiry', '<', now());
            })
            ->latest()
            ->paginate(15);

        $assetTypes = AssetType::active()->orderBy('name')->pluck('name', 'id');
        $locations = Location::active()->orderBy('name')->pluck('name', 'id');
        $statuses = ['active' => 'Aktif', 'inactive' => 'Tidak Aktif', 'maintenance' => 'Maintenance', 'calibration' => 'Kalibrasi', 'retired' => 'Retired', 'lost' => 'Hilang', 'damaged' => 'Rusak'];
        $conditions = ['excellent' => 'Excellent', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'];

        return view('assets.index', compact('assets', 'assetTypes', 'locations', 'statuses', 'conditions', 'search', 'assetType', 'location', 'status', 'condition', 'warranty'));
    }

    public function create(): View
    {
        $assetTypes = AssetType::active()->orderBy('name')->pluck('name', 'id');
        $locations = Location::active()->orderBy('name')->pluck('name', 'id');
        $suppliers = Supplier::active()->orderBy('name')->pluck('name', 'id');
        $conditions = ['excellent' => 'Excellent', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'];
        $statuses = ['active' => 'Aktif', 'inactive' => 'Tidak Aktif'];

        return view('assets.create', compact('assetTypes', 'locations', 'suppliers', 'conditions', 'statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'asset_type_id' => 'required|exists:asset_types,id',
            'location_id' => 'required|exists:locations,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100|unique:assets,serial_number',
            'part_number' => 'nullable|string|max:100',
            'purchase_price' => 'nullable|numeric|min:0|max:999999999999.99',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date|after:purchase_date',
            'condition' => 'required|in:excellent,good,fair,poor',
            'status' => 'required|in:active,inactive,maintenance,calibration,retired,lost,damaged',
            'current_value' => 'nullable|numeric|min:0|max:999999999999.99',
            'specifications' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['sku'] = 'AST-' . date('Y') . '-' . str_pad(Asset::max('id') + 1, 4, '0', STR_PAD_LEFT);
        $validated['created_by'] = auth()->id();

        if (!isset($validated['current_value']) && isset($validated['purchase_price'])) {
            $validated['current_value'] = $validated['purchase_price'];
        }

        $asset = Asset::create($validated);

        // Generate QR Code and Barcode
        $this->generateAssetCodes($asset);

        return redirect()->route('assets.show', $asset)
                        ->with('success', 'Aset berhasil ditambahkan.');
    }

    public function show(Asset $asset): View
    {
        $asset->load([
            'assetType',
            'location',
            'supplier',
            'createdBy',
            'maintenanceSchedules' => function ($query) {
                $query->with(['assignedTo', 'completedBy'])->latest();
            },
            'calibrationSchedules' => function ($query) {
                $query->with(['assignedTo', 'completedBy'])->latest();
            },
            'auditLogs' => function ($query) {
                $query->with('user')->latest()->limit(10);
            }
        ]);

        return view('assets.show', compact('asset'));
    }

    public function edit(Asset $asset): View
    {
        $assetTypes = AssetType::active()->orderBy('name')->pluck('name', 'id');
        $locations = Location::active()->orderBy('name')->pluck('name', 'id');
        $suppliers = Supplier::active()->orderBy('name')->pluck('name', 'id');
        $conditions = ['excellent' => 'Excellent', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'];
        $statuses = ['active' => 'Aktif', 'inactive' => 'Tidak Aktif', 'maintenance' => 'Maintenance', 'calibration' => 'Kalibrasi', 'retired' => 'Retired', 'lost' => 'Hilang', 'damaged' => 'Rusak'];

        return view('assets.edit', compact('asset', 'assetTypes', 'locations', 'suppliers', 'conditions', 'statuses'));
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'asset_type_id' => 'required|exists:asset_types,id',
            'location_id' => 'required|exists:locations,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100|unique:assets,serial_number,' . $asset->id,
            'part_number' => 'nullable|string|max:100',
            'purchase_price' => 'nullable|numeric|min:0|max:999999999999.99',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date|after:purchase_date',
            'condition' => 'required|in:excellent,good,fair,poor',
            'status' => 'required|in:active,inactive,maintenance,calibration,retired,lost,damaged',
            'current_value' => 'nullable|numeric|min:0|max:999999999999.99',
            'specifications' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        $asset->update($validated);

        return redirect()->route('assets.show', $asset)
                        ->with('success', 'Aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        // Check if asset has active schedules
        if ($asset->maintenanceSchedules()->where('status', '!=', 'completed')->exists() ||
            $asset->calibrationSchedules()->where('status', '!=', 'completed')->exists()) {
            return redirect()->route('assets.show', $asset)
                            ->with('error', 'Aset tidak dapat dihapus karena masih memiliki jadwal maintenance/kalibrasi yang aktif.');
        }

        $asset->delete();

        return redirect()->route('assets.index')
                        ->with('success', 'Aset berhasil dihapus.');
    }

    private function generateAssetCodes(Asset $asset): void
    {
        // Generate QR Code
        $qrCodeData = route('assets.show', $asset);
        $qrCode = QrCode::format('png')->size(200)->generate($qrCodeData);
        $qrPath = 'qrcodes/' . $asset->sku . '.png';
        Storage::disk('public')->put($qrPath, $qrCode);
        $asset->update(['qr_code' => $qrPath]);

        // Generate Barcode
        $barcode = DNS1DFacade::getBarcodePNG($asset->sku, 'C39', 2, 50);
        $barcodePath = 'barcodes/' . $asset->sku . '.png';
        Storage::disk('public')->put($barcodePath, base64_decode($barcode));
        $asset->update(['barcode' => $barcodePath]);
    }

    public function printQrCode(Asset $asset)
    {
        $qrCode = Storage::disk('public')->exists($asset->qr_code) 
            ? Storage::disk('public')->url($asset->qr_code)
            : QrCode::format('png')->size(200)->generate(route('assets.show', $asset));

        return view('assets.print-qr', compact('asset', 'qrCode'));
    }

    public function printBarcode(Asset $asset)
    {
        $barcode = Storage::disk('public')->exists($asset->barcode)
            ? Storage::disk('public')->url($asset->barcode)
            : 'data:image/png;base64,' . DNS1DFacade::getBarcodePNG($asset->sku, 'C39', 2, 50);

        return view('assets.print-barcode', compact('asset', 'barcode'));
    }

    public function changeStatus(Request $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,maintenance,calibration,retired,lost,damaged',
            'notes' => 'nullable|string|max:1000',
        ]);

        $asset->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $asset->notes,
        ]);

        return redirect()->route('assets.show', $asset)
                        ->with('success', 'Status aset berhasil diperbarui.');
    }

    public function transferLocation(Request $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $oldLocation = $asset->location->name;
        $asset->update([
            'location_id' => $validated['location_id'],
            'notes' => ($validated['notes'] ?? '') . "\n\n[Transfer dari: {$oldLocation} - " . now()->format('d/m/Y') . ']',
        ]);

        return redirect()->route('assets.show', $asset)
                        ->with('success', 'Aset berhasil dipindahkan lokasinya.');
    }
}