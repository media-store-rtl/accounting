export function normalizeReportId(value) {
    const normalized = String(value ?? '').replace(/[۰-۹٠-٩]/g, digit => (
        String(digit.charCodeAt(0) - (digit >= '۰' && digit <= '۹' ? 0x06f0 : 0x0660))
    ));
    const match = normalized.match(/#\s*(\d+)/) ?? normalized.match(/^\s*(\d+)\s*$/);
    return match ? Number(match[1]) : null;
}

export function normalizeIsoDateForPicker(value) {
    const match = String(value ?? '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
    return match ? `${match[1]}/${match[2]}/${match[3]}` : '';
}

export function formatGregorianIsoDate(unixDate, PersianDateCtor = globalThis.persianDate) {
    if (typeof PersianDateCtor !== 'function') {
        throw new Error('persian-date is required to format the selected date');
    }

    const date = new PersianDateCtor(unixDate);
    const gregorianDate = typeof date.toCalendar === 'function'
        ? date.toCalendar('gregorian')
        : date;
    gregorianDate.formatPersian = false;
    const value = gregorianDate.format('YYYY-MM-DD');

    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        throw new Error('The date picker returned an invalid Gregorian date');
    }

    return value;
}

export function setupProductionReportDatePicker(pickerSelector, isoSelector, {
    documentRef = globalThis.document,
    jQueryRef = globalThis.jQuery,
    PersianDateCtor = globalThis.persianDate,
} = {}) {
    const picker = documentRef?.querySelector(pickerSelector);
    const isoField = documentRef?.querySelector(isoSelector);
    const datepickerMethod = jQueryRef?.fn?.pDatepicker
        ? 'pDatepicker'
        : (jQueryRef?.fn?.persianDatepicker ? 'persianDatepicker' : null);

    if (!picker || !isoField || typeof jQueryRef !== 'function'
        || !datepickerMethod || typeof PersianDateCtor !== 'function') {
        return false;
    }

    picker.value = normalizeIsoDateForPicker(isoField.value);

    jQueryRef(picker)[datepickerMethod]({
        calendarType: 'persian',
        initialValue: Boolean(isoField.value),
        initialValueType: 'gregorian',
        format: 'YYYY/MM/DD',
        altField: isoSelector,
        altFormat: 'YYYY-MM-DD',
        altFieldFormatter: unixDate => formatGregorianIsoDate(unixDate, PersianDateCtor),
        onSelect: unixDate => {
            isoField.value = formatGregorianIsoDate(unixDate, PersianDateCtor);
        },
        calendar: { persian: { locale: 'fa' } },
        toolbox: {
            calendarSwitch: { enabled: false },
            todayButton: { enabled: true, text: { fa: 'امروز' } },
        },
    });

    return true;
}

export function setupCostingRefreshButton(documentRef = globalThis.document, fetchRef = globalThis.fetch, locationRef = globalThis.location) {
    const button = documentRef?.querySelector('[data-costing-refresh-all]');
    const note = documentRef?.querySelector('[data-costing-refresh-result]');
    if (!button || !note || typeof fetchRef !== 'function') return false;
    const csrfToken = documentRef.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || documentRef.querySelector('input[name="_token"]')?.value;
    let reloadPending = false;
    button.addEventListener('click', async () => {
        if (reloadPending) { locationRef?.reload?.(); return; }
        button.disabled = true; button.textContent = 'در حال بروزرسانی همه گزارش‌ها...'; button.style.cursor = 'wait';
        const headers = { Accept: 'application/json' };
        if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
        try {
            const response = await fetchRef(button.dataset.endpoint, { method: 'POST', credentials: 'same-origin', headers });
            const payload = await response.json().catch(() => null);
            if (!response.ok || !payload || typeof payload !== 'object') {
                throw new Error(payload?.message || 'پاسخ معتبر از سرور دریافت نشد.');
            }

            const processed = Number(payload.processed ?? 0);
            const succeeded = Number(payload.succeeded ?? 0);
            const failed = Number(payload.failed ?? 0);
            const partial = Number(payload.partial ?? 0);
            const details = Array.isArray(payload.details) ? payload.details.join(' | ') : '';
            if (processed === 0) {
                note.textContent = payload.message || 'هیچ گزارش واجد شرایطی برای بروزرسانی پیدا نشد.';
                note.setAttribute('role', 'alert'); note.style.color = '#f0d58a';
                button.textContent = 'تلاش دوباره برای بروزرسانی';
                return;
            }

            note.textContent = `نتیجه بروزرسانی همه گزارش‌ها: ${succeeded} موفق، ${failed} ناموفق از ${processed} گزارش؛ ${partial} گزارش همچنان بخشی بدون بها دارد. ${details}`;
            const hasIssues = failed > 0 || partial > 0 || payload.complete !== true;
            note.setAttribute('role', hasIssues ? 'alert' : 'status');
            note.style.color = hasIssues ? '#f0d58a' : '#a7e3c6';

            if (hasIssues || payload.success !== true) {
                reloadPending = true;
                button.textContent = 'بارگذاری مجدد برای مشاهده نتیجه';
                return;
            }

            // Only a fully successful run reloads automatically. Partial or failed
            // runs stay visible so their counts and diagnostic details are not lost.
            reloadPending = true;
            button.textContent = 'گزارش‌ها بروزرسانی شد';
            locationRef?.reload?.();
        } catch (error) {
            note.textContent = error?.message || 'بروزرسانی گزارش‌ها ناموفق بود؛ دوباره تلاش کنید.';
            note.setAttribute('role', 'alert'); note.style.color = '#f0d58a'; button.textContent = 'تلاش دوباره برای بروزرسانی';
        } finally { button.disabled = false; button.style.cursor = 'pointer'; }
    });
    return true;
}


export function setupCostingRowRefreshButtons(documentRef = globalThis.document, fetchRef = globalThis.fetch, locationRef = globalThis.location) {
    const buttons = Array.from(documentRef?.querySelectorAll('[data-costing-refresh-row]') ?? []);
    if (!buttons.length || typeof fetchRef !== 'function') return false;
    const csrfToken = documentRef.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || documentRef.querySelector('input[name="_token"]')?.value;

    buttons.forEach(button => {
        let finished = false;
        const reportId = button.dataset.reportId || button.dataset.endpoint?.match(/production-reports\/(\d+)\/value/)?.[1] || '';
        const note = documentRef.querySelector(`[data-costing-refresh-row-result="${reportId}"]`);
        button.addEventListener('click', async () => {
            if (finished) { locationRef?.reload?.(); return; }
            button.disabled = true;
            button.textContent = 'در حال بروزرسانی...';
            button.style.cursor = 'wait';
            if (note) note.textContent = 'در حال بررسی مواد و نرخ‌های معتبر...';
            const headers = { Accept: 'application/json' };
            if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
            try {
                const response = await fetchRef(button.dataset.endpoint, {
                    method: 'POST', credentials: 'same-origin', headers,
                });
                const payload = await response.json().catch(() => null);
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload?.message || 'بروزرسانی این گزارش انجام نشد.');
                }
                if (note) note.textContent = payload.message || 'گزارش بروزرسانی شد.';
                finished = true;
                button.textContent = 'بروزرسانی شد';
                locationRef?.reload?.();
            } catch (error) {
                if (note) note.textContent = error?.message || 'خطا در بروزرسانی گزارش؛ دوباره تلاش کنید.';
                button.textContent = 'تلاش دوباره';
                button.disabled = false;
                button.style.cursor = 'pointer';
            }
        });
    });
    return true;
}

if (globalThis.document) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => { setupCostingRefreshButton(); setupCostingRowRefreshButtons(); });
    } else {
        setupCostingRefreshButton();
        setupCostingRowRefreshButtons();
    }
}
