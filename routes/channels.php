<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::query()->find($conversationId);
    if (!$conversation) return false;

    if (!$user->can('view', $conversation)) return false;

    return [
        'id' => (int) $user->id,
        'name' => (string) ($user->name ?? ''),
        'role' => (string) ($user->role ?? ''),
    ];
});
