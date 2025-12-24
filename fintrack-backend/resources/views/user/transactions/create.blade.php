@extends('layouts.user')

@section('title', 'Add Transaction')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <!-- Header Card -->
            <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-20 rounded-3 p-3 me-3">
                            <i class="fas fa-plus-circle text-white fs-4"></i>
                        </div>
                        <h2 class="card-title text-white mb-0 fw-bold">Add New Transaction</h2>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="card border-0 shadow-lg" style="border-radius: 16px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm" style="border-radius: 12px;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Please fix the following errors:</strong>
                            </div>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('user.transactions.store') }}" enctype="multipart/form-data">
                        @csrf

                        @if($categories->isEmpty())
                            <div class="alert alert-warning border-0 shadow-sm" style="border-radius: 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    No categories available yet. Please create at least one income or expense category before adding a transaction.
                                </div>
                            </div>
                        @endif

                        <!-- Description Field -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">
                                Description <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;">
                                    <i class="fas fa-comment text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0 @error('description') is-invalid @enderror"
                                       id="description" name="description" value="{{ old('description') }}" placeholder="What is this transaction for?"
                                       style="border-radius: 0 12px 12px 0; border-left: none;" required>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Type and Amount Row -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="type" id="expense" value="expense"
                                           {{ old('type', 'expense') == 'expense' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-danger rounded-start-3" for="expense"
                                           style="border-radius: 12px 0 0 12px !important;">
                                        <i class="fas fa-minus-circle me-2"></i>Expense
                                    </label>

                                    <input type="radio" class="btn-check" name="type" id="income" value="income"
                                           {{ old('type') == 'income' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-success rounded-end-3" for="income"
                                           style="border-radius: 0 12px 12px 0 !important;">
                                        <i class="fas fa-plus-circle me-2"></i>Income
                                    </label>
                                </div>
                                @error('type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="amount" class="form-label fw-semibold">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;">
                                        <i class="fas fa-dollar-sign text-muted"></i>
                                    </span>
                                    <input type="number" class="form-control border-start-0 ps-0 @error('amount') is-invalid @enderror"
                                           id="amount" name="amount" value="{{ old('amount') }}" step="0.01" min="0" placeholder="0.00"
                                           style="border-radius: 0 12px 12px 0; border-left: none;">
                                    @error('amount')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Category Field -->
                        <div class="mb-4">
                            <label for="category_id" class="form-label fw-semibold">
                                Category <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;">
                                    <i class="fas fa-tag text-muted"></i>
                                </span>
                                @php
                                    $groupedCategories = $categories->groupBy(fn ($category) => $category->type ?? 'uncategorized');
                                    $selectedCategory = old('category_id');
                                @endphp
                                <select class="form-select border-start-0 ps-0 @error('category_id') is-invalid @enderror"
                                        id="category_id" name="category_id" {{ $categories->isEmpty() ? 'disabled' : '' }}
                                        style="border-radius: 0 12px 12px 0; border-left: none;" required>
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

                        <!-- Date Field -->
                        <div class="mb-4">
                            <label for="date" class="form-label fw-semibold">
                                Date <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;">
                                    <i class="fas fa-calendar text-muted"></i>
                                </span>
                                <input type="date" class="form-control border-start-0 ps-0 @error('date') is-invalid @enderror"
                                       id="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}"
                                       style="border-radius: 0 12px 12px 0; border-left: none;" required>
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Receipt Field -->
                        <div class="mb-4">
                            <label for="receipt" class="form-label fw-semibold">Receipt (optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px;">
                                    <i class="fas fa-receipt text-muted"></i>
                                </span>
                                <input type="file" class="form-control border-start-0 ps-0 @error('receipt') is-invalid @enderror"
                                       id="receipt" name="receipt" accept="image/*"
                                       style="border-radius: 0 12px 12px 0; border-left: none;">
                                @error('receipt')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">
                                Upload a bill or receipt image (JPG, PNG, etc.)
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm {{ $categories->isEmpty() ? 'disabled' : '' }}"
                                    style="border-radius: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                <i class="fas fa-save me-2"></i>Add Transaction
                            </button>
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
            const typeInputs = document.querySelectorAll('input[name="type"]');
            const categorySelect = document.getElementById('category_id');

            if (!typeInputs.length || !categorySelect) {
                return;
            }

            const defaultOption = categorySelect.querySelector('option[value=""]');
            const categoryOptions = Array.from(categorySelect.querySelectorAll('option[data-type]'));

            const syncCategoryOptions = () => {
                const selectedType = document.querySelector('input[name="type"]:checked').value;
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

            typeInputs.forEach(input => {
                input.addEventListener('change', syncCategoryOptions);
            });
            syncCategoryOptions();
        });
    </script>
@endpush

@push('styles')
<style>
.btn-outline-danger, .btn-outline-success {
    border-width: 2px;
}

.btn-check:checked + .btn-outline-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn-check:checked + .btn-outline-success {
    background-color: #198754;
    border-color: #198754;
}

.card {
    backdrop-filter: blur(10px);
}

.input-group-text {
    background: rgba(255, 255, 255, 0.8) !important;
    backdrop-filter: blur(10px);
}

.form-control:focus, .form-select:focus {
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    border-color: #667eea;
}
</style>
@endpush
