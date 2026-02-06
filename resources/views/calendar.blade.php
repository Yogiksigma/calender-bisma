<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FullCalendar Laravel</title>
    
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
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
        .event-type-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 5px;
        }
        .badge-public {
            background-color: #28a745;
            color: white;
        }
        .badge-private {
            background-color: #ffc107;
            color: black;
        }
        .select2-container {
            width: 100% !important;
            z-index: 9999 !important;
        }
        .swal2-html-container {
            overflow: visible !important;
        }
        
        /* Fix untuk masalah overlap toolbar */
        .fc .fc-toolbar {
            margin-bottom: 1.5em !important;
            position: relative !important;
            z-index: 100 !important;
            height: auto !important;
            min-height: 40px !important;
        }
        
        .fc .fc-toolbar-chunk {
            position: relative !important;
            z-index: 100 !important;
        }
        
        /* Pastikan tombol bisa diklik */
        .fc-button,
        .fc .fc-button {
            position: relative !important;
            z-index: 101 !important;
            pointer-events: auto !important;
            cursor: pointer !important;
        }
        
        /* Fix khusus untuk list view */
        .fc-list {
            margin-top: 20px !important;
            position: relative !important;
            z-index: 1 !important;
        }
        
        .fc-scroller,
        .fc-list-table {
            position: relative !important;
            z-index: 1 !important;
        }
        
        /* Jangan biarkan elemen lain menutupi toolbar */
        .fc-view-harness {
            position: relative !important;
            z-index: 1 !important;
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
                    <strong>✓ Mode Admin:</strong> Klik tanggal untuk menambah kegiatan, klik kegiatan untuk menghapus/edit
                </p>
                <p class="text-muted small">
                    <span class="badge badge-public">Publik</span> = Semua user bisa lihat | 
                    <span class="badge badge-private">Private</span> = Hanya user tertentu yang bisa lihat
                </p>
            @else
                <p class="text-muted">Anda dapat melihat kegiatan publik dan kegiatan yang di-assign kepada Anda</p>
            @endif
        </div>
        <div id='calendar'></div>
    </div>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let allUsers = [];

        @if(auth()->user()->isAdmin())
        $.ajax({
            url: '/users',
            type: 'GET',
            success: function(data) {
                allUsers = data;
                console.log('✅ Users loaded:', allUsers);
            },
            error: function(xhr) {
                console.error('❌ Failed to load users:', xhr);
            }
        });
        @endif

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
                
                events: function(info, successCallback, failureCallback) {
                    $.ajax({
                        url: '/events',
                        type: 'GET',
                        success: function(data) {
                            console.log('Events loaded:', data);
                            successCallback(data);
                        },
                        error: function(xhr) {
                            console.error('Error loading events:', xhr);
                            failureCallback(xhr);
                        }
                    });
                },
                
                eventDidMount: function(info) {
                    if (isAdmin && info.event.extendedProps.is_public !== undefined) {
                        const badge = document.createElement('span');
                        badge.className = info.event.extendedProps.is_public ? 'event-type-badge badge-public' : 'event-type-badge badge-private';
                        badge.textContent = info.event.extendedProps.is_public ? 'Publik' : 'Private';
                        info.el.querySelector('.fc-event-title').appendChild(badge);
                    }
                },
                
                editable: isAdmin,
                selectable: isAdmin,
                
                dateClick: function(info) {
                    if (!isAdmin) {
                        return;
                    }
                    
                    let userOptions = allUsers.map(user => 
                        `<option value="${user.id}">${user.name} (${user.email})</option>`
                    ).join('');
                    
                    Swal.fire({
                        title: 'Tambah Kegiatan Baru',
                        html: `
                            <input id="event-title" class="swal2-input" placeholder="Judul Kegiatan">
                            <input id="event-start" type="datetime-local" class="swal2-input" value="${info.dateStr}T09:00">
                            <input id="event-end" type="datetime-local" class="swal2-input" value="${info.dateStr}T10:00">
                            <input id="event-color" type="color" class="swal2-input" value="#3788d8">
                            <textarea id="event-description" class="swal2-textarea" placeholder="Deskripsi (opsional)"></textarea>
                            
                            <div style="margin-top: 15px; text-align: left;">
                                <label style="display: block; margin-bottom: 10px;">
                                    <input type="radio" name="event-type" value="public" checked> 
                                    <strong>Event Publik</strong> <small>(Semua user bisa lihat)</small>
                                </label>
                                <label style="display: block; margin-bottom: 10px;">
                                    <input type="radio" name="event-type" value="private"> 
                                    <strong>Event Private</strong> <small>(Pilih user yang bisa lihat)</small>
                                </label>
                            </div>
                            
                            <select id="event-users" class="swal2-input" multiple style="width: 100%; display: none;">
                                ${userOptions}
                            </select>
                        `,
                        width: '600px',
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Simpan',
                        cancelButtonText: 'Batal',
                        didOpen: () => {
                            $('#event-users').select2({
                                placeholder: 'Pilih user yang bisa melihat event ini',
                                dropdownParent: $('.swal2-container')
                            });
                            
                            $('input[name="event-type"]').on('change', function() {
                                if ($(this).val() === 'private') {
                                    $('#event-users').show();
                                } else {
                                    $('#event-users').hide();
                                }
                            });
                        },
                        willClose: () => {
                            if ($('#event-users').hasClass('select2-hidden-accessible')) {
                                $('#event-users').select2('destroy');
                            }
                        },
                        preConfirm: () => {
                            const title = document.getElementById('event-title').value;
                            const start = document.getElementById('event-start').value;
                            const end = document.getElementById('event-end').value;
                            const color = document.getElementById('event-color').value;
                            const description = document.getElementById('event-description').value;
                            const eventType = $('input[name="event-type"]:checked').val();
                            const isPublic = eventType === 'public';
                            const userIds = $('#event-users').val() || [];
    
                            if (!title) {
                                Swal.showValidationMessage('Judul kegiatan harus diisi!');
                                return false;
                            }
    
                            if (!isPublic && userIds.length === 0) {
                                Swal.showValidationMessage('Pilih minimal 1 user untuk event private!');
                                return false;
                            }
    
                            return { title, start, end, color, description, isPublic, userIds };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const eventData = {
                                title: result.value.title,
                                start: result.value.start,
                                end: result.value.end,
                                color: result.value.color,
                                description: result.value.description,
                                is_public: result.value.isPublic ? 1 : 0,
                                user_ids: result.value.userIds,
                                _token: $('meta[name="csrf-token"]').attr('content')
                            };

                            $.ajax({
                                url: '/events',
                                type: 'POST',
                                data: eventData,
                                success: function(data) {
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
                                    let errorMessage = 'Gagal menambahkan kegiatan';
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    }
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: errorMessage
                                    });
                                }
                            });
                        }
                    });
                },
                
                eventClick: function(info) {
                    let eventInfo = `
                        <p><strong>Mulai:</strong> ${info.event.start.toLocaleString('id-ID')}</p>
                        ${info.event.end ? `<p><strong>Selesai:</strong> ${info.event.end.toLocaleString('id-ID')}</p>` : ''}
                        ${info.event.extendedProps.description ? `<p><strong>Deskripsi:</strong> ${info.event.extendedProps.description}</p>` : ''}
                    `;
                    
                    if (isAdmin) {
                        eventInfo += `<p><strong>Tipe:</strong> ${info.event.extendedProps.is_public ? '<span class="badge badge-public">Publik</span>' : '<span class="badge badge-private">Private</span>'}</p>`;
                        
                        if (!info.event.extendedProps.is_public && info.event.extendedProps.assigned_users) {
                            eventInfo += `<p><strong>Dapat dilihat oleh:</strong> ${info.event.extendedProps.assigned_users.join(', ')}</p>`;
                        }
                    }
                    
                    if (!isAdmin) {
                        Swal.fire({
                            title: info.event.title,
                            html: eventInfo,
                            icon: 'info'
                        });
                        return;
                    }
                    
                    Swal.fire({
                        title: info.event.title,
                        html: eventInfo,
                        icon: 'question',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonText: 'Hapus',
                        denyButtonText: 'Edit',
                        cancelButtonText: 'Tutup',
                        confirmButtonColor: '#dc3545'
                    }).then((result) => {
                        if (result.isConfirmed) {
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
                                    Swal.fire('Gagal!', 'Gagal menghapus kegiatan', 'error');
                                }
                            });
                        } else if (result.isDenied) {
                            const startDate = info.event.start.toISOString().slice(0, 16);
                            const endDate = info.event.end ? info.event.end.toISOString().slice(0, 16) : startDate;
                            
                            let userOptions = allUsers.map(user => 
                                `<option value="${user.id}">${user.name} (${user.email})</option>`
                            ).join('');
                            
                            Swal.fire({
                                title: 'Edit Kegiatan',
                                html: `
                                    <input id="edit-title" class="swal2-input" value="${info.event.title}">
                                    <input id="edit-start" type="datetime-local" class="swal2-input" value="${startDate}">
                                    <input id="edit-end" type="datetime-local" class="swal2-input" value="${endDate}">
                                    <input id="edit-color" type="color" class="swal2-input" value="${info.event.backgroundColor || '#3788d8'}">
                                    <textarea id="edit-description" class="swal2-textarea">${info.event.extendedProps.description || ''}</textarea>
                                    
                                    <div style="margin-top: 15px; text-align: left;">
                                        <label style="display: block; margin-bottom: 10px;">
                                            <input type="radio" name="edit-event-type" value="public" ${info.event.extendedProps.is_public ? 'checked' : ''}> 
                                            <strong>Event Publik</strong>
                                        </label>
                                        <label style="display: block; margin-bottom: 10px;">
                                            <input type="radio" name="edit-event-type" value="private" ${!info.event.extendedProps.is_public ? 'checked' : ''}> 
                                            <strong>Event Private</strong>
                                        </label>
                                    </div>
                                    
                                    <select id="edit-users" class="swal2-input" multiple style="width: 100%; ${info.event.extendedProps.is_public ? 'display: none;' : ''}">
                                        ${userOptions}
                                    </select>
                                `,
                                width: '600px',
                                focusConfirm: false,
                                showCancelButton: true,
                                confirmButtonText: 'Update',
                                cancelButtonText: 'Batal',
                                didOpen: () => {
                                    $('#edit-users').select2({
                                        placeholder: 'Pilih user',
                                        dropdownParent: $('.swal2-container')
                                    });
                                    
                                    $('input[name="edit-event-type"]').on('change', function() {
                                        if ($(this).val() === 'private') {
                                            $('#edit-users').show();
                                        } else {
                                            $('#edit-users').hide();
                                        }
                                    });
                                },
                                willClose: () => {
                                    if ($('#edit-users').hasClass('select2-hidden-accessible')) {
                                        $('#edit-users').select2('destroy');
                                    }
                                },
                                preConfirm: () => {
                                    const title = document.getElementById('edit-title').value;
                                    const start = document.getElementById('edit-start').value;
                                    const end = document.getElementById('edit-end').value;
                                    const color = document.getElementById('edit-color').value;
                                    const description = document.getElementById('edit-description').value;
                                    const eventType = $('input[name="edit-event-type"]:checked').val();
                                    const isPublic = eventType === 'public';
                                    const userIds = $('#edit-users').val() || [];
                                    
                                    if (!title) {
                                        Swal.showValidationMessage('Judul harus diisi!');
                                        return false;
                                    }
                                    
                                    return { title, start, end, color, description, isPublic, userIds };
                                }
                            }).then((editResult) => {
                                if (editResult.isConfirmed) {
                                    const updateData = {
                                        title: editResult.value.title,
                                        start: editResult.value.start,
                                        end: editResult.value.end,
                                        color: editResult.value.color,
                                        description: editResult.value.description,
                                        is_public: editResult.value.isPublic ? 1 : 0,
                                        user_ids: editResult.value.userIds,
                                        _token: $('meta[name="csrf-token"]').attr('content')
                                    };

                                    $.ajax({
                                        url: '/events/' + info.event.id,
                                        type: 'PUT',
                                        data: updateData,
                                        success: function(data) {
                                            calendar.refetchEvents();
                                            Swal.fire('Berhasil!', 'Kegiatan berhasil diupdate', 'success');
                                        },
                                        error: function(xhr) {
                                            Swal.fire('Gagal!', 'Gagal mengupdate kegiatan', 'error');
                                        }
                                    });
                                }
                            });
                        }
                    });
                },
                
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
            
            // Force enable button clicks - solusi untuk masalah overlap
            setTimeout(function() {
                document.querySelectorAll('.fc-button').forEach(function(btn) {
                    btn.style.pointerEvents = 'auto';
                    btn.style.cursor = 'pointer';
                    btn.style.position = 'relative';
                    btn.style.zIndex = '1000';
                });
                
                const toolbar = document.querySelector('.fc-toolbar');
                if (toolbar) {
                    toolbar.style.position = 'relative';
                    toolbar.style.zIndex = '999';
                    toolbar.style.marginBottom = '20px';
                }
                
                const listView = document.querySelector('.fc-list');
                if (listView) {
                    listView.style.marginTop = '30px';
                    listView.style.position = 'relative';
                    listView.style.zIndex = '1';
                }
            }, 500);
            
            calendar.on('viewDidMount', function() {
                setTimeout(function() {
                    document.querySelectorAll('.fc-button').forEach(function(btn) {
                        btn.style.pointerEvents = 'auto';
                        btn.style.cursor = 'pointer';
                        btn.style.position = 'relative';
                        btn.style.zIndex = '1000';
                    });
                }, 100);
            });
        });
    </script>
</body>
</html>