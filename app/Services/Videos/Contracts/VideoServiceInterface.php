<?php

declare(strict_types=1);

namespace App\Services\Videos\Contracts;

use App\Models\Center;
use App\Models\User;
use App\Models\Video;

interface VideoServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Center $center, User $admin, array $data): Video;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Video $video, User $admin, array $data): Video;

    public function delete(Video $video, User $admin): void;
}
