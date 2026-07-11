<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\RegistrationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivateAccountController extends Controller
{
    /**
     * Handle the incoming account validation request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $registrationCode = RegistrationCode::where('code', strtoupper(trim($request->code)))
            ->where('is_used', false)
            ->first();

        if (! $registrationCode) {
            return response()->json([
                'message' => 'The registration code provided is invalid or has already been redeemed.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        if ($user->activated_at) {
            return response()->json([
                'message' => 'Your account profile is already activated.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->update([
            'unit_id' => $registrationCode->unit_id,
            'activated_at' => now(),
        ]);

        $registrationCode->update([
            'is_used' => true,
        ]);

        return response()->json([
            'message' => 'Account successfully activated and linked to your unit!',
            'user' => [
                'is_activated' => true,
                'activated_at' => $user->activated_at->toIso8601String(),
                'unit_id' => $user->unit_id,
            ],
        ], Response::HTTP_OK);
    }
}
