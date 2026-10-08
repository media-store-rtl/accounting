<?php

namespace AppHttpControllers;

use AppServicesExcelImportService;
use IlluminateHttpRequest;
use RuntimeException;

class ExcelImportController extends Controller
{
    public function page()
    {
        return view('imports.excel');
    }

    public function inspect(Request $request, ExcelImportService $service)
    {
        $request->validate(['file' => ['required', 'file', 'max:25600']]);

        try {
            return response()->json(
                $service->inspect($request->file('file'), (int) $request->session()->get('company_id'))
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function validateMapping(Request $request, ExcelImportService $service)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'target' => ['required', 'string'],
            'mapping' => ['required', 'array'],
        ]);

        try {
            return response()->json(
                $service->validate(
                    $data['token'],
                    $data['target'],
                    $data['mapping'],
                    (int) $request->session()->get('company_id')
                )
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function import(Request $request, ExcelImportService $service)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'target' => ['required', 'string'],
            'mapping' => ['required', 'array'],
        ]);

        try {
            $count = $service->import(
                $data['token'],
                $data['target'],
                $data['mapping'],
                (int) $request->session()->get('company_id')
            );

            return response()->json(['status' => 'imported', 'rows' => $count]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
