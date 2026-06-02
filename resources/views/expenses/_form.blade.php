@csrf

@isset($expense)
    @method('PUT')
@endisset

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Title</label>
        <input type="text"
               name="title"
               value="{{ old('title', $expense->title ?? '') }}"
               class="form-control"
               required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Category</label>
        <input type="text"
               name="category"
               value="{{ old('category', $expense->category ?? '') }}"
               class="form-control"
               placeholder="Rent, Salary, Utilities"
               required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Amount</label>
        <input type="number"
               step="0.01"
               min="0.01"
               name="amount"
               value="{{ old('amount', $expense->amount ?? '') }}"
               class="form-control"
               required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Expense Date</label>
        <input type="date"
               name="expense_date"
               value="{{ old('expense_date', isset($expense) ? $expense->expense_date->format('Y-m-d') : now()->format('Y-m-d')) }}"
               class="form-control"
               required>
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes"
                  rows="3"
                  class="form-control">{{ old('notes', $expense->notes ?? '') }}</textarea>
    </div>
</div>

<button class="btn btn-primary">
    {{ isset($expense) ? 'Update Expense' : 'Save Expense' }}
</button>
