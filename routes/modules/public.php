<?php

use App\Http\Controllers\Public\LeadController;
use App\Http\Controllers\Public\PublicPageController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', function () {
    $lastmod = now()->toAtomString();
    $urls = [
        url('/'),
        url('/demo'),
        url('/pricing'),
        url('/privacy'),
        url('/terms'),
    ];

    $escape = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');

    $items = array_map(
        static fn (string $loc) => "<url><loc>{$escape($loc)}</loc><lastmod>{$escape($lastmod)}</lastmod></url>",
        $urls
    );

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
        . implode('', $items)
        . '</urlset>';

    return response($xml, 200)
        ->header('Content-Type', 'application/xml; charset=UTF-8')
        ->header('Cache-Control', 'public, max-age=3600');
})
    ->withoutMiddleware([
        // Sitemaps should be stateless and cacheable.
        \App\Http\Middleware\EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \App\Http\Middleware\HandleInertiaRequests::class,
        \App\Http\Middleware\ForcePasswordChange::class,
        \App\Http\Middleware\EnsureDemoSafe::class,
    ])
    ->name('public.sitemap');

Route::get('/storage-images/{filename}', function (string $filename) {
    if (!preg_match('/\A[a-zA-Z0-9._-]+\z/', $filename)) {
        abort(404);
    }

    $path = storage_path('images'.DIRECTORY_SEPARATOR.$filename);
    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('filename', '[A-Za-z0-9._-]+')->name('public.storage-images.show');

Route::get('/', [PublicPageController::class, 'landing'])
    ->name('home');

Route::get('/pricing', [PublicPageController::class, 'pricing'])
    ->name('public.pricing');

Route::get('/demo', [PublicPageController::class, 'demo'])
    ->name('public.demo');

Route::get('/privacy', [PublicPageController::class, 'privacy'])
    ->name('public.privacy');

Route::get('/terms', [PublicPageController::class, 'terms'])
    ->name('public.terms');

Route::post('/leads', [LeadController::class, 'store'])
    ->middleware(['throttle:20,1'])
    ->name('public.leads.store');
