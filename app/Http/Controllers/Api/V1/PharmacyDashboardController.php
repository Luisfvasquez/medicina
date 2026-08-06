<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PharmacyOrder;
use App\Models\PharmacyInventory;
use App\Models\Notification;
use Illuminate\Http\Request;

class PharmacyDashboardController extends Controller
{
    public function summary(Request $request)
    {
        // El usuario autenticado es un provider (farmacia)
        $user = $request->user();
        
        // Asumiendo que el usuario tiene un providerProfile asociado
        // o si es a nivel de provider_id, se puede extraer del perfil
        $providerId = $user->providerProfile->id ?? null;
        
        if (!$providerId) {
            return response()->json(['message' => 'No provider profile found'], 403);
        }

        // KPIs
        $pendingOrdersCount = PharmacyOrder::where('provider_id', $providerId)
            ->whereIn('status', ['pending', 'en-preparacion', 'pendiente'])
            ->count();
            
        $completedOrdersToday = PharmacyOrder::where('provider_id', $providerId)
            ->whereIn('status', ['dispensed', 'listo'])
            ->whereDate('confirmed_at', now()->toDateString())
            ->count();
            
        $activeStockAlertsCount = PharmacyInventory::where('provider_id', $providerId)
            ->whereRaw('stock <= min_stock_alert')
            ->count();
            
        // Calcular tiempo promedio de espera (created_at a confirmed_at) de las últimas 30 órdenes
        $recentConfirmedOrders = PharmacyOrder::where('provider_id', $providerId)
            ->whereNotNull('confirmed_at')
            ->orderBy('confirmed_at', 'desc')
            ->take(30)
            ->get(['created_at', 'confirmed_at']);

        if ($recentConfirmedOrders->isNotEmpty()) {
            $totalMinutes = $recentConfirmedOrders->sum(function ($order) {
                // Si por alguna razón created_at es null, evitamos errores
                if (!$order->created_at || !$order->confirmed_at) return 0;
                return $order->created_at->diffInMinutes($order->confirmed_at);
            });
            $avg = round($totalMinutes / $recentConfirmedOrders->count());
            $averageWaitTime = $avg . " min";
        } else {
            $averageWaitTime = "--";
        }

        $kpis = [
            [
                'label' => 'Órdenes pendientes',
                'value' => $pendingOrdersCount,
                'trend' => 0,
                'trendDirection' => 'stable',
                'subtitle' => 'hoy',
                'icon' => 'Package', // El icono se inyecta en el front
            ],
            [
                'label' => 'Órdenes completadas hoy',
                'value' => $completedOrdersToday,
                'trend' => 0,
                'trendDirection' => 'up',
                'subtitle' => 'hoy',
                'icon' => 'CheckCircle',
            ],
            [
                'label' => 'Tiempo promedio de espera',
                'value' => $averageWaitTime,
                'trend' => 0,
                'trendDirection' => 'stable',
                'subtitle' => 'por orden',
                'icon' => 'Clock',
            ],
            [
                'label' => 'Alertas de stock activas',
                'value' => $activeStockAlertsCount,
                'trend' => 0,
                'trendDirection' => 'stable',
                'subtitle' => 'requieren atención',
                'icon' => 'AlertTriangle',
            ]
        ];

        // Últimas órdenes activas
        $recentOrders = PharmacyOrder::with(['patientAccount.patients', 'items'])
            ->where('provider_id', $providerId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($order) {
                // Adaptando al formato de UI esperado
                $patientName = $order->patientAccount->patients->first()->name ?? 'Paciente Anónimo';
                $prescriptionItem = $order->items->first();
                $prescription = $prescriptionItem ? $prescriptionItem->product_name : 'Sin productos';
                
                return [
                    'id' => $order->id,
                    'patientName' => $patientName,
                    'prescription' => $prescription,
                    'status' => $order->status,
                    'fulfillmentType' => 'delivery', // Asumido o sacado de la orden real
                    'time' => $order->created_at->diffForHumans(),
                ];
            });

        // Notificaciones no leídas para la farmacia
        $recentNotifications = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'type' => $notif->type === 'STOCK_ALERT' ? 'stock-alert' : 'prescription-error',
                    'title' => $notif->title,
                    'message' => $notif->message,
                    'actionText' => 'Ver detalle',
                    'actionHref' => $notif->link ?? '#',
                ];
            });

        return response()->json([
            'kpis' => $kpis,
            'orders' => $recentOrders,
            'notifications' => $recentNotifications
        ]);
    }
}
