<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agile Accounts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Adding FontAwesome for icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        body {
            background-color: #f0f2f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            background-color: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-radius: 16px;
            overflow: hidden;
        }
        .login-header {
            text-align: center;
            padding: 2.5rem 1.5rem;
            background-color: #0d47a1; 
        }
        .login-header h1 {
            font-weight: 800;
            color: white; 
            margin: 0;
            letter-spacing: -1px;
        }
        .login-header p {
            color: rgba(255, 255, 255, 0.8); 
            font-size: 0.95rem;
            margin-top: 0.5rem;
            margin-bottom: 0;
        }
        .login-body {
            padding: 2.5rem;
        }
        .btn-deep-blue {
            background-color: #0d47a1;
            border-color: #0d47a1;
            color: white;
            padding: 12px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .btn-deep-blue:hover {
            background-color: #0a3a82;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 71, 161, 0.3);
            color: white;
        }
        .form-control {
            padding: 12px;
            border-radius: 8px;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #0d47a1;
        }
        /* CSS Guardrail: Force disable backdrops that lock the UI */
        .modal-backdrop {
            display: none !important;
        }
        body.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }
    </style>
</head>
<body class="bg-light">
    <div class="login-container">
        <div class="login-header">
            <h1>AgileAccounts</h1>
            <p>Your Future is Our Focus</p>
        </div>

        <div class="login-body">
            {{-- 1. SUCCESS MESSAGE (Registration success) --}}
            @if (session('success'))
                <div class="alert alert-success d-flex align-items-center mb-4 shadow-sm border-0 border-start border-success border-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            {{-- 2. STATUS MESSAGE --}}
            @if (session('status'))
                <div class="alert alert-info d-flex align-items-center mb-4 shadow-sm border-0 border-start border-info border-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            {{-- 3. ERROR MESSAGES --}}
            @if ($errors->any())
                <div class="alert alert-danger shadow-sm border-0 border-start border-danger border-4">
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Email Address</label>
                    <input type="email" class="form-control" name="email" placeholder="name@example.com" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between">
                        <label class="form-label small fw-bold text-muted">Password</label>
                        <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot?</a>
                    </div>
                    <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                </div>
                
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-deep-blue">SIGN IN</button>
                </div>
            </form>
            
            <hr class="my-4 text-muted">

            <div class="text-center">
                <p class="mb-2 small text-muted">
                    Account activation: <strong>0740859082</strong>
                </p>
                <p class="mb-0 small">
                    New here? <a href="{{ route('register') }}" class="fw-bold text-decoration-none">Create a Manager Account</a>
                </p>
            </div>
        </div>
    </div>

    {{-- BACKDROP KILLER SCRIPT: Ensures screen never locks --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function killBackdrops() {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = 'auto';
                document.body.style.paddingRight = '0px';
            }
            // Run immediately and again after a short delay to catch late-loading scripts
            killBackdrops();
            setTimeout(killBackdrops, 500);
            setTimeout(killBackdrops, 1500);
        });
    </script>
</body>
</html>