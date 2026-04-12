<?php

namespace App\Http\Controllers;

use App\Models\LandingEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LandingEventReportController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $from = Carbon::now()->subDays($days)->startOfDay();

        $events = LandingEvent::query()
            ->where('created_at', '>=', $from)
            ->orderByDesc('created_at')
            ->get();

        $totalEvents = $events->count();
        $uniqueCtas = $events->pluck('cta_id')->unique()->count();

        $byVariant = $events
            ->groupBy(fn (LandingEvent $event) => $event->ab_variant ?: 'unknown')
            ->map(fn ($items, $variant) => [
                'ab_variant' => $variant,
                'events' => $items->count(),
            ])
            ->sortByDesc('events')
            ->values();

        $byCta = $events
            ->groupBy(fn (LandingEvent $event) => ($event->cta_id ?: 'unknown').'|'.($event->ab_variant ?: 'unknown'))
            ->map(function ($items) {
                /** @var LandingEvent $first */
                $first = $items->first();

                return [
                    'cta_id' => $first?->cta_id ?: 'unknown',
                    'ab_variant' => $first?->ab_variant ?: 'unknown',
                    'events' => $items->count(),
                ];
            })
            ->sortByDesc('events')
            ->values();

        $daily = $events
            ->groupBy(fn (LandingEvent $event) => $event->created_at?->toDateString() ?: 'unknown')
            ->map(fn ($items, $date) => [
                'date' => $date,
                'events' => $items->count(),
            ])
            ->sortBy('date')
            ->values();

        return response()->json([
            'range_days' => $days,
            'totals' => [
                'events' => $totalEvents,
                'unique_ctas' => $uniqueCtas,
            ],
            'by_variant' => $byVariant,
            'by_cta' => $byCta,
            'daily' => $daily,
        ]);
    }
}
