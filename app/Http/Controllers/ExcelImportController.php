<?php

namespace App\Http\Controllers;

use App\Services\ExcelImportService;
use Illuminate\Http\Request;

class ExcelImportController extends Controller
{
    public function page()
    {
        return view('imports.excel');
    }

    public function inspect(Request $request, ExcelImportService $service)
    {
        $request->validate(['file' => ['required', 'file', 'max:25600']]);
        return response()->json($service->inspect($request->file('file'), (int) $request->session()->get('company_id')));
    }

    public function validateMapping(Request $request, ExcelImportService $service)
    {
        $data = $request->validate(['token' => ['required', 'string'], 'target' => ['required', 'string'], 'mapping' => ['required', 'array']]);
        return response()->json($service->validate($data['token'], $data['target'], $data['mapping'], (int) $request->session()->get('company_id')));
    }

    public function import(Request $request, ExcelImportService $service)
    {
        $data = $request->validate(['token' => ['required', 'string'], 'target' => ['required', 'string'], 'mapping' => ['required', 'array']]);
        $count = $service->import($data['token'], $data['target'], $data['mapping'], (int) $request->session()->get('company_id'));
        return response()->json(['status' => 'imported', 'rows' => $count]);
    }
}
