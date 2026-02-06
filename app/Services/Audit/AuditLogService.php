<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(?User $actor, Model $entity, string $action, array $metadata = []): void
    {
        $resolved = $this->resolveEntityMetadata($entity);
        $payload = array_merge($resolved, $metadata);

        AuditLog::create([
            'user_id' => $actor?->id,
            'center_id' => $this->resolveId($payload['center_id'] ?? null),
            'course_id' => $this->resolveId($payload['course_id'] ?? null),
            'action' => $action,
            'entity_type' => $entity::class,
            'entity_id' => (int) $entity->getKey(),
            'metadata' => $payload,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logByType(?User $actor, string $entityType, int $entityId, string $action, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'center_id' => $this->resolveId($metadata['center_id'] ?? null),
            'course_id' => $this->resolveId($metadata['course_id'] ?? null),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveEntityMetadata(Model $entity): array
    {
        $keys = ['center_id', 'course_id', 'section_id', 'user_id', 'video_id', 'pdf_id'];
        $metadata = [];

        foreach ($keys as $key) {
            $value = $entity->getAttribute($key);
            if ($value !== null && $value !== '') {
                $metadata[$key] = $value;
            }
        }

        return $metadata;
    }

    private function resolveId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_numeric($value)) {
            $resolved = (int) $value;

            return $resolved > 0 ? $resolved : null;
        }

        return null;
    }
}
