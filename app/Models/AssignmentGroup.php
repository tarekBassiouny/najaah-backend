<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $assignment_id
 * @property string $name
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Assignment $assignment
 * @property-read User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssignmentGroupMember> $members
 * @property-read AssignmentSubmission|null $submission
 */
class AssignmentGroup extends Model
{
    /** @use HasFactory<\Database\Factories\AssignmentGroupFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'assignment_id',
        'name',
        'created_by',
    ];

    /** @return BelongsTo<Assignment, self> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<AssignmentGroupMember, self> */
    public function members(): HasMany
    {
        return $this->hasMany(AssignmentGroupMember::class);
    }

    /** @return HasOne<AssignmentSubmission, self> */
    public function submission(): HasOne
    {
        return $this->hasOne(AssignmentSubmission::class, 'group_id');
    }

    public function getMemberCount(): int
    {
        return $this->members()->count();
    }

    public function isFull(): bool
    {
        $maxSize = $this->assignment->max_group_size;

        if ($maxSize === null) {
            return false;
        }

        return $this->getMemberCount() >= $maxSize;
    }

    public function hasMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function getLeader(): ?AssignmentGroupMember
    {
        return $this->members()->where('role', 1)->first();
    }
}
