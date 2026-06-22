@extends('layouts.app')

@section('content')
<div class="page-heading">
    <div>
        <h3 class="mb-1">Product Attributes</h3>
        <div class="text-muted">Reusable options such as size, color, strength, or pack type.</div>
    </div>
    @can('products.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttributeModal"><i data-lucide="plus"></i> Add Attribute</button>
    @endcan
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Attribute</th><th>Values</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($attributes as $attribute)
                    <tr>
                        <td class="fw-bold">{{ $attribute->name }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($attribute->values as $value)<span class="badge bg-secondary-lt">{{ $value->value }}</span>@endforeach
                            </div>
                        </td>
                        <td><span class="badge {{ $attribute->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $attribute->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            @can('products.edit')
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editAttributeModal{{ $attribute->id }}"><i data-lucide="pencil"></i> Edit</button>
                                @if($attribute->values->every(fn ($value) => $value->variants()->doesntExist()))
                                    <form method="POST" action="{{ route('product-attributes.destroy', $attribute) }}" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this attribute?')"><i data-lucide="trash-2"></i></button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No attributes added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('products.create')
<div class="modal fade" id="addAttributeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('product-attributes.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_catalog_form" value="add-attribute">
            <div class="modal-header"><h3 class="modal-title">Add Attribute</h3><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Name</label>
                <input name="name" value="{{ old('name') }}" class="form-control mb-3" placeholder="Color" required>
                <label class="form-label">Values</label>
                <input name="values" value="{{ old('values') }}" class="form-control mb-2" placeholder="Black, Blue, White" required>
                <small class="text-muted">Separate values with commas.</small>
                <label class="form-check form-switch mt-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked><span class="form-check-label">Active</span></label>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i data-lucide="save"></i> Save Attribute</button></div>
        </form>
    </div>
</div>
@endcan

@can('products.edit')
    @foreach($attributes as $attribute)
        <div class="modal fade" id="editAttributeModal{{ $attribute->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('product-attributes.update', $attribute) }}" class="modal-content">
                    @csrf @method('PUT')
                    <input type="hidden" name="_catalog_form" value="edit-attribute-{{ $attribute->id }}">
                    <div class="modal-header"><h3 class="modal-title">Edit Attribute</h3><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <label class="form-label">Name</label>
                        <input name="name" value="{{ old('_catalog_form') === 'edit-attribute-'.$attribute->id ? old('name') : $attribute->name }}" class="form-control mb-3" required>
                        <label class="form-label">Values</label>
                        <input name="values"
                               value="{{ old('_catalog_form') === 'edit-attribute-'.$attribute->id ? old('values') : $attribute->values->pluck('value')->implode(', ') }}"
                               class="form-control mb-2"
                               required>
                        <small class="text-muted">Values already used by variants are preserved.</small>
                        <label class="form-check form-switch mt-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($attribute->is_active)><span class="form-check-label">Active</span></label>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i data-lucide="save"></i> Update Attribute</button></div>
                </form>
            </div>
        </div>
    @endforeach
@endcan

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const formContext = @json(old('_catalog_form'));
        const modalId = formContext?.startsWith('edit-attribute-')
            ? `editAttributeModal${formContext.replace('edit-attribute-', '')}`
            : ((window.location.hash === '#add-attribute' || formContext === 'add-attribute') ? 'addAttributeModal' : null);
        if (modalId) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).show();
        }
    });
</script>
@endsection
