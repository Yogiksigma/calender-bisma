<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bisma Informatika - Kalender</title>
    
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="{{ asset('css/calendar.css') }}" rel="stylesheet">
</head>
<body>
    <!-- Navbar Merah -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="{{ asset('images/logo-bisma.png') }}" alt="Bisma Informatika">
        </a>
        <div class="d-flex align-items-center">
            <!-- Welcome Message -->
            <span class="welcome-text">
                Selamat Datang, <strong>{{ auth()->user()->name }}</strong>!
            </span>
            
            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm">
                    Log Out
                </button>
            </form>
        </div>
    </div>
</nav>

    <div class="container">
        <div class="text-center mb-4">
            <h1>Kalender Kegiatan</h1>
            @if(auth()->user()->isAdmin())
                <p class="text-success">
                    Selamat datang, Admin! Klik pada tanggal untuk menambahkan kegiatan baru.
                </p>
            @else
                <p class="text-muted">
                    Anda dapat melihat kegiatan publik dan kegiatan yang di-assign kepada Anda
                </p>
            @endif
        </div>
        
        <div id='calendar' data-is-admin="{{ auth()->user()->isAdmin() ? 'true' : 'false' }}"></div>
    </div>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="{{ asset('js/calendar.js') }}"></script>
</body>
</html>