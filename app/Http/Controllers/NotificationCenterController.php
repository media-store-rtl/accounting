<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CompanyAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class NotificationCenterController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'notification.view');

        $rows = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $request->user()->id)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.company_id')) = ?", [(string) $companyId])
            ->latest('created_at')
            ->paginate(30);

        $batchIds = $rows->getCollection()
            ->map(fn ($notification) => (json_decode($notification->data, true) ?: [])['notification_batch_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $readersByBatch = collect();
        if ($batchIds->isNotEmpty()) {
            $readersByBatch = DB::table('notifications as n')
                ->join('users as u', 'u.id', '=', 'n.notifiable_id')
                ->join('company_user as cu', function ($join) use ($companyId) {
                    $join->on('cu.user_id', '=', 'u.id')->where('cu.company_id', '=', $companyId);
                })
                ->where('n.notifiable_type', User::class)
                ->whereNotNull('n.read_at')
                ->where('cu.is_active', true)
                ->where('u.is_active', true)
                ->whereIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(n.data, '$.notification_batch_id'))"), $batchIds)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(n.data, '$.company_id')) = ?", [(string) $companyId])
                ->select([
                    DB::raw("JSON_UNQUOTE(JSON_EXTRACT(n.data, '$.notification_batch_id')) as batch_id"),
                    'u.id as user_id',
                    'u.name as user_name',
                    'u.username as username',
                    'n.read_at',
                ])
                ->orderBy('n.read_at')
                ->get()
                ->groupBy('batch_id');
        }

        foreach ($rows as $notification) {
            $data = json_decode($notification->data, true) ?: [];
            $batchId = $data['notification_batch_id'] ?? null;
            $notification->readers = $batchId ? ($readersByBatch->get($batchId, collect())) : collect();
        }

        return view('notifications.index', compact('rows'));
    }

    public function read(Request $request, string $id)
    {
        $companyId = CompanyAuthorization::authorize($request, 'notification.view');

        DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $request->user()->id)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.company_id')) = ?", [(string) $companyId])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}