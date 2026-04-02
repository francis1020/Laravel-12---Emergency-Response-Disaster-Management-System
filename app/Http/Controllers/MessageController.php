<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\MessageRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function store(Request $request, $reportId)
    {
        $report = EmergencyReport::findOrFail($reportId);
        
        // Check if user has access to this report
        $hasAccess = $report->user_id === Auth::id() 
            || Auth::user()->isAdmin()
            || $report->responders()->where('responder_id', Auth::id())->exists();

        if (!$hasAccess) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'type' => 'nullable|in:text,system,status_update',
            'attachments' => 'nullable|array',
        ]);

        $message = Message::create([
            'emergency_report_id' => $reportId,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'type' => $validated['type'] ?? 'text',
            'attachments' => $validated['attachments'] ?? null,
        ]);

        // Mark message as read by sender (they've obviously read their own message)
        $message->readBy()->syncWithoutDetaching([
            Auth::id() => ['read_at' => now()]
        ]);

        // Get sender's info for read status broadcast
        $sender = Auth::user();
        $senderName = $sender ? $sender->name : 'Unknown';
        $senderAvatar = \Laravolt\Avatar\Facade::create($senderName)->toBase64();
        
        // Prepare all readers data (including sender)
        $allReaders = [
            $message->id => [
                [
                    'user_id' => Auth::id(),
                    'user_name' => $senderName,
                    'avatar' => $senderAvatar,
                ]
            ]
        ];

        // Reload message with readBy relationship to include read status
        $message->load('readBy');
        
        // Add avatar for the message sender (user)
        $messageWithUser = $message->load('user');
        if ($messageWithUser->user) {
            try {
                $userAvatar = \Laravolt\Avatar\Facade::create($messageWithUser->user->name ?? 'Unknown')->toBase64();
                if (empty($userAvatar)) {
                    $userAvatar = $this->generateFallbackAvatar($messageWithUser->user->name ?? 'Unknown');
                }
            } catch (\Exception $e) {
                $userAvatar = $this->generateFallbackAvatar($messageWithUser->user->name ?? 'Unknown');
            }
            $messageWithUser->user->avatar = $userAvatar;
        }
        
        // Format read_by data for response
        $readByUsers = $message->readBy;
        $message->read_by = $readByUsers->map(function($user) {
            $avatar = \Laravolt\Avatar\Facade::create($user->name ?? 'Unknown')->toBase64();
            return [
                'user_id' => $user->id,
                'user_name' => $user->name ?? 'Unknown',
                'avatar' => $avatar,
                'read_at' => $user->pivot->read_at ?? null,
            ];
        });

        // Broadcast message
        broadcast(new MessageSent($messageWithUser))->toOthers();
        
        // Broadcast read status so sender's avatar appears on their own message
        broadcast(new MessageRead([$message->id], $reportId, Auth::id(), $senderName, $allReaders));

        return response()->json([
            'status' => 'success',
            'message' => $messageWithUser,
        ]);
    }

    public function index()
    {
        $user = Auth::user();
        
        // Get all reports the user has access to
        $reportsQuery = EmergencyReport::query();
        
        if ($user->isAdmin()) {
            // Admins can see all reports
            $reportsQuery = EmergencyReport::query();
        } elseif ($user->isResponder()) {
            // Responders can see reports they're assigned to
            $reportsQuery = EmergencyReport::whereHas('responders', function($q) use ($user) {
                $q->where('responder_id', $user->id);
            });
        } else {
            // Regular users can only see their own reports
            $reportsQuery = EmergencyReport::where('user_id', $user->id);
        }
        
        // Get all reports (not just ones with messages) with latest message info
        // We need to get all reports first, sort them, then paginate
        $allReports = $reportsQuery->with(['user', 'emergencyType'])
            ->withCount('messages')
            ->get();
        
        $currentUserId = Auth::id();
        $reportIds = $allReports->pluck('id');
        
        // Get latest message for each report using a simpler approach
        $latestMessages = Message::whereIn('emergency_report_id', $reportIds)
            ->with('user')
            ->orderBy('emergency_report_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('emergency_report_id')
            ->map(function($messages) {
                return $messages->first();
            });
        
        // Get unread message count for each report (messages not read by current user)
        $unreadCounts = Message::whereIn('emergency_report_id', $reportIds)
            ->where('user_id', '!=', $currentUserId)
            ->whereDoesntHave('readBy', function($q) use ($currentUserId) {
                $q->where('user_id', $currentUserId);
            })
            ->selectRaw('emergency_report_id, COUNT(*) as unread_count')
            ->groupBy('emergency_report_id')
            ->pluck('unread_count', 'emergency_report_id');
        
        // Attach latest messages and unread counts to reports
        foreach ($allReports as $report) {
            $report->latest_message = $latestMessages->get($report->id);
            $report->unread_count = $unreadCounts->get($report->id) ?? 0;
        }
        
        // Sort reports: unread messages first, then by latest message time
        $sortedReports = $allReports->sortByDesc(function($report) {
            // Primary sort: unread count (reports with unread messages first)
            // Secondary sort: latest message timestamp (most recent first)
            $latestMessageTime = $report->latest_message ? $report->latest_message->created_at->timestamp : 0;
            return [
                $report->unread_count > 0 ? 1 : 0, // Unread first
                $latestMessageTime // Then by latest message time
            ];
        })->values(); // Reset keys after sorting
        
        // Manually paginate the sorted collection
        $currentPage = request()->get('page', 1);
        $perPage = 15;
        $currentItems = $sortedReports->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        $reports = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $sortedReports->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        
        return view('messages.index', compact('reports'));
    }

    public function getMessages($reportId, Request $request)
    {
        try {
            $report = EmergencyReport::with(['user', 'responders.responder', 'emergencyType'])->findOrFail($reportId);
            $currentUserId = Auth::id();
            
            // Check if user has access to this report
            $hasAccess = $report->user_id === $currentUserId 
                || Auth::user()->isAdmin()
                || $report->responders()->where('responder_id', $currentUserId)->exists();

            if (!$hasAccess) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
            }

            // Eager load relationships to prevent N+1 queries
            $messages = $report->messages()
                ->with(['user', 'readBy'])
                ->orderBy('created_at', 'asc')
                ->get();
        
        // Get all users involved in the report and generate their avatars
        $usersInvolved = collect();

        // Get all readers from message_reads table grouped by user_id within this report
        $messageIds = $messages->pluck('id')->toArray();
        if (!empty($messageIds)) {
            $allReaders = \DB::table('message_reads')
                ->join('messages', 'message_reads.message_id', '=', 'messages.id')
                ->where('messages.emergency_report_id', $reportId)
                ->select('message_reads.user_id')
                ->distinct()
                ->pluck('user_id');
            
            // Get user models for all readers
            $readerUsers = User::whereIn('id', $allReaders)->get();
            foreach ($readerUsers as $reader) {
                $usersInvolved->push($reader);
            }
        }
        
        // Get unique users and generate avatars
        $uniqueUsers = $usersInvolved->unique('id');
        $usersWithAvatars = $uniqueUsers->map(function($user) {
            try {
                // Generate Laravolt Avatar with consistent size (48x48 to match display size)
                $avatar = \Laravolt\Avatar\Facade::create($user->name ?? 'Unknown')
                    ->toBase64();
                if (empty($avatar)) {
                    $avatar = $this->generateFallbackAvatar($user->name ?? 'Unknown');
                }
            } catch (\Exception $e) {
                $avatar = $this->generateFallbackAvatar($user->name ?? 'Unknown');
            }
            return [
                'user_id' => $user->id,
                'user_name' => $user->name ?? 'Unknown',
                'avatar' => $avatar,
            ];
        })->values();
        
        // Get messages not sent by current user that haven't been read by current user
        $unreadMessages = Message::where('emergency_report_id', $reportId)
            ->where('user_id', '!=', $currentUserId)
            ->whereDoesntHave('readBy', function($q) use ($currentUserId) {
                $q->where('user_id', $currentUserId);
            })
            ->with('readBy') // Eager load readBy to prevent N+1 queries
            ->get();
        
        // Mark messages as read by current user
        if ($unreadMessages->count() > 0) {
            $messageIds = $unreadMessages->pluck('id')->toArray();
            
            // Mark messages as read by current user (using pivot table)
            foreach ($unreadMessages as $message) {
                $message->readBy()->syncWithoutDetaching([
                    $currentUserId => ['read_at' => now()]
                ]);
            }
            
            // Get reader's name for broadcast
            $reader = \App\Models\User::find($currentUserId);
            $readerName = $reader ? $reader->name : 'Unknown';
            
            // Get all readers for each message to include in broadcast
            $messagesWithReaders = [];
            foreach ($unreadMessages as $message) {
                $readers = $message->readBy()->orderBy('message_reads.read_at', 'desc')->get();
                $messagesWithReaders[$message->id] = $readers->map(function($user) {
                    try {
                        // Generate Laravolt Avatar with consistent size (48x48 to match display size)
                        $avatar = \Laravolt\Avatar\Facade::create($user->name ?? 'Unknown')
                            ->toBase64();
                        // Ensure avatar is not empty
                        if (empty($avatar)) {
                            $avatar = $this->generateFallbackAvatar($user->name ?? 'Unknown');
                        }
                    } catch (\Exception $e) {
                        $avatar = $this->generateFallbackAvatar($user->name ?? 'Unknown');
                    }
                    return [
                        'user_id' => $user->id,
                        'user_name' => $user->name ?? 'Unknown',
                        'avatar' => $avatar,
                    ];
                })->toArray();
            }
            
            // Broadcast read status update with all readers
            // Use toOthers() to exclude current user, but also send to current user so they see their own read status update
            // Wrap in try-catch to prevent broadcast errors from breaking the API response
            try {
                broadcast(new MessageRead($messageIds, $reportId, $currentUserId, $readerName, $messagesWithReaders));
            } catch (\Exception $e) {
                // Log the error but don't fail the request
                Log::error('Failed to broadcast message read status: ' . $e->getMessage());
            }
        }

        // Add read status per user to each message and include readBy information
        $messages = $messages->map(function($message) use ($currentUserId) {
            try {
                $message->is_read = $message->isReadBy($currentUserId);
                $readRecord = $message->readBy()->where('user_id', $currentUserId)->first();
                $message->read_at = $readRecord ? $readRecord->pivot->read_at : null;
                
                // Add avatar for the message sender (user)
                if ($message->user) {
                    try {
                        $senderAvatar = \Laravolt\Avatar\Facade::create($message->user->name ?? 'Unknown')->toBase64();
                        if (empty($senderAvatar)) {
                            $senderAvatar = $this->generateFallbackAvatar($message->user->name ?? 'Unknown');
                        }
                    } catch (\Exception $e) {
                        $senderAvatar = $this->generateFallbackAvatar($message->user->name ?? 'Unknown');
                    }
                    $message->user->avatar = $senderAvatar;
                }
                
                // Get all users who read this message (for display purposes)
                // Use already loaded relationship if available, otherwise query
                $readByUsers = $message->relationLoaded('readBy') 
                    ? $message->readBy->sortByDesc(function($user) {
                        return $user->pivot->read_at ?? now();
                    })->values()
                    : $message->readBy()->orderBy('message_reads.read_at', 'desc')->get();
                $message->read_by = $readByUsers->map(function($user) {
                    try {
                        // Generate Laravolt Avatar with consistent size (48x48 to match display size)
                        // This ensures consistent avatar generation
                        $avatar = \Laravolt\Avatar\Facade::create($user->name ?? 'Unknown')
                            ->toBase64();
                        if (empty($avatar)) {
                            $avatar = $this->generateFallbackAvatar($user->name ?? 'Unknown');
                        }
                    } catch (\Exception $e) {
                        $avatar = $this->generateFallbackAvatar($user->name ?? 'Unknown');
                    }
                    return [
                        'user_id' => $user->id,
                        'user_name' => $user->name ?? 'Unknown',
                        'avatar' => $avatar,
                        'read_at' => $user->pivot->read_at ?? null,
                    ];
                });
                
                // Get the last user who read it (most recent)
                $lastReader = $readByUsers->first();
                if ($lastReader) {
                    try {
                        // Generate Laravolt Avatar with consistent size (48x48 to match display size)
                        $lastReaderAvatar = \Laravolt\Avatar\Facade::create($lastReader->name ?? 'Unknown')
                            ->toBase64();
                        if (empty($lastReaderAvatar)) {
                            $lastReaderAvatar = $this->generateFallbackAvatar($lastReader->name ?? 'Unknown');
                        }
                    } catch (\Exception $e) {
                        $lastReaderAvatar = $this->generateFallbackAvatar($lastReader->name ?? 'Unknown');
                    }
                    $message->last_read_by = [
                        'user_id' => $lastReader->id,
                        'user_name' => $lastReader->name ?? 'Unknown',
                        'avatar' => $lastReaderAvatar,
                        'read_at' => $lastReader->pivot->read_at ?? null,
                    ];
                } else {
                    $message->last_read_by = null;
                }
            } catch (\Exception $e) {
                // If there's an error processing a message, log it but continue with other messages
                Log::error('Error processing message ' . $message->id . ': ' . $e->getMessage());
                // Set default values to prevent serialization errors
                $message->read_by = [];
                $message->last_read_by = null;
            }
            
            return $message;
        });

            // Prepare report details for response
            $reportDetails = [
                'id' => $report->id,
                'type' => $report->type,
                'type_name' => $report->type_name,
                'type_icon' => $report->type_icon,
                'description' => $report->description,
                'address' => $report->address,
                'landmark' => $report->landmark,
                'latitude' => $report->latitude,
                'longitude' => $report->longitude,
                'severity_level' => $report->severity_level,
                'reported_at' => $report->reported_at?->toIso8601String(),
                'contact_name' => $report->contact_name,
                'contact_number' => $report->contact_number,
                'email' => $report->email,
                'reporter' => $report->user ? [
                    'id' => $report->user->id,
                    'name' => $report->user->name,
                ] : null,
            ];

            return response()->json([
                'status' => 'success',
                'messages' => $messages,
                'users_involved' => $usersWithAvatars,
                'report' => $reportDetails,
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error in getMessages: ' . $e->getMessage(), [
                'report_id' => $reportId,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a proper JSON error response instead of letting Laravel return HTML error page
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load messages. Please try again.',
            ], 500);
        }
    }

    public function markAsRead(Request $request, $messageId)
    {
        $message = Message::findOrFail($messageId);
        $currentUserId = Auth::id();
        
        // Only the recipient can mark as read
        if ($message->user_id === $currentUserId) {
            return response()->json(['status' => 'error', 'message' => 'Cannot mark your own message as read'], 400);
        }

        // Mark message as read by current user (using pivot table)
        if (!$message->isReadBy($currentUserId)) {
            $message->readBy()->syncWithoutDetaching([
                $currentUserId => ['read_at' => now()]
            ]);
            
            // Get reader's name for broadcast
            $reader = \App\Models\User::find($currentUserId);
            $readerName = $reader ? $reader->name : 'Unknown';
            
            // Get all readers for this message
            $readers = $message->readBy()->orderBy('message_reads.read_at', 'desc')->get();
            $allReaders = $readers->map(function($user) {
                try {
                    $avatar = \Laravolt\Avatar\Facade::create($user->name ?? 'Unknown')->toBase64();
                    // Ensure avatar is not empty
                    if (empty($avatar)) {
                        $avatar = $this->generateFallbackAvatar($user->name ?? 'Unknown');
                    }
                } catch (\Exception $e) {
                    $avatar = $this->generateFallbackAvatar($user->name ?? 'Unknown');
                }
                return [
                    'user_id' => $user->id,
                    'user_name' => $user->name ?? 'Unknown',
                    'avatar' => $avatar,
                ];
            })->toArray();
            
            // Broadcast read status update with all readers
            broadcast(new MessageRead([$messageId], $message->emergency_report_id, $currentUserId, $readerName, [$messageId => $allReaders]));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Message marked as read',
        ]);
    }

    public function unreadCount()
    {
        // Get unread messages for reports the user has access to
        $user = Auth::user();
        
        // Get all reports the user has access to (same logic as recentMessages)
        if ($user->isAdmin()) {
            $reportIds = EmergencyReport::pluck('id');
        } elseif ($user->isResponder()) {
            $reportIds = EmergencyReport::whereHas('responders', function($q) use ($user) {
                $q->where('responder_id', $user->id);
            })->pluck('id');
        } else {
            $reportIds = EmergencyReport::where('user_id', $user->id)->pluck('id');
        }

        $unreadCount = Message::whereIn('emergency_report_id', $reportIds)
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('readBy', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();

        return response()->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
        ]);
    }

    public function recentMessages()
    {
        // Get recent UNREAD messages for dropdown
        $user = Auth::user();
        
        // Get all reports the user has access to
        if ($user->isAdmin()) {
            $reportIds = EmergencyReport::pluck('id');
        } elseif ($user->isResponder()) {
            $reportIds = EmergencyReport::whereHas('responders', function($q) use ($user) {
                $q->where('responder_id', $user->id);
            })->pluck('id');
        } else {
            $reportIds = EmergencyReport::where('user_id', $user->id)->pluck('id');
        }
        
        // Get all UNREAD messages (not sent by current user) from accessible reports, grouped by report
        $allUnreadMessages = Message::whereIn('emergency_report_id', $reportIds)
            ->where('user_id', '!=', $user->id) // Not sent by current user
            ->whereDoesntHave('readBy', function($q) use ($user) {
                $q->where('user_id', $user->id);
            }) // Only unread messages (not read by current user)
            ->with(['user', 'emergencyReport.emergencyType'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Group messages by report_id
        $groupedByReport = $allUnreadMessages->groupBy('emergency_report_id');
        
        // Format grouped reports with latest message and count
        $formattedReports = $groupedByReport->map(function($messages, $reportId) use ($user) {
            $latestMessage = $messages->first(); // Most recent message
            $report = $latestMessage->emergencyReport;
            
            return [
                'report_id' => (int) $reportId,
                'report_type' => $report->emergencyType->name ?? 'Unknown',
                'unread_count' => $messages->count(),
                'latest_message' => [
                    'id' => $latestMessage->id,
                    'message' => Str::limit($latestMessage->message, 5),
                    'sender' => $latestMessage->user->name,
                    'sender_id' => $latestMessage->user_id,
                    'created_at' => $latestMessage->created_at->diffForHumans(),
                    'created_at_full' => $latestMessage->created_at->format('M d, Y g:i A'),
                    'created_at_timestamp' => $latestMessage->created_at->timestamp,
                ],
            ];
        })->values()->sortByDesc(function($report) {
            return $report['latest_message']['created_at_timestamp'];
        })->values()->take(10); // Limit to 10 reports
        
        // Get total unread count
        $unreadCount = Message::whereIn('emergency_report_id', $reportIds)
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('readBy', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();
        
        return response()->json([
            'status' => 'success',
            'reports' => $formattedReports,
            'unread_count' => $unreadCount,
        ]);
    }
    
    public function getAccessibleReportIds()
    {
        // Get all report IDs the user has access to for Pusher subscription
        $user = Auth::user();
        
        if ($user->isAdmin()) {
            $reportIds = EmergencyReport::pluck('id');
        } elseif ($user->isResponder()) {
            $reportIds = EmergencyReport::whereHas('responders', function($q) use ($user) {
                $q->where('responder_id', $user->id);
            })->pluck('id');
        } else {
            $reportIds = EmergencyReport::where('user_id', $user->id)->pluck('id');
        }
        
        return response()->json([
            'status' => 'success',
            'report_ids' => $reportIds->toArray(),
        ]);
    }
    
    /**
     * Generate a fallback avatar as SVG data URI
     */
    private function generateFallbackAvatar($name)
    {
        $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2'];
        $colorIndex = ord(substr($name, 0, 1)) % count($colors);
        $bgColor = $colors[$colorIndex];
        $initials = strtoupper(substr($name, 0, 2));
        
        $svg = '<svg width="40" height="40" xmlns="http://www.w3.org/2000/svg"><circle cx="20" cy="20" r="20" fill="' . $bgColor . '"/><text x="20" y="20" font-family="Arial" font-size="14" fill="white" text-anchor="middle" dominant-baseline="central" font-weight="bold">' . htmlspecialchars($initials) . '</text></svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

