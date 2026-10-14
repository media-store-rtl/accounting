<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class NotificationRecipientService
{
    public function send(int $companyId, string $event, array $payload = [], ?int $actorId = null, ?string $legacyPermission = null): void
    {
        $rule = DB::table('notification_recipient_rules')
            ->where('company_id', $companyId)
            ->where('event_key', $event)
            ->first();

        // Preserve existing delivery until the company explicitly saves this event's rule.
        if (! $rule || ! $rule->is_configured) {
            $this->sendLegacy($companyId, $event, $payload, $actorId, $legacyPermission);
            return;
        }

        if (! $rule->is_active) {
            return;
        }

        $departmentUserIds = DB::table('notification_recipient_rule_departments as rrd')
            ->join('organizational_departments as d', 'd.id', '=', 'rrd.department_id')
            ->join('organizational_department_user as du', 'du.department_id', '=', 'd.id')
            ->join('company_user as cu', function ($join) {
                $join->on('cu.user_id', '=', 'du.user_id')->on('cu.company_id', '=', 'du.company_id');
            })
            ->join('users as u', 'u.id', '=', 'cu.user_id')
            ->where('rrd.rule_id', $rule->id)
            ->where('d.company_id', $companyId)->where('d.is_active', true)
            ->where('du.company_id', $companyId)->where('cu.company_id', $companyId)
            ->where('cu.is_active', true)->where('u.is_active', true)
            ->pluck('u.id');

        $directUserIds = DB::table('notification_recipient_rule_users as rru')
            ->join('users as u', 'u.id', '=', 'rru.user_id')
            ->join('company_user as cu', 'cu.user_id', '=', 'u.id')
            ->where('rru.rule_id', $rule->id)
            ->where('cu.company_id', $companyId)->where('cu.is_active', true)->where('u.is_active', true)
            ->pluck('u.id');

        $ids = $departmentUserIds->merge($directUserIds)->unique();

        // Configured recipient rules must not cross the authorization boundary of the event's owning team.
        $requiredPermission = match ($event) {
            'order.production_required' => 'production.supervise',
            'order.ready_for_delivery' => 'order.view',
            'production.shift_report_pending' => 'production.operation.review',
            'production.shift_report_warehouse_receipt' => 'production.output.receive',
            'supply.stock_check_required' => 'supply_request.handover.create',
            'supply.shortage' => 'supply.manage',
            'supply.stock_rejected' => 'supply_request.create',
            'supply.stock_available' => 'supply_request.create',
            'supply.purchased' => 'supply_request.create',
            'inventory.finished_goods_low' => 'production.supervise',
            'inventory.raw_material_low' => 'supply.manage',
            default => null,
        };
        if ($requiredPermission && $ids->isNotEmpty()) {
            $authorizedIds = DB::table('company_user as cu')
                ->join('role_permissions as rp', 'rp.role_id', '=', 'cu.role_id')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('cu.company_id', $companyId)->where('cu.is_active', true)
                ->where('p.slug', $requiredPermission)->whereIn('cu.user_id', $ids)
                ->pluck('cu.user_id')->unique();
            $ids = $ids->intersect($authorizedIds)->values();
        }

        if ($rule->exclude_actor && $actorId) {
            $ids = $ids->reject(fn ($id) => (int) $id === $actorId);
        }

        if ($ids->isEmpty()) {
            Log::warning('Configured notification rule has no active recipients.', [
                'company_id' => $companyId, 'event' => $event, 'rule_id' => $rule->id,
            ]);
            return;
        }

        $notificationPayload = $payload + ['company_id' => $companyId];
        $notificationPayload['notification_batch_id'] = (string) Str::uuid();

        Notification::send(
            User::query()->whereIn('id', $ids)->get(),
            new WorkflowNotification($event, $notificationPayload)
        );
    }

    private function sendLegacy(int $companyId, string $event, array $payload, ?int $actorId, ?string $legacyPermission): void
    {
        if (! $legacyPermission) {
            Log::warning('Notification has no configured rule or legacy permission.', ['company_id' => $companyId, 'event' => $event]);
            return;
        }

        $ids = DB::table('company_user as cu')
            ->join('users as u', 'u.id', '=', 'cu.user_id')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'cu.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('cu.company_id', $companyId)->where('cu.is_active', true)->where('u.is_active', true)
            ->where('p.slug', $legacyPermission)->pluck('cu.user_id')->unique()
            ->reject(fn ($id) => $actorId && (int) $id === $actorId);

        if ($ids->isEmpty()) {
            return;
        }

        $notificationPayload = $payload + ['company_id' => $companyId];
        $notificationPayload['notification_batch_id'] = (string) Str::uuid();

        Notification::send(
            User::query()->whereIn('id', $ids)->get(),
            new WorkflowNotification($event, $notificationPayload)
        );
    }
}