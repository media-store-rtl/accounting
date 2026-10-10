<?php

namespace App\Http\Controllers;

use App\Support\CompanyAuthorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class NotificationRecipientController extends Controller
{
    private const EVENTS = [
        ['key' => 'order.production_required', 'name' => 'کسری موجودی سفارش و نیاز به تولید'],
        ['key' => 'order.production_due_set', 'name' => 'ثبت موعد قابل تحویل تولید برای فروش'],
        ['key' => 'order.ready_for_delivery', 'name' => 'آماده شدن سفارش برای تحویل'],
        ['key' => 'delivery.requested', 'name' => 'ثبت درخواست تحویل برای انبار'],
        ['key' => 'supply.shortage', 'name' => 'کسری موجودی درخواست تأمین برای تدارکات'],
        ['key' => 'purchase.received_financial_value', 'name' => 'تأیید ورود خرید و اطلاع به مالی'],
    ];

    public function index(Request $request): View
    {
        $companyId = CompanyAuthorization::authorize($request, 'user.update');
        foreach (self::EVENTS as $event) {
            $exists = DB::table('notification_recipient_rules')
                ->where('company_id', $companyId)->where('event_key', $event['key'])->exists();
            if (! $exists) {
                DB::table('notification_recipient_rules')->insert([
                    'company_id' => $companyId, 'event_key' => $event['key'], 'name' => $event['name'],
                    'is_active' => true, 'is_configured' => false, 'exclude_actor' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        $rules = DB::table('notification_recipient_rules')->where('company_id', $companyId)->orderBy('id')->get();
        foreach ($rules as $rule) {
            $rule->department_ids = DB::table('notification_recipient_rule_departments as rrd')
                ->join('organizational_departments as d', 'd.id', '=', 'rrd.department_id')
                ->where('rrd.rule_id', $rule->id)->where('d.company_id', $companyId)
                ->pluck('d.id')->map(fn ($id) => (int) $id)->all();
            $rule->user_ids = DB::table('notification_recipient_rule_users as rru')
                ->join('company_user as cu', 'cu.user_id', '=', 'rru.user_id')
                ->where('rru.rule_id', $rule->id)->where('cu.company_id', $companyId)
                ->pluck('cu.user_id')->map(fn ($id) => (int) $id)->all();
        }

        $departments = DB::table('organizational_departments')->where('company_id', $companyId)
            ->where('is_active', true)->orderBy('name')->get();
        $users = DB::table('company_user as cu')->join('users as u', 'u.id', '=', 'cu.user_id')
            ->where('cu.company_id', $companyId)->where('cu.is_active', true)->where('u.is_active', true)
            ->select('u.id', 'u.name', 'u.email')->orderBy('u.name')->get();

        return view('settings.notification-recipients.index', compact('rules', 'departments', 'users'));
    }

    public function update(Request $request, int $rule): RedirectResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'user.update');
        $row = DB::table('notification_recipient_rules')->where('id', $rule)->where('company_id', $companyId)->first();
        abort_unless($row, 404);
        $data = $request->validate([
            'departments' => ['nullable', 'array'], 'departments.*' => ['integer'],
            'users' => ['nullable', 'array'], 'users.*' => ['integer'],
            'is_active' => ['nullable', 'boolean'], 'exclude_actor' => ['nullable', 'boolean'],
        ]);

        $departmentIds = collect($data['departments'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $validDepartmentIds = DB::table('organizational_departments')->where('company_id', $companyId)
            ->where('is_active', true)->whereIn('id', $departmentIds)->pluck('id')->map(fn ($id) => (int) $id);
        abort_unless($validDepartmentIds->count() === $departmentIds->count(), 422, 'یکی از واحدهای انتخاب‌شده نامعتبر یا غیرفعال است.');

        $userIds = collect($data['users'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $validUserIds = DB::table('company_user as cu')->join('users as u', 'u.id', '=', 'cu.user_id')
            ->where('cu.company_id', $companyId)->where('cu.is_active', true)->where('u.is_active', true)
            ->whereIn('u.id', $userIds)->pluck('u.id')->map(fn ($id) => (int) $id);
        abort_unless($validUserIds->count() === $userIds->count(), 422, 'یکی از کاربران انتخاب‌شده عضو فعال این شرکت نیست.');

        DB::transaction(function () use ($companyId, $rule, $data, $departmentIds, $userIds) {
            DB::table('notification_recipient_rules')->where('id', $rule)->where('company_id', $companyId)->update([
                'is_active' => (bool) ($data['is_active'] ?? false),
                'is_configured' => true,
                'exclude_actor' => (bool) ($data['exclude_actor'] ?? false),
                'updated_at' => now(),
            ]);
            DB::table('notification_recipient_rule_departments')->where('rule_id', $rule)->delete();
            foreach ($departmentIds as $departmentId) {
                DB::table('notification_recipient_rule_departments')->insert(['rule_id' => $rule, 'department_id' => $departmentId]);
            }
            DB::table('notification_recipient_rule_users')->where('rule_id', $rule)->delete();
            foreach ($userIds as $userId) {
                DB::table('notification_recipient_rule_users')->insert(['rule_id' => $rule, 'user_id' => $userId]);
            }
        });

        if (($data['is_active'] ?? false) && $departmentIds->isEmpty() && $userIds->isEmpty()) {
            Log::warning('Notification rule saved without recipients.', [
                'company_id' => $companyId, 'rule_id' => $rule, 'event' => $row->event_key,
            ]);
        }
        return back()->with('success', 'تنظیم گیرندگان اعلان ذخیره شد.');
    }
}