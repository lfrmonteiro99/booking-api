<?php

namespace App\Http\Controllers;

use App\Interfaces\AvailabilityServiceInterface;
use App\Interfaces\DialogflowIntentHandlerInterface;
use App\Services\AvailabilityService;
use App\Services\DialogflowService;
use App\IntentHandlers\CheckAvailabilityIntentHandler;
use Illuminate\Http\Request;
use Illuminate\Contracts\Container\Container;

class DialogflowController extends Controller
{
    protected DialogflowService $dialogflow;
    protected AvailabilityServiceInterface $availabilityService;
    protected Container $container;
    protected array $intentHandlers;

    public function __construct(DialogflowService $dialogflow, AvailabilityServiceInterface $availabilityService, Container $container)
    {
        $this->dialogflow = $dialogflow;
        $this->availabilityService = $availabilityService;
        $this->container = $container;
        $this->intentHandlers = [
            'CheckAvailability' => CheckAvailabilityIntentHandler::class,
        ];
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

        $userMessage = $request->input('message');
        $dialogflowResponse = $this->dialogflow->detectIntent($userMessage);

        if (!$dialogflowResponse) {
            return response()->json([
                'error' => 'Invalid response from Dialogflow',
                'response' => $dialogflowResponse
            ], 500);
        }
        
        $availabilityResponse = $this->availabilityService->checkAvailability($dialogflowResponse);

        $naturalLanguageReply = $this->availabilityService->formatAvailabilityResponse($availabilityResponse);

        return response()->json([
            'intent'     => $dialogflowResponse['queryResult']['intent']['displayName'] ?? null,
            'parameters' => $dialogflowResponse['queryResult']['parameters'] ?? [],
            'reply'      => $naturalLanguageReply,
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
        $body = $request->json('queryResult');

        $intent = $body['intent']['displayName'];
        $params = $body['parameters'];

        $replyText = $body['fulfillmentText'] ?? "Sorry, I didn't get that.";

        if (isset($this->intentHandlers[$intent])) {
            $handlerClass = $this->intentHandlers[$intent];
            /** @var \App\Interfaces\DialogflowIntentHandlerInterface $handler */
            $handler = $this->container->make($handlerClass);
            $replyText = $handler->handle($params);
        }

        return response()->json(['fulfillmentText' => $replyText]);
    }
}
