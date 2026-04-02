<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        $user = Auth::user();
        
        $unreadCount = $user->unreadNotifications()->count();
        
        return response()->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Get unread notifications list
     */
    public function unreadNotifications()
    {
        $user = Auth::user();
        
            $notifications = $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;
                $notificationType = $notification->type; // e.g., 'App\Notifications\EmergencyReportStatusChanged'
                
                // Check if this is a responder status change notification
                if (str_contains($notificationType, 'ResponderStatusChanged')) {
                    // Handle responder status change notifications
                    $reportId = $data['report_id'] ?? null;
                    $responderName = $data['responder_name'] ?? 'Responder';
                    $action = $data['action'] ?? 'updated';
                    $role = $data['role'] ?? 'responder';
                    $message = $data['message'] ?? 'Responder status updated';
                    $type = $data['type'] ?? 'info';
                    
                    $roleText = $role === 'primary' ? 'Primary Responder' : 'Secondary Responder';
                    $title = "{$responderName} ({$roleText})";
                    
                    return [
                        'id' => $notification->id,
                        'title' => $title,
                        'message' => $message,
                        'type' => $type,
                        'reportId' => $reportId,
                        'responderId' => $data['responder_id'] ?? null,
                        'action' => $action,
                        'role' => $role,
                        'timestamp' => $notification->created_at->timestamp,
                        'created_at' => $notification->created_at->toIso8601String(),
                    ];
                } else {
                    // Handle emergency report status change notifications
                    $reportId = $data['report_id'] ?? null;
                    $message = $data['message'] ?? 'Notification';
                    $newStatus = $data['new_status'] ?? null;
                    
                    // Determine notification type based on status
                    $type = 'info';
                    if ($newStatus === 'resolved') {
                        $type = 'success';
                    } elseif ($newStatus === 'cancelled') {
                        $type = 'error';
                    } elseif ($newStatus === 'acknowledged') {
                        $type = 'warning';
                    }
                    
                    // Extract title from message or create one
                    $title = "Report #{$reportId} Status Update";
                    if (preg_match('/Report #(\d+)\s+(.+)/', $message, $matches)) {
                        $title = "Report #{$matches[1]}";
                        $message = $matches[2];
                    }
                    
                    return [
                        'id' => $notification->id,
                        'title' => $title,
                        'message' => $message,
                        'type' => $type,
                        'reportId' => $reportId,
                        'timestamp' => $notification->created_at->timestamp,
                        'created_at' => $notification->created_at->toIso8601String(),
                    ];
                }
            });
        
        return response()->json([
            'status' => 'success',
            'notifications' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification not found'
            ], 404);
        }
        
        $notification->markAsRead();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        $user->unreadNotifications->markAsRead();
        
        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ]);
    }
}

