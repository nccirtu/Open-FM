<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    private const ACTIVE_CONTENT_TYPES = [
        'text/html',
        'application/xhtml+xml',
        'image/svg+xml',
        'text/xml',
        'application/xml',
    ];

    private const ACTIVE_EXTENSIONS = ['html', 'htm', 'xhtml', 'xht', 'shtml', 'svg', 'svgz', 'xml', 'xsl'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        if ($this->servesStoredFile($request) && $this->isActiveContent($request, $response)) {
            $this->forceDownload($response);
        }

        return $response;
    }

    private function servesStoredFile(Request $request): bool
    {
        return str_starts_with((string) $request->route()?->getName(), 'storage.');
    }

    private function isActiveContent(Request $request, Response $response): bool
    {
        $type = strtolower(trim(explode(';', (string) $response->headers->get('Content-Type'))[0]));
        $extension = strtolower(pathinfo((string) $request->route('path'), PATHINFO_EXTENSION));

        return in_array($type, self::ACTIVE_CONTENT_TYPES, true)
            || in_array($extension, self::ACTIVE_EXTENSIONS, true);
    }

    private function forceDownload(Response $response): void
    {
        $disposition = trim((string) $response->headers->get('Content-Disposition'));

        $response->headers->set(
            'Content-Disposition',
            $disposition === '' ? 'attachment' : (string) preg_replace('/^inline/i', 'attachment', $disposition),
        );
    }
}
