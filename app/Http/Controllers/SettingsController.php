<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettingsRequest;
use App\Services\StoreSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request, StoreSettingsService $storeSettingsService): View
    {
        abort_unless(
            $request->user()->hasRole('admin') && $request->user()->can('pengaturan.kelola'),
            403,
        );

        $settings = $storeSettingsService->all();
        $logoUrl = $storeSettingsService->logoUrl();

        return view('settings.edit', compact('settings', 'logoUrl'));
    }

    public function update(
        StoreSettingsRequest $request,
        StoreSettingsService $storeSettingsService,
    ): RedirectResponse {
        $storeSettingsService->update($request->validated());

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Pengaturan toko berhasil disimpan.');
    }
}
