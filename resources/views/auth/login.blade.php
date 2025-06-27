<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agile Accounts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="text-center pt-4">
                        <img src="{{ asset('images/logo.jpg') }}" alt="Agile Accounts Logo" style="width: 150px; border-radius: 8px;">
                    </div>
                    <div class="card-header text-center border-0 fs-4"><h4>Login</h4></div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                        @endif

                        <form method="POST" action="{{ route('login.store') }}">
                            @csrf
                            <div class="mb-3"><label for="email" class="form-label">Email Address</label><input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required></div>
                            <div class="mb-3"><label for="password" class="form-label">Password</label><input type="password" class="form-control" id="password" name="password" required></div>
                            <div class="d-grid"><button type="submit" class="btn btn-primary">Login</button></div>
                        </form>
                        <div class="text-center mt-3"><a href="{{ route('password.request') }}">Forgot Your Password?</a></div>
                    </div>
                    
                    <div class="card-footer text-center bg-white border-0 pb-4">
                        <small class="text-muted">For account activation, please contact the administrator at <strong>0740859082</strong>.</small>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <p>Don't have an account? <a href="{{ route('register') }}">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>