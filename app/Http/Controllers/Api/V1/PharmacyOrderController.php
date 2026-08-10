<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PharmacyOrder;
use App\Services\PharmacyOrderService;
use Illuminate\Http\Request;

class PharmacyOrderController extends Controller
{
    protected PharmacyOrderService $orderService;

    public function __construct(PharmacyOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function confirmOrder(Request $request, $id)
    {
        $order = $this->orderService->confirmOrder($id);

        \App\Models\AuditLog::logUpdate(
            $request->user(),
            'PharmacyOrder',
            $order->id,
            [], // Usually old data would go here, but this is an action
            $order->toArray()
        );

        return response()->json([
            'message' => 'Orden de compra confirmada y stock descontado exitosamente.',
            'data' => $order
        ]);
    }
}
