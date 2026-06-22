@extends('layouts.app')

@section('content')
@php
    $categoryRows = collect();
    $visitedCategoryIds = collect();

    $appendCategoryRows = function ($parentId = null, $depth = 0) use (&$appendCategoryRows, &$categoryRows, &$visitedCategoryIds, $categories) {
        $categories
            ->filter(fn ($category) => $category->parent_id === $parentId)
            ->sortBy('name')
            ->each(function ($category) use (&$appendCategoryRows, &$categoryRows, &$visitedCategoryIds, $depth) {
                if ($visitedCategoryIds->contains($category->id)) {
                    return;
                }

                $visitedCategoryIds->push($category->id);
                $categoryRows->push(['category' => $category, 'depth' => $depth]);
                $appendCategoryRows($category->id, $depth + 1);
            });
    };

    $appendCategoryRows();
    $categories->whereNotIn('id', $visitedCategoryIds)->sortBy('name')->each(
        fn ($category) => $categoryRows->push(['category' => $category, 'depth' => 0])
    );
@endphp

<div class="page-heading">
    <div>
        <h3 class="mb-1">Categories</h3>
        <div class="text-muted">Product groups used for inventory organization and reporting.</div>
    </div>
    @can('products.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i data-lucide="plus"></i> Add Category
        </button>
    @endcan
</div>

<div class="row row-cards mb-3">
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted">Total Categories</div>
            <div class="h2 mb-0">{{ number_format($categories->count()) }}</div>
        </div></div>
    </div>
</div>

<div class="card category-tree-card">
    <div class="category-tree-header">
        <span>Category Hierarchy</span>
        <span>Products</span>
        <span>Actions</span>
    </div>
    <div class="category-tree">
        @forelse($categoryRows as $row)
            @php($category = $row['category'])
            <div class="category-tree-row" style="--category-depth: {{ $row['depth'] }}">
                <div class="category-tree-identity">
                    <span class="category-tree-branch">
                        <i data-lucide="{{ $row['depth'] ? 'corner-down-right' : 'folder' }}"></i>
                    </span>
                    <div>
                        <strong>{{ $category->name }}</strong>
                        <small>
                            {{ $row['depth'] ? 'Under '.$category->parent?->name : 'Top-level category' }}
                            @if($category->children_count)
                                · {{ $category->children_count }} {{ Str::plural('subcategory', $category->children_count) }}
                            @endif
                        </small>
                    </div>
                </div>
                <div class="category-tree-count">
                    <span class="badge bg-light text-dark border">{{ number_format($category->products_count) }}</span>
                </div>
                <div class="category-tree-actions">
                    @can('products.edit')
                        <button class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal"
                                data-bs-target="#editCategoryModal{{ $category->id }}"
                                title="Edit category">
                            <i data-lucide="pencil"></i>
                            Edit
                        </button>
                        @if($category->products_count === 0 && $category->children_count === 0)
                            <form method="POST"
                                  action="{{ route('categories.destroy', $category) }}"
                                  onsubmit="return confirm('Delete {{ addslashes($category->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete category">
                                    <i data-lucide="trash-2"></i>
                                    Delete
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        @empty
            <div class="category-tree-empty">No categories found.</div>
        @endforelse
    </div>
</div>

@can('products.create')
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('categories.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="_catalog_form" value="add-category">
            <div class="modal-header">
                <h3 class="modal-title" id="addCategoryTitle">Add Category</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Category Name</label>
                <input name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <label class="form-label mt-3">Parent Category</label>
                <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                    <option value="">No parent (top-level category)</option>
                    @foreach($categories as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </select>
                @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i data-lucide="save"></i> Save Category</button>
            </div>
        </form>
    </div>
</div>
@endcan

@can('products.edit')
    @foreach($categories as $category)
        <div class="modal fade" id="editCategoryModal{{ $category->id }}" tabindex="-1" aria-labelledby="editCategoryTitle{{ $category->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('categories.update', $category) }}" class="modal-content">
                    @csrf @method('PUT')
                    <input type="hidden" name="_catalog_form" value="edit-category-{{ $category->id }}">
                    <div class="modal-header">
                        <h3 class="modal-title" id="editCategoryTitle{{ $category->id }}">Edit Category</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Category Name</label>
                        <input name="name"
                               value="{{ old('_catalog_form') === 'edit-category-'.$category->id ? old('name') : $category->name }}"
                               class="form-control"
                               required>
                        <label class="form-label mt-3">Parent Category</label>
                        <select name="parent_id" class="form-select">
                            <option value="">No parent (top-level category)</option>
                            @foreach($categories->where('id', '!=', $category->id) as $parent)
                                <option value="{{ $parent->id }}"
                                        @selected((old('_catalog_form') === 'edit-category-'.$category->id ? old('parent_id') : $category->parent_id) == $parent->id)>
                                    {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary"><i data-lucide="save"></i> Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endcan

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const formContext = @json(old('_catalog_form'));
        const modalId = formContext?.startsWith('edit-category-')
            ? `editCategoryModal${formContext.replace('edit-category-', '')}`
            : ((window.location.hash === '#add-category' || formContext === 'add-category') ? 'addCategoryModal' : null);
        if (modalId) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).show();
        }
    });
</script>
@endsection
