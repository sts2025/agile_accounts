<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agile Accounts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 5rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-header h1 {
            font-weight: 700;
            color: #2c3e50;
        }
        .login-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }
        .btn-deep-blue {
            background-color: #0d47a1;
            border-color: #0d47a1;
            color: white;
            padding: 10px;
            font-weight: 600;
        }
        .btn-deep-blue:hover {
            background-color: #0a3a82;
            border-color: #0a3a82;
            color: white;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #0d47a1;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="/agile_accounts/public/images/logo.jpg" alt="Agile Accounts Logo" style="width: 80px; margin-bottom: 1rem;">
            <h1>AgileAccounts</h1>
            <p>Your Future is Our Focus</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger pb-0"><ul class="mb-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="Email Address" value="{{ old('email') }}" required>
            </div>

            <div class="mb-4">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-deep-blue">LOGIN</button>
            </div>

            <div class="text-center">
                <a href="{{ route('password.request') }}">Forgot Password?</a>
            </div>
        </form>
        
        <hr>

        <div class="text-center">
             <small class="text-muted">For account activation, please contact the administrator at <strong>0740859082</strong>.</small>
             <p class="mt-3">Don't have an account? <a href="{{ route('register') }}">Register here</a></p>
        </div>
    </div>
</body>
</html>