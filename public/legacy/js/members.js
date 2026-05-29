const API_URL = "/members/api/list";
const SUPABASE_PROFILE_PICTURES_BASE = "https://luiillhngqpddvlbeeay.supabase.co/storage/v1/object/public/profile-pictures/";

function getProfilePictureUrl(picture) {
    const value = typeof picture === "string" ? picture.trim() : "";

    if (!value) {
        return `${SUPABASE_PROFILE_PICTURES_BASE}default.png`;
    }

    if (/^https?:\/\//i.test(value)) {
        return value;
    }

    return `${SUPABASE_PROFILE_PICTURES_BASE}${value}`;
}

function loadUsers() {
    fetch(API_URL, { credentials: 'include' })
        .then(res => {
            if (!res.ok) {
                throw new Error(`Failed to load members: ${res.status}`);
            }
            return res.json();
        })
        .then(users => {

            const tbody = document.getElementById("table-body");
            tbody.innerHTML = "";

            users.forEach(m => {
                const pictureUrl = getProfilePictureUrl(m.picture);
                tbody.innerHTML += `
                    <tr>
                        <td>
                            <img src="${pictureUrl}" class="member-img">
                        </td>
                        <td>${m.firstname}</td>
                        <td>${m.lastname}</td>
                        <td>${m.department}</td>
                        <td>${m.fieldofstudy}</td>
                        <td>
                            <a href="/members/profile/${m.id}">ℹ️</a>
                            <button class="delete-btn" data-id="${m.id}">🧹</button>
                        </td>
                    </tr>
                `;
            });

            if ($.fn.DataTable.isDataTable("#members-list")) {
                $("#members-list").DataTable().destroy();
            }

            $("#members-list").DataTable({
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
            });
        });
}

loadUsers();

// ================================
// DELETE
// ================================

$(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');
    confirmDelete(id);
});

async function confirmDelete(id) {

    const result = await Swal.fire({
        title: "Are you sure you want to delete this account?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete",
        cancelButtonText: "Cancel"
    });

    if (result.isConfirmed) {

        const res = await fetch(
            `/members/api/delete/${id}`,
            { method: 'DELETE', credentials: 'include' }
        );

        if (res.ok) {
            await Swal.fire("Deleted!", "The record has been removed.", "success");
            loadUsers();
        }
    }
}

// ================================
// FILTER
// ================================

document.getElementById("filter-section").addEventListener("submit", async (e) => {
    e.preventDefault();

    const col = document.querySelector("select[id='filter-column']").value;
    const val = document.querySelector("input[id='filter-value']").value;

    const res = await fetch(
        `/members/api/filter?filter-column=${col}&filter-value=${val}`,
        { credentials: 'include' }
    );

    const users = await res.json();

    if ($.fn.DataTable.isDataTable("#members-list")) {
        $("#members-list").DataTable().destroy();
    }

    const tbody = document.getElementById("table-body");
    tbody.innerHTML = "";

    users.forEach(m => {
        const pictureUrl = getProfilePictureUrl(m.picture);
        tbody.innerHTML += `
            <tr>
                <td><img src="${pictureUrl}" class="member-img"></td>
                <td>${m.firstname}</td>
                <td>${m.lastname}</td>
                <td>${m.department}</td>
                <td>${m.fieldofstudy}</td>
                <td>
                    <a href="/members/profile/${m.id}">ℹ️</a>
                    <button class="delete-btn" data-id="${m.id}">🧹</button>
                </td>
            </tr>
        `;
    });

    $("#members-list").DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
    });
});