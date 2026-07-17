<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role->value, $roles, true)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke resource ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
