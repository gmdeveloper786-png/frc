<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Branch;
use App\Services\SettingService;
use App\Support\SettingKeys;
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

        $pricingCities = Branch::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->map(fn ($c) => (string) $c)
            ->values()
            ->all();

        $citySessionPrices = $this->settings->citySessionPrices();
        foreach (array_keys($citySessionPrices) as $city) {
            if (! in_array($city, $pricingCities, true)) {
                $pricingCities[] = $city;
            }
        }
        sort($pricingCities, SORT_NATURAL | SORT_FLAG_CASE);

        return view('settings.edit', compact('groups', 'pricingCities', 'citySessionPrices'));
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
