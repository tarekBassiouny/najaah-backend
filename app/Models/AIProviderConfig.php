<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $provider_key
 * @property string $display_name
 * @property bool $is_enabled
 * @property string|null $default_model
 * @property array<int, string>|null $models
 * @property string|null $api_key
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
class AIProviderConfig extends Model
{
    /** @use HasFactory<\Database\Factories\AIProviderConfigFactory> */
    use HasFactory;

    protected $table = 'ai_provider_configs';

    /** @var list<string> */
    protected $fillable = [
        'provider_key',
        'display_name',
        'is_enabled',
        'default_model',
        'models',
        'api_key',
        'created_by',
        'updated_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_enabled' => 'boolean',
        'models' => 'array',
        'api_key' => 'encrypted',
    ];

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
