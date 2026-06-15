<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class EstadisticasController extends Controller
{
    /**
     * Devuelve el panel de métricas con el recuento de uso.
     */
    public function index()
    {
        // 1. Usuarios recientemente activos (últimas 24 hrs)
        $usuariosActivos = User::select('users.id', 'users.name', 'users.email', DB::raw('MAX(activity_log.created_at) as last_seen'))
            ->join('activity_log', function ($join) {
                $join->on('users.id', '=', 'activity_log.causer_id')
                     ->where('activity_log.causer_type', User::class);
            })
            ->where('activity_log.created_at', '>=', Carbon::now()->subDays(3))
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('last_seen', 'desc')
            ->get();

        // 2. Módulos más utilizados (Productividad) en la última semana
        $modulosTop = Activity::select(
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(properties, "$.url")) as path'),
                DB::raw('COUNT(*) as total_visitas')
            )
            ->where('log_name', 'Usabilidad')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('path')
            ->orderBy('total_visitas', 'desc')
            ->limit(10)
            ->get();

        // 3. Log histórico detallado (Últimos 1000 eventos para Reportes de RH)
        $historial = Activity::with('causer')
            ->where('log_name', 'Usabilidad')
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get()
            ->map(function ($act) {
                return [
                    'id' => $act->id,
                    'usuario' => $act->causer ? $act->causer->name : 'Desconocido',
                    'accion' => $act->description,
                    'url' => $act->getExtraProperty('url'),
                    'fecha' => $act->created_at->format('d/m/Y h:i A'),
                    'hace' => $act->created_at->diffForHumans()
                ];
            });

        return Inertia::render('Estadisticas', [
            'usuariosActivos' => $usuariosActivos,
            'modulosTop' => $modulosTop,
            'historial' => $historial
        ]);
    }
}
