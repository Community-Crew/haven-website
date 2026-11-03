<x-filament-panels::page>
    {{-- Plain <style> rather than Tailwind utilities: Filament's precompiled
        bundle only ships the classes its own components use. Colors come from
        Filament's runtime CSS variables so they follow light/dark. --}}
    <style>
        .room-status { --rs-border: var(--gray-200); --rs-muted: var(--gray-500); --rs-surface: #fff; }
        html.dark .room-status { --rs-border: rgba(255, 255, 255, 0.1); --rs-muted: var(--gray-400); --rs-surface: var(--gray-900); }
        .room-status { display: grid; grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr)); gap: 1rem; }

        .room-status__card { display: flex; flex-direction: column; border-radius: 0.75rem; border: 1px solid var(--rs-border); border-top-width: 4px; background: var(--rs-surface); overflow: hidden; }
        .room-status__open { display: flex; flex: 1; flex-direction: column; gap: 0.75rem; padding: 1.25rem; text-align: start; background: none; border: 0; cursor: pointer; font: inherit; color: inherit; transition: background 120ms; }
        .room-status__open:hover { background: var(--gray-50); }
        html.dark .room-status__open:hover { background: rgba(255, 255, 255, 0.03); }
        .room-status__open:focus-visible { outline: 2px solid var(--primary-500); outline-offset: -2px; }
        .room-status__actions { display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 0.75rem 1.25rem; border-top: 1px solid var(--rs-border); }
        .room-status__card--success { border-top-color: var(--success-500); }
        .room-status__card--danger { border-top-color: var(--danger-500); }
        .room-status__card--warning { border-top-color: var(--warning-500); }
        .room-status__card--info { border-top-color: var(--info-500); }
        .room-status__card--gray { border-top-color: var(--gray-300); }

        .room-status__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
        .room-status__name { margin: 0; font-size: 1.0625rem; font-weight: 600; }
        .room-status__pills { display: flex; flex-wrap: wrap; gap: 0.375rem; }
        .room-status__detail { margin: 0; font-size: 0.8125rem; color: var(--rs-muted); }

        .room-status__dots { display: flex; gap: 0.25rem; margin-top: auto; }
        .room-status__dot { width: 0.75rem; height: 0.75rem; border-radius: 999px; background: var(--gray-200); }
        html.dark .room-status__dot { background: rgba(255, 255, 255, 0.12); }
        .room-status__dot--clean { background: var(--success-500) !important; }
        .room-status__dot--dirty { background: var(--danger-500) !important; }
        .room-status__dot--ghosted { background: var(--warning-500) !important; }
        .room-status__dots-label { font-size: 0.6875rem; color: var(--rs-muted); }
    </style>

    <div class="room-status">
        @foreach ($this->getRooms() as $entry)
            @php($room = $entry['room'])
            <div class="room-status__card room-status__card--{{ $entry['color'] }}" wire:key="room-{{ $room->id }}">
                <button type="button"
                        class="room-status__open"
                        wire:click="mountAction('history', { room: {{ $room->id }} })"
                        aria-label="Geschiedenis van {{ $room->name }}">
                    <div class="room-status__head">
                        <h3 class="room-status__name">{{ $room->name }}</h3>
                        <div class="room-status__pills">
                            @if ($entry['in_use'])
                                <x-filament::badge color="primary" icon="heroicon-m-user-group">Nu in gebruik</x-filament::badge>
                            @endif
                            @if ($note = \App\Filament\Vestide\Pages\RoomStatuses::roomStatusNote($room->status))
                                <x-filament::badge color="gray">{{ $note }}</x-filament::badge>
                            @endif
                        </div>
                    </div>

                    <div>
                        <x-filament::badge :color="$entry['color']" :icon="$entry['icon']" size="lg" style="display: inline-flex">{{ $entry['label'] }}</x-filament::badge>
                    </div>
                    @if ($entry['detail'])
                        <p class="room-status__detail">{{ $entry['detail'] }}</p>
                    @endif

                    @if ($entry['history']->isNotEmpty())
                        <div>
                            {{-- Oldest on the left, so it reads as a timeline ending today. --}}
                            <div class="room-status__dots" aria-hidden="true">
                                @foreach ($entry['history']->reverse() as $past)
                                    <span class="room-status__dot {{ $past['outcome'] ? 'room-status__dot--'.$past['outcome']->value : '' }}"
                                          title="{{ $past['reservation']->end_at->translatedFormat('D j M') }}: {{ $past['outcome']?->getLabel() ?? 'wacht op beoordeling' }}"></span>
                                @endforeach
                            </div>
                            <span class="room-status__dots-label">Laatste {{ $entry['history']->count() }} {{ $entry['history']->count() === 1 ? 'reservering' : 'reserveringen' }}</span>
                        </div>
                    @endif
                </button>
                <div class="room-status__actions">
                    @if ($room->status !== \App\Enums\RoomStatus::CLEANING)
                        {{ ($this->startCleaningAction)(['room' => $room->id]) }}
                    @endif
                    {{ ($this->finishCleaningAction)(['room' => $room->id]) }}
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
