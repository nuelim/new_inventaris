<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SuppliersImport;
use App\Exports\SuppliersExport;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:supplier-list|supplier-create|supplier-edit|supplier-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:supplier-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:supplier-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:supplier-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $search = $request->get('search');
        $city = $request->get('city');
        $province = $request->get('province');
        $status = $request->get('status');

        $suppliers = Supplier::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                           ->orWhere('code', 'like', "%{$search}%")
                           ->orWhere('contact_person', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($city, function ($query, $city) {
                return $query->where('city', $city);
            })
            ->when($province, function ($query, $province) {
                return $query->where('province', $province);
            })
            ->when($status !== null, function ($query, $status) {
                return $query->where('is_active', $status);
            })
            ->withCount(['assets', 'assets as recent_assets_count' => function ($query) {
                $query->where('purchase_date', '>=', now()->subMonths(12));
            }])
            ->orderBy('name')
            ->paginate(10);

        $cities = Supplier::distinct()->pluck('city');
        $provinces = Supplier::distinct()->pluck('province');

        return view('suppliers.index', compact('suppliers', 'cities', 'provinces', 'search', 'city', 'province', 'status'));
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:suppliers,code',
            'description' => 'nullable|string|max:1000',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:suppliers,email',
            'phone' => 'required|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Supplier::create($validated);

        return redirect()->route('suppliers.index')
                        ->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['assets' => function ($query) {
            $query->with(['assetType', 'location'])->latest();
        }]);

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:suppliers,code,' . $supplier->id,
            'description' => 'nullable|string|max:1000',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:suppliers,email,' . $supplier->id,
            'phone' => 'required|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $supplier->update($validated);

        return redirect()->route('suppliers.index')
                        ->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->assets()->exists()) {
            return redirect()->route('suppliers.index')
                            ->with('error', 'Supplier tidak dapat dihapus karena masih digunakan oleh aset.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')
                        ->with('success', 'Supplier berhasil dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new SuppliersImport, $request->file('file'));
            return redirect()->route('suppliers.index')
                            ->with('success', 'Supplier berhasil diimpor.');
        } catch (\Exception $e) {
            return redirect()->route('suppliers.index')
                            ->with('error', 'Terjadi kesalahan saat mengimpor: ' . $e->getMessage());
        }
    }

    public function export(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new SuppliersExport, 'suppliers-' . date('Y-m-d') . '.xlsx');
    }

    public function toggleStatus(Supplier $supplier): RedirectResponse
    {
        $supplier->update(['is_active' => !$supplier->is_active]);
        
        $status = $supplier->is_active ? 'diaktifkan' : 'dinonaktifkan';
        
        return redirect()->route('suppliers.index')
                        ->with('success', "Supplier berhasil {$status}.");
    }
}