<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    // Master Data Routes
    Route::resource('asset-types', App\Http\Controllers\AssetTypeController::class);
    Route::post('asset-types/import', [App\Http\Controllers\AssetTypeController::class, 'import'])->name('asset-types.import');
    Route::get('asset-types/export', [App\Http\Controllers\AssetTypeController::class, 'export'])->name('asset-types.export');
    Route::patch('asset-types/{asset_type}/toggle-status', [App\Http\Controllers\AssetTypeController::class, 'toggleStatus'])->name('asset-types.toggle-status');
    
    Route::resource('locations', App\Http\Controllers\LocationController::class);
    Route::post('locations/import', [App\Http\Controllers\LocationController::class, 'import'])->name('locations.import');
    Route::get('locations/export', [App\Http\Controllers\LocationController::class, 'export'])->name('locations.export');
    Route::patch('locations/{location}/toggle-status', [App\Http\Controllers\LocationController::class, 'toggleStatus'])->name('locations.toggle-status');
    
    Route::resource('suppliers', App\Http\Controllers\SupplierController::class);
    Route::post('suppliers/import', [App\Http\Controllers\SupplierController::class, 'import'])->name('suppliers.import');
    Route::get('suppliers/export', [App\Http\Controllers\SupplierController::class, 'export'])->name('suppliers.export');
    Route::patch('suppliers/{supplier}/toggle-status', [App\Http\Controllers\SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');
    
    // Asset Management Routes
    Route::resource('assets', App\Http\Controllers\AssetController::class);
    Route::get('assets/{asset}/print-qr', [App\Http\Controllers\AssetController::class, 'printQrCode'])->name('assets.print-qr');
    Route::get('assets/{asset}/print-barcode', [App\Http\Controllers\AssetController::class, 'printBarcode'])->name('assets.print-barcode');
    Route::patch('assets/{asset}/change-status', [App\Http\Controllers\AssetController::class, 'changeStatus'])->name('assets.change-status');
    Route::patch('assets/{asset}/transfer-location', [App\Http\Controllers\AssetController::class, 'transferLocation'])->name('assets.transfer-location');
    
    // Maintenance Routes
    Route::resource('maintenance', App\Http\Controllers\MaintenanceController::class);
    Route::patch('maintenance/{maintenance}/start-work', [App\Http\Controllers\MaintenanceController::class, 'startWork'])->name('maintenance.start-work');
    Route::patch('maintenance/{maintenance}/complete-work', [App\Http\Controllers\MaintenanceController::class, 'completeWork'])->name('maintenance.complete-work');
    Route::get('maintenance/calendar', [App\Http\Controllers\MaintenanceController::class, 'calendar'])->name('maintenance.calendar');
    Route::get('maintenance/overdue', [App\Http\Controllers\MaintenanceController::class, 'overdue'])->name('maintenance.overdue');
    Route::get('maintenance/upcoming', [App\Http\Controllers\MaintenanceController::class, 'upcoming'])->name('maintenance.upcoming');
    Route::get('maintenance/my-tasks', [App\Http\Controllers\MaintenanceController::class, 'myTasks'])->name('maintenance.my-tasks');
    Route::get('maintenance/report', [App\Http\Controllers\MaintenanceController::class, 'report'])->name('maintenance.report');
    
    // Calibration Routes
    Route::resource('calibration', App\Http\Controllers\CalibrationController::class);
    Route::patch('calibration/{calibration}/start-work', [App\Http\Controllers\CalibrationController::class, 'startWork'])->name('calibration.start-work');
    Route::patch('calibration/{calibration}/complete-work', [App\Http\Controllers\CalibrationController::class, 'completeWork'])->name('calibration.complete-work');
    Route::get('calibration/calendar', [App\Http\Controllers\CalibrationController::class, 'calendar'])->name('calibration.calendar');
    Route::get('calibration/overdue', [App\Http\Controllers\CalibrationController::class, 'overdue'])->name('calibration.overdue');
    Route::get('calibration/upcoming', [App\Http\Controllers\CalibrationController::class, 'upcoming'])->name('calibration.upcoming');
    Route::get('calibration/my-tasks', [App\Http\Controllers\CalibrationController::class, 'myTasks'])->name('calibration.my-tasks');
    Route::get('calibration/certificates', [App\Http\Controllers\CalibrationController::class, 'certificates'])->name('calibration.certificates');
    Route::get('calibration/expiring-certificates', [App\Http\Controllers\CalibrationController::class, 'expiringCertificates'])->name('calibration.expiring-certificates');
    Route::get('calibration/report', [App\Http\Controllers\CalibrationController::class, 'report'])->name('calibration.report');
    
    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\DashboardController::class, 'reports'])->name('index');
        Route::get('assets', [App\Http\Controllers\DashboardController::class, 'assetReport'])->name('assets');
        Route::get('maintenance', [App\Http\Controllers\DashboardController::class, 'maintenanceReport'])->name('maintenance');
        Route::get('calibration', [App\Http\Controllers\DashboardController::class, 'calibrationReport'])->name('calibration');
        Route::get('value', [App\Http\Controllers\DashboardController::class, 'valueReport'])->name('value');
        
        Route::get('assets/export', [App\Http\Controllers\DashboardController::class, 'exportAssetReport'])->name('assets.export');
        Route::get('maintenance/export', [App\Http\Controllers\DashboardController::class, 'exportMaintenanceReport'])->name('maintenance.export');
        Route::get('calibration/export', [App\Http\Controllers\DashboardController::class, 'exportCalibrationReport'])->name('calibration.export');
    });
});

// Authentication Routes (Breeze style)
Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
});