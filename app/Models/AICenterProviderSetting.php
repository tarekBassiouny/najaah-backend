<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $center_id
 * @property string $provider_key
 * @property bool $is_enabled
 * @property array<int, string>|null $allowed_models
 * @property string|null $default_model
 * @property array<string, int>|null $limits
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Center $center
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
class AICenterProviderSetting extends Model
{
    /** @use HasFactory<\Database\Factories\AICenterProviderSettingFactory> */
    use HasFactory;

    protected $table = 'ai_center_provider_settings';

    /** @var list<string> */
    protected $fillable = [
        'center_id',
        'provider_key',
        'is_enabled',
        'allowed_models',
        'default_model',
        'limits',
        'created_by',
        'updated_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_enabled' => 'boolean',
        'allowed_models' => 'array',
        'limits' => 'array',
    ];

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, self> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
