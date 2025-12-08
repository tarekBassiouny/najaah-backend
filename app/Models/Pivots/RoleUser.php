<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleUser extends Pivot
{
    use SoftDeletes;

    protected $table = 'role_user';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'role_id',
        'user_id',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'user_id' => 'integer',
    ];

    /** @return BelongsTo<Role, self> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
