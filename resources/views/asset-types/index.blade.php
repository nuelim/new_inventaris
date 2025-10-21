@extends('layouts.app')

@section('title', 'Tipe Aset')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-tags me-2"></i>Master Data Tipe Aset</h1>
    <div>
        @can('asset-type-create')
        <a href="{{ route('asset-types.create') }}" class="btn btn-primary me-2">
            <i class="fas fa-plus me-1"></i>Tambah Tipe Aset
        </a>
        @endcan
        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-upload me-1"></i>Import
        </button>
        <a href="{{ route('asset-types.export') }}" class="btn btn-info">
            <i class="fas fa-download me-1"></i>Export
        </a>
    </div>
</div>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('asset-types.index') }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Cari</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Nama, Kode, atau Deskripsi">
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Kategori</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="{{ route('asset-types.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Depresiasi</th>
                        <th>Kalibrasi</th>
                        <th>Maintenance</th>
                        <th>Jumlah Aset</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assetTypes as $assetType)
                    <tr>
                        <td>{{ $assetType->code }}</td>
                        <td>
                            <strong>{{ $assetType->name }}</strong>
                            @if($assetType->description)
                            <br><small class="text-muted">{{ Str::limit($assetType->description, 50) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $assetType->category }}</span>
                        </td>
                        <td>{{ $assetType->depreciation_years }} tahun</td>
                        <td>
                            @if($assetType->requires_calibration)
                            <span class="badge bg-success"><i class="fas fa-check"></i></span>
                            @else
                            <span class="badge bg-secondary"><i class="fas fa-times"></i></span>
                            @endif
                        </td>
                        <td>
                            @if($assetType->requires_maintenance)
                            <span class="badge bg-success"><i class="fas fa-check"></i></span>
                            @else
                            <span class="badge bg-secondary"><i class="fas fa-times"></i></span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-primary">{{ $assetType->asset_count ?? 0 }}</span>
                        </td>
                        <td>
                            @if($assetType->is_active)
                            <span class="badge bg-success">Aktif</span>
                            @else
                            <span class="badge bg-danger">Tidak Aktif</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('asset-types.show', $assetType) }}" class="btn btn-sm btn-outline-info" title="Lihat">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('asset-type-edit')
                                <a href="{{ route('asset-types.edit', $assetType) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('asset-type-delete')
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        data-bs-toggle="modal" data-bs-target="#deleteModal{{ $assetType->id }}" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada data tipe aset</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                Menampilkan {{ $assetTypes->firstItem() }}-{{ $assetTypes->lastItem() }} dari {{ $assetTypes->total() }} data
            </div>
            {{ $assetTypes->links() }}
        </div>
    </div>
</div>

<!-- Delete Modals -->
@forelse($assetTypes as $assetType)
<div class="modal fade" id="deleteModal{{ $assetType->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus tipe aset <strong>{{ $assetType->name }}</strong>?</p>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Tindakan ini tidak dapat dibatalkan!
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="{{ route('asset-types.destroy', $assetType) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforelse

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Tipe Aset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('asset-types.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file" class="form-label">Pilih File Excel/CSV</label>
                        <input type="file" class="form-control" id="file" name="file" required accept=".xlsx,.xls,.csv">
                        <div class="form-text">
                            Format file: .xlsx, .xls, .csv (Maks. 10MB)
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Pastikan file memiliki format yang sesuai dengan template yang disediakan.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i>Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection