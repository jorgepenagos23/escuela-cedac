<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LogUsabilityMetrics
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Solo evaluamos si hay usuario activo, es GET y no es de APIs o carga de assets
        if (auth()->check() && $request->isMethod('GET')) {
            $path = ltrim($request->path(), '/');

            // Ignorar polls ocultos, APIs, o rutas superfluas
            $ignored = ['mi-panel/data', 'api/v1', 'sanctum', '_debugbar', 'public', 'build', 'up'];
            
            $shouldLog = true;
            foreach ($ignored as $ig) {
                if (str_starts_with($path, $ig)) {
                    $shouldLog = false;
                    break;
                }
            }

            // Ignoramos si es un request interno puramente AJAX que NO sea una navegación de Inertia
            // (Inertia manda JSON pero con header X-Inertia)
            if ($request->ajax() && !$request->hasHeader('X-Inertia')) {
                $shouldLog = false;
            }

            if ($shouldLog && !empty($path)) {
                // Extraer un "Nombre Limpio" para el reporte
                $modulo = strtoupper(str_replace(['-', '_', '/'], ' ', collect(explode('/', $path))->first() ?? $path));

                activity('Usabilidad')
                    ->causedBy(auth()->user())
                    ->withProperties(['url' => '/' . $path])
                    ->log("Consultó el Módulo: " . $modulo);
            }
        }

        return $response;
    }
}
