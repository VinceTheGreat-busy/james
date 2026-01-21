document.addEventListener("DOMContentLoaded", () => {
    const inputs = document.querySelectorAll("input, select, textarea");

    inputs.forEach(input => {
        const key = "form_" + input.name;

        // restore value
        if (!input.value) {
            input.value = localStorage.getItem(key) || "";
        }

        // save on change
        input.addEventListener("input", () => {
            localStorage.setItem(key, input.value);
        });
    });
});

function addItem() {
    const modal = document.getElementById('addItemModal');
    modal.style.display = 'block';
};

function closeModal() {
    const modal = document.getElementById('addItemModal');
    modal.style.display = 'none';
};

window.onclick = function (event) {
    const modal = document.getElementById("addItemModal");
    if (event.target === modal) {
        modal.style.display = "none";
    }
};

function openEditModal(id, name, quantity, type, issue, conditions, room, description, date) {
    document.getElementById("edit-id").value = id;
    document.getElementById("edit-name").value = name;
    document.getElementById("edit-quantity").value = quantity;
    document.getElementById("edit-type").value = type;
    document.getElementById("edit-issue").value = issue;
    document.getElementById("edit-conditions").value = conditions;
    document.getElementById("edit-room").value = room;
    document.getElementById("edit-description").value = description;
    document.getElementById("edit-date").value = date;

    document.getElementById("editItemModal").style.display = "block";
}

function closeEditModal() {
    document.getElementById("editItemModal").style.display = "none";
}

document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        e.preventDefault();
        const id = btn.dataset.id;

        if (!confirm("Are you sure you want to delete this item?")) return;

        fetch("delete.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "id=" + id
        })
            .then(() => location.reload());
    });
});

fetch('getReport.php')
    .then(res => res.json())
    .then(data => console.log(data))
    .catch(err => console.error(err));