<div class="password-field">
    <input type="password" autocomplete="new-password" {{ $attributes }}>
    <div class="password-actions">
        <button class="password-generate" type="button" data-password-generate>Generate</button>
        <button class="password-action" type="button" aria-label="Copy password" title="Copy password" data-password-copy>
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2" /><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3" /></svg>
        </button>
        <button class="password-action" type="button" aria-label="Show password" aria-pressed="false" title="Show password" data-password-toggle>
            <svg viewBox="0 0 24 24" aria-hidden="true" data-password-show>
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            <svg viewBox="0 0 24 24" aria-hidden="true" data-password-hide hidden>
                <path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a15.7 15.7 0 0 1-3.1 3.8M6.2 6.2C3.5 8.1 2 12 2 12s3.5 7 10 7a10.5 10.5 0 0 0 4.1-.8" />
            </svg>
        </button>
    </div>
    <span class="visually-hidden" role="status" aria-live="polite" data-password-status></span>
</div>
