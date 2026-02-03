/**
 * Reports page – Add Room modal
 * SHJCS Inventory System
 */

function openRoomModal() {
    document.getElementById('roomModal').style.display = 'flex';
}

function closeRoomModal() {
    document.getElementById('roomModal').style.display = 'none';
}

/* Handle form submit – plain XHR, addRoom.php returns JSON */
document.getElementById('addRoomForm').addEventListener('submit', function (e) {
    e.preventDefault();

    var form = this;
    var body = 'rn=' + encodeURIComponent(form.elements['rn'].value)
        + '&name=' + encodeURIComponent(form.elements['name'].value)
        + '&floor_number=' + encodeURIComponent(form.elements['floor_number'].value);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../config/addRoom.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.onload = function () {
        var data;
        try { data = JSON.parse(xhr.responseText); } catch (ex) { data = null; }

        if (data && data.success) {
            alert('Room added successfully.');
            location.reload();
        } else {
            alert((data && data.message) || 'Failed to add room.');
        }
    };

    xhr.onerror = function () {
        alert('Server error. Please try again.');
    };

    xhr.send(body);
});