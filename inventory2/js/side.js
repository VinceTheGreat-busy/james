document.getElementById('toggleSidebar').addEventListener('click', () => {
    const aside = document.querySelector('aside');
    aside.style.display = aside.style.display === 'block' ? 'none' : 'block';
});