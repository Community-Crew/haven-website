{{-- A room's recent reservations and how each was left. Deliberately no
    names - those are only on a single reservation's details. --}}
<div class="room-history">
    <style>
        .room-history { --rh-border: var(--gray-200); --rh-muted: var(--gray-500); font-size: 0.875rem; }
        html.dark .room-history { --rh-border: rgba(255, 255, 255, 0.1); --rh-muted: var(--gray-400); }
        .room-history ol { list-style: none; margin: 0; padding: 0; }
        .room-history li { display: grid; grid-template-columns: 1fr auto; gap: 0.25rem 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--rh-border); }
        .room-history li:last-child { border-bottom: 0; }
        .room-history small { grid-column: 1 / -1; color: var(--rh-muted); }
        .room-history__none { color: var(--rh-muted); }
    </style>

    @if ($room?->cleaned_at)
        <p class="room-history__none">Laatst schoongemaakt door Vestide: {{ $room->cleaned_at->translatedFormat('D j M Y') }}, {{ $room->cleaned_at->format('H:i') }}.</p>
    @endif

    @if ($history->isEmpty())
        <p class="room-history__none">Nog geen afgelopen reserveringen.</p>
    @else
        <ol>
            @foreach ($history as $entry)
                @php($reservation = $entry['reservation'])
                <li>
                    <span>{{ $reservation->start_at->translatedFormat('D j M Y') }}, {{ $reservation->start_at->format('H:i') }} – {{ $reservation->end_at->format('H:i') }}</span>
                    @if ($entry['outcome'])
                        <x-filament::badge :color="$entry['outcome']->getColor()" :icon="$entry['outcome']->getIcon()">{{ $entry['outcome']->getLabel() }}</x-filament::badge>
                    @else
                        <x-filament::badge color="info">Wacht op beoordeling</x-filament::badge>
                    @endif
                    @if ($entry['outcome'] === \App\Enums\CleanlinessOutcome::DIRTY && $entry['check_out']->rejection_note)
                        <small>{{ $entry['check_out']->rejection_note }}</small>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif
</div>
