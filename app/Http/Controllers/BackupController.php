<?php

namespace AppHttpControllers;

use AppModelsBackupFile;
use AppServicesBackupService;
use IlluminateHttpRequest;
use IlluminateSupportFacadesStorage;
use RuntimeException;

class BackupController extends Controller
{
    public function page(Request $request)
    {
        $companyId = (int) $request->session()->get('company_id');

        return view('backups.index', [
            'backups' => BackupFile::where('company_id', $companyId)->latest()->get(),
            'companyId' => $companyId,
        ]);
    }

    public function index(Request $request)
    {
        $companyId = (int) $request->session()->get('company_id');

        return response()->json([
            'backups' => BackupFile::where('company_id', $companyId)->latest()->get(),
        ]);
    }

    public function create(Request $request, BackupService $service)
    {
        try {
            $backup = $service->create(
                (int) $request->session()->get('company_id'),
                (int) $request->user()->id
            );

            return response()->json(['backup' => $backup], 201);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
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

        try {
            $backup = $service->upload(
                (int) $request->session()->get('company_id'),
                (int) $request->user()->id,
                $request->file('backup')
            );

            return response()->json(['backup' => $backup], 201);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function restore(Request $request, BackupFile $backup, BackupService $service)
    {
        abort_unless($backup->company_id === (int) $request->session()->get('company_id'), 404);

        $data = $request->validate([
            'confirmation' => ['required', 'in:RESTORE'],
        ]);

        try {
            $service->restore($backup, $data['confirmation']);

            return response()->json(['status' => 'restored']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
