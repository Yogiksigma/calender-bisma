<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalender Bisma - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/register.css') }}" rel="stylesheet">
</head>
<body style="padding-top: 70px;">
    <!-- Navbar Merah -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #b52026 !important; margin-bottom: 0; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; width: 100%;">
        <div class="container">
            <a class="navbar-brand" href="/" style="font-size: 1.2rem; font-weight: 600; display: flex; align-items: center;">
                <img src="{{ asset('images/Logo-Bisma-Informatika-Indonesia-Merah (2) 2 (1).png') }}" alt="Bisma Informatika" style="height: 50px; margin-right: 15px;">
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="register-card text-center">
                    @if (file_exists(public_path('images/logo-bisma.png')))
                        <img src="{{ asset('images/logo-bisma.png') }}" alt="Logo" class="mb-3 login-logo">
                    @endif
                    <div class="mb-3">
                        <h2>Kalender Bisma</h2>
                        <p class="text-muted small">Silahkan Register untuk melanjutkan halaman berikutnya</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        
                        <div class="mb-3 text-start">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                value="{{ old('name') }}" required autofocus>
                        </div>

                        <div class="mb-3 text-start">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                value="{{ old('email') }}" required>
                        </div>

                        <div class="mb-3 text-start">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted d-block">Minimal 8 karakter</small>
                        </div>

                        <div class="mb-3 text-start">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirmation" 
                                name="password_confirmation" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">Daftar</button>

                        <div class="text-center">
                            <p class="mb-0">Sudah punya akun? <a href="{{ route('login') }}">Login disini</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>