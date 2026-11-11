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
    const input = button.closest('.password-field')?.querySelector('input');

    if (!input) return;

    button.addEventListener('click', () => {
        const showPassword = input.type === 'password';
        input.type = showPassword ? 'text' : 'password';
        button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
        button.setAttribute('title', showPassword ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', String(showPassword));
        button.querySelector('[data-password-show]').hidden = showPassword;
        button.querySelector('[data-password-hide]').hidden = !showPassword;
    });
});

document.querySelectorAll('.password-field').forEach((field) => {
    const input = field.querySelector('input');
    const status = field.querySelector('[data-password-status]');

    if (!input) return;

    field.querySelector('[data-password-generate]')?.addEventListener('click', () => {
        const groups = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789', '!@#$%*-_'];
        const characters = groups.join('');
        const randomIndex = (maximum) => {
            const value = new Uint32Array(1);
            window.crypto.getRandomValues(value);
            return value[0] % maximum;
        };
        const password = groups.map((group) => group[randomIndex(group.length)]);

        while (password.length < 18) {
            password.push(characters[randomIndex(characters.length)]);
        }

        for (let index = password.length - 1; index > 0; index -= 1) {
            const swapIndex = randomIndex(index + 1);
            [password[index], password[swapIndex]] = [password[swapIndex], password[index]];
        }

        input.value = password.join('');
        input.dispatchEvent(new Event('input', { bubbles: true }));
        status.textContent = 'A temporary password was generated.';
    });

    field.querySelector('[data-password-copy]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;

        if (!input.value) {
            status.textContent = 'Enter or generate a password before copying.';
            return;
        }

        try {
            await navigator.clipboard.writeText(input.value);
        } catch {
            const inputType = input.type;
            input.type = 'text';
            input.focus();
            input.select();
            document.execCommand('copy');
            input.type = inputType;
            input.setSelectionRange(input.value.length, input.value.length);
        }

        button.setAttribute('aria-label', 'Password copied');
        button.setAttribute('title', 'Password copied');
        status.textContent = 'Password copied to clipboard.';
        window.setTimeout(() => {
            button.setAttribute('aria-label', 'Copy password');
            button.setAttribute('title', 'Copy password');
        }, 1500);
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
