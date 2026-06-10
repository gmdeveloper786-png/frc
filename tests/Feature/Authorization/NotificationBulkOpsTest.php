<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsAuthorizationCatalog;
use Tests\TestCase;

class NotificationBulkOpsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthorizationCatalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAuthorizationCatalog();
    }

    public function test_user_cannot_bulk_mark_another_users_notification_as_read(): void
    {
        $branch = $this->makeBranch();
        $owner  = $this->makeBranchAdmin($branch);
        $attacker = $this->makeBranchAdmin($this->makeBranch('Branch B'), ['email' => 'attacker@example.com']);
        $foreign = $this->makeNotification($owner);

        Sanctum::actingAs($attacker);

        $this->postJson('/api/notifications/bulk-mark-as-read', ['ids' => [$foreign->id]])
            ->assertForbidden();

        $this->assertFalse($foreign->fresh()->is_read);
    }

    public function test_user_cannot_bulk_delete_another_users_notification(): void
    {
        $branch = $this->makeBranch();
        $owner  = $this->makeBranchAdmin($branch);
        $attacker = $this->makeBranchAdmin($this->makeBranch('Branch B'), ['email' => 'attacker@example.com']);
        $foreign = $this->makeNotification($owner);

        Sanctum::actingAs($attacker);

        $this->postJson('/api/notifications/bulk-delete', ['ids' => [$foreign->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('user_notifications', ['id' => $foreign->id]);
    }

    public function test_user_can_bulk_mark_own_notifications_as_read(): void
    {
        $branch = $this->makeBranch();
        $owner  = $this->makeBranchAdmin($branch);
        $mine   = $this->makeNotification($owner);

        Sanctum::actingAs($owner);

        $this->postJson('/api/notifications/bulk-mark-as-read', ['ids' => [$mine->id]])
            ->assertOk()
            ->assertJsonPath('updated', 1);

        $this->assertTrue($mine->fresh()->is_read);
    }

    public function test_mixed_own_and_foreign_notification_ids_are_rejected(): void
    {
        $branch = $this->makeBranch();
        $owner  = $this->makeBranchAdmin($branch);
        $other  = $this->makeBranchAdmin($this->makeBranch('Branch B'), ['email' => 'other@example.com']);
        $mine   = $this->makeNotification($owner);
        $foreign = $this->makeNotification($other);

        Sanctum::actingAs($owner);

        $this->postJson('/api/notifications/bulk-mark-as-read', ['ids' => [$mine->id, $foreign->id]])
            ->assertForbidden();

        $this->assertFalse($mine->fresh()->is_read);
        $this->assertFalse($foreign->fresh()->is_read);
    }
}
