<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>سرنخ‌های فرنام</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f5f7;color:#203247;font-family:Vazirmatn,Tahoma,sans-serif}.wrap{width:min(1180px,calc(100% - 28px));margin:28px auto}.panel{background:#fff;border:1px solid #e5e9ed;border-radius:16px;padding:20px;margin-bottom:16px;overflow:auto}h1{margin:0 0 8px;color:#142d46;font-size:23px}p{color:#718092;font-size:13px}.links{display:flex;gap:18px;margin:16px 0}.links a{color:#705b30;text-decoration:none;font-size:13px}table{width:100%;border-collapse:collapse;min-width:850px}th,td{text-align:right;padding:12px;border-bottom:1px solid #edf0f2;vertical-align:top;font-size:12px}th{color:#64748b;background:#f8fafb}input,select,textarea{font:inherit;border:1px solid #dce2e8;border-radius:8px;padding:8px;width:100%;font-size:12px}textarea{min-height:54px}.save{background:#142d46;color:white;border:0;border-radius:8px;padding:9px 14px;cursor:pointer;margin-top:7px}.notice{padding:12px;background:#edf8f1;color:#24643d;border-radius:9px;margin:12px 0}.muted{color:#718092}.pagination{padding-top:15px}
</style></head><body><main class="wrap">
<section class="panel"><h1>پیگیری درخواست‌های همکاری فرنام</h1><p>درخواست‌های ثبت‌شده از وب‌سایت را پیگیری کنید و وضعیت هرکدام را تا عقد قرارداد به‌روز نگه دارید.</p>
<nav class="links"><a href="{{ route('farnam.admin.edit') }}">مدیریت محتوا</a><a href="{{ route('home') }}" target="_blank" rel="noopener">مشاهده سایت</a></nav>
@if (session('status'))<div class="notice">{{ session('status') }}</div>@endif
</section>
<section class="panel"><table><thead><tr><th>تاریخ</th><th>نام و تماس</th><th>کسب‌وکار</th><th>نیاز</th><th>توضیح</th><th>پیگیری</th></tr></thead><tbody>
@forelse ($leads as $lead)
<tr><td>{{ $lead->created_at?->format('Y/m/d H:i') }}</td><td>{{ $lead->name }}<br><a href="tel:{{ preg_replace('/[^0-9+]/', '', $lead->phone) }}">{{ $lead->phone }}</a></td><td>{{ $lead->business_name ?: '—' }}<br><span class="muted">{{ $lead->business_type }}</span></td><td>{{ $lead->service_interest }}</td><td>{{ $lead->message ?: '—' }}</td><td><form method="POST" action="{{ route('farnam.admin.leads.update', $lead) }}">@csrf @method('PATCH')
<select name="status">@foreach ($statuses as $status)<option value="{{ $status }}" @selected($lead->status === $status)>{{ ['new'=>'جدید','contacted'=>'تماس گرفته شد','meeting'=>'جلسه تنظیم شد','proposal'=>'پیشنهاد ارسال شد','won'=>'قرارداد بسته شد','lost'=>'مختومه'][ $status ] }}</option>@endforeach</select>
<textarea name="internal_notes" placeholder="یادداشت داخلی">{{ $lead->internal_notes }}</textarea><button class="save" type="submit">ذخیره پیگیری</button></form></td></tr>
@empty<tr><td colspan="6">هنوز درخواستی ثبت نشده است.</td></tr>@endforelse
</tbody></table><div class="pagination">{{ $leads->links() }}</div></section>
</main></body></html>
