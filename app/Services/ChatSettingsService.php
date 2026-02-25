<?php

namespace App\Services;

use App\DTO\ChatSoundPreferenceData;
use App\Models\User;

class ChatSettingsService extends Service
{
    /**
     * @return array{ok:bool,chat_sound_enabled:bool}
     */
    public function updateSound(User $user, ChatSoundPreferenceData $dto): array
    {
        $user->forceFill([
            'chat_sound_enabled' => (bool) $dto->enabled,
        ])->save();

        return [
            'ok' => true,
            'chat_sound_enabled' => (bool) $user->chat_sound_enabled,
        ];
    }
}
