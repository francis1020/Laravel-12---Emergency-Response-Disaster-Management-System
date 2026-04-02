<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportCommentController extends Controller
{
    public function store(Request $request, $report_id)
    {
        $report = EmergencyReport::findOrFail($report_id);
        
        // Check if user has access to this report
        $hasAccess = $report->user_id === Auth::id() 
            || Auth::user()->isAdmin()
            || $report->responders()->where('responder_id', Auth::id())->exists();

        if (!$hasAccess) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        // Store comment as a message with type 'text'
        $message = Message::create([
            'emergency_report_id' => $report_id,
            'user_id' => Auth::id(),
            'message' => $validated['comment'],
            'type' => 'text',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Comment added successfully',
            'comment' => $message->load('user'),
        ]);
    }

    public function getComments($report_id)
    {
        $report = EmergencyReport::findOrFail($report_id);
        
        // Check if user has access to this report
        $hasAccess = $report->user_id === Auth::id() 
            || Auth::user()->isAdmin()
            || $report->responders()->where('responder_id', Auth::id())->exists();

        if (!$hasAccess) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        // Get comments (messages of type 'text')
        $comments = Message::where('emergency_report_id', $report_id)
            ->where('type', 'text')
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'comments' => $comments,
        ]);
    }
}

