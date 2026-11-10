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
