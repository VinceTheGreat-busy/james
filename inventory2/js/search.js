const searchInput = document.getElementById('itemSearch');
const cardsContainer = document.getElementById('itemCards');

function renderCards(items) {
    cardsContainer.innerHTML = '';
    if (!items.length) {
        cardsContainer.innerHTML = '<p>No items found.</p>';
        return;
    }

    items.forEach(item => {
        const card = document.createElement('div');
        card.className = 'itemCard';
        card.setAttribute('draggable', 'true');
        card.setAttribute('data-item-id', item.id);
        card.innerHTML = `
            <h3>${item.name}</h3>
            <p><strong>Quantity:</strong> ${item.quantity}</p>
            <p><strong>Condition:</strong> ${item.conditions}</p>
            <p>${item.description}</p>
        `;
        cardsContainer.appendChild(card);
    });
}

// Fetch items on input
searchInput.addEventListener('input', () => {
    const q = searchInput.value;

    fetch(`../config/search.php?q=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(data => {
            renderCards(data);
        });
});

// Optional: fetch all items on page load
fetch('../config/search.php')
    .then(res => res.json())
    .then(data => renderCards(data));