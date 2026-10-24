// Mobile menu toggle (no framework needed). aria-expanded tracks the state.
const toggle = document.getElementById('mobile-menu-toggle');
const menu = document.getElementById('mobile-menu');

if (toggle && menu) {
    toggle.addEventListener('click', () => {
        const open = menu.classList.toggle('hidden') === false;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
        toggle.classList.toggle('open', open);
    });
}
