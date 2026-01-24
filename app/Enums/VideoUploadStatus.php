<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoUploadStatus: int
{
    case Pending = 0;
    case Uploading = 1;
    case Processing = 2;
    case Ready = 3;
    case Failed = 4;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Uploading => 'Uploading',
            self::Processing => 'Processing',
            self::Ready => 'Ready',
            self::Failed => 'Failed',
        };
    }

    public static function fromLabel(string $label): self
    {
        return match (strtoupper($label)) {
            'PENDING' => self::Pending,
            'UPLOADING' => self::Uploading,
            'PROCESSING' => self::Processing,
            'READY' => self::Ready,
            'FAILED' => self::Failed,
            default => throw new \ValueError('Unknown status label: '.$label),
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Uploading, self::Processing, self::Ready, self::Failed],
            self::Uploading => [self::Processing, self::Ready, self::Failed],
            self::Processing => [self::Ready, self::Failed],
            self::Ready => [],
            self::Failed => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        if ($this === $next) {
            return true;
        }

        return in_array($next, $this->allowedTransitions(), true);
    }
}
