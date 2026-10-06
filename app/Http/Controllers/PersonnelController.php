<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PersonnelController extends Controller
{
    public function index(Request $request): View
    {
        $personnel = Personnel::where('account_id', $request->user()->account_id)
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('personnel.index', compact('personnel'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('users.create');
    }

    public function store(): RedirectResponse
    {
        abort(422, 'پرسنل جدید باید هم‌زمان با کاربر ایجاد شود.');
    }

    public function edit(Request $request, Personnel $personnel): View
    {
        $this->assertSameAccount($request, $personnel);
        return view('personnel.form', compact('personnel'));
    }

    public function update(Request $request, Personnel $personnel): RedirectResponse
    {
        $this->assertSameAccount($request, $personnel);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($data, $personnel) {
            $personnel->update(array_merge($data, [
                'name' => trim($data['first_name'].' '.$data['last_name']),
            ]));

            if ($personnel->user) {
                $personnel->user->update([
                    'name' => $personnel->name,
                    'email' => $data['email'] ?? $personnel->user->email,
                ]);
            }
        });

        return redirect()->route('personnel.index')->with('success', 'پرسنل ویرایش شد.');
    }

    public function deactivate(Request $request, Personnel $personnel): RedirectResponse
    {
        $this->assertSameAccount($request, $personnel);
        abort_if($personnel->user_id && (int) $personnel->user_id === (int) $request->user()->id, 422, 'کاربر جاری را نمی‌توان غیرفعال کرد.');

        DB::transaction(function () use ($personnel) {
            $personnel->update(['is_active' => false]);

            if ($personnel->user) {
                $personnel->user->update(['is_active' => false]);
                $personnel->user->companies()->updateExistingPivot(
                    $personnel->user->companies()->pluck('companies.id')->all(),
                    ['is_active' => false]
                );
            }
        });

        return back()->with('success', 'پرسنل غیرفعال شد.');
    }

    private function assertSameAccount(Request $request, Personnel $personnel): void
    {
        abort_unless((int) $personnel->account_id === (int) $request->user()->account_id, 404);
    }
}