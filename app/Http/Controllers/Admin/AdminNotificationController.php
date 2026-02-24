<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Notifications\ListAdminNotificationsRequest;
use App\Http\Resources\Admin\AdminNotificationResource;
use App\Models\AdminNotification;
use App\Models\User;
use App\Services\AdminNotifications\Contracts\AdminNotificationServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * @group Admin Notifications
 *
 * APIs for managing admin in-app notifications with polling support.
 */
class AdminNotificationController extends Controller
{
    public function __construct(
        private readonly AdminNotificationServiceInterface $notificationService
    ) {}

    /**
     * List notifications
     *
     * Get paginated list of notifications for the authenticated admin.
     * Supports filtering by type, unread status, and timestamp for polling.
     *
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page (max 100). Example: 15
     * @queryParam unread_only boolean Show only unread notifications. Example: true
     * @queryParam type integer Notification type filter. Example: 2
     * @queryParam since integer Unix timestamp for polling (get notifications after this time). Example: 1708123456
     */
    public function index(ListAdminNotificationsRequest $request): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user('admin');

        $paginator = $this->notificationService->list(
            $request->filters(),
            $admin
        );

        return response()->json([
            'success' => true,
            'data' => AdminNotificationResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Get unread count
     *
     * Get the count of unread notifications for the authenticated admin.
     * This is a lightweight endpoint optimized for polling.
     */
    public function count(ListAdminNotificationsRequest $request): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user('admin');

        $count = $this->notificationService->getUnreadCount($admin);

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    /**
     * Mark notification as read
     *
     * Mark a specific notification as read.
     *
     * @urlParam notification integer required The notification ID. Example: 1
     */
    public function markAsRead(AdminNotification $notification, ListAdminNotificationsRequest $request): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user('admin');

        $notification = $this->notificationService->markAsRead($notification, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data' => new AdminNotificationResource($notification),
        ]);
    }

    /**
     * Mark all notifications as read
     *
     * Mark all unread notifications as read for the authenticated admin.
     */
    public function markAllAsRead(ListAdminNotificationsRequest $request): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user('admin');

        $count = $this->notificationService->markAllAsRead($admin);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
            'data' => [
                'marked_count' => $count,
            ],
        ]);
    }

    /**
     * Delete notification
     *
     * Delete a specific notification.
     *
     * @urlParam notification integer required The notification ID. Example: 1
     */
    public function destroy(AdminNotification $notification, ListAdminNotificationsRequest $request): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user('admin');

        $this->notificationService->delete($notification, $admin);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 200);
    }
}
