<?php

namespace Tests\Unit;

use App\Notifications\WorkflowNotification;
use PHPUnit\Framework\TestCase;

class WorkflowNotificationTest extends TestCase
{
    public function test_notification_payload_preserves_event_and_context(): void
    {
        $notification=new WorkflowNotification('production_operation_review_required',['operation_run_id'=>7,'production_id'=>9]);
        $payload=$notification->toArray(new \stdClass());
        $this->assertSame('production_operation_review_required',$payload['event']);
        $this->assertSame(7,$payload['operation_run_id']);
        $this->assertSame(9,$payload['production_id']);
    }
}
