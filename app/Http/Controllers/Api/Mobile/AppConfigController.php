<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'app' => [
                'name'         => config('app.name'),
                'version'      => '1.0.0',
                'base_url'     => 'https://portal.kaipropertiesgh.com',
                'api_base_url' => 'https://portal.kaipropertiesgh.com/api/mobile/v1',
            ],
            'ticket_statuses'    => \App\Models\Ticket::STATUSES,
            'ticket_priorities'  => \App\Models\Ticket::PRIORITIES,
            'cost_currencies'    => \App\Models\Ticket::ESTIMATED_COST_CURRENCIES,
            'currency_symbols'   => \App\Models\Ticket::ESTIMATED_COST_CURRENCY_SYMBOLS,
        ]);
    }
}
