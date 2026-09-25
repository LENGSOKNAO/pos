<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/index', [
            'settings' => Setting::orderBy('branch_id')->orderBy('key')->get(),
            'branches' => Branch::select('id', 'name')->orderBy('name')->get(),
            'defaults' => [
                'shop_name' => '',
                'receipt_footer' => '',
                'tax_rate' => '0',
                'low_stock_threshold' => '5',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:255'],
            'settings.*.value' => ['nullable', 'string'],
            'settings.*.branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        foreach ($validated['settings'] as $row) {
            Setting::updateOrCreate(
                ['branch_id' => $row['branch_id'] ?? null, 'key' => $row['key']],
                ['value' => $row['value'] ?? null]
            );
        }

        return redirect()->route('settings.index');
    }
}
