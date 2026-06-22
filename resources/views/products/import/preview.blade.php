@extends('layouts.app')

@section('content')
<div class="page-heading">
    <div>
        <h3 class="mb-1">Map Product Columns</h3>
        <div class="text-muted">{{ number_format(count($rows)) }} product rows found. Match your CSV columns to Zephrant ERP fields.</div>
    </div>
    <a href="{{ route('products.import') }}" class="btn btn-outline-secondary">
        <i data-lucide="arrow-left"></i>
        Upload Another File
    </a>
</div>

<form method="POST" action="{{ route('products.import.store') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-title">Column Mapping</h4>
        </div>
        <div class="card-body border-bottom">
            <div class="alert alert-info mb-0">
                <strong>Stock example:</strong>
                Customer Unit = KG, Supplier Unit = Bag,
                Customer Units in One Supplier Unit = 50,
                Opening Supplier Quantity = 3, and Additional Customer-Unit Stock = 10.
                Zephrant ERP will create 160 KG opening stock.
            </div>
            <div class="alert alert-info mb-0 mt-2">
                <strong>Variant example:</strong>
                use the same Product Group SKU for every variant row, then provide a unique SKU,
                Variant Name, and Attribute/Option values on each row.
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table product-import-mapping">
                <thead>
                    <tr>
                        <th>Zephrant ERP Field</th>
                        <th>Your CSV Column</th>
                        <th>Example Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fields as $field => $label)
                        @php($selectedColumn = old("mapping.$field", $mapping[$field] ?? ''))
                        <tr>
                            <td>
                                <strong>{{ $label }}</strong>
                                @if(in_array($field, $requiredFields, true))
                                    <span class="text-danger">*</span>
                                @endif
                            </td>
                            <td>
                                <select name="mapping[{{ $field }}]"
                                        class="form-select @error("mapping.$field") is-invalid @enderror">
                                    <option value="">Do not import</option>
                                    @foreach($headers as $index => $header)
                                        <option value="{{ $index }}" @selected((string) $selectedColumn === (string) $index)>
                                            {{ $header }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("mapping.$field")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </td>
                            <td class="text-muted">
                                {{ $selectedColumn !== '' ? ($rows[0][(int) $selectedColumn] ?? '-') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-title">Data Preview</h4>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Line</th>
                        @foreach($headers as $header)<th>{{ $header }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($rows, 0, 5) as $index => $row)
                        <tr>
                            <td>{{ $index + 2 }}</td>
                            @foreach($headers as $column => $header)
                                <td>{{ $row[$column] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="product-import-actions">
        <label class="form-check">
            <input type="checkbox"
                   name="create_missing"
                   value="1"
                   class="form-check-input"
                   @checked(old('create_missing', true))>
            <span class="form-check-label">
                <strong>Create missing categories, brands, and units</strong>
                <small class="d-block text-muted">Recommended for importing an existing business catalog.</small>
            </span>
        </label>
        <button class="btn btn-primary">
            <i data-lucide="upload"></i>
            Import {{ number_format(count($rows)) }} Products
        </button>
    </div>
</form>
@endsection
