<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeRequest;
use App\Models\Advertisement;
use App\Notifications\EmailConfirmationNotification;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Post(
 *     path="/subscribe",
 *     summary="Subscribe to an advertisement",
 *     description="Subscribe to an OLX advertisement to receive price change notifications",
 *     operationId="subscribe",
 *     tags={"Subscriptions"},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"url", "email"},
 *
 *             @OA\Property(property="url", type="string", format="url", example="https://www.olx.pl/d/oferta/test-ad-ID123abc.html", description="OLX advertisement URL"),
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="Email address for notifications")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Subscription created successfully",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Subscription created. Please check your email to verify.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="url",
 *                     type="array",
 *
 *                     @OA\Items(type="string", example="The url must be a valid OLX advertisement URL.")
 *                 ),
 *
 *                 @OA\Property(
 *                     property="email",
 *                     type="array",
 *
 *                     @OA\Items(type="string", example="The email must be a valid email address.")
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="An error occurred while processing your subscription. Please try again later.")
 *         )
 *     )
 * )
 */
class SubscribeController extends Controller
{
    public function __invoke(SubscribeRequest $request)
    {
        try {
            // Create or find the advertisement
            $advertisement = Advertisement::createOrFirst(['url' => $request->validated('url')]);

            // Find existing subscription or create a new one
            $subscription = $advertisement->subscribers()
                ->firstOrNew([
                    'email' => $request->validated('email'),
                ]);

            $isNewSubscription = ! $subscription->exists;
            $needsVerification = $isNewSubscription || $subscription->isTokenExpired();

            // Generate a new verification token if needed
            if ($needsVerification) {
                $subscription->generateVerificationToken();
                $subscription->save();

                // Send verification email
                $subscription->notify(new EmailConfirmationNotification);

                Log::info('Subscription created or renewed', [
                    'email'            => $subscription->email,
                    'advertisement_id' => $advertisement->id,
                    'is_new'           => $isNewSubscription,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription created. Please check your email to verify.',
                ]);
            }

            // Subscription already exists and is verified
            return response()->json([
                'success' => true,
                'message' => 'You are already subscribed to this advertisement.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error creating subscription: '.$e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your subscription. Please try again later.',
            ], 500);
        }
    }
}
