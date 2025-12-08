<?php

declare(strict_types=1);

namespace App\Services\Sections\Contracts;

use App\Models\Section;

interface SectionWorkflowServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $videos
     * @param  array<int, int>  $pdfs
     */
    public function createWithStructure(array $data, array $videos = [], array $pdfs = []): Section;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $videos
     * @param  array<int, int>  $pdfs
     */
    public function updateWithStructure(Section $section, array $data, array $videos = [], array $pdfs = []): Section;

    public function deleteWithStructure(Section $section): void;

    public function publish(Section $section): Section;

    public function unpublish(Section $section): Section;
}
