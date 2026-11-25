<?php

namespace App\Http\Middleware;

use App\Support\MemoryLimit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RaiseMemoryLimitForApiDocs
{
    public function handle(Request $request, Closure $next): Response
    {
        MemoryLimit::raiseTo((string) config('api.docs_memory_limit', '512M'));

        return $next($request);
    }
}
