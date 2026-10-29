<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportChatController extends Controller
{
    /**
     * Authorize that the current user can manage support chats on the Web Dashboard.
     */
    protected function authorizeSupportAccess(?User $user): void
    {
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $role = strtolower($user->roles->first()?->name ?? $user->role ?? '');

        if (! in_array($role, ['superadmin', 'admin'], true) && ! $user->hasAnyRole(['superadmin', 'admin'])) {
            abort(403, 'Unauthorized to access the CSN Support Chat Desk.');
        }
    }

    /**
     * Build the full list of guardian support threads for the Admin Web Dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function buildThreadsForDashboard(): array
    {
        $guardians = User::with(['children'])
            ->where(function ($q) {
                $q->where('role', 'guardian')
                    ->orWhereHas('roles', fn ($rq) => $rq->where('name', 'guardian'))
                    ->orWhereIn('id', SupportMessage::select('guardian_id')->distinct());
            })
            ->get();

        $allMessages = SupportMessage::orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('guardian_id');

        $threads = $guardians->map(function (User $guardian) use ($allMessages) {
            $msgs = $allMessages->get($guardian->id, collect());
            $lastMsg = $msgs->last();
            $primaryChild = $guardian->children->first();
            $unreadCount = $msgs->where('sender', 'user')->where('is_read', false)->count();

            return [
                'guardian_id' => $guardian->id,
                'guardian_name' => $guardian->name,
                'guardian_email' => $guardian->email,
                'guardian_phone' => $guardian->phone ?? '—',
                'avatar_initials' => strtoupper(collect(explode(' ', trim($guardian->name)))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') ?: 'GD'),
                'child_id' => $primaryChild?->id,
                'child_name' => $primaryChild?->name ?? 'No linked child',
                'child_condition' => $primaryChild?->condition ?? '—',
                'unread_count' => $unreadCount,
                'last_message_text' => $lastMsg?->text ?? 'No messages yet — Click to start conversation',
                'last_message_sender' => $lastMsg?->sender ?? null,
                'last_message_at' => $lastMsg?->created_at?->toISOString() ?? null,
                'last_message_time' => $lastMsg?->created_at ? $lastMsg->created_at->diffForHumans(null, true, true) : '',
                'has_messages' => $msgs->isNotEmpty(),
                'messages' => $msgs->map->toApiArray()->values()->all(),
            ];
        })
            ->sortByDesc(function (array $t) {
                return ($t['unread_count'] > 0 ? '1_' : '0_').($t['last_message_at'] ?? '0000-00-00');
            })
            ->values()
            ->all();

        return $threads;
    }

    /**
     * Get all guardian support chat threads for the Web Dashboard.
     */
    public function threads(Request $request): JsonResponse
    {
        $this->authorizeSupportAccess($request->user());

        return response()->json([
            'threads' => self::buildThreadsForDashboard(),
        ]);
    }

    /**
     * Get all messages for a specific guardian thread and mark guardian messages as read.
     */
    public function messages(Request $request, User $guardian): JsonResponse
    {
        $this->authorizeSupportAccess($request->user());

        SupportMessage::where('guardian_id', $guardian->id)
            ->where('sender', 'user')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = SupportMessage::where('guardian_id', $guardian->id)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map->toApiArray()
            ->values();

        return response()->json([
            'guardian_id' => $guardian->id,
            'messages' => $messages,
        ]);
    }

    /**
     * Send a reply from the CSN Coordinator / Admin to a guardian's thread.
     */
    public function reply(Request $request, User $guardian): JsonResponse
    {
        $this->authorizeSupportAccess($request->user());

        $validated = $request->validate([
            'text' => ['nullable', 'string', 'max:5000', 'required_without:body'],
            'body' => ['nullable', 'string', 'max:5000', 'required_without:text'],
            'child_id' => ['nullable', 'integer'],
        ]);

        $rawText = trim((string) ($validated['text'] ?? $validated['body'] ?? ''));
        if ($rawText === '') {
            return response()->json([
                'message' => 'Reply message cannot be empty.',
            ], 422);
        }

        // Mark all incoming messages from this guardian as read
        SupportMessage::where('guardian_id', $guardian->id)
            ->where('sender', 'user')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $admin = $request->user();
        $childId = $validated['child_id'] ?? $guardian->child?->id;

        $reply = SupportMessage::create([
            'guardian_id' => $guardian->id,
            'child_id' => $childId,
            'admin_id' => $admin->id,
            'sender' => 'support',
            'sender_name' => 'Parañaque CSN Desk ('.$admin->name.')',
            'text' => $rawText,
            'is_read' => false,
        ]);

        return response()->json([
            'message' => 'Reply sent to guardian.',
            'data' => $reply->toApiArray(),
        ], 201);
    }
}
