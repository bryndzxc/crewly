<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\Pagination;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\URL;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use Pagination;
    use SoftDeletes;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_HR = 'hr';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_EMPLOYEE = 'employee';

    protected array $searchable_fields = [
        'name',
        'email',
        'role',
    ];

    public function role(): string
    {
        return $this->getAttribute('role') ?? self::ROLE_ADMIN;
    }

    public function hasRole(string $role): bool
    {
        return $this->role() === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isHR(): bool
    {
        return $this->hasRole(self::ROLE_HR);
    }

    public function isManager(): bool
    {
        return $this->hasRole(self::ROLE_MANAGER);
    }

    public function isEmployee(): bool
    {
        return $this->hasRole(self::ROLE_EMPLOYEE);
    }

    public function isDeveloper(): bool
    {
        if (!config('app.developer_bypass', false)) {
            return false;
        }

        $email = strtolower(trim((string) $this->getAttribute('email')));
        $allowed = (array) config('app.developer_emails', []);

        return $email !== '' && in_array($email, $allowed, true);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'must_change_password',
        'chat_sound_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'must_change_password' => 'boolean',
        'chat_sound_enabled' => 'boolean',
    ];

    public function getProfilePhotoUrlAttribute(): ?string
    {
        $path = (string) ($this->getAttribute('profile_photo_path') ?? '');
        if (trim($path) === '') {
            return null;
        }

        return URL::route('profile.photo.show');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'id');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['role_in_conversation', 'last_read_at'])
            ->withTimestamps();
    }

    public function conversationParticipants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
