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
    let finished = false;
    button.addEventListener('click', async () => {
        if (finished) { locationRef?.reload?.(); return; }
        button.disabled = true; button.textContent = 'در حال بروزرسانی همه گزارش‌ها...'; button.style.cursor = 'wait';
        const headers = { Accept: 'application/json' };
        if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
        try {
            const response = await fetchRef(button.dataset.endpoint, { method: 'POST', credentials: 'same-origin', headers });
            const payload = await response.json().catch(() => null);
            if (!response.ok || !payload || payload.success !== true) throw new Error(payload?.message || 'پاسخ معتبر از سرور دریافت نشد.');
            const details = Array.isArray(payload.details) ? payload.details.join(' | ') : '';
            note.textContent = `نتیجه بروزرسانی همه گزارش‌ها: ${payload.succeeded ?? 0} موفق، ${payload.failed ?? 0} ناموفق از ${payload.processed ?? 0} گزارش؛ ${payload.partial ?? 0} گزارش همچنان بخشی بدون بها دارد. ${details} برای مشاهده ارقام تازه، صفحه را بارگذاری مجدد کنید.`;
            note.setAttribute('role', payload.failed ? 'alert' : 'status'); note.style.color = payload.failed ? '#f0d58a' : '#a7e3c6';
            finished = true; button.textContent = 'بارگذاری مجدد گزارش‌ها';
        } catch (error) {
            note.textContent = error?.message || 'بروزرسانی گزارش‌ها ناموفق بود؛ دوباره تلاش کنید.';
            note.setAttribute('role', 'alert'); note.style.color = '#f0d58a'; button.textContent = 'تلاش دوباره برای بروزرسانی';
        } finally { button.disabled = false; button.style.cursor = 'pointer'; }
    });
    return true;
}

if (globalThis.document) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setupCostingRefreshButton());
    } else {
        setupCostingRefreshButton();
    }
}
