<?php

namespace App\Http\Controllers;

use App\Models\AssetType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AssetTypesImport;
use App\Exports\AssetTypesExport;

class AssetTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:asset-type-list|asset-type-create|asset-type-edit|asset-type-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:asset-type-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:asset-type-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:asset-type-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $search = $request->get('search');
        $category = $request->get('category');
        $status = $request->get('status');

        $assetTypes = AssetType::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                           ->orWhere('code', 'like', "%{$search}%")
                           ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($category, function ($query, $category) {
                return $query->where('category', $category);
            })
            ->when($status !== null, function ($query, $status) {
                return $query->where('is_active', $status);
            })
            ->with('assets')
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(10);

        $categories = AssetType::distinct()->pluck('category');

        return view('asset-types.index', compact('assetTypes', 'categories', 'search', 'category', 'status'));
    }

    public function create(): View
    {
        $categories = ['IT', 'Kendaraan', 'Bangunan', 'Mesin', 'Lainnya'];
        return view('asset-types.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:asset_types,code',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:IT,Kendaraan,Bangunan,Mesin,Lainnya',
            'depreciation_years' => 'required|integer|min:1|max:50',
            'requires_calibration' => 'boolean',
            'requires_maintenance' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['requires_calibration'] = $request->has('requires_calibration');
        $validated['requires_maintenance'] = $request->has('requires_maintenance');
        $validated['is_active'] = $request->has('is_active');

        AssetType::create($validated);

        return redirect()->route('asset-types.index')
                        ->with('success', 'Tipe aset berhasil ditambahkan.');
    }

    public function show(AssetType $assetType): View
    {
        $assetType->load('assets.location', 'assets.supplier');
        
        return view('asset-types.show', compact('assetType'));
    }

    public function edit(AssetType $assetType): View
    {
        $categories = ['IT', 'Kendaraan', 'Bangunan', 'Mesin', 'Lainnya'];
        return view('asset-types.edit', compact('assetType', 'categories'));
    }

    public function update(Request $request, AssetType $assetType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:asset_types,code,' . $assetType->id,
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:IT,Kendaraan,Bangunan,Mesin,Lainnya',
            'depreciation_years' => 'required|integer|min:1|max:50',
            'requires_calibration' => 'boolean',
            'requires_maintenance' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['requires_calibration'] = $request->has('requires_calibration');
        $validated['requires_maintenance'] = $request->has('requires_maintenance');
        $validated['is_active'] = $request->has('is_active');

        $assetType->update($validated);

        return redirect()->route('asset-types.index')
                        ->with('success', 'Tipe aset berhasil diperbarui.');
    }

    public function destroy(AssetType $assetType): RedirectResponse
    {
        if ($assetType->assets()->exists()) {
            return redirect()->route('asset-types.index')
                            ->with('error', 'Tipe aset tidak dapat dihapus karena masih digunakan oleh aset.');
        }

        $assetType->delete();

        return redirect()->route('asset-types.index')
                        ->with('success', 'Tipe aset berhasil dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new AssetTypesImport, $request->file('file'));
            return redirect()->route('asset-types.index')
                            ->with('success', 'Tipe aset berhasil diimpor.');
        } catch (\Exception $e) {
            return redirect()->route('asset-types.index')
                            ->with('error', 'Terjadi kesalahan saat mengimpor: ' . $e->getMessage());
        }
    }

    public function export(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new AssetTypesExport, 'asset-types-' . date('Y-m-d') . '.xlsx');
    }

    public function toggleStatus(AssetType $assetType): RedirectResponse
    {
        $assetType->update(['is_active' => !$assetType->is_active]);
        
        $status = $assetType->is_active ? 'diaktifkan' : 'dinonaktifkan';
        
        return redirect()->route('asset-types.index')
                        ->with('success', "Tipe aset berhasil {$status}.");
    }
}