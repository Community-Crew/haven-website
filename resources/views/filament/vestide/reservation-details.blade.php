{{-- Slide-over for one reservation, shared by the timeline and the
    reservations list. This is the only place in the Vestide panel that names
    the resident: the lists themselves don't, so they can't be read as a log
    of who booked what. Plain <style> rather than Tailwind utilities, same
    constraint as the review page: Filament's precompiled bundle only ships
    the classes its own components use. --}}
<div class="vestide-res">
    <style>
        .vestide-res { --vr-border: var(--gray-200); --vr-muted: var(--gray-500); --vr-subtle: var(--gray-50); display: grid; gap: 1.5rem; font-size: 0.875rem; }
        html.dark .vestide-res { --vr-border: rgba(255, 255, 255, 0.1); --vr-muted: var(--gray-400); --vr-subtle: rgba(255, 255, 255, 0.03); }
        .vestide-res dl { display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem; margin: 0; }
        .vestide-res dt { color: var(--vr-muted); }
        .vestide-res dd { margin: 0; min-width: 0; overflow-wrap: anywhere; }
        .vestide-res h4 { margin: 0 0 0.5rem; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: var(--vr-muted); }
        .vestide-res__reason { padding: 0.75rem 1rem; border-radius: 0.5rem; background: var(--vr-subtle); border: 1px solid var(--vr-border); white-space: pre-line; }
        .vestide-res__gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr)); gap: 0.5rem; }
        .vestide-res__gallery a { display: block; aspect-ratio: 3 / 4; border-radius: 0.5rem; overflow: hidden; background: var(--vr-subtle); }
        .vestide-res__gallery img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .vestide-res__none { padding: 1rem; border: 1px dashed var(--vr-border); border-radius: 0.5rem; color: var(--vr-muted); text-align: center; }
    </style>

    @php
        $checkIn = $reservation->checks->firstWhere('type', \App\Enums\CheckType::CHECK_IN);
        $checkOut = $reservation->checks->firstWhere('type', \App\Enums\CheckType::CHECK_OUT);
        $outcome = $checkOut ? \App\Enums\CleanlinessOutcome::forCheck($checkOut) : null;
        $ghosted = $outcome === \App\Enums\CleanlinessOutcome::GHOSTED;
    @endphp

    <dl>
        <dt>Bewoner</dt>
        <dd>{{ $reservation->user?->name ?? '-' }}{{ $reservation->user?->unit ? ' · '.$reservation->user->unit->name : '' }}</dd>
        @if ($reservation->organisation)
            <dt>Namens</dt>
            <dd>{{ $reservation->organisation->name }}</dd>
        @endif
        <dt>Ruimte</dt>
        <dd>{{ $reservation->room->name }}</dd>
        <dt>Gereserveerd</dt>
        <dd>{{ $reservation->start_at->translatedFormat('D j M Y') }}, {{ $reservation->start_at->format('H:i') }} – {{ $reservation->end_at->format('H:i') }}</dd>
        @if (filled($reservation->name))
            <dt>Omschrijving</dt>
            <dd>{{ $reservation->name }}</dd>
        @endif
        <dt>Status</dt>
        <dd>{{ \App\Filament\Vestide\Pages\Reservations::statusLabel($reservation->status) }}</dd>
        <dt>Achtergelaten</dt>
        <dd>
            @if ($outcome)
                <x-filament::badge :color="$outcome->getColor()" :icon="$outcome->getIcon()" style="display: inline-flex">{{ $outcome->getLabel() }}</x-filament::badge>
            @elseif ($checkOut)
                Wacht op beoordeling
            @else
                -
            @endif
        </dd>
        @if ($checkOut && ! $ghosted)
            <dt>Uitgecheckt</dt>
            <dd>{{ $checkOut->occurred_at->format('H:i') }}</dd>
        @endif
    </dl>

    @if ($ghosted)
        <div class="vestide-res__reason">De bewoner heeft binnen een uur na het einde van de reservering geen uitcheckfoto's ingestuurd.</div>
    @elseif ($outcome === \App\Enums\CleanlinessOutcome::DIRTY && $checkOut->rejection_note)
        <div>
            <h4>Reden</h4>
            <div class="vestide-res__reason">{{ $checkOut->rejection_note }}</div>
        </div>
    @endif

    @foreach ([
        'Voor (incheck)' => $checkIn,
        'Na (uitcheck)' => $ghosted ? null : $checkOut,
    ] as $heading => $photoCheck)
        @continue($heading === 'Na (uitcheck)' && $ghosted)
        <div>
            <h4>{{ $heading }}</h4>
            @if ($photoCheck && count($photoCheck->photo_urls) > 0)
                <div class="vestide-res__gallery">
                    @foreach ($photoCheck->photo_urls as $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="Foto {{ strtolower($heading) }}" loading="lazy"></a>
                    @endforeach
                </div>
            @else
                <div class="vestide-res__none">Geen foto's.</div>
            @endif
        </div>
    @endforeach
</div>
