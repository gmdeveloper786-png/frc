<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffUserRequest;
use App\Http\Requests\UpdateStaffUserRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\StaffUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffUserController extends Controller
{
    public function __construct(private readonly StaffUserService $staffUsers) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $users = $this->staffUsers->getStaffUsers($request->only(['search', 'role', 'status']));

        return view('super-admin.staff-users.index', compact('users'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $branches = Branch::published()->forDropdown()->orderedForDropdown()->get();

        return view('super-admin.staff-users.create', compact('branches'));
    }

    public function store(StoreStaffUserRequest $request): RedirectResponse
    {
        $this->staffUsers->createStaffUser($request->validated(), $request->user());

        return redirect()->route('super-admin.staff-users.index')->with('success', 'Staff user created successfully.');
    }

    public function edit(Request $request, User $user): View
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $this->staffUsers->ensureStaffUser($user);
        $user->load(['role', 'branch']);
        $branches = Branch::published()->forDropdown()->orderedForDropdown()->get();

        return view('super-admin.staff-users.edit', compact('user', 'branches'));
    }

    public function update(UpdateStaffUserRequest $request, User $user): RedirectResponse
    {
        $this->staffUsers->ensureStaffUser($user);
        $this->staffUsers->updateStaffUser($user, $request->validated(), $request->user());

        return redirect()->route('super-admin.staff-users.index')->with('success', 'Staff user updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $this->staffUsers->ensureStaffUser($user);
        $this->staffUsers->deleteStaffUser($user, $request->user());

        return redirect()->route('super-admin.staff-users.index')->with('success', 'Staff user deleted.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $this->staffUsers->ensureStaffUser($user);
        $this->staffUsers->toggleUserStatus($user, $request->user());

        return redirect()->route('super-admin.staff-users.index')->with('success', 'Status updated.');
    }
}
