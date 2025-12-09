<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            Log::info('Loading notifications for user: ' . $user->id);

            $perPage = $request->input('per_page', 20);
            
            // ✅ OPTIMASI: Limit max per_page
            $perPage = min($perPage, 100);
            
            // Get notifications with pagination
            $query = $user->notifications()->latest();
            
            // ✅ FIXED: Search filter dengan KAPITAL field names
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    // ✅ SQL Server: Use JSON_VALUE dengan KAPITAL field names
                    $q->whereRaw("JSON_VALUE(data, '$.No_Unit') LIKE ?", ["%{$search}%"])      
                      ->orWhereRaw("JSON_VALUE(data, '$.Alarm') LIKE ?", ["%{$search}%"])      
                      ->orWhereRaw("JSON_VALUE(data, '$.Location') LIKE ?", ["%{$search}%"])   
                      ->orWhereRaw("JSON_VALUE(data, '$.message') LIKE ?", ["%{$search}%"]);
                });
            }
            
            // Status filter
            $filter = $request->input('filter', 'all');
            if ($filter === 'unread') {
                $query->whereNull('read_at');
            } elseif ($filter === 'read') {
                $query->whereNotNull('read_at');
            }
            
            // Paginate
            $notifications = $query->paginate($perPage);
            
            // ✅ OPTIMASI: Cache unread count (1 menit)
            $cacheKey = "user_{$user->id}_unread_notifications_count";
            $unreadCount = cache()->remember($cacheKey, 60, function() use ($user) {
                return $user->unreadNotifications()->count();
            });

            Log::info('Notifications loaded successfully', [
                'total' => $notifications->total(),
                'unread' => $unreadCount
            ]);

            return response()->json([
                'notifications' => $notifications->items(),
                'unread_count' => $unreadCount,
                'total' => $notifications->total(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading notifications: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to load notifications',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function markAsRead(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $notification = $user->notifications()->find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->markAsRead();

            // ✅ Clear cache
            cache()->forget("user_{$user->id}_unread_notifications_count");

            Log::info('Notification marked as read', [
                'user_id' => $user->id,
                'notification_id' => $id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'unread_count' => $user->unreadNotifications()->count(), // ✅ Return updated count
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking notification as read: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to mark as read',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function markAllAsRead(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // ✅ OPTIMASI: Batch update dengan query builder (lebih cepat)
            DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            // ✅ Clear cache
            cache()->forget("user_{$user->id}_unread_notifications_count");

            Log::info('All notifications marked as read for user: ' . $user->id);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
                'unread_count' => 0,
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking all as read: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to mark all as read',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $notification = $user->notifications()->find($id);

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            // ✅ Clear cache
            cache()->forget("user_{$user->id}_unread_notifications_count");

            Log::info('Notification deleted', [
                'user_id' => $user->id,
                'notification_id' => $id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting notification: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete notification',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    // ✅ OPTIMIZED: Bulk Delete dengan Chunking (Avoid 2100 Parameter Limit)
    public function bulkDelete(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No notifications selected'
                ], 400);
            }

            // ✅ SOLUSI: Chunk IDs untuk avoid 2100 parameter limit
            $totalDeleted = 0;
            $chunkSize = 1000; // Safe limit untuk SQL Server
            
            foreach (array_chunk($ids, $chunkSize) as $chunk) {
                $deleted = $user->notifications()
                    ->whereIn('id', $chunk)
                    ->delete();
                
                $totalDeleted += $deleted;
            }

            // ✅ Clear cache
            cache()->forget("user_{$user->id}_unread_notifications_count");

            Log::info('Bulk delete notifications', [
                'user_id' => $user->id,
                'count' => $totalDeleted,
                'requested_ids' => count($ids),
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$totalDeleted} notification(s) deleted",
                'deleted_count' => $totalDeleted,
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk deleting notifications: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete notifications',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    // ✅ OPTIMIZED: Delete All dengan Query Builder
    public function deleteAll(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // ✅ OPTIMASI: Direct query builder (lebih cepat dari Eloquent)
            $deleted = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->delete();

            // ✅ Clear cache
            cache()->forget("user_{$user->id}_unread_notifications_count");

            Log::info('Delete all notifications', [
                'user_id' => $user->id,
                'count' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => $deleted > 0 
                    ? "All {$deleted} notification(s) deleted" 
                    : "No notifications to delete",
                'deleted_count' => $deleted,
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting all notifications: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete all notifications',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}