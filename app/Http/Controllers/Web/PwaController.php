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
        $iconUrl = asset('kaipwa.png').'?v=1';
        $iconType = 'image/png';

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
                    'src' => $iconUrl,
                    'sizes' => '192x192',
                    'type' => $iconType,
                    'purpose' => 'any',
                ],
                [
                    'src' => $iconUrl,
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
