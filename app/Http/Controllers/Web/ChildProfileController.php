<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChildOwnPasswordRequest;
use App\Http\Requests\UpdateChildOwnProfileRequest;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Authenticated child portal — self-service profile/password (`/my-profile`). Not staff {@see ChildController}. */
class ChildProfileController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function edit(): View
    {
        $user = auth()->user()->load('disabilities');

        return view('child.profile', compact('user'));
    }

    public function update(UpdateChildOwnProfileRequest $request): RedirectResponse
    {
        $this->userRepository->update($request->user(), $request->validated());

        return redirect()->route('child.profile.edit')->with('success', 'Your profile has been updated.');
    }

    public function updatePassword(UpdateChildOwnPasswordRequest $request): RedirectResponse
    {
        $this->userRepository->update($request->user(), [
            'password' => $request->validated('password'),
        ]);

        return redirect()->route('child.profile.edit')->with('success', 'Your password has been changed.');
    }
}
