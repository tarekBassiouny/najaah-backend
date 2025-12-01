<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property array<string, string> $name_translations
 * @property string $slug
 * @property array<string,string>|null $description_translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 */
class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'name_translations',
        'slug',
        'description_translations',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'description_translations' => 'array',
    ];

    /** @return BelongsToMany<User, self> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
