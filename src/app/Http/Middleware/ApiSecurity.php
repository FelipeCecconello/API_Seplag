<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ApiToken;
use Carbon\Carbon;

class ApiSecurity
{
    public function handle(Request $request, Closure $next)
    {
        $allowedDomains = array_merge(
            explode(',', env('API_ALLOWED_DOMAINS', '')),
            ['http://localhost', 'http://127.0.0.1', 'http://localhost:8000', 'http://127.0.0.1:8000']
        );
        $origin = $request->header('Origin') ?? $request->header('Referer');
        
        if ($origin && !in_array(parse_url($origin, PHP_URL_HOST), array_map('parse_url', $allowedDomains))) {
            return response()->json(['error' => 'Acesso não autorizado a partir deste domínio'], 403);
        }

        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'Token de acesso não fornecido'], 401);
        }

        $apiToken = ApiToken::where('token', hash('sha256', $token))
                          ->where('expires_at', '>', Carbon::now())
                          ->first();

        if (!$apiToken) {
            return response()->json(['error' => 'Token inválido ou expirado'], 401);
        }

        Auth::login($apiToken->user);

        if ($request->is('api/auth/renew')) {
            $apiToken->update([
                'expires_at' => Carbon::now()->addMinutes(5)
            ]);
        }

        return $next($request);
    }
}