<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $siteName = Setting::valueFor('site_name', 'Kai Properties');
        $logoPath = Setting::valueFor('logo_path');
        $logoUrl = $logoPath ? asset('storage/'.$logoPath) : asset('favicon.ico');
        $extension = strtolower(pathinfo((string) $logoPath, PATHINFO_EXTENSION));
        $iconType = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            default => 'image/png',
        };

        $manifest = [
            'name' => $siteName.' Maintenance',
            'short_name' => $siteName,
            'description' => 'Kai Properties maintenance and operations platform.',
            'start_url' => route('login'),
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#eef3fa',
            'theme_color' => '#0c1f3f',
            'icons' => [
                [
                    'src' => $logoUrl,
                    'sizes' => '512x512',
                    'type' => $iconType,
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        return response()
            ->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }
}
