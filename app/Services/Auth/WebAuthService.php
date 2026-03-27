<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\ParentLinkMethod;
use App\Enums\ParentLinkStatus;
use App\Models\OtpCode;
use App\Models\ParentStudentLink;
use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use App\Services\Auth\Contracts\WebAuthServiceInterface;
use App\Services\Phone\PhoneNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class WebAuthService implements WebAuthServiceInterface
{
    public function __construct(
        private readonly OtpServiceInterface $otpService,
        private readonly PhoneNormalizer $phoneNormalizer
    ) {}

    public function sendOtp(string $phone, string $countryCode, ?int $centerId = null, bool $isParent = false): string
    {
        $token = $this->otpService->send($phone, $countryCode, $centerId);

        if (! $isParent) {
            return $token;
        }

        $parent = $this->resolveExistingUser($phone, $countryCode, $centerId);

        if ($parent instanceof User) {
            OtpCode::query()
                ->where('otp_token', $token)
                ->update(['user_id' => $parent->id]);
        }

        return $token;
    }

    public function verifyOtp(string $otp, string $token): ?OtpCode
    {
        return $this->otpService->verify($otp, $token);
    }

    /**
     * @return array{user: User, auto_linked: bool}
     */
    public function resolveOrRegisterParent(string $phone, string $countryCode, ?int $centerId): array
    {
        return DB::transaction(function () use ($phone, $countryCode, $centerId): array {
            $user = $this->resolveExistingUser($phone, $countryCode, $centerId);
            $autoLinked = false;

            if ($user !== null) {
                // Upgrade to parent if not already
                if (! $user->is_parent) {
                    $user->forceFill(['is_parent' => true])->save();
                }
            } else {
                $user = $this->createParentUser($phone, $countryCode, $centerId);
            }

            // Auto-link: find students in this center whose parent_phone matches
            if ($centerId !== null) {
                $autoLinked = $this->autoLinkByPhone($user, $phone, $centerId);
            }

            return ['user' => $user, 'auto_linked' => $autoLinked];
        });
    }

    private function resolveExistingUser(string $phone, string $countryCode, ?int $centerId): ?User
    {
        $normalizedPhone = $this->phoneNormalizer->normalize($phone, $countryCode);
        $query = User::query();

        $this->applyPhoneLookup($query, $normalizedPhone, $phone, $countryCode);

        if (is_numeric($centerId)) {
            $query->where('center_id', $centerId);
        } else {
            $query->whereNull('center_id');
        }

        /** @var User|null $user */
        $user = $query->first();

        return $user;
    }

    private function createParentUser(string $phone, string $countryCode, ?int $centerId): User
    {
        try {
            /** @var User $user */
            $user = User::create([
                'name' => 'Parent',
                'phone' => $phone,
                'country_code' => $countryCode,
                'center_id' => $centerId,
                'is_student' => false,
                'is_parent' => true,
                'status' => 1, // Active
                'password' => bin2hex(random_bytes(16)),
            ]);

            return $user;
        } catch (UniqueConstraintViolationException) {
            // Race condition — re-resolve
            $resolved = $this->resolveExistingUser($phone, $countryCode, $centerId);
            if ($resolved !== null) {
                if (! $resolved->is_parent) {
                    $resolved->forceFill(['is_parent' => true])->save();
                }

                return $resolved;
            }

            throw new \RuntimeException('Parent user could not be resolved after unique constraint violation.');
        }
    }

    private function autoLinkByPhone(User $parent, string $phone, int $centerId): bool
    {
        // Find students in this center whose parent_phone matches
        $students = User::query()
            ->where('center_id', $centerId)
            ->where('is_student', true)
            ->where(function (Builder $query) use ($parent, $phone): void {
                if ($parent->phone_normalized !== null) {
                    $query->where('parent_phone_normalized', $parent->phone_normalized);
                }

                $query->orWhere('parent_phone', $phone);
            })
            ->get();

        $linked = false;

        foreach ($students as $student) {
            $exists = ParentStudentLink::query()
                ->where('parent_user_id', $parent->id)
                ->where('student_user_id', $student->id)
                ->where('center_id', $centerId)
                ->exists();

            if (! $exists) {
                ParentStudentLink::create([
                    'parent_user_id' => $parent->id,
                    'student_user_id' => $student->id,
                    'center_id' => $centerId,
                    'status' => ParentLinkStatus::Active,
                    'link_method' => ParentLinkMethod::AutoMatched,
                    'linked_at' => now(),
                ]);
                $linked = true;
            }
        }

        return $linked;
    }

    /**
     * @param  Builder<User>  $query
     */
    private function applyPhoneLookup(Builder $query, ?string $normalizedPhone, string $phone, string $countryCode): void
    {
        $query->where(function (Builder $builder) use ($normalizedPhone, $phone, $countryCode): void {
            if ($normalizedPhone !== null) {
                $builder->where('phone_normalized', $normalizedPhone)
                    ->orWhereRaw(
                        "CONCAT('+', REPLACE(REPLACE(COALESCE(country_code, ''), '+', ''), '00', ''), COALESCE(phone, '')) = ?",
                        [$normalizedPhone]
                    );

                return;
            }

            $builder->where('phone', $phone)
                ->where('country_code', $countryCode);
        });
    }
}
