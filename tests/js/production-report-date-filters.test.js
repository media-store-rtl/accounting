import test from 'node:test';
import assert from 'node:assert/strict';
import {
    formatGregorianIsoDate,
    normalizeIsoDateForPicker,
    setupProductionReportDatePicker,
} from '../../resources/js/production-report-date-filters.js';

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

    assert.equal(setupProductionReportDatePicker('#from_picker', '#from', {
        documentRef,
        jQueryRef: jquery,
        PersianDateCtor: class {},
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
