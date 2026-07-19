<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unread_only' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->user()->notifications();
        if ($validated['unread_only'] ?? false) {
            $query->whereNull('read_at');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $request->user()->unreadNotifications()->count(),
                'notifications' => $query->latest()->paginate($validated['per_page'] ?? 20)->withQueryString(),
            ],
        ]);
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        abort_unless(
            $notification->notifiable_type === $request->user()::class
            && (int) $notification->notifiable_id === (int) $request->user()->getKey(),
            Response::HTTP_NOT_FOUND,
        );

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil ditandai sudah dibaca.',
            'data' => $notification->refresh(),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi berhasil ditandai sudah dibaca.',
            'data' => ['updated' => $updated],
        ]);
    }
}
