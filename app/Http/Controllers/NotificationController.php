<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SystemNotification::class);

        $user = auth()->user();
        $activeBranchId = session('active_branch_id');

        $filters = $request->only(['type', 'status']);
        $notifications = $this->notificationService->getNotifications($user, $activeBranchId, $filters);
        $unreadCount = $this->notificationService->getUnreadCount($user, $activeBranchId);

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function unreadCount(): JsonResponse
    {
        $user = auth()->user();
        $activeBranchId = session('active_branch_id');

        $count = $this->notificationService->getUnreadCount($user, $activeBranchId);

        return response()->json(['unread_count' => $count]);
    }

    public function markAsRead(SystemNotification $notification): RedirectResponse
    {
        $this->authorize('markAsRead', $notification);

        $this->notificationService->markAsRead($notification);

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        $this->authorize('markAllAsRead', SystemNotification::class);

        $user = auth()->user();
        $activeBranchId = session('active_branch_id');

        $this->notificationService->markAllAsRead($user, $activeBranchId);

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}
