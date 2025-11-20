

<?php $__env->startSection('title', 'My Loans'); ?>

<?php $__env->startSection('content'); ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        
        <h1>Loans</h1>
        <div>
            
            <a href="<?php echo e(route('clients.index')); ?>" class="btn btn-secondary">Manage Clients</a>
            <a href="<?php echo e(route('loans.create')); ?>" class="btn btn-primary">Create New Loan</a>
        </div>
    </div>
    
    <?php if(session('status')): ?>
        <div class="alert alert-success">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>
    
    
    <div id="status-message-container"></div>

    <div class="card mb-4">
        <div class="card-header">Find a Loan</div>
        <div class="card-body">
            <form method="GET" action="<?php echo e(route('loans.index')); ?>">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by client's name..." value="<?php echo e(request('search')); ?>">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        
                        
                        <th>Principal Amount (<?php echo e($currency_symbol ?? 'UGX'); ?>)</th>
                        <th>Interest Rate (%)</th>
                        <th>Term</th>
                        <th>Frequency</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $loans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                
                                <a href="<?php echo e(route('clients.edit', $loan->client->id)); ?>"><?php echo e($loan->client->name ?? 'N/A'); ?></a>
                                <br>
                                <small class="text-muted"><?php echo e($loan->client->phone_number ?? ''); ?></small>
                            </td>
                            <td><?php echo e(number_format($loan->principal_amount, 0)); ?></td>
                            <td><?php echo e($loan->interest_rate); ?>%</td>
                            <td><?php echo e($loan->term); ?></td>
                            <td><?php echo e($loan->repayment_frequency); ?></td>
                            <td>
                                
                                <form action="<?php echo e(route('loans.update-status', $loan->id)); ?>" method="POST" class="d-inline status-form" data-loan-id="<?php echo e($loan->id); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <input type="hidden" name="new_status" value="<?php echo e($loan->status == 'active' ? 'paid' : 'active'); ?>">
                                    
                                    <?php
                                        $buttonClass = $loan->status == 'active' ? 'btn-primary' : ($loan->status == 'paid' ? 'btn-success' : 'btn-secondary');
                                    ?>
                                    
                                    <button type="submit" 
                                            class="btn btn-sm text-white rounded-pill loan-status-btn <?php echo e($buttonClass); ?>" 
                                            data-current-status="<?php echo e($loan->status); ?>"
                                            id="status-btn-<?php echo e($loan->id); ?>">
                                        <?php echo e(ucfirst($loan->status)); ?>

                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="<?php echo e(route('loans.show', $loan->id)); ?>" class="btn btn-info btn-sm">View</a>
                                <a href="<?php echo e(route('loans.edit', $loan->id)); ?>" class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" action="<?php echo e(route('loans.destroy', $loan->id)); ?>" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="text-center">No loans found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            
            
                
            
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        const messageContainer = document.getElementById('status-message-container');

        // Function to display messages (replacing confirm/alert for better UX)
        function displayMessage(message, type = 'success') {
            messageContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            setTimeout(() => {
                // Clear the message after 5 seconds
                messageContainer.innerHTML = '';
            }, 5000);
        }

        // --- Status Update via AJAX ---
        document.querySelectorAll('.status-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const loanId = this.dataset.loanId;
                const button = document.getElementById(`status-btn-${loanId}`);
                const currentStatus = button.dataset.currentStatus;
                const newStatusInput = this.querySelector('input[name="new_status"]');
                const newStatus = newStatusInput.value;
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                if (!confirm(`Are you sure you want to change the status from '${currentStatus.toUpperCase()}' to '${newStatus.toUpperCase()}'?`)) {
                    return;
                }

                // Temporary disable button and show loading state
                button.textContent = 'Updating...';
                button.disabled = true;

                fetch(this.action, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ new_status: newStatus })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { 
                            throw new Error(err.message || 'Server error occurred.'); 
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    // Success: Update button UI
                    const updatedStatus = data.status;
                    let buttonClass = 'btn-secondary';

                    if (updatedStatus === 'paid') {
                        button.textContent = 'Paid';
                        buttonClass = 'btn-success'; // Green for Paid
                        newStatusInput.value = 'active'; // Next click will try to set to active
                    } else if (updatedStatus === 'active') {
                        button.textContent = 'Active';
                        buttonClass = 'btn-primary'; // Blue for Active
                        newStatusInput.value = 'paid'; // Next click will try to set to paid
                    }
                    
                    // Remove existing classes and add new one
                    button.className = 'btn btn-sm text-white rounded-pill loan-status-btn ' + buttonClass;
                    button.dataset.currentStatus = updatedStatus;
                    button.disabled = false;

                    displayMessage(`Loan status updated to ${updatedStatus.toUpperCase()} successfully.`, 'success');
                })
                .catch(error => {
                    // Failure: Revert button and show error
                    button.textContent = ucfirst(currentStatus); // Revert text
                    button.disabled = false;
                    console.error('Error:', error);
                    displayMessage('Error updating status: ' + error.message, 'danger');
                });
            });
        });
        
        // Helper function for title casing
        function ucfirst(str) {
            if (!str) return str;
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/loan-manager/loans/index.blade.php ENDPATH**/ ?>