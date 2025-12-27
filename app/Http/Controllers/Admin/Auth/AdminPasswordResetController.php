<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminPasswordResetRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class AdminPasswordResetController extends Controller
{
    public function reset(AdminPasswordResetRequest $request): JsonResponse
    {
        /** @var array{token:string,email:string,password:string} $data */
        $data = $request->validated();
        /** @var User|null $user */
        $user = User::where('email', $data['email'])->first();

        if (! $user instanceof User || $user->is_student) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PASSWORD_RESET_FAILED',
                    'message' => 'Unable to reset password.',
                ],
            ], 422);
        }

        $status = Password::broker()->reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password'],
                'token' => $data['token'],
            ],
            function (User $user, string $password): void {
                $user->password = $password;
                $user->force_password_reset = false;
                $user->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PASSWORD_RESET_FAILED',
                    'message' => 'Unable to reset password.',
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Password reset successfully.',
            ],
        ]);
    }
}
