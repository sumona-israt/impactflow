<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Every action here is scoped to the requesting user's own notifications —
 * no PermissionEnum gating, since there's nothing to permission-check beyond
 * "is this yours" (see docs/database-design.md §11).
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 25), 100);

        $notifications = $request->user()->notifications()->paginate($perPage);

        return ApiResponse::data(
            NotificationResource::collection($notifications->items()),
            ['page' => $notifications->currentPage(), 'per_page' => $notifications->perPage(), 'total' => $notifications->total()],
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::data(['count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        abort_if(
            $notification->notifiable_type !== $request->user()::class || $notification->notifiable_id !== $request->user()->id,
            404,
        );

        $notification->markAsRead();

        return ApiResponse::data(new NotificationResource($notification->fresh()));
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return ApiResponse::message('All notifications marked as read.');
    }
}
