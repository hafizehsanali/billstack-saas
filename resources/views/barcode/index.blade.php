@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Barcode Scanner</h3>
        <div class="text-muted">Scan or enter a product barcode to quickly open product details.</div>
    </div>

    <a href="{{ route('products.index') }}" class="btn btn-secondary">
        Products
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Scan Product</h3>
            </div>
            <div class="card-body">
                <label class="form-label">Barcode</label>
                <div class="input-group">
                    <input type="text"
                           id="barcodeInput"
                           class="form-control"
                           placeholder="Scan or type barcode"
                           autofocus>
                    <button type="button"
                            id="lookupButton"
                            class="btn btn-primary">
                        Find
                    </button>
                </div>

                <div id="barcodeMessage" class="text-muted small mt-3">
                    Ready for scanner input.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Result</h3>
            </div>
            <div class="card-body" id="barcodeResult">
                <div class="text-muted">No product selected.</div>
            </div>
        </div>
    </div>
</div>

<script>
    const barcodeInput = document.getElementById('barcodeInput');
    const lookupButton = document.getElementById('lookupButton');
    const message = document.getElementById('barcodeMessage');
    const result = document.getElementById('barcodeResult');

    function renderProduct(product) {
        result.innerHTML = `
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h3 class="mb-1">${escapeHtml(product.name)}</h3>
                    <div class="text-muted">SKU: ${escapeHtml(product.sku || '-')}</div>
                    <div class="text-muted">Barcode: ${escapeHtml(product.barcode || '-')}</div>
                </div>
                <span class="badge ${product.stock_quantity > 0 ? 'bg-success text-white' : 'bg-light text-dark border'}">
                    ${product.stock_quantity > 0 ? 'In Stock' : 'Out of Stock'}
                </span>
            </div>

            <div class="row g-3 my-3">
                <div class="col-md-6">
                    <div class="border rounded p-3">
                        <div class="text-secondary small fw-semibold text-uppercase">Selling Price</div>
                        <div class="h3 mb-0">Rs ${Number(product.selling_price).toFixed(2)}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3">
                        <div class="text-secondary small fw-semibold text-uppercase">Stock</div>
                        <div class="h3 mb-0">${product.stock_quantity}</div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="${product.edit_url}" class="btn btn-outline-secondary">Edit Product</a>
                <a href="${product.stock_ledger_url}" class="btn btn-outline-primary">Stock Ledger</a>
            </div>
        `;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async function lookupBarcode() {
        const barcode = barcodeInput.value.trim();

        if (!barcode) {
            message.textContent = 'Enter or scan a barcode first.';
            return;
        }

        message.textContent = 'Searching...';
        lookupButton.disabled = true;

        try {
            const response = await fetch('{{ route('barcode.lookup') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ barcode }),
            });

            if (!response.ok) {
                const error = await response.json();
                result.innerHTML = `<div class="alert alert-warning mb-0">${escapeHtml(error.message || 'No product found.')}</div>`;
                message.textContent = 'No matching product.';
                return;
            }

            const product = await response.json();
            renderProduct(product);
            message.textContent = 'Product found.';
            barcodeInput.value = '';
            barcodeInput.focus();
        } finally {
            lookupButton.disabled = false;
        }
    }

    lookupButton.addEventListener('click', lookupBarcode);
    barcodeInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            lookupBarcode();
        }
    });
</script>
@endsection
