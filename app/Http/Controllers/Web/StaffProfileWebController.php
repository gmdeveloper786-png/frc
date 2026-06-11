<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Profile + password self-service for Admin, Finance, and Approval Discount (read-only profile; password only). */
class StaffProfileWebController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        abort_unless($this->canAccess($user), 403);

        $user->load(['role', 'branch']);

        return view('staff.profile', [
            'user'        => $user,
            'passwordUrl' => $this->passwordUpdateRoute($user),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canAccess($user), 403);

        $data = $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->back()->with('success', 'Password updated.');
    }

    private function canAccess(?User $user): bool
    {
        return $user !== null
            && ($user->isAdmin() || $user->isFinance() || $user->isApprovalDiscount());
    }

    private function passwordUpdateRoute(User $user): string
    {
        if ($user->isAdmin()) {
            return route('admin.profile.password');
        }

        if ($user->isFinance()) {
            return route('finance.profile.password');
        }

        if ($user->isApprovalDiscount()) {
            return route('approval-discount.profile.password');
        }

        abort(403);
    }
}
