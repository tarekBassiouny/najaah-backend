<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $assignment_group_id
 * @property int $user_id
 * @property int $role
 * @property \Carbon\Carbon|null $joined_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read AssignmentGroup $group
 * @property-read User $user
 */
class AssignmentGroupMember extends Model
{
    /** @use HasFactory<\Database\Factories\AssignmentGroupMemberFactory> */
    use HasFactory;

    public const ROLE_MEMBER = 0;

    public const ROLE_LEADER = 1;

    /** @var list<string> */
    protected $fillable = [
        'assignment_group_id',
        'user_id',
        'role',
        'joined_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'role' => 'integer',
        'joined_at' => 'datetime',
    ];

    /** @return BelongsTo<AssignmentGroup, self> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(AssignmentGroup::class, 'assignment_group_id');
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isLeader(): bool
    {
        return $this->role === self::ROLE_LEADER;
    }

    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    public function getRoleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_LEADER => 'Leader',
            default => 'Member',
        };
    }
}
