<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $admin_notification_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property AdminNotification $notification
 * @property User $user
 */
/**
 * @uses HasFactory<\Database\Factories\AdminNotificationUserStateFactory>
 */
class AdminNotificationUserState extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'admin_notification_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<AdminNotification, AdminNotificationUserState>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(AdminNotification::class, 'admin_notification_id');
    }

    /**
     * @return BelongsTo<User, AdminNotificationUserState>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
