<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use App\Support\StaffBranchScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;

class UserNotificationService
{
    public function createNotification(
        int $userId,
        string $title,
        string $message,
        string $type,
        string $module,
        ?int $recordId = null,
        ?string $actionUrl = null,
    ): UserNotification {
        return UserNotification::query()->create([
            'user_id'    => $userId,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'module'     => $module,
            'record_id'  => $recordId,
            'action_url' => $actionUrl,
            'is_read'    => false,
            'read_at'    => null,
        ]);
    }

    /**
     * @param  array<int, int>  $userIds
     */
    public function createForUsers(
        array $userIds,
        string $title,
        string $message,
        string $type,
        string $module,
        ?int $recordId = null,
        ?string $actionUrl = null,
    ): void {
        $userIds = array_values(array_unique(array_filter($userIds)));
        if ($userIds === []) {
            return;
        }

        $now = now();
        $rows = [];
        foreach ($userIds as $uid) {
            $rows[] = [
                'user_id'    => $uid,
                'title'      => $title,
                'message'    => $message,
                'type'       => $type,
                'module'     => $module,
                'record_id'  => $recordId,
                'action_url' => $actionUrl,
                'is_read'    => false,
                'read_at'    => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            UserNotification::query()->insert($chunk);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getUserNotifications(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = $this->scopedInboxQuery($user);
        $this->applyFilters($q, $filters);

        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $q->latest()->paginate($perPage);
        return $paginator->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, UserNotification>
     */
    public function getLatestNotifications(User $user, int $limit = 5, array $filters = []): Collection
    {
        $q = $this->scopedInboxQuery($user);
        $this->applyFilters($q, $filters);

        return $q->orderByRaw('is_read ASC, created_at DESC')->limit($limit)->get();
    }

    public function getUnreadCount(User $user): int
    {
        return $this->scopedInboxQuery($user)->unread()->count();
    }

    public function markAsRead(int $notificationId, User $user): UserNotification
    {
        $n = $this->findOwnedOrFail($notificationId, $user);
        $n->markRead();

        return $n->fresh();
    }

    public function markAsUnread(int $notificationId, User $user): UserNotification
    {
        $n = $this->findOwnedOrFail($notificationId, $user);
        $n->markUnread();

        return $n->fresh();
    }

    public function markAllAsRead(User $user): int
    {
        return $this->scopedInboxQuery($user)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * @param  array<int>  $ids
     */
    public function bulkMarkAsRead(array $ids, User $user): int
    {
        $ids = $this->normalizeIds($ids);
        $this->assertAllIdsInScopedInbox($ids, $user);

        return $this->scopedInboxQuery($user)
            ->whereIn('id', $ids)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * @param  array<int>  $ids
     */
    public function bulkMarkAsUnread(array $ids, User $user): int
    {
        $ids = $this->normalizeIds($ids);
        $this->assertAllIdsInScopedInbox($ids, $user);

        return $this->scopedInboxQuery($user)
            ->whereIn('id', $ids)
            ->update([
                'is_read' => false,
                'read_at' => null,
            ]);
    }

    public function deleteNotification(int $notificationId, User $user): void
    {
        $this->findOwnedOrFail($notificationId, $user)->delete();
    }

    /**
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids, User $user): int
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            return 0;
        }

        $this->assertAllIdsInScopedInbox($ids, $user);

        return $this->scopedInboxQuery($user)
            ->whereIn('id', $ids)
            ->delete();
    }

    public function deleteReadNotifications(User $user): int
    {
        return $this->scopedInboxQuery($user)
            ->read()
            ->delete();
    }

    public function findOwnedOrFail(int $notificationId, User $user): UserNotification
    {
        return $this->scopedInboxQuery($user)
            ->whereKey($notificationId)
            ->firstOrFail();
    }

    /** @return Builder<UserNotification> */
    private function scopedInboxQuery(User $user): Builder
    {
        $q = UserNotification::query()->where('user_id', $user->id);
        StaffBranchScope::applyNotificationInboxScope($q, $user);

        return $q;
    }

    /**
     * @param  Builder<UserNotification>  $q
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $q, array $filters): void
    {
        $tab = $filters['tab'] ?? 'all';
        if ($tab === 'unread') {
            $q->unread();
        } elseif ($tab === 'read') {
            $q->read();
        }

        if (! empty($filters['search'])) {
            $s = frc_like_pattern((string) $filters['search']);
            $q->where(function (Builder $w) use ($s): void {
                $w->where('title', 'like', $s)->orWhere('message', 'like', $s);
            });
        }

        if (! empty($filters['type'])) {
            $q->where('type', (string) $filters['type']);
        }

        if (! empty($filters['module'])) {
            $q->where('module', (string) $filters['module']);
        }

        if (! empty($filters['date_from'])) {
            try {
                $q->whereDate('created_at', '>=', $filters['date_from']);
            } catch (\Throwable) {
            }
        }

        if (! empty($filters['date_to'])) {
            try {
                $q->whereDate('created_at', '<=', $filters['date_to']);
            } catch (\Throwable) {
            }
        }
    }

    /**
     * @param  array<int>  $ids
     */
    private function assertAllIdsInScopedInbox(array $ids, User $user): void
    {
        if ($ids === []) {
            return;
        }

        $accessible = $this->scopedInboxQuery($user)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        abort_if(
            count($accessible) !== count($ids),
            403,
            'One or more notifications are not in your inbox.',
        );
    }

    /**
     * @param  array<int|string>  $ids
     * @return array<int>
     */
    private function normalizeIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $i = (int) $id;
            if ($i > 0) {
                $out[] = $i;
            }
        }

        return array_values(array_unique($out));
    }
}
