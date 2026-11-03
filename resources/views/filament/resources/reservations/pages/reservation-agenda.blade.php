@php
    $agenda = $this->getAgenda();
    $span = $agenda['end'] - $agenda['start'];
    $hours = range(intdiv($agenda['start'], 60), intdiv($agenda['end'], 60) - 1);
@endphp

<x-filament-panels::page>
    {{-- Plain <style> rather than Tailwind utilities, like the heatmap page:
        Filament's precompiled bundle only ships the classes its own
        components use. Colours come from Filament's runtime CSS variables so
        they follow light/dark; rooms get their own hue via --hue. --}}
    <style>
        .agenda { --ag-border: var(--gray-200); --ag-muted: var(--gray-500); --ag-surface: #fff; --ag-subtle: var(--gray-50); --ag-row: 3rem; }
        html.dark .agenda { --ag-border: rgba(255, 255, 255, 0.1); --ag-muted: var(--gray-400); --ag-surface: var(--gray-900); --ag-subtle: rgba(255, 255, 255, 0.03); }

        .agenda__toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1rem; margin-bottom: 1rem; }
        .agenda__nav { display: inline-flex; align-items: center; gap: 0.25rem; }
        .agenda__seg { display: inline-flex; border: 1px solid var(--ag-border); border-radius: 0.5rem; overflow: hidden; }
        .agenda__seg button { padding: 0.375rem 0.75rem; font-size: 0.875rem; font-weight: 500; background: var(--ag-surface); color: inherit; border: 0; cursor: pointer; }
        .agenda__seg button + button { border-left: 1px solid var(--ag-border); }
        .agenda__seg button[aria-pressed="true"] { background: var(--primary-600); color: #fff; }
        .agenda__toolbar-end { margin-left: auto; display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; font-size: 0.875rem; }
        .agenda__toggle { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; user-select: none; }

        .agenda__scroll { overflow-x: auto; border: 1px solid var(--ag-border); border-radius: 0.75rem; background: var(--ag-surface); }
        .agenda__grid { display: grid; grid-template-columns: 3.5rem repeat(var(--cols), minmax(8rem, 1fr)); min-width: calc(3.5rem + var(--cols) * 8rem); }

        .agenda__head { position: sticky; top: 0; z-index: 3; padding: 0.625rem 0.5rem; text-align: center; font-size: 0.8125rem; font-weight: 600; background: var(--ag-surface); border-bottom: 1px solid var(--ag-border); border-left: 1px solid var(--ag-border); }
        .agenda__head small { display: block; font-size: 1.125rem; font-weight: 700; }
        .agenda__head.is-today small { display: inline-grid; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; background: var(--primary-600); color: #fff; }
        .agenda__head button { background: none; border: 0; padding: 0; font: inherit; color: inherit; cursor: pointer; }
        .agenda__head-corner { border-bottom: 1px solid var(--ag-border); background: var(--ag-surface); position: sticky; top: 0; z-index: 3; }

        .agenda__hours { position: relative; }
        .agenda__hour { height: var(--ag-row); position: relative; font-size: 0.6875rem; color: var(--ag-muted); text-align: right; padding-right: 0.5rem; }
        .agenda__hour span { position: relative; top: -0.5em; }

        .agenda__col { position: relative; border-left: 1px solid var(--ag-border);
            background-image: linear-gradient(to bottom, var(--ag-border) 1px, transparent 1px); background-size: 100% var(--ag-row); }
        .agenda__col.is-today { background-color: color-mix(in oklab, var(--primary-500) 4%, transparent); }
        .agenda__now { position: absolute; left: 0; right: 0; height: 2px; background: var(--danger-500); z-index: 2; pointer-events: none; }
        .agenda__now::before { content: ""; position: absolute; left: -4px; top: -3px; width: 8px; height: 8px; border-radius: 999px; background: var(--danger-500); }

        .agenda__block { position: absolute; z-index: 1; overflow: hidden; padding: 0.25rem 0.375rem; border-radius: 0.375rem; font-size: 0.75rem; line-height: 1.25;
            color: hsl(var(--hue) 60% 20%); background: hsl(var(--hue) 85% 92%); border: 1px solid hsl(var(--hue) 60% 75%); border-left: 3px solid hsl(var(--hue) 65% 45%);
            text-decoration: none; transition: box-shadow 120ms; }
        html.dark .agenda__block { color: hsl(var(--hue) 70% 88%); background: hsl(var(--hue) 35% 22%); border-color: hsl(var(--hue) 35% 32%); border-left-color: hsl(var(--hue) 65% 55%); }
        .agenda__block:hover { box-shadow: 0 4px 12px -4px rgba(0, 0, 0, 0.35); z-index: 2; }
        .agenda__block strong { display: block; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .agenda__block span { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; opacity: 0.85; }
        .agenda__block.is-pending { border-style: dashed; background: repeating-linear-gradient(-45deg, hsl(var(--hue) 85% 94%) 0 6px, hsl(var(--hue) 85% 89%) 6px 12px); }
        html.dark .agenda__block.is-pending { background: repeating-linear-gradient(-45deg, hsl(var(--hue) 35% 22%) 0 6px, hsl(var(--hue) 35% 18%) 6px 12px); }
        .agenda__block.is-cancelled { opacity: 0.5; }
        .agenda__block.is-cancelled strong { text-decoration: line-through; }

        .agenda__legend { display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; margin-top: 0.75rem; font-size: 0.75rem; color: var(--ag-muted); }
        .agenda__legend i { display: inline-block; width: 0.75rem; height: 0.75rem; margin-right: 0.375rem; vertical-align: -0.125rem; border-radius: 0.2rem; }
    </style>

    <div class="agenda">
        <div class="agenda__toolbar">
            <div class="agenda__nav">
                <x-filament::icon-button icon="heroicon-m-chevron-left" color="gray" wire:click="previous" :label="$mode === 'day' ? 'Previous day' : 'Previous week'" />
                <x-filament::button size="sm" color="gray" wire:click="today">Today</x-filament::button>
                <x-filament::icon-button icon="heroicon-m-chevron-right" color="gray" wire:click="next" :label="$mode === 'day' ? 'Next day' : 'Next week'" />
            </div>

            <input type="date" wire:model.live="date" class="fi-input" style="width: auto; padding: 0.375rem 0.5rem; border: 1px solid var(--ag-border); border-radius: 0.5rem; background: var(--ag-surface); font-size: 0.875rem;" aria-label="Go to date">

            <div class="agenda__seg" role="group" aria-label="View">
                <button type="button" wire:click="$set('mode', 'week')" aria-pressed="{{ $mode === 'week' ? 'true' : 'false' }}">Week</button>
                <button type="button" wire:click="$set('mode', 'day')" aria-pressed="{{ $mode === 'day' ? 'true' : 'false' }}">Day</button>
            </div>

            <div class="agenda__toolbar-end">
                @if ($mode === 'week')
                    <select wire:model.live="room" class="fi-select-input" style="padding: 0.375rem 2rem 0.375rem 0.5rem; border: 1px solid var(--ag-border); border-radius: 0.5rem; background-color: var(--ag-surface); font-size: 0.875rem;" aria-label="Room">
                        <option value="">All rooms</option>
                        @foreach ($this->getRooms() as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                @endif
                <label class="agenda__toggle">
                    <input type="checkbox" class="fi-checkbox-input" wire:model.live="showCancelled">
                    Show cancelled/rejected
                </label>
            </div>
        </div>

        <div class="agenda__scroll">
            <div class="agenda__grid" style="--cols: {{ count($agenda['columns']) }}">
                <div class="agenda__head-corner"></div>
                @foreach ($agenda['columns'] as $column)
                    <div @class(['agenda__head', 'is-today' => $column['today'] && $mode === 'week'])>
                        @if ($mode === 'week')
                            <button type="button" wire:click="showDay('{{ $column['key'] }}')" title="Open this day">
                                {{ $column['label'] }} <small>{{ $column['sub'] }}</small>
                            </button>
                        @else
                            {{ $column['label'] }}
                        @endif
                    </div>
                @endforeach

                <div class="agenda__hours">
                    @foreach ($hours as $hour)
                        <div class="agenda__hour"><span>{{ $loop->first ? '' : sprintf('%02d:00', $hour) }}</span></div>
                    @endforeach
                </div>

                @foreach ($agenda['columns'] as $column)
                    <div @class(['agenda__col', 'is-today' => $column['today']]) style="height: calc({{ count($hours) }} * var(--ag-row))" wire:key="col-{{ $column['key'] }}">
                        @if ($column['now'] !== null)
                            <div class="agenda__now" style="top: {{ $column['now'] }}%"></div>
                        @endif

                        @foreach ($column['blocks'] as $block)
                            @php($reservation = $block['reservation'])
                            <a href="{{ $block['url'] }}"
                               @class([
                                   'agenda__block',
                                   'is-pending' => $reservation->status === \App\Enums\ReservationStatus::PENDING,
                                   'is-cancelled' => in_array($reservation->status, [\App\Enums\ReservationStatus::CANCELLED, \App\Enums\ReservationStatus::REJECTED], true),
                               ])
                               style="--hue: {{ $block['hue'] }}; top: {{ $block['top'] }}%; height: {{ $block['height'] }}%; left: calc({{ $block['lane'] }} / {{ $block['lanes'] }} * 100% + 2px); width: calc(100% / {{ $block['lanes'] }} - 4px);"
                               title="{{ $block['time'] }} · {{ $reservation->room->name }} · {{ $reservation->name ?: '(no title)' }} · {{ $reservation->user?->name }}{{ $reservation->organisation ? ' for '.$reservation->organisation->name : '' }} · {{ $reservation->status->getLabel() }}{{ $block['fast'] ? ' · ⚡ booked suspiciously fast' : '' }}">
                                <strong>{{ $block['fast'] ? '⚡ ' : '' }}{{ $reservation->name ?: $reservation->room->name }}</strong>
                                <span>{{ $block['time'] }}</span>
                                <span>{{ $mode === 'week' && ! $room ? $reservation->room->name.' · ' : '' }}{{ $reservation->organisation?->name ?? $reservation->user?->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <div class="agenda__legend">
            <span><i style="background: hsl(212 85% 89%); border: 1px dashed hsl(212 60% 60%)"></i>Pending approval</span>
            <span><i style="background: var(--danger-500); height: 2px; vertical-align: middle"></i>Now</span>
            <span>⚡ booked suspiciously fast</span>
            @if ($mode === 'week')
                <span>Click a day to open it</span>
            @endif
        </div>
    </div>
</x-filament-panels::page>
