<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $request_id
 * @property string|null $instance
 * @property string|null $event
 * @property string $status
 * @property int|null $response_code
 * @property string|null $error_message
 * @property array<string, mixed>|null $headers
 * @property array<string, mixed> $payload
 */
class EvolutionWebhookLog extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;

    protected $fillable = [
        'request_id',
        'instance',
        'event',
        'status',
        'response_code',
        'error_message',
        'headers',
        'payload',
    ];

    protected $casts = [
        'response_code' => 'integer',
        'headers' => 'array',
        'payload' => 'array',
    ];
}
