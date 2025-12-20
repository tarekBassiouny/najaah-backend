<?php

declare(strict_types=1);

namespace App\Services\Sections\Contracts;

use App\Models\Section;
use App\Models\User;

interface SectionWorkflowServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $videos
     * @param  array<int, int>  $pdfs
     */
    public function createWithStructure(User $actor, array $data, array $videos = [], array $pdfs = []): Section;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $videos
     * @param  array<int, int>  $pdfs
     */
    public function updateWithStructure(User $actor, Section $section, array $data, array $videos = [], array $pdfs = []): Section;

    public function deleteWithStructure(User $actor, Section $section): void;

    public function publish(User $actor, Section $section): Section;

    public function unpublish(User $actor, Section $section): Section;
}
