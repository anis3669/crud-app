<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        return response()->json([
            'notifications' => $notifications->through(
                fn($notification) => $this->formatNotification($notification)
            ),
        ]);
    }

    public function unread(Request $request)
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->get();

        return response()->json([
            'notifications' => $notifications->map(
                fn($notification) => $this->formatNotification($notification)
            )->values(),
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
        ]);
    }

    private function formatNotification($notification): array
    {
        return [
            'id' => $notification->id,
            'type' => class_basename($notification->type),
            'message' => $notification->data['message'] ?? 'You have a new notification.',
            'invoice_id' => $notification->data['invoice_id'] ?? null,
            'invoice_number' => $notification->data['invoice_number'] ?? null,
            'customer_name' => $notification->data['customer_name'] ?? null,
            'total' => $notification->data['total'] ?? null,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
        ];
    }
}
