<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

/**
 * @property int $id
 * @property int|null $center_id
 * @property string $name
 * @property string|null $username
 * @property string $phone
 * @property string|null $country_code
 * @property string|null $email
 * @property string $password
 * @property int $status
 * @property bool $is_student
 * @property string|null $avatar_url
 * @property \Carbon\Carbon|null $last_login_at
 * @property-read Center|null $center
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Center> $centers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $roles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserDevice> $devices
 * @property-read \Illuminate\Database\Eloquent\Collection<int, JwtToken> $tokens
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Enrollment> $enrollments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlaybackSession> $playbackSessions
 * @property-read StudentSetting|null $studentSetting
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AuditLog> $auditLogs
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\UserFactory>
 */
class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'name',
        'username',
        'phone',
        'country_code',
        'email',
        'password',
        'status',
        'is_student',
        'avatar_url',
        'last_login_at',
    ];

    protected $casts = [
        'status' => 'integer',
        'is_student' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsToMany<Center, self> */
    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'user_centers')
            ->using(\App\Models\Pivots\UserCenter::class)
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /** @return BelongsToMany<Role, self> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->using(\App\Models\Pivots\RoleUser::class)
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /** @return HasMany<UserDevice, self> */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /** @return HasMany<JwtToken, self> */
    public function jwtTokens(): HasMany
    {
        return $this->hasMany(JwtToken::class);
    }

    /** @return HasMany<Enrollment, self> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @return HasMany<PlaybackSession, self> */
    public function playbackSessions(): HasMany
    {
        return $this->hasMany(PlaybackSession::class);
    }

    /** @return HasMany<AuditLog, self> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /** @return HasOne<StudentSetting, self> */
    public function studentSetting(): HasOne
    {
        return $this->hasOne(StudentSetting::class);
    }

    /** Automatically hash password */
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function getJWTIdentifier(): int
    {
        /** @var int $id */
        $id = $this->getKey();

        return $id;
    }

    /** @return array<string, mixed> */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
