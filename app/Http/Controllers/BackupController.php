<?php

namespace App\Http\Controllers;

use App\Models\BackupFile;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) $request->session()->get('company_id');
        return response()->json(['backups' => BackupFile::where('company_id', $companyId)->latest()->get()]);
    }

    public function create(Request $request, BackupService $service)
    {
        $backup = $service->create((int) $request->session()->get('company_id'), (int) $request->user()->id);
        return response()->json(['backup' => $backup], 201);
    }

    public function download(Request $request, BackupFile $backup)
    {
        abort_unless($backup->company_id === (int) $request->session()->get('company_id'), 404);
        abort_unless(Storage::disk('local')->exists($backup->disk_path), 404);
        return Storage::disk('local')->download($backup->disk_path, $backup->original_name);
    }

    public function upload(Request $request, BackupService $service)
    {
        $request->validate(['backup' => ['required', 'file', 'max:102400']]);
        $backup = $service->upload((int) $request->session()->get('company_id'), (int) $request->user()->id, $request->file('backup'));
        return response()->json(['backup' => $backup], 201);
    }

    public function restore(Request $request, BackupFile $backup, BackupService $service)
    {
        abort_unless($backup->company_id === (int) $request->session()->get('company_id'), 404);
        $data = $request->validate(['confirmation' => ['required', 'in:RESTORE']]);
        $service->restore($backup, $data['confirmation']);
        return response()->json(['status' => 'restored']);
    }
}
