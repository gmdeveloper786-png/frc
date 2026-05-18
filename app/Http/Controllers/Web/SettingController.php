<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function edit(Request $request): View
    {
        $this->authorizeAccess($request);

        $groups = $this->settings->groupedForForm();

        return view('settings.edit', compact('groups'));
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->settings->sync($request->settingsPayload(), (int) $request->user()->id);

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Settings saved successfully.');
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user?->isSuperAdmin() || $user?->hasPermission('manage_settings'),
            403
        );
    }
}
