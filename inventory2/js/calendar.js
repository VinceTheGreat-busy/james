const calendarBody = document.getElementById('calendarBody');
const calendarTitle = document.getElementById('calendarTitle');
const prevBtn = document.getElementById('prevMonth');
const nextBtn = document.getElementById('nextMonth');

let currentDate = new Date(); // starts with current month

function renderCalendar(date) {
    const year = date.getFullYear();
    const month = date.getMonth(); // 0-11
    const monthName = date.toLocaleString('default', { month: 'long' });
    calendarTitle.textContent = `${monthName} ${year}`;

    const firstDay = new Date(year, month, 1).getDay() || 7; // Mon=1 ... Sun=7
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let html = '';
    let day = 1;

    for (let week = 0; week < 6; week++) {
        html += '<tr>';
        for (let dow = 1; dow <= 7; dow++) {
            if (week === 0 && dow < firstDay || day > daysInMonth) {
                html += '<td></td>';
            } else {
                html += `<td>${day}</td>`;
                day++;
            }
        }
        html += '</tr>';
    }
    calendarBody.innerHTML = html;
}

// Button events
prevBtn.addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar(currentDate);
});
nextBtn.addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar(currentDate);
});

// Initial render
renderCalendar(currentDate);