<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Restringe a rota a um papel específico (espelha o RoleFilter do psweb). */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== $role) {
            abort(403, 'Acesso restrito.');
        }

        return $next($request);
    }
}
