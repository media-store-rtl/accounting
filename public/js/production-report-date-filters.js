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
    // Keep the server value ASCII ISO even when PersianDate defaults to Persian digits.
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

    // The plugin parses its initial value using format. Use matching separators
    // while keeping the hidden field in the server's Gregorian ISO format.
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
