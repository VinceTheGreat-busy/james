function openRoomModal() {
    document.getElementById('roomModal').style.display = 'flex';
}

function closeRoomModal() {
    document.getElementById('roomModal').style.display = 'none';
}

/* Handle form submit */
document.getElementById('addRoomForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('../actions/add_room.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Room added successfully');
                location.reload();
            } else {
                alert(data.message || 'Failed to add room');
            }
        })
        .catch(() => alert('Server error'));
});