<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Branch;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

trait SeedsAuthorizationCatalog
{
    protected function seedAuthorizationCatalog(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    protected function makeBranch(string $name = 'Branch A'): Branch
    {
        return Branch::query()->create([
            'name'   => $name,
            'city'   => 'Lahore',
            'status' => 'publish',
        ]);
    }

    protected function makeService(): Service
    {
        return Service::query()->create([
            'name'   => 'Speech Therapy',
            'status' => 'publish',
        ]);
    }

    protected function makeChild(Branch $branch, array $overrides = []): User
    {
        return User::factory()->approvedChild()->create(array_merge([
            'branch_id' => $branch->id,
            'password'  => Hash::make('password'),
        ], $overrides));
    }

    protected function makeBranchAdmin(Branch $branch, array $overrides = []): User
    {
        return User::factory()->admin()->create(array_merge([
            'branch_id' => $branch->id,
            'password'  => Hash::make('password'),
        ], $overrides));
    }

    protected function makeTherapist(array $overrides = []): User
    {
        return User::factory()->therapist()->create(array_merge([
            'password' => Hash::make('password'),
        ], $overrides));
    }

    protected function makeEnrollment(User $child, Branch $branch, Service $service, array $overrides = []): Enrollment
    {
        return Enrollment::query()->create(array_merge([
            'child_id'          => $child->id,
            'branch_id'         => $branch->id,
            'service_id'        => $service->id,
            'price_per_session' => 1000,
            'total_sessions'    => 10,
            'subtotal'          => 10000,
            'final_total'       => 10000,
            'paid_amount'       => 0,
            'remaining_amount'  => 10000,
            'payment_status'    => 'unpaid',
            'status'            => 'active',
        ], $overrides));
    }

    protected function makePayment(Enrollment $enrollment, User $child, array $overrides = []): Payment
    {
        return Payment::query()->create(array_merge([
            'enrollment_id'     => $enrollment->id,
            'child_id'          => $child->id,
            'amount'            => 1000,
            'payment_method'    => 'cash',
            'payment_date'      => now()->toDateString(),
            'submitted_by_role' => 'admin',
            'status'            => 'paid',
            'receipt_number'    => 'RCPT-' . uniqid(),
        ], $overrides));
    }

    protected function makeNotification(User $owner, array $overrides = []): UserNotification
    {
        return UserNotification::query()->create(array_merge([
            'user_id' => $owner->id,
            'title'   => 'Test notification',
            'message' => 'Test body',
            'type'    => 'info',
            'module'  => 'users',
            'is_read' => false,
        ], $overrides));
    }

    protected function roleId(string $name): int
    {
        return (int) Role::query()->where('name', $name)->value('id');
    }
}
