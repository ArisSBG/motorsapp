<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Visiteur non connecté qui ouvre un document : vers la page de connexion
        $middleware->redirectGuestsTo('/gestion/login');

        // En-têtes de sécurité sur toutes les pages
        $middleware->append(\App\Http\Middleware\EnTetesSecurite::class);

        // Proxys de confiance (Cloudflare) : réglés dans AppServiceProvider, une fois la configuration chargée.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
