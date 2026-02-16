<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdminNotificationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $center_id
 * @property AdminNotificationType $type
 * @property string $title
 * @property string|null $body
 * @property array<string, mixed>|null $data
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read Center|null $center
 */
/**
 * @use HasFactory<\Database\Factories\AdminNotificationFactory>
 */
class AdminNotification extends Model
{
    /** @use HasFactory<\Database\Factories\AdminNotificationFactory> */
    use HasFactory;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'center_id',
        'type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'type' => AdminNotificationType::class,
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, AdminNotification>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Center, AdminNotification>
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeForUser(Builder $query, User $user, ?int $centerId = null): Builder
    {
        return $query->where(function (Builder $q) use ($user, $centerId): void {
            $q->where('user_id', $user->id)
                ->orWhere(function (Builder $subQ) use ($centerId): void {
                    $subQ->whereNull('user_id');
                    if ($centerId !== null) {
                        $subQ->where(function (Builder $centerQ) use ($centerId): void {
                            $centerQ->where('center_id', $centerId)
                                ->orWhereNull('center_id');
                        });
                    }
                });
        });
    }

    /**
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where(function (Builder $q) use ($centerId): void {
            $q->where('center_id', $centerId)
                ->orWhereNull('center_id');
        });
    }

    /**
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeOfType(Builder $query, AdminNotificationType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeSince(Builder $query, int $timestamp): Builder
    {
        return $query->where('created_at', '>', date('Y-m-d H:i:s', $timestamp));
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }
}
