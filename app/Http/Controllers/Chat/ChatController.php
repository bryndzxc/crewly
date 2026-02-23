<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $count = ConversationParticipant::query()
            ->join('conversations', 'conversations.id', '=', 'conversation_participants.conversation_id')
            ->where('conversation_participants.user_id', $user->id)
            ->whereNotNull('conversations.last_message_at')
            ->where(function ($q) {
                $q->whereNull('conversation_participants.last_read_at')
                    ->orWhereColumn('conversation_participants.last_read_at', '<', 'conversations.last_message_at');
            })
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $conversations = $this->listConversationsFor($user);
        $selectedId = (int) $request->input('conversation_id', 0);
        $selected = $selectedId > 0 ? Conversation::query()->find($selectedId) : null;
        if ($selected && !$user->can('view', $selected)) {
            $selected = null;
        }

        if (!$selected && count($conversations) > 0) {
            $selected = Conversation::query()->find((int) $conversations[0]['id']);
        }

        $selectedPayload = $selected ? $this->conversationPayload($user, $selected) : null;

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'selectedConversation' => $selectedPayload ? $selectedPayload['conversation'] : null,
            'messages' => $selectedPayload ? $selectedPayload['messages'] : [],
            'hasMore' => $selectedPayload ? $selectedPayload['has_more'] : false,
            'dmUsers' => $this->dmUserListFor($user),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $conversation = Conversation::query()->findOrFail($id);
        $this->authorize('view', $conversation);

        $beforeId = $request->integer('before_id');
        $payload = $this->conversationPayload($user, $conversation, $beforeId > 0 ? $beforeId : null);

        return response()->json($payload);
    }

    public function createOrOpenDm(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $other = User::query()->findOrFail((int) $data['user_id']);
        if ((int) $other->id === (int) $user->id) {
            throw ValidationException::withMessages(['user_id' => 'You cannot message yourself.']);
        }

        if (!$this->canStartDm($user, $other)) {
            abort(403);
        }

        $existing = Conversation::query()
            ->dms()
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $other->id))
            ->withCount('participants')
            ->having('participants_count', '=', 2)
            ->first();

        if ($existing) {
            return response()->json(['conversation_id' => $existing->id]);
        }

        $conversation = Conversation::query()->create([
            'type' => Conversation::TYPE_DM,
            'created_by' => $user->id,
        ]);

        ConversationParticipant::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'role_in_conversation' => 'OWNER',
        ]);

        ConversationParticipant::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $other->id,
            'role_in_conversation' => 'MEMBER',
        ]);

        app(AuditLogger::class)->log(
            'chat.dm.created',
            $conversation,
            [],
            ['type' => 'DM'],
            ['other_user_id' => $other->id, 'other_role' => $other->role()]
        );

        return response()->json(['conversation_id' => $conversation->id]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $conversation = Conversation::query()->findOrFail($id);
        $this->authorize('view', $conversation);

        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->forceFill(['last_read_at' => now()])->save();
        }

        app(AuditLogger::class)->log(
            'chat.conversation.read',
            $conversation,
            [],
            ['conversation_id' => $conversation->id],
            ['type' => $conversation->type, 'slug' => $conversation->slug]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listConversationsFor(User $user): array
    {
        $channels = Conversation::query()
            ->channels()
            ->whereIn('slug', $this->channelSlugsFor($user))
            ->get();

        $dms = Conversation::query()
            ->dms()
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with(['users:id,name,role'])
            ->orderByRaw('last_message_at is null')
            ->orderByDesc('last_message_at')
            ->get();

        $all = $channels->concat($dms);

        $participantRows = ConversationParticipant::query()
            ->where('user_id', $user->id)
            ->whereIn('conversation_id', $all->pluck('id')->all())
            ->get()
            ->keyBy('conversation_id');

        $items = [];
        foreach ($all as $conversation) {
            $p = $participantRows->get($conversation->id);
            $unread = false;
            if ($conversation->last_message_at) {
                $lastRead = $p?->last_read_at;
                $unread = !$lastRead || $lastRead->lt($conversation->last_message_at);
            }

            $items[] = [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'slug' => $conversation->slug,
                'name' => $this->displayNameFor($user, $conversation),
                'last_message_at' => $conversation->last_message_at?->toISOString(),
                'unread' => $unread,
            ];
        }

        usort($items, function ($a, $b) {
            // Channels first, then DMs by last_message_at
            if ($a['type'] !== $b['type']) {
                return $a['type'] === Conversation::TYPE_CHANNEL ? -1 : 1;
            }
            return strcmp((string) ($b['last_message_at'] ?? ''), (string) ($a['last_message_at'] ?? ''));
        });

        return $items;
    }

    /**
     * @return array<string,mixed>
     */
    private function conversationPayload(User $user, Conversation $conversation, ?int $beforeId = null): array
    {
        $conversation->loadMissing(['users:id,name,role']);

        $query = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with(['sender:id,name'])
            ->orderByDesc('id');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->limit(30)->get();
        $hasMore = $messages->count() === 30;
        $messages = $messages->reverse()->values();

        return [
            'conversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'slug' => $conversation->slug,
                'name' => $this->displayNameFor($user, $conversation),
                'participants' => $conversation->users->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role' => $u->role(),
                ])->values(),
            ],
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'conversation_id' => $m->conversation_id,
                'body' => $m->body,
                'type' => $m->type,
                'sender' => [
                    'id' => $m->sender?->id,
                    'name' => $m->sender?->name,
                ],
                'created_at' => $m->created_at?->toISOString(),
            ])->values(),
            'has_more' => $hasMore,
        ];
    }

    private function displayNameFor(User $user, Conversation $conversation): string
    {
        if ($conversation->type === Conversation::TYPE_CHANNEL) {
            return $conversation->name ?: ('#' . (string) $conversation->slug);
        }

        $other = $conversation->users->firstWhere('id', '!=', $user->id);
        return $other?->name ?: 'Direct Message';
    }

    /**
     * @return array<int,string>
     */
    private function channelSlugsFor(User $user): array
    {
        $slugs = ['announcements'];
        if ($user->isAdmin() || $user->isHR()) {
            $slugs[] = 'hr-team';
        }
        return $slugs;
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function dmUserListFor(User $user): array
    {
        $query = User::query()->select(['id', 'name', 'role'])->orderBy('name');

        if ($user->isEmployee()) {
            $query->whereIn('role', [User::ROLE_HR, User::ROLE_MANAGER]);
        } else {
            $query->whereIn('role', [User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER, User::ROLE_EMPLOYEE]);
        }

        $query->where('id', '!=', $user->id);

        return $query->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'role' => $u->role(),
        ])->values()->all();
    }

    private function canStartDm(User $actor, User $other): bool
    {
        if ($actor->isEmployee()) {
            return in_array($other->role(), [User::ROLE_HR, User::ROLE_MANAGER], true);
        }

        if (in_array($actor->role(), [User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER], true)) {
            if (in_array($other->role(), [User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER], true)) return true;
            return $other->role() === User::ROLE_EMPLOYEE;
        }

        return false;
    }
}
