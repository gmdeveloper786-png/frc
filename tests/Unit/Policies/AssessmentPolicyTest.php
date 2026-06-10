<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Assessment;
use App\Policies\AssessmentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsAuthorizationCatalog;
use Tests\TestCase;

class AssessmentPolicyTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthorizationCatalog;

    private AssessmentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAuthorizationCatalog();
        $this->policy = new AssessmentPolicy;
    }

    public function test_child_cannot_view_draft_assessment(): void
    {
        $branch = $this->makeBranch();
        $child  = $this->makeChild($branch);
        $assessment = Assessment::query()->create([
            'date'       => now()->toDateString(),
            'day'        => 'Monday',
            'time'       => '10:00:00',
            'branch_id'  => $branch->id,
            'status'     => 'draft',
        ]);
        $assessment->children()->attach($child->id);

        $this->assertFalse($this->policy->view($child, $assessment));
    }

    public function test_child_can_view_published_assessment_they_are_assigned_to(): void
    {
        $branch = $this->makeBranch();
        $child  = $this->makeChild($branch);
        $assessment = Assessment::query()->create([
            'date'       => now()->toDateString(),
            'day'        => 'Monday',
            'time'       => '10:00:00',
            'branch_id'  => $branch->id,
            'status'     => 'publish',
        ]);
        $assessment->children()->attach($child->id);

        $this->assertTrue($this->policy->view($child, $assessment));
    }

    public function test_therapist_can_complete_assigned_assessment(): void
    {
        $branch = $this->makeBranch();
        $therapist = $this->makeTherapist();
        $assessment = Assessment::query()->create([
            'date'          => now()->toDateString(),
            'day'           => 'Monday',
            'time'          => '10:00:00',
            'branch_id'     => $branch->id,
            'therapist_id'  => $therapist->id,
            'status'        => 'publish',
        ]);

        $this->assertTrue($this->policy->complete($therapist, $assessment));
    }
}
