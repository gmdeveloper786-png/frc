<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsAuthorizationCatalog;
use Tests\TestCase;

class IdorProtectionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthorizationCatalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAuthorizationCatalog();
    }

    public function test_child_cannot_view_another_childs_payment_via_api(): void
    {
        $service = $this->makeService();
        $branchA = $this->makeBranch('Branch A');
        $childA  = $this->makeChild($branchA);
        $childB  = $this->makeChild($branchA, ['email' => 'childb@example.com']);
        $payment = $this->makePayment($this->makeEnrollment($childA, $branchA, $service), $childA);

        Sanctum::actingAs($childB);

        $this->getJson("/api/payments/{$payment->id}")
            ->assertForbidden();
    }

    public function test_child_can_view_own_payment_via_api(): void
    {
        $service = $this->makeService();
        $branchA = $this->makeBranch('Branch A');
        $childA  = $this->makeChild($branchA);
        $payment = $this->makePayment($this->makeEnrollment($childA, $branchA, $service), $childA);

        Sanctum::actingAs($childA);

        $this->getJson("/api/payments/{$payment->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $payment->id);
    }

    public function test_therapist_cannot_view_unrelated_payment_via_api(): void
    {
        $service = $this->makeService();
        $branchA = $this->makeBranch('Branch A');
        $childA  = $this->makeChild($branchA);
        $payment = $this->makePayment($this->makeEnrollment($childA, $branchA, $service), $childA);
        $therapist = $this->makeTherapist();

        Sanctum::actingAs($therapist);

        $this->getJson("/api/payments/{$payment->id}")
            ->assertForbidden();
    }

    public function test_branch_admin_cannot_view_child_from_another_branch_via_api(): void
    {
        $branchA = $this->makeBranch('Branch A');
        $branchB = $this->makeBranch('Branch B');
        $adminA  = $this->makeBranchAdmin($branchA);
        $childB  = $this->makeChild($branchB, ['email' => 'remote-child@example.com']);

        Sanctum::actingAs($adminA);

        $this->getJson("/api/children/{$childB->id}")
            ->assertForbidden();
    }

    public function test_branch_admin_can_view_child_in_own_branch_via_api(): void
    {
        $branchA = $this->makeBranch('Branch A');
        $adminA  = $this->makeBranchAdmin($branchA);
        $childA  = $this->makeChild($branchA);

        Sanctum::actingAs($adminA);

        $this->getJson("/api/children/{$childA->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $childA->id);
    }

    public function test_child_cannot_view_another_childs_enrollment_via_api(): void
    {
        $service = $this->makeService();
        $branchA = $this->makeBranch('Branch A');
        $childA  = $this->makeChild($branchA);
        $childB  = $this->makeChild($branchA, ['email' => 'childb@example.com']);
        $enrollment = $this->makeEnrollment($childA, $branchA, $service);

        Sanctum::actingAs($childB);

        $this->getJson("/api/enrollments/{$enrollment->id}")
            ->assertForbidden();
    }

    public function test_branch_admin_cannot_view_enrollment_from_another_branch_via_api(): void
    {
        $service = $this->makeService();
        $branchA = $this->makeBranch('Branch A');
        $branchB = $this->makeBranch('Branch B');
        $adminA  = $this->makeBranchAdmin($branchA);
        $childB  = $this->makeChild($branchB, ['email' => 'remote-child@example.com']);
        $enrollment = $this->makeEnrollment($childB, $branchB, $service);

        Sanctum::actingAs($adminA);

        $this->getJson("/api/enrollments/{$enrollment->id}")
            ->assertForbidden();
    }
}
