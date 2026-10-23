import test from 'node:test';
import assert from 'node:assert/strict';
import { normalizeReportId } from '../../public/js/production-report-date-filters.js';

test('extracts report IDs from Persian, Arabic-Indic, and Latin digits', () => {
    assert.equal(normalizeReportId('# ۱۶'), 16);
    assert.equal(normalizeReportId('گزارش # ١٧'), 17);
    assert.equal(normalizeReportId('# 18'), 18);
    assert.equal(normalizeReportId('۱۹'), 19);
    assert.equal(normalizeReportId('شماره نامشخص'), null);
    assert.equal(normalizeReportId(''), null);
});
