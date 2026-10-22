<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

class CronController extends Controller
{
    /** Called every minute by an external pinger; runs whatever Laravel's scheduler says is due. */
    public function __invoke(string $token): Response
    {
        $secret = (string) config('talenthub.cron_token');

        abort_unless($secret !== '' && hash_equals($secret, $token), 404);

        Artisan::call('schedule:run');

        return response('ok', 200)->header('Cache-Control', 'no-store');
    }
}