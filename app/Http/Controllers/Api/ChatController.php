<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChatController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Get messages for a complaint.
     */
    public function index(Request $request, Complaint $complaint): JsonResponse
    {
        Gate::authorize('view', $complaint);
        
        $this->chatService->markAsRead($complaint->id, $request->user()->id);
        
        return response()->json([
            'data' => $this->chatService->getMessages($complaint->id)
        ]);
    }

    /**
     * Send a message.
     */
    public function store(Request $request, Complaint $complaint): JsonResponse
    {
        Gate::authorize('view', $complaint);

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message = $this->chatService->sendMessage(
            $complaint->id, 
            $request->message
        );

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message
        ], 201);
    }
}
