<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Enums\DeviceType;
use App\Enums\TokenPlatform;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

trait WebTestHelper
{
    private ?string $webBearerToken = null;

    private ?User $webUser = null;

    private ?Center $webCenter = null;

    /**
     * Authenticate as a web student and set up bearer headers.
     */
    public function asWebStudent(?User $user = null, ?Center $center = null): User
    {
        if ($user === null) {
            $center ??= $this->createWebEnabledCenter();

            /** @var User $user */
            $user = User::factory()->create([
                'is_student' => true,
                'is_parent' => false,
                'center_id' => $center->id,
                'status' => 1,
            ]);
        }

        $center ??= ($user->center_id ? Center::find($user->center_id) : null);
        $this->webCenter = $center;
        $this->webUser = $user;
        $this->webBearerToken = JWTAuth::fromUser($user);

        $deviceUuid = Str::uuid()->toString();
        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $deviceUuid,
            'device_name' => 'Web Browser',
            'device_type' => DeviceType::Web->value,
            'model' => 'Chrome',
            'os_version' => 'Windows 11',
            'status' => UserDevice::STATUS_ACTIVE,
            'approved_at' => now(),
            'last_used_at' => now(),
        ]);

        JwtToken::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'access_token' => $this->webBearerToken,
            'refresh_token' => Str::random(40),
            'platform' => TokenPlatform::Web,
            'expires_at' => now()->addHours(4),
            'refresh_expires_at' => now()->addDays(30),
        ]);

        $this->withHeaders($this->webHeaders());

        return $user;
    }

    /**
     * Authenticate as a web parent and set up bearer headers.
     */
    public function asWebParent(?User $user = null, ?Center $center = null): User
    {
        if ($user === null) {
            $center ??= $this->createParentPortalEnabledCenter();

            /** @var User $user */
            $user = User::factory()->create([
                'is_student' => false,
                'is_parent' => true,
                'center_id' => $center->id,
                'status' => 1,
            ]);
        }

        $center ??= ($user->center_id ? Center::find($user->center_id) : null);
        $this->webCenter = $center;
        $this->webUser = $user;
        $this->webBearerToken = JWTAuth::fromUser($user);

        $deviceUuid = Str::uuid()->toString();
        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $deviceUuid,
            'device_name' => 'Web Browser',
            'device_type' => DeviceType::Web->value,
            'model' => 'Chrome',
            'os_version' => 'Windows 11',
            'status' => UserDevice::STATUS_ACTIVE,
            'approved_at' => now(),
            'last_used_at' => now(),
        ]);

        JwtToken::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'access_token' => $this->webBearerToken,
            'refresh_token' => Str::random(40),
            'platform' => TokenPlatform::Web,
            'expires_at' => now()->addHours(4),
            'refresh_expires_at' => now()->addDays(30),
        ]);

        $this->withHeaders($this->webHeaders());

        return $user;
    }

    /**
     * Create a center with web access enabled.
     */
    public function createWebEnabledCenter(): Center
    {
        /** @var Center $center */
        $center = Center::factory()->create();

        CenterSetting::factory()->create([
            'center_id' => $center->id,
            'settings' => [
                'allow_web_access' => true,
                'allow_parent_portal' => false,
                'web_device_limit' => 3,
                'features' => [
                    'web_access' => true,
                    'web_playback' => true,
                    'parent_portal' => false,
                    'ai_content' => true,
                    'codes_access' => true,
                    'whatsapp_bulk' => true,
                    'guest_browsing' => true,
                    'pdf_downloads' => true,
                ],
            ],
        ]);

        return $center;
    }

    /**
     * Create a center with both web access and parent portal enabled.
     */
    public function createParentPortalEnabledCenter(): Center
    {
        /** @var Center $center */
        $center = Center::factory()->create();

        CenterSetting::factory()->create([
            'center_id' => $center->id,
            'settings' => [
                'allow_web_access' => true,
                'allow_parent_portal' => true,
                'web_device_limit' => 3,
                'features' => [
                    'web_access' => true,
                    'web_playback' => true,
                    'parent_portal' => true,
                    'ai_content' => true,
                    'codes_access' => true,
                    'whatsapp_bulk' => true,
                    'guest_browsing' => true,
                    'pdf_downloads' => true,
                ],
            ],
        ]);

        return $center;
    }

    /**
     * @return array<string, string>
     */
    private function webHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($this->webBearerToken !== null) {
            $headers['Authorization'] = 'Bearer '.$this->webBearerToken;
        }

        if ($this->webCenter instanceof Center) {
            if (! is_string($this->webCenter->api_key) || $this->webCenter->api_key === '') {
                $this->webCenter->api_key = 'center-key-'.$this->webCenter->id;
                $this->webCenter->save();
            }
            $headers['X-Api-Key'] = $this->webCenter->api_key;
        } else {
            $systemKey = (string) config('services.system_api_key', '');
            if ($systemKey === '') {
                $systemKey = 'system-test-key';
                config(['services.system_api_key' => $systemKey]);
            }
            $headers['X-Api-Key'] = $systemKey;
        }

        return $headers;
    }

    public function webGet(string $uri): TestResponse
    {
        return $this->getJson($uri);
    }

    public function webPost(string $uri, array $data = []): TestResponse
    {
        return $this->postJson($uri, $data);
    }
}
