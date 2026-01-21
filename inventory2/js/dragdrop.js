let draggedItemId = null;
let draggedItemName = '';
let draggedItemQty = 0;
let targetRoomId = null;

document.querySelectorAll('.itemCard').forEach(card => {
    card.addEventListener('dragstart', e => {
        e.dataTransfer.setData('item_id', card.dataset.itemId);
        e.dataTransfer.setData(
            'quantity',
            card.querySelector('span').innerText
        );
        e.dataTransfer.setData(
            'item_name',
            card.querySelector('h3').innerText
        );
    });
});

document.querySelectorAll('.roomCard').forEach(room => {
    room.addEventListener('dragover', e => e.preventDefault());

    room.addEventListener('drop', e => {
        e.preventDefault();

        const itemId = e.dataTransfer.getData('item_id');
        const itemQty = parseInt(e.dataTransfer.getData('quantity'));
        const itemName = e.dataTransfer.getData('item_name');

        document.getElementById('modalItemName').innerText = itemName;
        document.getElementById('modalItemQty').innerText = itemQty;

        const qtyInput = document.getElementById('modalAssignQty');
        qtyInput.max = itemQty;
        qtyInput.value = 1;

        const modal = document.getElementById('quantityModal');
        modal.dataset.roomId = room.dataset.roomId;
        modal.dataset.itemId = itemId;
        modal.style.display = 'block';
    });
});


// Modal close
document.getElementById('modalClose').onclick = () => {
    document.getElementById('quantityModal').style.display = 'none';
};

// Submit quantity
document.getElementById('modalSubmit').addEventListener('click', () => {
    const modal = document.getElementById('quantityModal');

    fetch('../config/assignItem.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            item_id: modal.dataset.itemId,
            room_id: modal.dataset.roomId,
            quantity: document.getElementById('modalAssignQty').value
        })
    })
        .then(res => res.text())
        .then(() => location.reload());
});
