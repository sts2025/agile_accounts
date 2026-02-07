@extends('layouts.app')

@section('title', 'Edit Expense')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Expense</h1>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="card shadow mb-4" style="max-width: 600px; margin: 0 auto;">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Update Details</h6>
        </div>
        <div class="card-body">

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('expenses.update', $expense->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Category Selection with "Create New" option --}}
                <div class="form-group">
                    <label class="font-weight-bold">Category</label>
                    
                    <select id="expense_category_select" name="expense_category_id" class="form-control">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $expense->expense_category_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                        <option disabled>──────────</option>
                        <option value="NEW_CATEGORY" class="font-weight-bold text-primary">+ Create New Category</option>
                    </select>

                    {{-- Hidden Input for New Category --}}
                    <div id="new_category_div" class="mt-2" style="display: none;">
                        <label class="small text-muted mb-0">Enter New Name:</label>
                        <input type="text" 
                               id="category_name_input"
                               name="category_name" 
                               class="form-control" 
                               placeholder="e.g. Lunch, Repairs...">
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Amount ({{ Auth::user()->loanManager->currency_symbol ?? 'UGX' }})</label>
                    <input type="number" name="amount" class="form-control" value="{{ $expense->amount }}" required min="0">
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Date</label>
                    <input type="date" name="expense_date" class="form-control" value="{{ $expense->expense_date }}" required>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ $expense->description }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save"></i> Update Expense
                </button>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var select = document.getElementById('expense_category_select');
        var inputDiv = document.getElementById('new_category_div');
        var inputField = document.getElementById('category_name_input');

        function toggleCategoryInput() {
            if (select.value === 'NEW_CATEGORY') {
                // Show input, hide select name to avoid conflict
                inputDiv.style.display = 'block';
                inputField.required = true;
                select.removeAttribute('name'); 
                inputField.focus();
            } else {
                // Hide input, restore select name
                inputDiv.style.display = 'none';
                inputField.required = false;
                select.setAttribute('name', 'expense_category_id');
            }
        }

        if(select) {
            select.addEventListener('change', toggleCategoryInput);
        }
    });
</script>
@endsection