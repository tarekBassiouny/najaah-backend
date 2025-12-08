<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    public function __construct(
        private readonly OtpServiceInterface $otpService
    ) {}

    public function send(SendOtpRequest $request): JsonResponse
    {
        /** @var array{phone:string,country_code:string} $data */
        $data = $request->validated();

        $otpResult = $this->otpService->send($data['phone'], $data['country_code']);

        return response()->json($otpResult);
    }
}
