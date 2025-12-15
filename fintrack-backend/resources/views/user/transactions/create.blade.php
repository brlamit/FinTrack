@extends('layouts.user')

@section('title', 'Add Transaction')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Add New Transaction</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('user.transactions.store') }}" enctype="multipart/form-data">
                        @csrf

                        @if($categories->isEmpty())
                            <div class="alert alert-warning">
                                No categories available yet. Please create at least one income or expense category before adding a transaction.
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror" 
                                   id="description" name="description" value="{{ old('description') }}" required>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                    <option value="income" {{ old('type', 'expense') === 'income' ? 'selected' : '' }}>Income</option>
                                    <option value="expense" {{ old('type', 'expense') === 'expense' ? 'selected' : '' }}>Expense</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control @error('amount') is-invalid @enderror" 
                                           id="amount" name="amount" step="0.01" value="{{ old('amount') }}">
                                </div>
                                @error('amount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                @php
                                    $groupedCategories = $categories->groupBy(fn ($category) => $category->type ?? 'uncategorized');
                                    $selectedCategory = old('category_id');
                                @endphp
                                <select class="form-select @error('category_id') is-invalid @enderror" 
                                        id="category_id" name="category_id" {{ $categories->isEmpty() ? 'disabled' : '' }} required>
                                    <option value="">Select a category</option>
                                    @forelse($groupedCategories as $type => $typeCategories)
                                        <optgroup label="{{ ucfirst($type) }}">
                                            @foreach($typeCategories as $category)
                                                <option value="{{ $category->id }}"
                                                    data-type="{{ $category->type ?? 'uncategorized' }}"
                                                    @selected($selectedCategory == $category->id)>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @empty
                                        <option value="" disabled>No categories available</option>
                                    @endforelse
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror" 
                                   id="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="receipt" class="form-label">Receipt (optional)</label>
                            <input type="file" class="form-control @error('receipt') is-invalid @enderror" id="receipt" name="receipt" accept="image/*">
                            @error('receipt')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" {{ $categories->isEmpty() ? 'disabled' : '' }}>
                                <i class="fas fa-save"></i> Add Transaction
                            </button>
                            <a href="{{ route('user.transactions') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.getElementById('type');
            const categorySelect = document.getElementById('category_id');

            if (!typeSelect || !categorySelect) {
                return;
            }

            const defaultOption = categorySelect.querySelector('option[value=""]');
            const categoryOptions = Array.from(categorySelect.querySelectorAll('option[data-type]'));

            const syncCategoryOptions = () => {
                const selectedType = typeSelect.value;
                let hasVisibleOption = false;

                categoryOptions.forEach(option => {
                    const optionType = option.dataset.type || 'uncategorized';
                    const shouldShow = optionType === selectedType || optionType === 'uncategorized';
                    option.hidden = !shouldShow;
                    option.disabled = !shouldShow;
                    if (shouldShow && option.value === categorySelect.value) {
                        hasVisibleOption = true;
                    }
                });

                if (!hasVisibleOption) {
                    categorySelect.value = '';
                }

                if (defaultOption) {
                    defaultOption.hidden = false;
                    defaultOption.disabled = false;
                }
            };

            typeSelect.addEventListener('change', syncCategoryOptions);
            syncCategoryOptions();
        });
    </script>
@endpush
