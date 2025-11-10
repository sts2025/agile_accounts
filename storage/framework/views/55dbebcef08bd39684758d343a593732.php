<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Agile Accounts'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="<?php echo e(route('dashboard')); ?>">
                <img src="<?php echo e(asset('images/logo.jpg')); ?>" alt="Agile Accounts Logo" style="height: 35px;">
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="<?php echo e(route('dashboard')); ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo e(route('clients.index')); ?>">My Clients</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo e(route('loans.index')); ?>">My Loans</a></li>
                </ul>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-danger">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <footer>
        <div class="text-center p-3 text-muted" style="font-size: 0.85rem;">
            Developed by **BKR TECH** &copy; <?php echo e(date('Y')); ?> | Need help? Contact support at 
            
            
            <a href="tel:<?php echo e(\App\Models\LoanManager::getGlobalSupportPhone()); ?>" class="fw-bold text-decoration-none">
                <?php echo e(\App\Models\LoanManager::getGlobalSupportPhone()); ?>

            </a>
            
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php echo $__env->yieldPushContent('modals'); ?> 
</body>
</html><?php /**PATH C:\xampp\htdocs\agile_accounts\agile_accounts\resources\views/layouts/app.blade.php ENDPATH**/ ?>