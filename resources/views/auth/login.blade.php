<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agile Accounts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .login-container {
            max-width: 420px;
            margin: 5rem auto;
            background-color: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 16px;
            overflow: hidden; /* Important for the top border-radius */
        }
        .login-header {
            text-align: center;
            padding: 2rem 1.5rem;
            /* This is the new deep blue background */
            background-color: #0d47a1; 
        }
        .login-header h1 {
            font-weight: 700;
            /* This makes the header text white */
            color: white; 
            margin: 0;
        }
        .login-header p {
            /* This makes the tagline text white */
            color: rgba(255, 255, 255, 0.9); 
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        .login-body {
            padding: 2rem;
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
            <h1>AgileAccounts</h1>
            <p>Your Future is Our Focus</p>
        </div>

        <div class="login-body">
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
    </div>
</body>
</html>