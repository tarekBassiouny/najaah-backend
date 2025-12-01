<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendOtpRequest;
use App\Services\Contracts\OtpServiceInterface;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    public function __construct(
        private readonly OtpServiceInterface $otpService
    ) {
    }

    public function send(SendOtpRequest $request): JsonResponse
    {
        /** @var array{phone:string,country_code:string} $data */
        $data = $request->validated();

        $otpResult = $this->otpService->send($data['phone'], $data['country_code']);

        return response()->json($otpResult);
    }
}
