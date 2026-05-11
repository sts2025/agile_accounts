

<?php $__env->startSection('title', 'Expense Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Expenses</h1>
        <div>
            
            <button class="btn btn-primary shadow-sm mr-2" data-toggle="modal" data-target="#addExpenseModal">
                <i class="fas fa-plus fa-sm text-white-50"></i> Add New Expense
            </button>
            <button onclick="window.print()" class="btn btn-secondary shadow-sm">
                <i class="fas fa-print fa-sm text-white-50"></i> Print
            </button>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo e(session('success')); ?>

            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <?php if($errors->any()): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    
    <div class="card shadow mb-4 no-print">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Expenses</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo e(route('expenses.index')); ?>" class="form-inline">
                <label class="mr-2 font-weight-bold">Start:</label>
                <input type="date" name="start_date" class="form-control mr-3" value="<?php echo e($startDate); ?>">
                
                <label class="mr-2 font-weight-bold">End:</label>
                <input type="date" name="end_date" class="form-control mr-3" value="<?php echo e($endDate); ?>">
                
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
    </div>

    
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                            <th class="text-center no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $totalExpenses = 0; ?>
                        
                        <?php $__empty_1 = true; $__currentLoopData = $expenses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $expense): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e(\Carbon\Carbon::parse($expense->expense_date)->format('d M, Y')); ?></td>
                            <td class="font-weight-bold"><?php echo e($expense->category->name ?? 'Uncategorized'); ?></td>
                            <td><?php echo e($expense->description ?? '-'); ?></td>
                            <td class="text-right font-weight-bold text-danger">
                                <?php echo e(number_format($expense->amount)); ?>

                            </td>
                            <td class="text-center no-print">
                                <a href="<?php echo e(route('expenses.edit', $expense->id)); ?>" class="btn btn-sm btn-info" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="<?php echo e(route('expenses.destroy', $expense->id)); ?>" method="POST" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this expense?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php $totalExpenses += $expense->amount; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No expenses found for this period.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    
                    
                    <?php if($expenses->count() > 0): ?>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="3" class="text-right">Total Expenses:</td>
                            <td class="text-right text-danger"><?php echo e(Auth::user()->loanManager->currency_symbol ?? 'UGX'); ?> <?php echo e(number_format($totalExpenses)); ?></td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
            
            <div class="mt-3 no-print">
                <?php echo e($expenses->appends(request()->query())->links()); ?>

            </div>
        </div>
    </div>

</div>




<div class="modal fade" id="addExpenseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?php echo e(route('expenses.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Record New Expense</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    
                    
                    <div class="form-group">
                        <label class="font-weight-bold">Category</label>
                        
                        
                        <select id="expense_category_select" name="expense_category_id" class="form-control">
                            <option value="">Select Category...</option>
                            <option value="NEW_CATEGORY" class="font-weight-bold text-primary">+ Create New Category</option>
                            <hr>
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>

                        
                        <div id="new_category_div" class="mt-2" style="display: none;">
                            <label class="small text-muted mb-0">Enter Name:</label>
                            <input type="text" 
                                   id="category_name_input"
                                   name="category_name" 
                                   class="form-control" 
                                   placeholder="e.g. Lunch, Repairs, Transport...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Amount (<?php echo e(Auth::user()->loanManager->currency_symbol ?? 'UGX'); ?>)</label>
                        <input type="number" name="amount" class="form-control" required min="0">
                    </div>

                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="expense_date" class="form-control" value="<?php echo e(date('Y-m-d')); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        #accordionSidebar, .topbar { display: none !important; } 
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var select = document.getElementById('expense_category_select');
        var inputDiv = document.getElementById('new_category_div');
        var inputField = document.getElementById('category_name_input');

        if(select) {
            select.addEventListener('change', function() {
                if (this.value === 'NEW_CATEGORY') {
                    // Show the text box
                    inputDiv.style.display = 'block';
                    inputField.required = true;
                    
                    // IMPORTANT: Remove the name attribute from select so it's NOT sent to backend
                    // This prevents the "Invalid ID" error
                    this.removeAttribute('name');
                    inputField.focus();
                } else {
                    // Hide the text box
                    inputDiv.style.display = 'none';
                    inputField.value = '';
                    inputField.required = false;
                    
                    // Restore the name attribute to the select
                    this.setAttribute('name', 'expense_category_id');
                }
            });
        }
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/expenses/index.blade.php ENDPATH**/ ?>