<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FullCalendar Laravel</title>
    
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        #calendar {
            max-width: 1100px;
            margin: 40px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .navbar {
            margin-bottom: 20px;
            border-radius: 10px;
        }
        .user-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        .admin-badge {
            background-color: #dc3545;
            color: white;
        }
        .user-badge-normal {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">📅 Calendar App</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <strong>{{ auth()->user()->name }}</strong>
                    <span class="user-badge {{ auth()->user()->isAdmin() ? 'admin-badge' : 'user-badge-normal' }}">
                        {{ auth()->user()->isAdmin() ? 'Admin' : 'User' }}
                    </span>
                </span>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">
                        <i>🚪</i> Logout
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
                    <strong>✓ Mode Admin:</strong> Klik tanggal untuk menambah kegiatan, klik kegiatan untuk menghapus
                </p>
            @else
                <p class="text-muted">Anda hanya dapat melihat kegiatan yang tersedia</p>
            @endif
        </div>
        <div id='calendar'></div>
    </div>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var isAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'id',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: {
                    today: 'Hari Ini',
                    month: 'Bulan',
                    week: 'Minggu',
                    day: 'Hari',
                    list: 'Daftar'
                },
                
                // Load events dari server
                events: function(info, successCallback, failureCallback) {
                    $.ajax({
                        url: '/events',
                        type: 'GET',
                        success: function(data) {
                            console.log('Events loaded:', data); // Debug
                            successCallback(data);
                        },
                        error: function(xhr) {
                            console.error('Error loading events:', xhr);
                            failureCallback(xhr);
                        }
                    });
                },
                
                // Event bisa di-drag hanya untuk admin
                editable: isAdmin,
                selectable: isAdmin,
                
                // Klik pada tanggal untuk tambah event (hanya admin)
                dateClick: function(info) {
                    if (!isAdmin) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Akses Ditolak',
                            text: 'Anda tidak memiliki akses untuk menambah kegiatan'
                        });
                        return;
                    }
                    
                    Swal.fire({
                        title: 'Tambah Kegiatan Baru',
                        html: `
                            <input id="event-title" class="swal2-input" placeholder="Judul Kegiatan">
                            <input id="event-start" type="datetime-local" class="swal2-input" value="${info.dateStr}T09:00">
                            <input id="event-end" type="datetime-local" class="swal2-input" value="${info.dateStr}T10:00">
                            <input id="event-color" type="color" class="swal2-input" value="#3788d8">
                            <textarea id="event-description" class="swal2-textarea" placeholder="Deskripsi (opsional)"></textarea>
                        `,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Simpan',
                        cancelButtonText: 'Batal',
                        preConfirm: () => {
                            const title = document.getElementById('event-title').value;
                            const start = document.getElementById('event-start').value;
                            const end = document.getElementById('event-end').value;
                            const color = document.getElementById('event-color').value;
                            const description = document.getElementById('event-description').value;
                            
                            if (!title) {
                                Swal.showValidationMessage('Judul kegiatan harus diisi!');
                                return false;
                            }
                            
                            return { title, start, end, color, description };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '/events',
                                type: 'POST',
                                data: {
                                    title: result.value.title,
                                    start: result.value.start,
                                    end: result.value.end,
                                    color: result.value.color,
                                    description: result.value.description,
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(data) {
                                    console.log('Event created:', data); // Debug
                                    calendar.refetchEvents();
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: 'Kegiatan berhasil ditambahkan',
                                        timer: 2000
                                    });
                                },
                                error: function(xhr) {
                                    console.error('Error creating event:', xhr);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: 'Gagal menambahkan kegiatan: ' + (xhr.responseJSON?.message || 'Unknown error')
                                    });
                                }
                            });
                        }
                    });
                },
                
                // Klik pada event
                eventClick: function(info) {
                    if (!isAdmin) {
                        Swal.fire({
                            title: info.event.title,
                            html: `
                                <p><strong>Mulai:</strong> ${info.event.start.toLocaleString('id-ID')}</p>
                                ${info.event.end ? `<p><strong>Selesai:</strong> ${info.event.end.toLocaleString('id-ID')}</p>` : ''}
                                ${info.event.extendedProps.description ? `<p><strong>Deskripsi:</strong> ${info.event.extendedProps.description}</p>` : ''}
                            `,
                            icon: 'info'
                        });
                        return;
                    }
                    
                    Swal.fire({
                        title: info.event.title,
                        html: `
                            <p><strong>Mulai:</strong> ${info.event.start.toLocaleString('id-ID')}</p>
                            ${info.event.end ? `<p><strong>Selesai:</strong> ${info.event.end.toLocaleString('id-ID')}</p>` : ''}
                            ${info.event.extendedProps.description ? `<p><strong>Deskripsi:</strong> ${info.event.extendedProps.description}</p>` : ''}
                        `,
                        icon: 'question',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonText: 'Hapus',
                        denyButtonText: 'Edit',
                        cancelButtonText: 'Tutup',
                        confirmButtonColor: '#dc3545'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Hapus event
                            $.ajax({
                                url: '/events/' + info.event.id,
                                type: 'DELETE',
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function() {
                                    info.event.remove();
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Terhapus!',
                                        text: 'Kegiatan berhasil dihapus',
                                        timer: 2000
                                    });
                                },
                                error: function(xhr) {
                                    console.error('Error deleting event:', xhr);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: 'Gagal menghapus kegiatan'
                                    });
                                }
                            });
                        } else if (result.isDenied) {
                            // Edit event
                            const startDate = info.event.start.toISOString().slice(0, 16);
                            const endDate = info.event.end ? info.event.end.toISOString().slice(0, 16) : startDate;
                            
                            Swal.fire({
                                title: 'Edit Kegiatan',
                                html: `
                                    <input id="edit-title" class="swal2-input" placeholder="Judul Kegiatan" value="${info.event.title}">
                                    <input id="edit-start" type="datetime-local" class="swal2-input" value="${startDate}">
                                    <input id="edit-end" type="datetime-local" class="swal2-input" value="${endDate}">
                                    <input id="edit-color" type="color" class="swal2-input" value="${info.event.backgroundColor || '#3788d8'}">
                                    <textarea id="edit-description" class="swal2-textarea" placeholder="Deskripsi (opsional)">${info.event.extendedProps.description || ''}</textarea>
                                `,
                                focusConfirm: false,
                                showCancelButton: true,
                                confirmButtonText: 'Update',
                                cancelButtonText: 'Batal',
                                preConfirm: () => {
                                    const title = document.getElementById('edit-title').value;
                                    const start = document.getElementById('edit-start').value;
                                    const end = document.getElementById('edit-end').value;
                                    const color = document.getElementById('edit-color').value;
                                    const description = document.getElementById('edit-description').value;
                                    
                                    if (!title) {
                                        Swal.showValidationMessage('Judul kegiatan harus diisi!');
                                        return false;
                                    }
                                    
                                    return { title, start, end, color, description };
                                }
                            }).then((editResult) => {
                                if (editResult.isConfirmed) {
                                    $.ajax({
                                        url: '/events/' + info.event.id,
                                        type: 'PUT',
                                        data: {
                                            title: editResult.value.title,
                                            start: editResult.value.start,
                                            end: editResult.value.end,
                                            color: editResult.value.color,
                                            description: editResult.value.description,
                                            _token: $('meta[name="csrf-token"]').attr('content')
                                        },
                                        success: function(data) {
                                            calendar.refetchEvents();
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Berhasil!',
                                                text: 'Kegiatan berhasil diupdate',
                                                timer: 2000
                                            });
                                        },
                                        error: function(xhr) {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Gagal!',
                                                text: 'Gagal mengupdate kegiatan'
                                            });
                                        }
                                    });
                                }
                            });
                        }
                    });
                },
                
                // Drag & drop event (hanya admin)
                eventDrop: function(info) {
                    if (!isAdmin) {
                        info.revert();
                        return;
                    }
                    
                    $.ajax({
                        url: '/events/' + info.event.id,
                        type: 'PUT',
                        data: {
                            start: info.event.start.toISOString(),
                            end: info.event.end ? info.event.end.toISOString() : null,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        error: function() {
                            Swal.fire('Gagal!', 'Gagal memperbarui kegiatan', 'error');
                            info.revert();
                        }
                    });
                },
                
                // Resize event (hanya admin)
                eventResize: function(info) {
                    if (!isAdmin) {
                        info.revert();
                        return;
                    }
                    
                    $.ajax({
                        url: '/events/' + info.event.id,
                        type: 'PUT',
                        data: {
                            start: info.event.start.toISOString(),
                            end: info.event.end.toISOString(),
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        error: function() {
                            Swal.fire('Gagal!', 'Gagal memperbarui kegiatan', 'error');
                            info.revert();
                        }
                    });
                }
            });
            
            calendar.render();
        });
    </script>
</body>
</html>