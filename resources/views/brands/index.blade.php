@extends('layouts.app')

@section('content')
<div class="page-heading">
    <div>
        <h3 class="mb-1">Brands</h3>
        <div class="text-muted">Organize products by manufacturer or store brand.</div>
    </div>
    @can('products.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
            <i data-lucide="plus"></i> Add Brand
        </button>
    @endcan
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Brand</th><th>Description</th><th class="text-end">Products</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($brands as $brand)
                    <tr>
                        <td class="fw-bold">{{ $brand->name }}</td>
                        <td class="text-muted">{{ $brand->description ?: '-' }}</td>
                        <td class="text-end">{{ number_format($brand->products_count) }}</td>
                        <td><span class="badge {{ $brand->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $brand->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            @can('products.edit')
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editBrandModal{{ $brand->id }}">
                                    <i data-lucide="pencil"></i> Edit
                                </button>
                                @if($brand->products_count === 0)
                                    <form method="POST" action="{{ route('brands.destroy', $brand) }}" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this brand?')" title="Delete brand"><i data-lucide="trash-2"></i></button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No brands added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('products.create')
<div class="modal fade" id="addBrandModal" tabindex="-1" aria-labelledby="addBrandTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('brands.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_catalog_form" value="add-brand">
            <div class="modal-header"><h3 class="modal-title" id="addBrandTitle">Add Brand</h3><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Name</label>
                <input name="name" value="{{ old('name') }}" class="form-control mb-3" required>
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control mb-3" rows="3">{{ old('description') }}</textarea>
                <label class="form-check form-switch"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked><span class="form-check-label">Active</span></label>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i data-lucide="save"></i> Save Brand</button></div>
        </form>
    </div>
</div>
@endcan

@can('products.edit')
    @foreach($brands as $brand)
        <div class="modal fade" id="editBrandModal{{ $brand->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('brands.update', $brand) }}" class="modal-content">
                    @csrf @method('PUT')
                    <input type="hidden" name="_catalog_form" value="edit-brand-{{ $brand->id }}">
                    <div class="modal-header"><h3 class="modal-title">Edit Brand</h3><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <label class="form-label">Name</label>
                        <input name="name" value="{{ old('_catalog_form') === 'edit-brand-'.$brand->id ? old('name') : $brand->name }}" class="form-control mb-3" required>
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control mb-3" rows="3">{{ old('_catalog_form') === 'edit-brand-'.$brand->id ? old('description') : $brand->description }}</textarea>
                        <label class="form-check form-switch"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($brand->is_active)><span class="form-check-label">Active</span></label>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i data-lucide="save"></i> Update Brand</button></div>
                </form>
            </div>
        </div>
    @endforeach
@endcan

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const formContext = @json(old('_catalog_form'));
        const modalId = formContext?.startsWith('edit-brand-')
            ? `editBrandModal${formContext.replace('edit-brand-', '')}`
            : ((window.location.hash === '#add-brand' || formContext === 'add-brand') ? 'addBrandModal' : null);
        if (modalId) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).show();
        }
    });
</script>
@endsection
