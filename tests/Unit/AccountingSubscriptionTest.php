<?php

namespace Tests\Unit;

use App\Models\AccountingSubscription;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AccountingSubscriptionTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_active_subscription_requires_active_status_and_valid_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 12:00:00'));

        $subscription = new AccountingSubscription([
            'status' => 'active',
            'starts_at' => Carbon::parse('2026-10-01 00:00:00'),
            'expires_at' => Carbon::parse('2026-11-01 00:00:00'),
        ]);

        $this->assertTrue($subscription->isActive());

        $subscription->expires_at = Carbon::parse('2026-10-06 11:59:59');

        $this->assertFalse($subscription->isActive());
    }

    public function test_refresh_status_distinguishes_pending_expired_and_inactive(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 12:00:00'));

        $subscription = new AccountingSubscription([
            'status' => 'active',
            'starts_at' => Carbon::parse('2026-10-07 00:00:00'),
            'expires_at' => Carbon::parse('2026-11-01 00:00:00'),
        ]);

        $this->assertSame('pending', $subscription->refreshStatus());

        $subscription->starts_at = Carbon::parse('2026-10-01 00:00:00');
        $subscription->expires_at = Carbon::parse('2026-10-06 11:59:59');

        $this->assertSame('expired', $subscription->refreshStatus());

        $subscription->status = 'inactive';
        $subscription->expires_at = Carbon::parse('2026-11-01 00:00:00');

        $this->assertSame('inactive', $subscription->refreshStatus());
    }
}
