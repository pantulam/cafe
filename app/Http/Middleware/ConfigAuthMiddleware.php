<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if basic auth is enabled
        if (!config('app.basic_auth_enabled', false)) {
            return $next($request);
        }

        $username = $request->getUser();
        $password = $request->getPassword();

        $validUsername = config('app.basic_auth_username');
        $validPassword = config('app.basic_auth_password');

        if ($username !== $validUsername || $password !== $validPassword) {
            return response('Unauthorized', 401, ['WWW-Authenticate' => 'Basic']);
        }

        return $next($request);
    }
}
