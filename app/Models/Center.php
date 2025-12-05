<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $slug
 * @property int $type
 * @property array<string, string> $name_translations
 * @property array<string, string>|null $description_translations
 * @property string|null $logo_url
 * @property string|null $primary_color
 * @property int $default_view_limit
 * @property bool $allow_extra_view_requests
 * @property bool $pdf_download_permission
 * @property int $device_limit
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Course> $courses
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Enrollment> $enrollments
 * @property-read CenterSetting|null $setting
 * @property-read \Illuminate\Database\Eloquent\Collection<int, VideoUploadSession> $videoUploadSessions
 */
class Center extends Model
{
    /** @use HasFactory<\Database\Factories\CenterFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'slug',
        'type',
        'name_translations',
        'description_translations',
        'logo_url',
        'primary_color',
        'default_view_limit',
        'allow_extra_view_requests',
        'pdf_download_permission',
        'device_limit',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'description_translations' => 'array',
        'allow_extra_view_requests' => 'boolean',
        'pdf_download_permission' => 'boolean',
        'default_view_limit' => 'integer',
        'device_limit' => 'integer',
    ];

    /** @return BelongsToMany<User, self> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_centers');
    }

    /** @return HasMany<Course, self> */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /** @return HasMany<Enrollment, self> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @return HasOne<CenterSetting, self> */
    public function setting(): HasOne
    {
        return $this->hasOne(CenterSetting::class);
    }

    /** @return HasMany<VideoUploadSession, self> */
    public function videoUploadSessions(): HasMany
    {
        return $this->hasMany(VideoUploadSession::class);
    }
}
