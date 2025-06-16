<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeUserRequest;
use App\Interfaces\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    protected SubscriptionServiceInterface $subscriptionService;

    public function __construct(SubscriptionServiceInterface $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * @OA\Post(
     *     path="/api/subscribe",
     *     summary="Subscribe user to a plan",
     *     tags={"Subscription"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plan_name"},
     *             @OA\Property(property="plan_name", type="string", enum={"basic", "premium", "pro", "enterprise"}, example="premium"),
     *             @OA\Property(property="ends_at", type="string", format="date", example="2025-12-31", description="Required only for enterprise plan")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful subscription"
     *     ),
     *      @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function subscribe(SubscribeUserRequest $request): JsonResponse
    {
        $user = $request->user();
        $planName = $request->input('plan_name');
        $data = $request->validated(); // Get all validated data, including 'ends_at' for Enterprise

        $result = $this->subscriptionService->subscribeUserToPlan($user, $planName, $data);

        return response()->json($result);
    }
}
