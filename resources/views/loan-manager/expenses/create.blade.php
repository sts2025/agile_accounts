@extends('layouts.app')

@section('title', 'Add Expense')

@section('content')
<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Record Expense</h1>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="card shadow mb-4" style="max-width: 600px; margin: 0 auto;">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Expense Details</h6>
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

            <form action="{{ route('expenses.store') }}" method="POST">
                @csrf
                
                {{-- SMART CATEGORY INPUT --}}
                <div class="form-group">
                    <label class="font-weight-bold">Category</label>
                    <input type="text" 
                           name="category_name" 
                           list="categoryListPage" 
                           class="form-control" 
                           placeholder="Type a new category or select existing..." 
                           required 
                           autocomplete="off">
                    
                    {{-- The List of Options (Invisible Helper) --}}
                    <datalist id="categoryListPage">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->name }}">
                        @endforeach
                    </datalist>
                    <small class="text-muted">Tip: Type a name like "Transport" to create a new category automatically.</small>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Amount</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">{{ Auth::user()->loanManager->currency_symbol ?? 'UGX' }}</span>
                        </div>
                        <input type="number" name="amount" class="form-control" placeholder="0.00" min="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Date</label>
                    <input type="date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Description (Optional)</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Additional details..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save"></i> Save Expense
                </button>
            </form>
        </div>
    </div>
</div>
@endsection