<?php

namespace App\Http\Controllers;

use App\Models\LandingEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LandingEventController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'page' => ['required', 'string', 'max:80'],
            'cta_id' => ['required', 'string', 'max:120'],
            'ab_variant' => ['nullable', 'string', 'max:10'],
            'path' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:255'],
        ]);

        LandingEvent::query()->create([
            ...$validated,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->noContent();
    }
}
