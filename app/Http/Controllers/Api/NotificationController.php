<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkNotificationIdsRequest;
use App\Http\Requests\NotificationIndexFilterRequest;
use App\Models\UserNotification;
use App\Services\UserNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly UserNotificationService $inbox,
    ) {}

    public function index(NotificationIndexFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 20);

        $paginator = $this->inbox->getUserNotifications($request->user(), $filters, $perPage);

        return response()->json($paginator);
    }

    public function latest(Request $request): JsonResponse
    {
        $items = $this->inbox->getLatestNotifications($request->user(), 5);

        return response()->json($items);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $this->inbox->getUnreadCount($request->user()),
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = $this->inbox->findOwnedOrFail($id, $request->user());
        $this->authorize('update', $notification);
        $n = $this->inbox->markAsRead($id, $request->user());

        return response()->json($n);
    }

    public function markUnread(Request $request, int $id): JsonResponse
    {
        $notification = $this->inbox->findOwnedOrFail($id, $request->user());
        $this->authorize('update', $notification);
        $n = $this->inbox->markAsUnread($id, $request->user());

        return response()->json($n);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $notification = $this->inbox->findOwnedOrFail($id, $request->user());
        $this->authorize('delete', $notification);
        $this->inbox->deleteNotification($id, $request->user());

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->inbox->markAllAsRead($request->user());

        return response()->json(['updated' => $count]);
    }

    public function bulkMarkRead(BulkNotificationIdsRequest $request): JsonResponse
    {
        $updated = $this->inbox->bulkMarkAsRead($request->validated('ids'), $request->user());

        return response()->json(['updated' => $updated]);
    }

    public function bulkMarkUnread(BulkNotificationIdsRequest $request): JsonResponse
    {
        $updated = $this->inbox->bulkMarkAsUnread($request->validated('ids'), $request->user());

        return response()->json(['updated' => $updated]);
    }

    public function bulkDelete(BulkNotificationIdsRequest $request): JsonResponse
    {
        $deleted = $this->inbox->bulkDelete($request->validated('ids'), $request->user());

        return response()->json(['deleted' => $deleted]);
    }

    public function deleteRead(Request $request): JsonResponse
    {
        $deleted = $this->inbox->deleteReadNotifications($request->user());

        return response()->json(['deleted' => $deleted]);
    }
}
