<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsAuthorizationCatalog;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthorizationCatalog;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAuthorizationCatalog();
        $this->policy = new UserPolicy;
    }

    public function test_branch_admin_can_view_child_in_same_branch(): void
    {
        $branch = $this->makeBranch();
        $admin  = $this->makeBranchAdmin($branch);
        $child  = $this->makeChild($branch);

        $this->assertTrue($this->policy->viewChild($admin, $child));
    }

    public function test_branch_admin_cannot_view_child_in_other_branch(): void
    {
        $branchA = $this->makeBranch('Branch A');
        $branchB = $this->makeBranch('Branch B');
        $adminA  = $this->makeBranchAdmin($branchA);
        $childB  = $this->makeChild($branchB);

        $this->assertFalse($this->policy->viewChild($adminA, $childB));
    }

    public function test_finance_user_can_view_child_across_branches(): void
    {
        $branchB = $this->makeBranch('Branch B');
        $finance = User::factory()->finance()->create(['password' => 'password']);
        $childB  = $this->makeChild($branchB);

        $this->assertTrue($this->policy->viewChild($finance, $childB));
    }

    public function test_approve_child_requires_matching_branch_for_admin(): void
    {
        $branchA = $this->makeBranch('Branch A');
        $branchB = $this->makeBranch('Branch B');
        $adminA  = $this->makeBranchAdmin($branchA);
        $childB  = $this->makeChild($branchB, ['status' => 'pending']);

        $this->assertFalse($this->policy->approveChild($adminA, $childB));
    }
}
