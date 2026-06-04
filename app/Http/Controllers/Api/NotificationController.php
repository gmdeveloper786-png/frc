<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Services\UserNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly UserNotificationService $inbox,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'tab'        => $request->query('tab', 'all'),
            'search'     => $request->query('search'),
            'type'       => $request->query('type'),
            'module'     => $request->query('module'),
            'date_from'  => $request->query('date_from'),
            'date_to'    => $request->query('date_to'),
        ];

        $paginator = $this->inbox->getUserNotifications($request->user(), $filters, (int) $request->query('per_page', 20));

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
        $n = $this->inbox->markAsRead($id, $request->user());

        return response()->json($n);
    }

    public function markUnread(Request $request, int $id): JsonResponse
    {
        $n = $this->inbox->markAsUnread($id, $request->user());

        return response()->json($n);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->inbox->deleteNotification($id, $request->user());

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->inbox->markAllAsRead($request->user());

        return response()->json(['updated' => $count]);
    }

    public function bulkMarkRead(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $updated = $this->inbox->bulkMarkAsRead(is_array($ids) ? $ids : [], $request->user());

        return response()->json(['updated' => $updated]);
    }

    public function bulkMarkUnread(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $updated = $this->inbox->bulkMarkAsUnread(is_array($ids) ? $ids : [], $request->user());

        return response()->json(['updated' => $updated]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $deleted = $this->inbox->bulkDelete(is_array($ids) ? $ids : [], $request->user());

        return response()->json(['deleted' => $deleted]);
    }

    public function deleteRead(Request $request): JsonResponse
    {
        $deleted = $this->inbox->deleteReadNotifications($request->user());

        return response()->json(['deleted' => $deleted]);
    }
}
