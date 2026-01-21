// Open Add Item Modal
function addItem() {
    document.getElementById('addItemModal').style.display = 'flex';
}

// Close Add Item Modal
function closeAddModal() {
    document.getElementById('addItemModal').style.display = 'none';
}

// Open Edit Item Modal and populate values
function openEditModal(id, name, quantity, remarks, issue, conditions, room, description, date) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_quantity').value = quantity;
    document.getElementById('edit_remarks').value = remarks;
    document.getElementById('edit_issue').value = issue;
    document.getElementById('edit_conditions').value = conditions;
    document.getElementById('edit_room').value = room;
    document.getElementById('edit_description').value = description;

    document.getElementById('editItemModal').style.display = 'flex';
}

// Close Edit Item Modal
function closeEditModal() {
    document.getElementById('editItemModal').style.display = 'none';
}

// Close modal on click outside
window.onclick = function (event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
