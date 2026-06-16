<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige aceite da política de privacidade (LGPD) antes de usar o sistema.
 * Se consent_policy === 'N', redireciona para a página de consentimento.
 */
class EnsureUserConsent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->consent_policy === 'N') {
            return redirect()->route('consent');
        }

        return $next($request);
    }
}
