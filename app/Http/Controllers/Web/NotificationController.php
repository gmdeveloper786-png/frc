<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Services\NotificationOpenService;
use App\Services\UserNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    private const INBOX_PER_PAGE = 15;

    public function __construct(
        private readonly UserNotificationService $inbox,
        private readonly NotificationOpenService $openService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'tab' => $request->query('tab', 'all'),
        ];

        $notifications = $this->inbox->getUserNotifications(
            $request->user(),
            $filters,
            self::INBOX_PER_PAGE,
        );

        return view('notifications.index', compact('notifications', 'filters'));
    }

    /**
     * @deprecated Use {@see open()} — kept for backwards-compatible URLs.
     */
    public function follow(Request $request, UserNotification $notification): RedirectResponse
    {
        return $this->open($request, $notification);
    }

    public function open(Request $request, UserNotification $notification): RedirectResponse
    {
        $this->authorize('view', $notification);

        $this->inbox->markAsRead((int) $notification->id, $request->user());

        return $this->openService->redirectAfterOpen($request, $notification);
    }

    public function markRead(Request $request, UserNotification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);
        $this->inbox->markAsRead((int) $notification->id, $request->user());

        return redirect()->back()->with('success', 'Marked as read.');
    }

    public function markUnread(Request $request, UserNotification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);
        $this->inbox->markAsUnread((int) $notification->id, $request->user());

        return redirect()->back()->with('success', 'Marked as unread.');
    }

    public function destroy(Request $request, UserNotification $notification): RedirectResponse
    {
        $this->authorize('delete', $notification);
        $this->inbox->deleteNotification((int) $notification->id, $request->user());

        return redirect()->back()->with('success', 'Notification removed.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $count = $this->inbox->markAllAsRead($request->user());

        return redirect()->back()->with('success', $count > 0 ? "Marked {$count} as read." : 'No unread notifications.');
    }

    public function bulkMarkRead(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $updated = $this->inbox->bulkMarkAsRead(is_array($ids) ? $ids : [], $request->user());

        return redirect()->back()->with('success', $updated > 0 ? "Marked {$updated} as read." : 'Nothing to update.');
    }

    public function bulkMarkUnread(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $updated = $this->inbox->bulkMarkAsUnread(is_array($ids) ? $ids : [], $request->user());

        return redirect()->back()->with('success', $updated > 0 ? "Marked {$updated} as unread." : 'Nothing to update.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $deleted = $this->inbox->bulkDelete(is_array($ids) ? $ids : [], $request->user());

        return redirect()->back()->with('success', $deleted > 0 ? "Deleted {$deleted} notification(s)." : 'Nothing to delete.');
    }

    public function deleteRead(Request $request): RedirectResponse
    {
        $deleted = $this->inbox->deleteReadNotifications($request->user());

        return redirect()->back()->with('success', $deleted > 0 ? "Removed {$deleted} read notification(s)." : 'No read notifications to remove.');
    }
}
