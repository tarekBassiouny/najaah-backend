<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCenter extends Pivot
{
    use SoftDeletes;

    protected $table = 'user_centers';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'center_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'center_id' => 'integer',
    ];

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
