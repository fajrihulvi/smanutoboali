<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Actions\HandleGatewayCallbackAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /webhooks/midtrans — tanpa CSRF/auth; keaslian dijamin signature_key.
 */
class WebhookController extends Controller
{
    public function midtrans(Request $request, HandleGatewayCallbackAction $handle): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all() ?: $request->all();
        $result = $handle->handle($payload);

        $status = match ($result) {
            'invalid_signature' => 403,
            'unknown_order' => 404,
            'amount_mismatch' => 422,
            default => 200,
        };

        return response()->json(['result' => $result], $status);
    }
}
