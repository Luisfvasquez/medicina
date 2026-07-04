<?php

use App\Exceptions\ApiException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\AuthenticateFromCookieOrBearer::class,
        ]);

        $middleware->alias([
            'kyc.approved' => \App\Http\Middleware\EnsureKycIsApproved::class,
            'user.status'  => \App\Http\Middleware\CheckUserStatus::class,
            'patient.status' => \App\Http\Middleware\CheckPatientStatus::class,
            'idempotent'  => \App\Http\Middleware\EnsureIdempotency::class,
            'deprecated' => \App\Http\Middleware\DeprecatedRoute::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // RFC 7807 — Custom API exceptions
        $exceptions->render(function (ApiException $e) {
            return $e->render();
        });

        // AuthenticationException → 401 RFC 7807
        $exceptions->render(function (AuthenticationException $e) {
            return response()->json([
                'type'     => 'https://api.pharmako.com/errors/unauthorized',
                'title'    => 'No Autenticado',
                'status'   => 401,
                'detail'   => 'Debe iniciar sesión para acceder a este recurso.',
                'instance' => '/' . request()->path(),
            ], 401);
        });

        // ValidationException → 422 RFC 7807 con invalidParams
        $exceptions->render(function (ValidationException $e) {
            $params = collect($e->errors())->map(fn ($msgs, $field) => [
                'name'   => $field,
                'reason' => $msgs[0],
            ])->values()->all();

            return response()->json([
                'type'          => 'https://api.pharmako.com/errors/validation',
                'title'         => 'Error de Validación',
                'status'        => 422,
                'detail'        => 'Uno o más campos enviados no cumplen con las reglas requeridas.',
                'instance'      => '/' . request()->path(),
                'invalidParams' => $params,
                'errors'        => $e->errors(),
            ], 422);
        });

        // ModelNotFoundException → 404
        $exceptions->render(function (ModelNotFoundException $e) {
            return response()->json([
                'type'     => 'https://api.pharmako.com/errors/not-found',
                'title'    => 'Recurso No Encontrado',
                'status'   => 404,
                'detail'   => 'El recurso solicitado no existe.',
                'instance' => '/' . request()->path(),
            ], 404);
        });

        // AccessDeniedHttpException → 403
        $exceptions->render(function (AccessDeniedHttpException $e) {
            return response()->json([
                'type'     => 'https://api.pharmako.com/errors/forbidden',
                'title'    => 'Acceso Denegado',
                'status'   => 403,
                'detail'   => 'No tiene permisos para acceder a este recurso.',
                'instance' => '/' . request()->path(),
            ], 403);
        });

        // NotFoundHttpException → 404
        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json([
                'type'     => 'https://api.pharmako.com/errors/not-found',
                'title'    => 'Endpoint No Encontrado',
                'status'   => 404,
                'detail'   => 'El endpoint solicitado no existe.',
                'instance' => '/' . request()->path(),
            ], 404);
        });
    })->create();
