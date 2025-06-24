<?php

namespace App\Http\Controllers;

use App\Services\DialogflowIntentService;
use Illuminate\Http\Request;

class DialogflowController extends Controller
{
    protected DialogflowIntentService $dialogflowIntentService;

    public function __construct(DialogflowIntentService $dialogflowIntentService)
    {
        $this->dialogflowIntentService = $dialogflowIntentService;
    }

    /**
     * @OA\Post(
     *     path="/api/dialogflow/detect-intent",
     *     summary="Detect intent from a user message",
     *     tags={"Dialogflow"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", example="Are there any rooms available tomorrow?")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *      @OA\Response(
     *         response=500,
     *         description="Invalid response from Dialogflow"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function detectIntent(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $result = $this->dialogflowIntentService->processUserMessage($request->input('message'));

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error'],
                'response' => $result['response']
            ], $result['status_code']);
        }

        return response()->json([
            'intent' => $result['intent'],
            'parameters' => $result['parameters'],
            'reply' => $result['reply'],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/dialogflow/webhook",
     *     summary="Dialogflow webhook",
     *     tags={"Dialogflow"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dialogflow webhook payload",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="queryResult", type="object",
     *                 @OA\Property(property="intent", type="object",
     *                     @OA\Property(property="displayName", type="string", example="CheckAvailability")
     *                 ),
     *                 @OA\Property(property="parameters", type="object"),
     *                 @OA\Property(property="fulfillmentText", type="string", example="Looking for availability...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     )
     * )
     */
    public function detect(Request $request)
    {
        $queryResult = $request->json('queryResult');
        $result = $this->dialogflowIntentService->processWebhookRequest($queryResult);

        return response()->json(['fulfillmentText' => $result['fulfillmentText']]);
    }
}
