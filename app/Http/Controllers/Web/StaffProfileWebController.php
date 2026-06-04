<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Profile + password self-service for Admin and Finance (read-only profile; password only). */
class StaffProfileWebController extends Controller
{
    public function show(Request $request): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isFinance(), 403);

        $user = $request->user()->load(['role', 'branch']);
        $passwordUrl = $request->user()->isAdmin()
            ? route('admin.profile.password')
            : route('finance.profile.password');

        return view('staff.profile', compact('user', 'passwordUrl'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isFinance(), 403);

        $data = $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', 'min:8'],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $request->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->back()->with('success', 'Password updated.');
    }
}
