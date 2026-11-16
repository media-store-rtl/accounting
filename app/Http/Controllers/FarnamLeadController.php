<?php

namespace App\Http\Controllers;

use App\Models\FarnamLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarnamLeadController extends Controller
{
    private const STATUSES = ['new', 'contacted', 'meeting', 'proposal', 'won', 'lost'];

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9۰-۹+()\-\s]+$/u'],
            'business_name' => ['nullable', 'string', 'max:180'],
            'business_type' => ['required', 'in:production,commerce,services,other'],
            'service_interest' => ['required', 'in:accounting,finance,tax,industrial_costing,management_reporting,other'],
            'message' => ['nullable', 'string', 'max:1500'],
            'consent' => ['accepted'],
        ]);

        FarnamLead::query()->create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'business_name' => $validated['business_name'] ?? null,
            'business_type' => $validated['business_type'],
            'service_interest' => $validated['service_interest'],
            'message' => $validated['message'] ?? null,
            'status' => 'new',
            'source' => 'website',
            'consent_at' => now(),
        ]);

        return redirect()->to('/#request-consultation')->with('lead_status', 'درخواست شما ثبت شد. برای هماهنگی با شما تماس می‌گیریم.');
    }

    public function index(): View
    {
        return view('farnam-admin.leads', [
            'leads' => FarnamLead::query()->latest()->paginate(25),
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, FarnamLead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', self::STATUSES)],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $lead->update($validated);

        return back()->with('status', 'وضعیت سرنخ به‌روزرسانی شد.');
    }
}
