<?php

namespace App\Services;

use App\Models\Disability;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly NotificationService $notificationService,
    ) {}

    public function registerChild(array $data): User
    {
        $childRole = Role::where('name', Role::CHILD)->firstOrFail();
        $disabilityIds = array_map('intval', $data['disability_ids'] ?? []);

        $user = $this->userRepository->create([
            'full_name'      => $data['full_name'],
            'father_name'    => $data['father_name'] ?? null,
            'email'          => $data['email'],
            'password'       => Hash::make($data['password']),
            'role_id'        => $childRole->id,
            'age'            => $data['age'] ?? null,
            'gender'         => $data['gender'] ?? null,
            'date_of_birth'  => $data['date_of_birth'] ?? null,
            'address'        => $data['address'] ?? null,
            'phone_number'   => $data['phone_number'] ?? null,
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'parent_notes'   => $data['parent_notes'] ?? null,
            'other_disability' => $this->includesOtherDisability($disabilityIds) && filled($data['other_disability'] ?? null)
                ? trim((string) $data['other_disability'])
                : null,
            'branch_id'      => $data['branch_id'],
            'status'         => 'pending',
        ]);

        $user->load('branch');

        if ($disabilityIds !== []) {
            $user->disabilities()->sync($disabilityIds);
        }

        $this->notificationService->notifyAdminsOfNewChild($user);

        return $user->load('role');
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->isChild() && $user->isPending()) {
            throw ValidationException::withMessages([
                'email' => ['Your account is pending admin approval.'],
            ]);
        }

        if ($user->status === 'rejected') {
            throw ValidationException::withMessages([
                'email' => ['Your account registration has been rejected.'],
            ]);
        }

        if ($user->status === 'inactive') {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact admin.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user'  => $user->load('role'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /** @param  array<int>  $disabilityIds */
    private function includesOtherDisability(array $disabilityIds): bool
    {
        if ($disabilityIds === []) {
            return false;
        }

        $otherId = Disability::otherId();

        return $otherId !== null && in_array((int) $otherId, $disabilityIds, true);
    }
}
