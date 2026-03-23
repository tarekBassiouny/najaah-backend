<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ParentLinkMethod;
use App\Enums\ParentLinkStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $parent_user_id
 * @property int $student_user_id
 * @property int $center_id
 * @property ParentLinkStatus $status
 * @property int|null $linked_by
 * @property ParentLinkMethod $link_method
 * @property \Carbon\Carbon $linked_at
 * @property-read User $parent
 * @property-read User $student
 * @property-read Center $center
 * @property-read User|null $linkedByUser
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ParentStudentLinkFactory>
 */
class ParentStudentLink extends Model
{
    /** @use HasFactory<\Database\Factories\ParentStudentLinkFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'parent_user_id',
        'student_user_id',
        'center_id',
        'status',
        'linked_by',
        'link_method',
        'linked_at',
    ];

    protected $casts = [
        'status' => ParentLinkStatus::class,
        'link_method' => ParentLinkMethod::class,
        'linked_at' => 'datetime',
    ];

    /** @return BelongsTo<User, self> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    /** @return BelongsTo<User, self> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<User, self> */
    public function linkedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ParentLinkStatus::Active);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForParent(Builder $query, int $parentUserId): Builder
    {
        return $query->where('parent_user_id', $parentUserId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForStudent(Builder $query, int $studentUserId): Builder
    {
        return $query->where('student_user_id', $studentUserId);
    }
}
