<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\LocationsImport;
use App\Exports\LocationsExport;

class LocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:location-list|location-create|location-edit|location-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:location-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:location-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:location-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $search = $request->get('search');
        $city = $request->get('city');
        $province = $request->get('province');
        $status = $request->get('status');

        $locations = Location::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                           ->orWhere('code', 'like', "%{$search}%")
                           ->orWhere('building', 'like', "%{$search}%")
                           ->orWhere('address', 'like', "%{$search}%");
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
            ->withCount(['assets', 'assets as active_assets_count' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('province')
            ->orderBy('city')
            ->orderBy('name')
            ->paginate(10);

        $cities = Location::distinct()->pluck('city');
        $provinces = Location::distinct()->pluck('province');

        return view('locations.index', compact('locations', 'cities', 'provinces', 'search', 'city', 'province', 'status'));
    }

    public function create(): View
    {
        return view('locations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:locations,code',
            'description' => 'nullable|string|max:1000',
            'building' => 'required|string|max:255',
            'floor' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:100',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'manager_name' => 'nullable|string|max:255',
            'manager_email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Location::create($validated);

        return redirect()->route('locations.index')
                        ->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function show(Location $location): View
    {
        $location->load(['assets' => function ($query) {
            $query->with(['assetType', 'supplier'])->latest();
        }]);

        return view('locations.show', compact('location'));
    }

    public function edit(Location $location): View
    {
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:locations,code,' . $location->id,
            'description' => 'nullable|string|max:1000',
            'building' => 'required|string|max:255',
            'floor' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:100',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'manager_name' => 'nullable|string|max:255',
            'manager_email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $location->update($validated);

        return redirect()->route('locations.index')
                        ->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        if ($location->assets()->exists()) {
            return redirect()->route('locations.index')
                            ->with('error', 'Lokasi tidak dapat dihapus karena masih digunakan oleh aset.');
        }

        $location->delete();

        return redirect()->route('locations.index')
                        ->with('success', 'Lokasi berhasil dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new LocationsImport, $request->file('file'));
            return redirect()->route('locations.index')
                            ->with('success', 'Lokasi berhasil diimpor.');
        } catch (\Exception $e) {
            return redirect()->route('locations.index')
                            ->with('error', 'Terjadi kesalahan saat mengimpor: ' . $e->getMessage());
        }
    }

    public function export(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new LocationsExport, 'locations-' . date('Y-m-d') . '.xlsx');
    }

    public function toggleStatus(Location $location): RedirectResponse
    {
        $location->update(['is_active' => !$location->is_active]);
        
        $status = $location->is_active ? 'diaktifkan' : 'dinonaktifkan';
        
        return redirect()->route('locations.index')
                        ->with('success', "Lokasi berhasil {$status}.");
    }
}