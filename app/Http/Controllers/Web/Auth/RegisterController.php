<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterChildRequest;
use App\Models\Branch;
use App\Models\Disability;
use App\Services\AuthService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly SettingService $settings,
    ) {}

    public function showRegistrationForm(): View
    {
        if (! $this->settings->childRegistrationEnabled()) {
            return view('auth.register-closed');
        }

        $disabilities = Disability::published()->orderBy('name')->get();
        $branches     = Branch::published()->forDropdown()->orderedForDropdown()->get();

        return view('auth.register', compact('disabilities', 'branches'));
    }

    public function register(RegisterChildRequest $request): RedirectResponse
    {
        if (! $this->settings->childRegistrationEnabled()) {
            abort(403, 'New child registration is currently disabled.');
        }

        $user = $this->authService->registerChild($request->validated());

        $message = (string) $this->settings->get(
            \App\Support\SettingKeys::REGISTRATION_SUCCESS_MESSAGE,
            'Your registration has been submitted. Please wait for admin approval.'
        );

        // if (filled($user->gr_number)) {
        //     $message .= ' Your GR Number is ' . $user->gr_number . '.';
        // }

        return redirect()->route('login')->with('success', $message);
    }
}
