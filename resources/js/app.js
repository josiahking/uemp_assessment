import './bootstrap';

document.querySelectorAll('[data-table-search]').forEach((input) => {
	input.addEventListener('input', () => {
		const query = input.value.trim().toLocaleLowerCase();
		const table = input.closest('section')?.querySelector('tbody');

		table?.querySelectorAll('[data-searchable]').forEach((row) => {
			row.hidden = !row.textContent.toLocaleLowerCase().includes(query);
		});
	});
});

document.querySelectorAll('input[name="student_number"]').forEach((input) => {
	input.readOnly = true;
});

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = button.parentElement?.querySelector('input');

    if (!input) return;

    button.addEventListener('click', () => {
        const showPassword = input.type === 'password';
        input.type = showPassword ? 'text' : 'password';
        button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', String(showPassword));
        button.querySelector('[data-password-show]').hidden = showPassword;
        button.querySelector('[data-password-hide]').hidden = !showPassword;
    });
});

const menuToggle = document.querySelector('[data-menu-toggle]');
const sidebar = menuToggle?.closest('.sidebar');

menuToggle?.addEventListener('click', () => {
    const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';

    menuToggle.setAttribute('aria-expanded', String(!isOpen));
    menuToggle.setAttribute('aria-label', isOpen ? 'Open main menu' : 'Close main menu');
    sidebar?.classList.toggle('is-open', !isOpen);
});

sidebar?.querySelectorAll('.primary-nav a').forEach((link) => {
    link.addEventListener('click', () => {
        menuToggle?.setAttribute('aria-expanded', 'false');
        menuToggle?.setAttribute('aria-label', 'Open main menu');
        sidebar.classList.remove('is-open');
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && sidebar?.classList.contains('is-open')) {
        menuToggle?.setAttribute('aria-expanded', 'false');
        menuToggle?.setAttribute('aria-label', 'Open main menu');
        sidebar.classList.remove('is-open');
        menuToggle?.focus();
    }
});
