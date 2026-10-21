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

export function setupCostingRefreshButton(documentRef = globalThis.document) {
    const table = documentRef?.querySelector('.costing-main-table');
    const panel = documentRef?.querySelector('.costing-panel');
    if (!table || !panel) return false;

    const incompleteRows = Array.from(table.querySelectorAll('tbody tr'))
        .filter(row => row.querySelector('.costing-status.incomplete'));
    if (!incompleteRows.length) return false;

    const csrfToken = documentRef.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return false;

    const heading = panel.querySelector('h2');
    if (!heading || panel.querySelector('[data-costing-refresh]')) return false;

    const wrapper = documentRef.createElement('div');
    wrapper.style.cssText = 'display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 12px';

    const button = documentRef.createElement('button');
    button.type = 'button';
    button.textContent = `بروزرسانی و ارزش‌گذاری مجدد (${incompleteRows.length})`;
    button.setAttribute('data-costing-refresh', 'true');
    button.style.cssText = 'background:#24516b;border:1px solid #3a6a88;border-radius:9px;color:#dcebf6;padding:9px 13px;font:inherit;font-size:11px;cursor:pointer';

    const note = documentRef.createElement('span');
    note.textContent = 'گزارش‌های ناقص دوباره با لایه‌های موجودی و FIFO تطبیق داده می‌شوند.';
    note.style.cssText = 'color:#91a6b9;font-size:10px';

    wrapper.append(button, note);
    heading.insertAdjacentElement('afterend', wrapper);

    button.addEventListener('click', async () => {
        button.disabled = true;
        button.textContent = 'در حال بروزرسانی...';
        button.style.cursor = 'wait';

        const reportIds = incompleteRows.map(row => {
            const value = row.querySelector('td')?.textContent?.match(/#\s*(\d+)/)?.[1];
            return value ? Number(value) : null;
        }).filter(Boolean);

        const results = await Promise.allSettled(reportIds.map(async reportId => {
            const response = await fetch(`/reports/costing/production-reports/${reportId}/value`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'text/html',
                },
            });
            if (!response.ok) throw new Error(`refresh failed for report ${reportId}`);
            return reportId;
        }));

        const failed = results.filter(result => result.status === 'rejected').length;
        note.textContent = failed
            ? `${failed} گزارش قابل بروزرسانی نبود؛ وضعیت موجود بررسی و صفحه دوباره بارگذاری می‌شود.`
            : 'بروزرسانی انجام شد؛ گزارش با داده‌های جدید دوباره محاسبه می‌شود.';
        window.setTimeout(() => window.location.reload(), 350);
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
