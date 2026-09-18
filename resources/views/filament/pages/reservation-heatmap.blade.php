@php
    $combined = $this->getCombinedHeatmap();
    $hourRange = $this->getHourRange();
@endphp

{{-- This admin app has no JS/CSS build step for custom pages - Filament ships a
    fixed, precompiled Tailwind bundle that only contains the utility classes
    Filament's own components happen to use, so arbitrary utility classes
    written here (grid-cols-2, gap-0.5, text-[10px], dark:border-white/5, ...)
    silently do nothing. Hence a plain <style> block instead of Tailwind
    classes for the parts that need actual layout/color, matching Filament's
    own html.dark toggle for dark mode. --}}
<style>
    .reservation-heatmap-page__legend {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        margin: 0 0 0.75rem;
        font-size: 0.75rem;
        color: #6b7280;
    }
    html.dark .reservation-heatmap-page__legend {
        color: #9ca3af;
    }

    .reservation-heatmap-page__legend-swatch {
        width: 0.875rem;
        height: 0.875rem;
        border-radius: 0.2rem;
        border: 1px solid #f3f4f6;
    }
    html.dark .reservation-heatmap-page__legend-swatch {
        border-color: rgba(255, 255, 255, 0.06);
    }

    /*
     * Grid, not flex-wrap: with flex-wrap, a short last row stretches its
     * card(s) to fill the leftover space, so cards end up different widths
     * depending how many happen to land in the last row. Grid columns are
     * sized once for the whole container, so a short last row just leaves
     * empty space instead - every card is the same width.
     */
    .reservation-heatmap-page__rooms {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.25rem;
    }

    .reservation-heatmap-page__room-card {
        min-width: 0;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
    }
    html.dark .reservation-heatmap-page__room-card {
        border-color: rgba(255, 255, 255, 0.1);
    }

    .reservation-heatmap-page__room-card-title {
        margin: 0 0 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #111827;
    }
    html.dark .reservation-heatmap-page__room-card-title {
        color: #f3f4f6;
    }

    .reservation-heatmap-page__room-card-title span {
        font-weight: 400;
        color: #6b7280;
    }
    html.dark .reservation-heatmap-page__room-card-title span {
        color: #9ca3af;
    }

    /*
     * A full-width grid with `fr` hour columns (not fixed rem widths) so the
     * whole week always fits the section's width - no horizontal scrollbar,
     * whatever the hour range or how narrow the "by room" cards get.
     */
    .reservation-heatmap {
        display: grid;
        width: 100%;
        gap: 2px;
        grid-template-columns: 2.25rem repeat(var(--hour-count), minmax(0, 1fr));
        grid-auto-rows: 1.75rem;
        box-sizing: border-box;
    }

    .reservation-heatmap--sm {
        grid-template-columns: 1.75rem repeat(var(--hour-count), minmax(0, 1fr));
        grid-auto-rows: 1.25rem;
    }

    .reservation-heatmap__label,
    .reservation-heatmap__hour {
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        font-size: 0.6875rem;
        color: #9ca3af;
    }
    html.dark .reservation-heatmap__label,
    html.dark .reservation-heatmap__hour {
        color: #6b7280;
    }

    .reservation-heatmap__label {
        justify-content: flex-start;
    }

    .reservation-heatmap--sm .reservation-heatmap__label,
    .reservation-heatmap--sm .reservation-heatmap__hour {
        font-size: 0.625rem;
    }

    .reservation-heatmap__cell {
        position: relative;
        min-width: 0;
        border-radius: 0.25rem;
        border: 1px solid #f3f4f6;
    }
    html.dark .reservation-heatmap__cell {
        border-color: rgba(255, 255, 255, 0.06);
    }

    /*
     * A CSS-only tooltip instead of the native `title` attribute: `title`'s
     * browser-controlled show delay makes it easy to miss, and it can get
     * clipped by an ancestor's overflow.
     */
    .reservation-heatmap__cell[data-tooltip]:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: calc(100% + 0.375rem);
        left: 50%;
        transform: translateX(-50%);
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        background-color: #111827;
        color: #f9fafb;
        font-size: 0.6875rem;
        white-space: nowrap;
        pointer-events: none;
        z-index: 10;
    }
    html.dark .reservation-heatmap__cell[data-tooltip]:hover::after {
        background-color: #f3f4f6;
        color: #111827;
    }
</style>

<x-filament-panels::page>
    <x-filament::section
        heading="All rooms (combined)"
        :description="$combined['total'].' approved reservation(s) in the last 3 months'"
    >
        <div class="reservation-heatmap-page__legend">
            <span>Fewer</span>
            @foreach ([0, 0.2, 0.4, 0.6, 0.8, 1] as $fraction)
                <span
                    class="reservation-heatmap-page__legend-swatch"
                    style="background-color: {{ $this->legendColor($fraction) }};"
                ></span>
            @endforeach
            <span>More</span>
            <span>&middot; hover a cell for the exact count</span>
        </div>

        @include('filament.pages.partials.reservation-heatmap-grid', [
            'data' => $combined,
            'hourRange' => $hourRange,
            'size' => 'lg',
        ])
    </x-filament::section>

    <x-filament::section heading="By room">
        <div class="reservation-heatmap-page__rooms">
            @foreach ($this->getPerRoomHeatmaps() as $roomHeatmap)
                <div class="reservation-heatmap-page__room-card">
                    <p class="reservation-heatmap-page__room-card-title">
                        {{ $roomHeatmap['room']->name }}
                        <span>&middot; {{ $roomHeatmap['total'] }} reservation(s)</span>
                    </p>
                    @include('filament.pages.partials.reservation-heatmap-grid', [
                        'data' => $roomHeatmap,
                        'hourRange' => $hourRange,
                        'size' => 'sm',
                    ])
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
