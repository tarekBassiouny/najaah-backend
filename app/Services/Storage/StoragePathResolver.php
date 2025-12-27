<?php

declare(strict_types=1);

namespace App\Services\Storage;

class StoragePathResolver
{
    public function centerLogo(int $centerId, string $filename): string
    {
        return $this->centerScoped($centerId, 'branding/logo/'.$filename);
    }

    public function defaultCenterLogo(): string
    {
        return 'centers/defaults/logo.png';
    }

    public function userAvatar(int $centerId, int $userId, string $filename): string
    {
        return $this->centerScoped($centerId, sprintf('users/%d/avatar/%s', $userId, $filename));
    }

    public function instructorAvatar(int $centerId, string $filename): string
    {
        return $this->centerScoped($centerId, 'instructors/avatars/'.$filename);
    }

    public function courseThumbnail(int $centerId, int $courseId, string $filename): string
    {
        return $this->centerScoped($centerId, sprintf('courses/%d/thumbnail/%s', $courseId, $filename));
    }

    public function pdf(int $centerId, string $filename): string
    {
        return $this->centerScoped($centerId, 'pdfs/'.$filename);
    }

    public function export(int $centerId, string $filename): string
    {
        return $this->centerScoped($centerId, 'exports/'.$filename);
    }

    private function centerScoped(int $centerId, string $path): string
    {
        $prefix = trim((string) config('storage.root_prefix', ''), '/');
        $base = sprintf('centers/%d/', $centerId).ltrim($path, '/');

        if ($prefix === '') {
            return $base;
        }

        return $prefix.'/'.$base;
    }
}
