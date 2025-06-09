<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyEmailRequest;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Get(
 *     path="/verify-email",
 *     summary="Verify email address",
 *     description="Verify a subscription email address using the verification token",
 *     operationId="verifyEmail",
 *     tags={"Subscriptions"},
 *
 *     @OA\Parameter(
 *         name="token",
 *         in="query",
 *         required=true,
 *         description="Verification token sent to the email",
 *
 *         @OA\Schema(type="string", example="a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6")
 *     ),
 *
 *     @OA\Parameter(
 *         name="email",
 *         in="query",
 *         required=true,
 *         description="Email address to verify",
 *
 *         @OA\Schema(type="string", format="email", example="user@example.com")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Email verified successfully",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="message", type="string", example="Email verified successfully, your subscription has been confirmed.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Invalid or expired verification token",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="message", type="string", example="Oops, please check your verification link. It may have expired or already been used.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="message", type="string", example="Oops, something went wrong. Please try again later.")
 *         )
 *     )
 * )
 */
class VerifyEmailController extends Controller
{
    public function __invoke(VerifyEmailRequest $request)
    {
        try {
            $subscription = Subscription::whereVerificationToken($request->validated('token'))
                ->whereEmail($request->validated('email'))
                ->firstOrFail();

            $subscription->verify();

            return response()->json([
                'message' => 'Email verified successfully, your subscription has been confirmed.',
            ]);
        } catch (ModelNotFoundException $e) {
            Log::warning('Verification attempt failed: Token or email mismatch', [
                'email' => $request->validated('email'),
                'token' => $request->validated('token'),
            ]);

            return response()->json([
                'message' => 'Oops, please check your verification link. It may have expired or already been used.',
            ], 422);
        } catch (\Throwable $throwable) {
            Log::error('Verification error: '.$throwable->getMessage(), [
                'exception' => get_class($throwable),
                'file'      => $throwable->getFile(),
                'line'      => $throwable->getLine(),
            ]);

            return response()->json([
                'message' => 'Oops, something went wrong. Please try again later.',
            ], 500);
        }
    }
}
