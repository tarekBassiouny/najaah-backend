<?php

declare(strict_types=1);

namespace Tests\Helpers;

class JsonStructure
{
    /** @return array<int|string, string|array<int, string|array<mixed>>> */
    public static function success(): array
    {
        return [
            'success',
            'message',
            'data',
        ];
    }

    /** @return array<int|string, string|array<int, string>> */
    public static function error(): array
    {
        return [
            'success',
            'error' => ['code', 'message'],
        ];
    }

    /** @return array<int|string, string|array<int, string|array<mixed>>> */
    public static function course(): array
    {
        return [
            'success',
            'message',
            'data' => [
                'id',
                'title',
                'description',
                'difficulty',
                'language',
                'price',
                'status',
                'created_at',
                'updated_at',
                'center',
                'category',
                'instructors',
                'sections',
                'videos',
                'pdfs',
                'settings',
            ],
        ];
    }

    /** @return array<int|string, string|array<int, string|array<mixed>>> */
    public static function publicCourse(): array
    {
        return [
            'success',
            'message',
            'data' => [
                'id',
                'title',
                'description',
                'difficulty',
                'language',
                'thumbnail',
                'published_at',
                'sections',
                'videos_count',
                'pdfs_count',
            ],
        ];
    }

    /** @return array<int, string> */
    public static function section(): array
    {
        return [
            'id',
            'title',
            'description',
            'order_index',
            'visible',
        ];
    }

    /** @return array<int, string> */
    public static function video(): array
    {
        return [
            'id',
            'title',
            'duration',
            'type',
        ];
    }

    /** @return array<int|string, string|array<int, string>> */
    public static function pagination(): array
    {
        return [
            'meta' => [
                'page',
                'per_page',
                'total',
            ],
        ];
    }
}
