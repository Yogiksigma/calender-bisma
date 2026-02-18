// public/js/calendar.js

let allUsers = [];

// Load users untuk admin
function loadUsers() {
    $.ajax({
        url: "/users",
        type: "GET",
        success: function (data) {
            allUsers = data;
            console.log("Users loaded:", allUsers);
        },
        error: function (xhr) {
            console.error("Failed to load users:", xhr);
        },
    });
}

document.addEventListener("DOMContentLoaded", function () {
    var calendarEl = document.getElementById("calendar");

    // Cek apakah user adalah admin dari data attribute
    var isAdmin = calendarEl.dataset.isAdmin === "true";

    // Load users jika admin
    if (isAdmin) {
        loadUsers();
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        locale: "id",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay",
        },
        buttonText: {
            today: "Hari Ini",
            month: "Bulan",
            week: "Minggu",
            day: "Hari",
        },

        events: function (info, successCallback, failureCallback) {
            $.ajax({
                url: "/events",
                type: "GET",
                success: function (data) {
                    console.log("Events loaded:", data);
                    successCallback(data);
                },
                error: function (xhr) {
                    console.error("Error loading events:", xhr);
                    failureCallback(xhr);
                },
            });
        },

        eventDidMount: function (info) {
            if (isAdmin && info.event.extendedProps.is_public !== undefined) {
                const badge = document.createElement("span");
                badge.className = info.event.extendedProps.is_public
                    ? "event-type-badge badge-public"
                    : "event-type-badge badge-private";
                badge.textContent = info.event.extendedProps.is_public
                    ? "Publik"
                    : "Private";
                info.el.querySelector(".fc-event-title").appendChild(badge);
            }
        },

        editable: isAdmin,
        selectable: isAdmin,

        dateClick: function (info) {
            if (!isAdmin) {
                return;
            }

            let userOptions = allUsers
                .map(
                    (user) =>
                        `<option value="${user.id}">${user.name} (${user.email})</option>`,
                )
                .join("");

            Swal.fire({
                title: "Tambah Kegiatan baru",
                html: `
                    <div class="modal-card">
                        <div class="form-group">
                            <label>Judul Kegiatan</label>
                            <input id="event-title" class="swal2-input custom-input" placeholder="Judul Kegiatan">
                        </div>

                        <div class="form-row">
                            <div style="flex:1;">
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input id="event-start" type="datetime-local" class="swal2-input custom-input" value="${info.dateStr}T09:00">
                                </div>
                            </div>
                            <div style="flex:1;">
                                <div class="form-group">
                                    <label>Berakhir</label>
                                    <input id="event-end" type="datetime-local" class="swal2-input custom-input" value="${info.dateStr}T10:00">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea id="event-description" class="swal2-textarea custom-textarea" placeholder="Deskripsi Kegiatan"></textarea>
                        </div>

                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="event-type" value="public" checked>Event Publik <small style="font-weight:400; color:#777;">(Semua user bisa lihat)</small></label>
                            <label class="radio-option"><input type="radio" name="event-type" value="private">Event Private <small style="font-weight:400; color:#777;">(Pilih user yang bisa lihat)</small></label>
                        </div>

                        <div class="form-group">
                            <select id="event-users" class="swal2-input" multiple style="width: 100%; display: none;">
                                ${userOptions}
                            </select>
                        </div>
                    </div>
                `,
                width: "520px",
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: "Simpan",
                cancelButtonText: "Batal",
                confirmButtonColor: "#B52026",
                didOpen: () => {
                    $("#event-users").select2({
                        placeholder: "Pilih user yang bisa melihat event ini",
                        dropdownParent: $(".swal2-container"),
                    });

                    $('input[name="event-type"]').on("change", function () {
                        if ($(this).val() === "private") {
                            $("#event-users").closest('.form-group').show();
                            $("#event-users").show();
                        } else {
                            $("#event-users").closest('.form-group').hide();
                            $("#event-users").hide();
                        }
                    });
                    // hide users initially
                    if ($('input[name="event-type"]:checked').val() === 'public') {
                        $("#event-users").closest('.form-group').hide();
                    }
                },
                willClose: () => {
                    if (
                        $("#event-users").hasClass("select2-hidden-accessible")
                    ) {
                        $("#event-users").select2("destroy");
                    }
                },
                preConfirm: () => {
                    const title = document.getElementById("event-title").value;
                    const start = document.getElementById("event-start").value;
                    const end = document.getElementById("event-end").value;
                    const description =
                        document.getElementById("event-description").value;
                    const eventType = $(
                        'input[name="event-type"]:checked',
                    ).val();
                    const isPublic = eventType === "public";
                    const userIds = $("#event-users").val() || [];

                    if (!title) {
                        Swal.showValidationMessage(
                            "Judul kegiatan harus diisi!",
                        );
                        return false;
                    }

                    if (!isPublic && userIds.length === 0) {
                        Swal.showValidationMessage(
                            "Pilih minimal 1 user untuk event private!",
                        );
                        return false;
                    }

                    return {
                        title,
                        start,
                        end,
                        description,
                        isPublic,
                        userIds,
                    };
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    const eventData = {
                        title: result.value.title,
                        start: result.value.start,
                        end: result.value.end,
                        description: result.value.description,
                        is_public: result.value.isPublic ? 1 : 0,
                        user_ids: result.value.userIds,
                        _token: $('meta[name="csrf-token"]').attr("content"),
                    };

                    $.ajax({
                        url: "/events",
                        type: "POST",
                        data: eventData,
                        success: function (data) {
                            calendar.refetchEvents();
                            Swal.fire({
                                icon: "success",
                                title: "Berhasil!",
                                text: "Kegiatan berhasil ditambahkan",
                                timer: 2000,
                                confirmButtonColor: "#B52026",
                            });
                        },
                        error: function (xhr) {
                            console.error("Error creating event:", xhr);
                            let errorMessage = "Gagal menambahkan kegiatan";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            Swal.fire({
                                icon: "error",
                                title: "Gagal!",
                                text: errorMessage,
                                confirmButtonColor: "#B52026",
                            });
                        },
                    });
                }
            });
        },

        eventClick: function (info) {
            let eventInfo = `
                <p><strong>Mulai:</strong> ${info.event.start.toLocaleString("id-ID")}</p>
                ${info.event.end ? `<p><strong>Selesai:</strong> ${info.event.end.toLocaleString("id-ID")}</p>` : ""}
                ${info.event.extendedProps.description ? `<p><strong>Deskripsi:</strong> ${info.event.extendedProps.description}</p>` : ""}
            `;

            if (isAdmin) {
                eventInfo += `<p><strong>Tipe:</strong> ${info.event.extendedProps.is_public ? '<span class="badge badge-public">Publik</span>' : '<span class="badge badge-private">Private</span>'}</p>`;

                if (
                    !info.event.extendedProps.is_public &&
                    info.event.extendedProps.assigned_users
                ) {
                    eventInfo += `<p><strong>Dapat dilihat oleh:</strong> ${info.event.extendedProps.assigned_users.join(", ")}</p>`;
                }
            }

            if (!isAdmin) {
                Swal.fire({
                    title: info.event.title,
                    html: eventInfo,
                    icon: "info",
                    confirmButtonColor: "#B52026",
                });
                return;
            }

            Swal.fire({
                title: info.event.title,
                html: eventInfo,
                icon: "question",
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: "Hapus",
                denyButtonText: "Edit",
                cancelButtonText: "Tutup",
                confirmButtonColor: "#dc3545",
                denyButtonColor: "#B52026",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/events/" + info.event.id,
                        type: "DELETE",
                        data: {
                            _token: $('meta[name="csrf-token"]').attr(
                                "content",
                            ),
                        },
                        success: function () {
                            info.event.remove();
                            Swal.fire({
                                icon: "success",
                                title: "Terhapus!",
                                text: "Kegiatan berhasil dihapus",
                                timer: 2000,
                                confirmButtonColor: "#B52026",
                            });
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: "error",
                                title: "Gagal!",
                                text: "Gagal menghapus kegiatan",
                                confirmButtonColor: "#B52026",
                            });
                        },
                    });
                } else if (result.isDenied) {
                    const startDate = info.event.start
                        .toISOString()
                        .slice(0, 16);
                    const endDate = info.event.end
                        ? info.event.end.toISOString().slice(0, 16)
                        : startDate;

                    let userOptions = allUsers
                        .map(
                            (user) =>
                                `<option value="${user.id}">${user.name} (${user.email})</option>`,
                        )
                        .join("");

                    Swal.fire({
                        title: "Edit Kegiatan",
                        html: `
                            <div class="modal-card">
                                <p class="modal-subtitle">edit jadwal</p>

                                <div class="form-group">
                                    <label>Judul Kegiatan</label>
                                    <input id="edit-title" class="swal2-input custom-input" value="${info.event.title}">
                                </div>

                                <div class="form-row">
                                    <div style="flex:1;">
                                        <div class="form-group">
                                            <label>Tanggal</label>
                                            <input id="edit-start" type="datetime-local" class="swal2-input custom-input" value="${startDate}">
                                        </div>
                                    </div>
                                    <div style="flex:1;">
                                        <div class="form-group">
                                            <label>Berakhir</label>
                                            <input id="edit-end" type="datetime-local" class="swal2-input custom-input" value="${endDate}">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Deskripsi</label>
                                    <textarea id="edit-description" class="swal2-textarea custom-textarea">${info.event.extendedProps.description || ""}</textarea>
                                </div>

                                <div class="radio-group">
                                    <label class="radio-option"><input type="radio" name="edit-event-type" value="public" ${info.event.extendedProps.is_public ? "checked" : ""}>Event Publik</label>
                                    <label class="radio-option"><input type="radio" name="edit-event-type" value="private" ${!info.event.extendedProps.is_public ? "checked" : ""}>Event Private</label>
                                </div>

                                <div class="form-group">
                                    <select id="edit-users" class="swal2-input" multiple style="width: 100%; ${info.event.extendedProps.is_public ? "display: none;" : ""}">
                                        ${userOptions}
                                    </select>
                                </div>
                            </div>
                        `,
                        width: "520px",
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: "Update",
                        cancelButtonText: "Batal",
                        confirmButtonColor: "#B52026",
                        didOpen: () => {
                            $("#edit-users").select2({
                                placeholder: "Pilih user",
                                dropdownParent: $(".swal2-container"),
                            });

                            $('input[name="edit-event-type"]').on(
                                "change",
                                function () {
                                    if ($(this).val() === "private") {
                                        $("#edit-users").closest('.form-group').show();
                                        $("#edit-users").show();
                                    } else {
                                        $("#edit-users").closest('.form-group').hide();
                                        $("#edit-users").hide();
                                    }
                                },
                            );
                            if ($('input[name="edit-event-type"]:checked').val() === 'public') {
                                $("#edit-users").closest('.form-group').hide();
                            }
                        },
                        willClose: () => {
                            if (
                                $("#edit-users").hasClass(
                                    "select2-hidden-accessible",
                                )
                            ) {
                                $("#edit-users").select2("destroy");
                            }
                        },
                        preConfirm: () => {
                            const title =
                                document.getElementById("edit-title").value;
                            const start =
                                document.getElementById("edit-start").value;
                            const end =
                                document.getElementById("edit-end").value;
                            const description =
                                document.getElementById(
                                    "edit-description",
                                ).value;
                            const eventType = $(
                                'input[name="edit-event-type"]:checked',
                            ).val();
                            const isPublic = eventType === "public";
                            const userIds = $("#edit-users").val() || [];

                            if (!title) {
                                Swal.showValidationMessage(
                                    "Judul harus diisi!",
                                );
                                return false;
                            }

                            return {
                                title,
                                start,
                                end,
                                description,
                                isPublic,
                                userIds,
                            };
                        },
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
                                _token: $('meta[name="csrf-token"]').attr(
                                    "content",
                                ),
                            };

                            $.ajax({
                                url: "/events/" + info.event.id,
                                type: "PUT",
                                data: updateData,
                                success: function (data) {
                                    calendar.refetchEvents();
                                    Swal.fire({
                                        icon: "success",
                                        title: "Berhasil!",
                                        text: "Kegiatan berhasil diupdate",
                                        confirmButtonColor: "#B52026",
                                    });
                                },
                                error: function (xhr) {
                                    Swal.fire({
                                        icon: "error",
                                        title: "Gagal!",
                                        text: "Gagal mengupdate kegiatan",
                                        confirmButtonColor: "#B52026",
                                    });
                                },
                            });
                        }
                    });
                }
            });
        },

        eventDrop: function (info) {
            if (!isAdmin) {
                info.revert();
                return;
            }

            $.ajax({
                url: "/events/" + info.event.id,
                type: "PUT",
                data: {
                    start: info.event.start.toISOString(),
                    end: info.event.end ? info.event.end.toISOString() : null,
                    _token: $('meta[name="csrf-token"]').attr("content"),
                },
                error: function () {
                    Swal.fire({
                        icon: "error",
                        title: "Gagal!",
                        text: "Gagal memperbarui kegiatan",
                        confirmButtonColor: "#B52026",
                    });
                    info.revert();
                },
            });
        },

        eventResize: function (info) {
            if (!isAdmin) {
                info.revert();
                return;
            }

            $.ajax({
                url: "/events/" + info.event.id,
                type: "PUT",
                data: {
                    start: info.event.start.toISOString(),
                    end: info.event.end.toISOString(),
                    _token: $('meta[name="csrf-token"]').attr("content"),
                },
                error: function () {
                    Swal.fire({
                        icon: "error",
                        title: "Gagal!",
                        text: "Gagal memperbarui kegiatan",
                        confirmButtonColor: "#B52026",
                    });
                    info.revert();
                },
            });
        },
    });

    calendar.render();
});
