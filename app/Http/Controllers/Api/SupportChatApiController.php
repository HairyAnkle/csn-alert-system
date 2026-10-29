<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChildProfile;
use App\Models\SupportMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportChatApiController extends Controller
{
    public const WELCOME_TEXT = 'Mabuhay! Welcome to Parañaque CSN Center Live Support (Col. E. de Leon St., Brgy. Sto. Niño). How can we assist you and your child today?';

    /**
     * List all support chat messages for the authenticated guardian.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $messages = SupportMessage::where('guardian_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($messages->isEmpty()) {
            $welcome = SupportMessage::create([
                'guardian_id' => $user->id,
                'child_id' => $user->child?->id,
                'sender' => 'support',
                'sender_name' => 'Parañaque CSN Desk',
                'text' => self::WELCOME_TEXT,
                'is_read' => true,
            ]);

            $messages = collect([$welcome]);
        } else {
            SupportMessage::where('guardian_id', $user->id)
                ->where('sender', 'support')
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return response()->json([
            'data' => $messages->map(fn (SupportMessage $msg) => [
                'id' => $msg->id,
                'sender' => $msg->sender,
                'sender_name' => $msg->sender_name,
                'text' => $msg->text,
                'created_at' => $msg->created_at?->toISOString() ?? now()->toISOString(),
            ])->values(),
        ]);
    }

    /**
     * Store a new support message from the authenticated guardian.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:message'],
            'message' => ['nullable', 'string', 'max:5000', 'required_without:body'],
            'child_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $rawText = trim((string) ($request->input('body') ?? $request->input('message') ?? ''));

        if ($rawText === '') {
            return response()->json([
                'message' => 'The message text cannot be empty.',
                'errors' => ['body' => ['The message text cannot be empty.']],
            ], 422);
        }

        $childId = $request->input('child_id');
        if ($childId && ! ChildProfile::where('id', $childId)->exists()) {
            $childId = null;
        }
        $childId = $childId ?? $user->child?->id;

        // Check if this is the guardian's first user message in the last 15 minutes
        // or if no live coordinator has replied yet
        $recentUserMessagesCount = SupportMessage::where('guardian_id', $user->id)
            ->where('sender', 'user')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        $hasLiveAdminReply = SupportMessage::where('guardian_id', $user->id)
            ->where('sender', 'support')
            ->whereNotNull('admin_id')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->exists();

        $userMessage = SupportMessage::create([
            'guardian_id' => $user->id,
            'child_id' => $childId,
            'sender' => 'user',
            'sender_name' => $user->name,
            'text' => $rawText,
            'is_read' => false,
        ]);

        // Smart automated replies for suggested chat chips or FAQ topics
        $lower = strtolower($rawText);
        $childName = $user->child?->name ?? 'your child';
        $smartReplyText = null;

        if (
            str_contains($lower, 'schedule & address') ||
            str_contains($lower, 'schedule and address') ||
            (str_contains($lower, 'schedule') && str_contains($lower, 'address'))
        ) {
            $smartReplyText = 'CSN Center Parañaque is located at Col. E. de Leon Street, Barangay Sto. Niño, Parañaque City (partnered with CSWDD/CSWDO & PDAO). Office hours are Monday–Friday, 8:00 AM – 5:00 PM. Would you like us to reserve a consultation slot?';
        } elseif ($rawText === 'Request a Callback' || str_contains($lower, 'request a callback')) {
            $smartReplyText = "We have logged a priority callback request for {$childName}'s caregiver. A CSN Social Welfare Coordinator at (02) 8820-7783 will call your registered number shortly.";
        } elseif ($rawText === 'Sensory Overload Advice' || str_contains($lower, 'sensory overload advice')) {
            $smartReplyText = "For {$childName}'s sensory or medical needs: please move to a quiet, low-stimulation space. If you need responder assistance without loud sirens, trigger a Low or High Urgency \"Seizure / Sensory Overload\" alert from the Home tab so our team approaches calmly.";
        } elseif ($rawText === 'Update Child Medical Info' || str_contains($lower, 'update child medical info')) {
            $smartReplyText = "You can update {$childName}'s Disability Classification, allergies, medications, and communication tags anytime in the \"Medical Info\" tab. Changes sync immediately to the CSN Admin Dashboard.";
        }

        $autoReply = null;
        if ($smartReplyText !== null) {
            $autoReply = SupportMessage::create([
                'guardian_id' => $user->id,
                'child_id' => $childId,
                'sender' => 'support',
                'sender_name' => 'Parañaque CSN Desk',
                'text' => $smartReplyText,
                'is_read' => true,
            ]);
        } elseif ($recentUserMessagesCount === 0 && ! $hasLiveAdminReply) {
            $autoReply = SupportMessage::create([
                'guardian_id' => $user->id,
                'child_id' => $childId,
                'sender' => 'support',
                'sender_name' => 'Parañaque CSN Desk',
                'text' => 'Thank you for messaging Parañaque CSN Center Live Support. A Social Welfare Coordinator has received your message and will reply shortly. If this is an active medical or elopement crisis, please press the SOS Emergency Button immediately.',
                'is_read' => true,
            ]);
        }

        return response()->json([
            'message' => 'Message sent.',
            'data' => [
                'id' => $userMessage->id,
                'sender' => $userMessage->sender,
                'sender_name' => $userMessage->sender_name,
                'text' => $userMessage->text,
                'created_at' => $userMessage->created_at?->toISOString() ?? now()->toISOString(),
            ],
            'reply' => $autoReply ? [
                'id' => $autoReply->id,
                'sender' => $autoReply->sender,
                'sender_name' => $autoReply->sender_name,
                'text' => $autoReply->text,
                'created_at' => $autoReply->created_at?->toISOString() ?? now()->toISOString(),
            ] : null,
        ], 201);
    }
}
