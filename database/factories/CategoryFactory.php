<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'title_translations' => [
                'en' => 'Category',
                'ar' => 'فئة',
            ],
            'description_translations' => [
                'en' => 'Category description',
                'ar' => 'وصف الفئة',
            ],
            'parent_id' => null,
            'order_index' => 1,
            'is_active' => true,
        ];
    }
}
