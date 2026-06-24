<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Disability;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChildRegistrationService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SecureFileStorageService $secureFiles,
        private readonly EnrollmentService $enrollmentService,
    ) {}

    /**
     * Staff registers a child — account is approved immediately; no emails or pending notifications.
     *
     * @param  array<int, UploadedFile>  $documentFiles
     */
    public function registerByStaff(array $data, User $staff, array $documentFiles = []): User
    {
        $childRole     = Role::query()->where('name', Role::CHILD)->firstOrFail();
        $disabilityIds = array_map('intval', $data['disability_ids'] ?? []);
        $documentPaths = $this->secureFiles->storeMany($documentFiles, 'children/documents');

        $user = $this->userRepository->create([
            'full_name'        => $data['full_name'],
            'father_name'      => $data['father_name'] ?? null,
            'email'            => $data['email'],
            'password'         => Hash::make($data['password']),
            'role_id'          => $childRole->id,
            'age'              => $data['age'] ?? null,
            'gender'           => $data['gender'] ?? null,
            'date_of_birth'    => $data['date_of_birth'] ?? null,
            'address'          => $data['address'] ?? null,
            'phone_number'     => $data['phone_number'] ?? null,
            'whatsapp_number'  => $data['whatsapp_number'] ?? null,
            'parent_notes'     => $data['parent_notes'] ?? null,
            'other_disability' => $this->includesOtherDisability($disabilityIds) && filled($data['other_disability'] ?? null)
                ? trim((string) $data['other_disability'])
                : null,
            'branch_id'        => $data['branch_id'],
            'status'           => 'approved',
            'approved_by'      => $staff->id,
            'approved_at'      => now(),
            'created_by'       => $staff->id,
            'documents'        => $documentPaths !== [] ? $documentPaths : null,
        ]);

        if ($disabilityIds !== []) {
            $user->disabilities()->sync($disabilityIds);
        }

        return $user->load(['role', 'disabilities', 'branch']);
    }

    /**
     * Permanently remove a child account and related records that are safe to delete.
     */
    public function delete(User $child): void
    {
        abort_unless($child->isChild(), 404);

        if ($child->payments()->exists()) {
            throw ValidationException::withMessages([
                'child' => ['Cannot permanently delete this child while payment records exist.'],
            ]);
        }

        DB::transaction(function () use ($child): void {
            $enrollments = Enrollment::withTrashed()
                ->where('child_id', $child->id)
                ->get();

            foreach ($enrollments as $enrollment) {
                if ($enrollment->paidPayments()->exists()) {
                    throw ValidationException::withMessages([
                        'child' => ['Cannot permanently delete this child while verified payments exist on their enrollments.'],
                    ]);
                }

                $this->enrollmentService->delete($enrollment);
            }

            $documents = is_array($child->documents) ? $child->documents : [];
            foreach ($documents as $path) {
                if (is_string($path) && $path !== '') {
                    $this->secureFiles->delete($path);
                }
            }

            $child->disabilities()->detach();
            $child->tokens()->delete();
            $child->forceDelete();
        });
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
