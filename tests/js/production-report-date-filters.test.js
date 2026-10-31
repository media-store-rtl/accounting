import test from 'node:test';
import assert from 'node:assert/strict';
import {
    formatGregorianIsoDate,
    normalizeIsoDateForPicker,
    setupProductionReportDatePicker,
    setupCostingRefreshButton,
    setupCostingRowRefreshButtons,
} from '../../public/js/production-report-date-filters.js';

test('converts an ISO Gregorian date to the matching picker input format', () => {
    assert.equal(normalizeIsoDateForPicker('2026-10-12'), '2026/10/12');
    assert.equal(normalizeIsoDateForPicker(''), '');
    assert.equal(normalizeIsoDateForPicker('1405/07/20'), '');
    assert.equal(normalizeIsoDateForPicker('2026-2-12'), '');
});

test('formats the selected timestamp using the date library Gregorian calendar, not local Date parsing', () => {
    class FakePersianDate {
        constructor(timestamp) { this.timestamp = timestamp; }
        toCalendar(calendar) {
            assert.equal(calendar, 'gregorian');
            return this;
        }
        format(pattern) {
            assert.equal(this.formatPersian, false);
            assert.equal(pattern, 'YYYY-MM-DD');
            assert.equal(this.timestamp, 1791763200000);
            return '2026-10-12';
        }
    }

    assert.equal(formatGregorianIsoDate(1791763200000, FakePersianDate), '2026-10-12');
});

test('initialises from the hidden Gregorian value and submits the ISO value via the named hidden field', () => {
    const picker = { value: '2026-10-12' };
    const hidden = { value: '2026-10-12', name: 'from' };
    let options;
    const jquery = () => ({ persianDatepicker(config) { options = config; } });
    jquery.fn = { persianDatepicker() {} };
    const documentRef = {
        querySelector(selector) {
            return selector === '#from_picker' ? picker : selector === '#from' ? hidden : null;
        },
    };

    class FakePersianDate {
        constructor(timestamp) { this.timestamp = timestamp; }
        toCalendar(calendar) { assert.equal(calendar, 'gregorian'); return this; }
        format(pattern) {
            assert.equal(this.formatPersian, false);
            assert.equal(pattern, 'YYYY-MM-DD');
            assert.equal(this.timestamp, 1791763200000);
            return '2026-10-12';
        }
    }

    assert.equal(setupProductionReportDatePicker('#from_picker', '#from', {
        documentRef,
        jQueryRef: jquery,
        PersianDateCtor: FakePersianDate,
    }), true);
    assert.equal(picker.value, '2026/10/12');
    assert.equal(hidden.value, '2026-10-12');
    assert.equal(options.initialValue, true);
    assert.equal(options.initialValueType, 'gregorian');
    assert.equal(options.format, 'YYYY/MM/DD');
    assert.equal(options.altFormat, 'YYYY-MM-DD');
    assert.equal(options.altField, '#from');
    assert.equal(options.altFieldFormatter(1791763200000), '2026-10-12');

    // The visible picker is intentionally unnamed; GET submission uses the ISO hidden field.
    const params = new URLSearchParams([[hidden.name, hidden.value]]);
    assert.equal(params.toString(), 'from=2026-10-12');
});

test('does not initialise when date-picker dependencies are unavailable', () => {
    assert.equal(setupProductionReportDatePicker('#from_picker', '#from', {
        documentRef: { querySelector: () => ({ value: '2026-10-12' }) },
        jQueryRef: () => ({}),
        PersianDateCtor: class {},
    }), false);
});

test('global refresh keeps partial and failed results visible instead of auto-reloading', async () => {
    let clickHandler; let request; let reloads = 0;
    const button = { dataset: { endpoint: '/reports/costing/production-reports/refresh-all' }, textContent: 'بروزرسانی گزارش‌ها', style: {}, addEventListener(event, handler) { assert.equal(event, 'click'); clickHandler = handler; } };
    const note = { textContent: '', style: {}, setAttribute(name, value) { this[name] = value; } };
    const documentRef = { querySelector(selector) {
        if (selector === '[data-costing-refresh-all]') return button;
        if (selector === '[data-costing-refresh-result]') return note;
        if (selector === 'meta[name="csrf-token"]') return { getAttribute: () => 'csrf-token' };
        return null;
    } };
    const fetchRef = async (url, options) => {
        request = { url, options };
        return { ok: true, async json() { return { success: false, complete: false, processed: 18, succeeded: 13, failed: 3, partial: 2, details: ['گزارش #15: بدون بهای قابل ردیابی'] }; } };
    };
    assert.equal(setupCostingRefreshButton(documentRef, fetchRef, { reload() { reloads++; } }), true);
    await clickHandler();
    assert.equal(request.url, '/reports/costing/production-reports/refresh-all');
    assert.equal(request.options.method, 'POST');
    assert.equal(request.options.headers['X-CSRF-TOKEN'], 'csrf-token');
    assert.match(note.textContent, /13 موفق، 3 ناموفق از 18 گزارش/);
    assert.match(note.textContent, /گزارش #15/);
    assert.equal(note.role, 'alert');
    assert.equal(button.textContent, 'بارگذاری مجدد برای مشاهده نتیجه');
    assert.equal(reloads, 0);
    await clickHandler();
    assert.equal(reloads, 1);
});

test('global refresh does not claim success or reload when no eligible reports were found', async () => {
    let clickHandler; let reloads = 0;
    const button = { dataset: { endpoint: '/reports/costing/production-reports/refresh-all' }, textContent: 'بروزرسانی گزارش‌ها', style: {}, addEventListener(event, handler) { clickHandler = handler; } };
    const note = { textContent: '', style: {}, setAttribute(name, value) { this[name] = value; } };
    const documentRef = { querySelector(selector) {
        if (selector === '[data-costing-refresh-all]') return button;
        if (selector === '[data-costing-refresh-result]') return note;
        return null;
    } };
    const fetchRef = async () => ({ ok: true, async json() { return { success: false, complete: false, processed: 0, succeeded: 0, failed: 0, partial: 0, message: 'هیچ گزارش تولید واجد شرایطی پیدا نشد.' }; } });
    assert.equal(setupCostingRefreshButton(documentRef, fetchRef, { reload() { reloads++; } }), true);
    await clickHandler();
    assert.match(note.textContent, /هیچ گزارش تولید واجد شرایطی/);
    assert.equal(note.role, 'alert');
    assert.equal(reloads, 0);
    assert.equal(button.textContent, 'تلاش دوباره برای بروزرسانی');
});

test('global refresh reloads only after a fully successful run', async () => {
    let clickHandler; let reloads = 0;
    const button = { dataset: { endpoint: '/reports/costing/production-reports/refresh-all' }, textContent: 'بروزرسانی گزارش‌ها', style: {}, addEventListener(event, handler) { clickHandler = handler; } };
    const note = { textContent: '', style: {}, setAttribute(name, value) { this[name] = value; } };
    const documentRef = { querySelector(selector) {
        if (selector === '[data-costing-refresh-all]') return button;
        if (selector === '[data-costing-refresh-result]') return note;
        return null;
    } };
    const fetchRef = async () => ({ ok: true, async json() { return { success: true, complete: true, processed: 4, succeeded: 4, failed: 0, partial: 0, details: [] }; } });
    assert.equal(setupCostingRefreshButton(documentRef, fetchRef, { reload() { reloads++; } }), true);
    await clickHandler();
    assert.equal(reloads, 1);
    assert.equal(button.textContent, 'گزارش‌ها بروزرسانی شد');
});


test('row refresh button sends one report request and reloads after success', async () => {
    let clickHandler; let request; let reloads = 0;
    const button = {
        dataset: { endpoint: '/reports/costing/production-reports/42/value' },
        textContent: 'بروزرسانی', style: {},
        addEventListener(event, handler) { assert.equal(event, 'click'); clickHandler = handler; },
    };
    const note = { textContent: '' };
    const documentRef = {
        querySelectorAll(selector) { assert.equal(selector, '[data-costing-refresh-row]'); return [button]; },
        querySelector(selector) {
            if (selector === '[data-costing-refresh-row-result="42"]') return note;
            if (selector === 'meta[name="csrf-token"]') return { getAttribute: () => 'csrf-token' };
            return null;
        },
    };
    const fetchRef = async (url, options) => {
        request = { url, options };
        return { ok: true, async json() { return { success: true, message: 'مبالغ ریالی گزارش بروزرسانی شد.' }; } };
    };
    assert.equal(setupCostingRowRefreshButtons(documentRef, fetchRef, { reload() { reloads++; } }), true);
    await clickHandler();
    assert.equal(request.url, '/reports/costing/production-reports/42/value');
    assert.equal(request.options.method, 'POST');
    assert.equal(request.options.headers['X-CSRF-TOKEN'], 'csrf-token');
    assert.equal(note.textContent, 'مبالغ ریالی گزارش بروزرسانی شد.');
    assert.equal(button.textContent, 'بروزرسانی شد');
    assert.equal(reloads, 1);
});
