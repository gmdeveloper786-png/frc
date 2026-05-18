<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
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
    public function getUserNotifications(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = UserNotification::query()->where('user_id', $userId);

        $this->applyFilters($q, $filters);

        /** @var LengthAwarePaginatorConcrete $paginator */
        $paginator = $q->latest()->paginate($perPage);
        return $paginator->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, UserNotification>
     */
    public function getLatestNotifications(int $userId, int $limit = 5, array $filters = []): Collection
    {
        $q = UserNotification::query()->where('user_id', $userId);
        $this->applyFilters($q, $filters);

        return $q->orderByRaw('is_read ASC, created_at DESC')->limit($limit)->get();
    }

    public function getUnreadCount(int $userId): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->unread()
            ->count();
    }

    public function markAsRead(int $notificationId, int $userId): UserNotification
    {
        $n = $this->findOwnedOrFail($notificationId, $userId);
        $n->markRead();

        return $n->fresh();
    }

    public function markAsUnread(int $notificationId, int $userId): UserNotification
    {
        $n = $this->findOwnedOrFail($notificationId, $userId);
        $n->markUnread();

        return $n->fresh();
    }

    public function markAllAsRead(int $userId): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * @param  array<int>  $ids
     */
    public function bulkMarkAsRead(array $ids, int $userId): int
    {
        $ids = $this->normalizeIds($ids);

        return UserNotification::query()
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * @param  array<int>  $ids
     */
    public function bulkMarkAsUnread(array $ids, int $userId): int
    {
        $ids = $this->normalizeIds($ids);

        return UserNotification::query()
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->update([
                'is_read' => false,
                'read_at' => null,
            ]);
    }

    public function deleteNotification(int $notificationId, int $userId): void
    {
        $this->findOwnedOrFail($notificationId, $userId)->delete();
    }

    /**
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids, int $userId): int
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            return 0;
        }

        return UserNotification::query()
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->delete();
    }

    public function deleteReadNotifications(int $userId): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->read()
            ->delete();
    }

    public function findOwnedOrFail(int $notificationId, int $userId): UserNotification
    {
        return UserNotification::query()
            ->whereKey($notificationId)
            ->where('user_id', $userId)
            ->firstOrFail();
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
            $s = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['search']) . '%';
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
