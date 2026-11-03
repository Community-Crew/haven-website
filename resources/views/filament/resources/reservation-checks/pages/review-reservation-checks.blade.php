@php
    $check = $this->getCheck();
    $pending = $this->getPendingCount();
    $flagged = $this->getFlaggedCount();
@endphp

<x-filament-panels::page>
    {{-- Same constraint as the heatmap page: Filament's precompiled Tailwind
        bundle only has the utilities its own components use, so layout lives
        in a plain <style> block. Colors come from Filament's runtime CSS
        variables (--gray-*, --primary-*, ...) so they follow the panel theme,
        with html.dark overrides where the light/dark shade differs. The one
        exception is the Tiedr brand gradient, which is the same in both. --}}
    <style>
        .tiedr { --td-border: var(--gray-200); --td-muted: var(--gray-500); --td-surface: #fff; --td-subtle: var(--gray-50);
            --td-brand: linear-gradient(135deg, #fd267a 0%, #ff6036 100%); --td-like: #12b981; --td-nope: #ff4458; --td-later: #3b82f6; }
        html.dark .tiedr { --td-border: rgba(255, 255, 255, 0.1); --td-muted: var(--gray-400); --td-surface: var(--gray-900); --td-subtle: rgba(255, 255, 255, 0.03); }

        .tiedr-wordmark { display: inline-flex; align-items: center; gap: 0.15em; font-weight: 800; letter-spacing: -0.04em;
            background: linear-gradient(135deg, #fd267a 0%, #ff6036 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .tiedr-wordmark svg { width: 0.95em; height: 0.95em; color: #fd3a6a; }

        .tiedr__toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; }
        .tiedr__chip { display: inline-flex; align-items: baseline; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 999px; font-size: 0.875rem; color: var(--td-muted);
            background: var(--td-surface); border: 1px solid var(--td-border); }
        .tiedr__chip strong { font-size: 1rem; font-weight: 700; color: var(--gray-950); font-variant-numeric: tabular-nums; }
        html.dark .tiedr__chip strong { color: #fff; }
        .tiedr__chip--flag strong { color: var(--td-nope); }
        .tiedr__chip--hot { border-color: transparent; color: #fff; background: var(--td-brand); }
        .tiedr__chip--hot strong { color: #fff !important; }
        .tiedr__toolbar-end { margin-left: auto; display: flex; align-items: center; gap: 1rem; font-size: 0.875rem; }
        .tiedr__toggle { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; user-select: none; }

        .tiedr__layout { display: grid; grid-template-columns: minmax(0, 28rem) minmax(0, 1fr); gap: 2rem; align-items: start; justify-content: center; }
        @media (max-width: 1000px) { .tiedr__layout { grid-template-columns: minmax(0, 1fr); } .tiedr__deck-col { max-width: 28rem; width: 100%; margin: 0 auto; } }

        /* The deck: the current check as a profile card, with the rest of the queue peeking out underneath. */
        .tiedr__deck { position: relative; padding-bottom: 1rem; }
        .tiedr__ghost { position: absolute; left: 50%; bottom: 0; height: 2rem; border-radius: 1rem; transform: translateX(-50%); background: var(--td-surface); border: 1px solid var(--td-border); }
        .tiedr__ghost--1 { width: 92%; bottom: 0.5rem; }
        .tiedr__ghost--2 { width: 84%; bottom: 0; opacity: 0.6; }

        .tiedr__card { position: relative; z-index: 1; aspect-ratio: 3 / 4; border-radius: 1rem; overflow: hidden; background: var(--gray-950); color: #fff;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.45); touch-action: pan-y; user-select: none; cursor: grab; will-change: transform; }
        .tiedr__card.is-dragging { cursor: grabbing; }
        .tiedr__card.is-static { cursor: default; }
        .tiedr__backdrop { position: absolute; inset: -2rem; background-size: cover; background-position: center; filter: blur(24px) brightness(0.55); }
        .tiedr__photo { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; pointer-events: none; }
        .tiedr__nophoto { position: absolute; inset: 0; display: grid; place-items: center; padding: 2rem; text-align: center; color: var(--gray-400); font-size: 0.9375rem; }

        .tiedr__bars { position: absolute; top: 0.5rem; left: 0.5rem; right: 0.5rem; display: flex; gap: 0.25rem; z-index: 2; }
        .tiedr__bars span { flex: 1; height: 0.25rem; border-radius: 999px; background: rgba(255, 255, 255, 0.35); }
        .tiedr__bars span.is-active { background: #fff; }

        .tiedr__top { position: absolute; top: 1.25rem; left: 0.75rem; right: 0.75rem; z-index: 2; display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
        .tiedr__pill { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.625rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: rgba(0, 0, 0, 0.55); backdrop-filter: blur(6px); }
        .tiedr__pill--flag { background: var(--td-nope); max-width: 75%; }
        .tiedr__pill svg { width: 0.875rem; height: 0.875rem; flex: none; }
        .tiedr__zoom { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; background: rgba(0, 0, 0, 0.55); color: #fff; }
        .tiedr__zoom svg { width: 1rem; height: 1rem; }

        .tiedr__info { position: absolute; left: 0; right: 0; bottom: 0; z-index: 2; padding: 4rem 1.25rem 1.25rem; pointer-events: none;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.6) 55%, transparent 100%); }
        .tiedr__name { margin: 0; font-size: 1.75rem; font-weight: 800; line-height: 1.1; letter-spacing: -0.01em; }
        .tiedr__name small { font-size: 1.125rem; font-weight: 400; opacity: 0.85; }
        .tiedr__meta { display: flex; align-items: center; gap: 0.375rem; margin-top: 0.375rem; font-size: 0.9375rem; opacity: 0.9; }
        .tiedr__meta svg { width: 1rem; height: 1rem; flex: none; }
        .tiedr__flagtext { margin-top: 0.625rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-size: 0.8125rem; background: rgba(255, 68, 88, 0.25); border: 1px solid rgba(255, 68, 88, 0.6); }

        .tiedr__stamp { position: absolute; top: 3.5rem; z-index: 3; padding: 0.25rem 0.75rem; border: 4px solid; border-radius: 0.5rem; font-size: 2.25rem; font-weight: 900; letter-spacing: 0.06em; pointer-events: none; }
        .tiedr__stamp--like { left: 1.25rem; color: var(--td-like); border-color: var(--td-like); transform: rotate(-16deg); }
        .tiedr__stamp--nope { right: 1.25rem; color: var(--td-nope); border-color: var(--td-nope); transform: rotate(16deg); }

        .tiedr__buttons { display: flex; align-items: center; justify-content: center; gap: 1.25rem; margin-top: 1.25rem; }
        .tiedr__buttons .fi-icon-btn { width: 4.25rem; height: 4.25rem; border-radius: 999px; background: var(--td-surface); border: 1px solid var(--td-border);
            box-shadow: 0 6px 16px -6px rgba(0, 0, 0, 0.3); transition: transform 120ms ease; }
        .tiedr__buttons .fi-icon-btn:hover { transform: scale(1.08); }
        .tiedr__buttons .fi-icon-btn svg, .tiedr__buttons .fi-icon-btn .fi-icon { width: 2rem; height: 2rem; }
        .tiedr__buttons [data-tiedr-action="approve"] .fi-icon-btn { color: var(--td-like); }
        .tiedr__buttons [data-tiedr-action="reject"] .fi-icon-btn { color: var(--td-nope); }
        .tiedr__buttons [data-tiedr-action="skip"] .fi-icon-btn { width: 3.25rem; height: 3.25rem; color: var(--td-later); }
        .tiedr__buttons [data-tiedr-action="skip"] .fi-icon-btn svg, .tiedr__buttons [data-tiedr-action="skip"] .fi-icon-btn .fi-icon { width: 1.5rem; height: 1.5rem; }

        .tiedr__keys { display: flex; flex-wrap: wrap; justify-content: center; gap: 0.5rem 1rem; margin-top: 1rem; font-size: 0.75rem; color: var(--td-muted); }
        .tiedr__keys kbd { display: inline-block; min-width: 1.25rem; padding: 0 0.3rem; margin-right: 0.25rem; border: 1px solid var(--td-border); border-bottom-width: 2px; border-radius: 0.25rem; font-family: inherit; font-size: 0.6875rem; text-align: center; }
        .tiedr__verdict { margin-top: 1.25rem; padding: 1rem; border-radius: 0.75rem; background: var(--td-surface); border: 1px solid var(--td-border); font-size: 0.875rem; white-space: pre-line; }
        .tiedr__verdict strong { display: block; margin-bottom: 0.25rem; font-size: 1rem; }

        .tiedr__panel { background: var(--td-surface); border: 1px solid var(--td-border); border-radius: 1rem; padding: 1.25rem; }
        .tiedr__panel + .tiedr__panel { margin-top: 1rem; }
        .tiedr__panel-title { margin: 0 0 0.75rem; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--td-muted); }

        .tiedr__dl { display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem; margin: 0; font-size: 0.875rem; }
        .tiedr__dl dt { color: var(--td-muted); }
        .tiedr__dl dd { margin: 0; min-width: 0; overflow-wrap: anywhere; }
        .tiedr__dl dd small { display: block; color: var(--td-muted); }

        .tiedr__history { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; text-align: center; }
        .tiedr__history div { padding: 0.625rem 0.25rem; border-radius: 0.75rem; background: var(--td-subtle); }
        .tiedr__history strong { display: block; font-size: 1.25rem; font-variant-numeric: tabular-nums; }
        .tiedr__history span { font-size: 0.75rem; color: var(--td-muted); }
        .tiedr__history .is-good strong { color: var(--td-like); }
        .tiedr__history .is-bad strong { color: var(--td-nope); }

        .tiedr__compare { display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1.25rem; }
        .tiedr__compare-head { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 600; }
        .tiedr__compare-head small { font-weight: 400; color: var(--td-muted); }
        .tiedr__gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(6rem, 1fr)); gap: 0.5rem; }
        .tiedr__gallery a { display: block; aspect-ratio: 3 / 4; border-radius: 0.625rem; overflow: hidden; background: var(--td-subtle); }
        .tiedr__gallery img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 160ms; }
        .tiedr__gallery a:hover img { transform: scale(1.04); }
        .tiedr__none { padding: 1rem; border: 1px dashed var(--td-border); border-radius: 0.75rem; font-size: 0.875rem; color: var(--td-muted); text-align: center; }

        .tiedr__notes { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.75rem; font-size: 0.875rem; }
        .tiedr__notes li { padding-left: 0.75rem; border-left: 2px solid var(--td-border); }
        .tiedr__notes li.is-public { border-left-color: #fd3a6a; }
        .tiedr__notes small { display: block; margin-top: 0.125rem; color: var(--td-muted); font-size: 0.75rem; }
    </style>

    <div class="tiedr">
        <div class="tiedr__toolbar">
            <div class="tiedr__chip tiedr__chip--hot"><strong>{{ $pending }}</strong> {{ $pending === 1 ? 'room' : 'rooms' }} waiting for you</div>
            <div @class(['tiedr__chip', 'tiedr__chip--flag' => $flagged > 0])>🚩 <strong>{{ $flagged }}</strong> red {{ $flagged === 1 ? 'flag' : 'flags' }}</div>
            @if ($this->reviewedCount > 0)
                <div class="tiedr__chip">🔥 <strong>{{ $this->reviewedCount }}</strong> {{ $this->reviewedCount === 1 ? 'swipe' : 'swipes' }} this session</div>
            @endif

            <div class="tiedr__toolbar-end">
                @if ($this->resetSkippedAction->isVisible())
                    {{ $this->resetSkippedAction }}
                @endif
                <label class="tiedr__toggle">
                    <input type="checkbox" class="fi-checkbox-input" wire:model.live="flaggedOnly">
                    Red flags only
                </label>
            </div>
        </div>

        @if (! $check)
            <x-filament::empty-state
                icon="heroicon-o-fire"
                icon-color="danger"
                :heading="$flaggedOnly ? 'No red flags left' : 'No new rooms nearby'"
                :description="count($skipped) > 0
                    ? 'You left '.count($skipped).' on read this session - use \'Give skipped ones another chance\' to go back to them.'
                    : 'You\'ve swiped through every check-in and check-out photo. Check back later.'"
            />
        @else
            @php
                $reservation = $check->reservation;
                $counterpart = $this->getCounterpart();
                $history = $this->getResidentHistory();
                $notes = $this->getNotes();
                $photos = $check->photo_urls;
                $examples = $reservation->room->checkin_example_photo_urls;
                $canLike = $this->approveAction->isVisible();
                $canNope = $this->rejectAction->isVisible();
                $isPending = $check->verification_status === \App\Enums\CheckVerificationStatus::PENDING;
                $statusColor = fn ($status) => match ($status) {
                    \App\Enums\CheckVerificationStatus::VERIFIED => 'success',
                    \App\Enums\CheckVerificationStatus::REJECTED => 'danger',
                    default => 'warning',
                };
            @endphp

            {{-- wire:key per check so the card's Alpine state (selected photo,
                drag offset) resets when the queue advances. --}}
            <div class="tiedr__layout" wire:key="check-{{ $check->id }}">
                {{-- Swiping past the threshold clicks the same Filament action
                    button the A/R keys do, so authorization, the reject
                    reason modal and the "already reviewed" guard all apply. A
                    left swipe springs the card back first since the reject
                    modal can still be cancelled. --}}
                <div class="tiedr__deck-col"
                     x-data="{
                        active: 0,
                        photos: @js($photos),
                        canLike: @js($canLike),
                        canNope: @js($canNope),
                        threshold: 110,
                        dx: 0,
                        dy: 0,
                        startX: 0,
                        startY: 0,
                        dragging: false,
                        leaving: false,
                        get swipeable() { return this.canLike || this.canNope },
                        get likeOpacity() { return this.canLike ? Math.min(Math.max(this.dx / this.threshold, 0), 1) : 0 },
                        get nopeOpacity() { return this.canNope ? Math.min(Math.max(-this.dx / this.threshold, 0), 1) : 0 },
                        get cardStyle() {
                            return `transform: translate(${this.dx}px, ${this.dy * 0.25}px) rotate(${this.dx / 20}deg); transition: ${this.dragging ? 'none' : 'transform 220ms ease'}`
                        },
                        next() { if (this.photos.length) this.active = (this.active + 1) % this.photos.length },
                        prev() { if (this.photos.length) this.active = (this.active - 1 + this.photos.length) % this.photos.length },
                        down(e) {
                            if (this.leaving || e.button > 0) return
                            this.dragging = true
                            this.startX = e.clientX
                            this.startY = e.clientY
                            e.currentTarget.setPointerCapture(e.pointerId)
                        },
                        move(e) {
                            if (! this.dragging) return
                            this.dx = this.swipeable ? e.clientX - this.startX : 0
                            this.dy = this.swipeable ? e.clientY - this.startY : 0
                        },
                        up(e) {
                            if (! this.dragging) return
                            this.dragging = false
                            if (Math.abs(e.clientX - this.startX) + Math.abs(e.clientY - this.startY) < 6) {
                                const rect = e.currentTarget.getBoundingClientRect()
                                e.clientX - rect.left < rect.width / 2 ? this.prev() : this.next()
                                return this.reset()
                            }
                            if (this.dx > this.threshold && this.canLike) return this.swipe('approve')
                            if (this.dx < -this.threshold && this.canNope) return this.swipe('reject')
                            this.reset()
                        },
                        reset() { this.dx = 0; this.dy = 0 },
                        swipe(action) {
                            const button = this.$root.querySelector(`[data-tiedr-action=${action}] button`)
                            if (! button) return this.reset()
                            if (action === 'reject') {
                                this.reset()
                                return button.click()
                            }
                            this.leaving = true
                            this.dx = window.innerWidth
                            setTimeout(() => button.click(), 180)
                            {{-- Normally the re-render replaces this card; bring it back if the request never lands. --}}
                            setTimeout(() => { this.leaving = false; this.reset() }, 5000)
                        },
                     }"
                     x-on:keydown.window.arrow-right="if (! $event.target.closest('input, textarea, [contenteditable]')) next()"
                     x-on:keydown.window.arrow-left="if (! $event.target.closest('input, textarea, [contenteditable]')) prev()">
                    <div class="tiedr__deck">
                        @if ($pending > 1)
                            <div class="tiedr__ghost tiedr__ghost--1"></div>
                        @endif
                        @if ($pending > 2)
                            <div class="tiedr__ghost tiedr__ghost--2"></div>
                        @endif

                        <div class="tiedr__card"
                             x-bind:class="{ 'is-dragging': dragging, 'is-static': ! swipeable && photos.length < 2 }"
                             x-bind:style="cardStyle"
                             x-on:pointerdown="down($event)"
                             x-on:pointermove="move($event)"
                             x-on:pointerup="up($event)"
                             x-on:pointercancel="dragging = false; reset()">
                            @if (count($photos) > 0)
                                <div class="tiedr__backdrop" x-bind:style="`background-image: url('${photos[active]}')`" style="background-image: url('{{ $photos[0] }}')"></div>
                                <img class="tiedr__photo" x-bind:src="photos[active]" src="{{ $photos[0] }}" draggable="false" alt="Submitted {{ strtolower($check->type->getLabel()) }} photo">
                            @else
                                <div class="tiedr__nophoto">No photos were submitted with this {{ strtolower($check->type->getLabel()) }}. Suspicious.</div>
                            @endif

                            @if (count($photos) > 1)
                                <div class="tiedr__bars" aria-hidden="true">
                                    @foreach ($photos as $i => $url)
                                        <span x-bind:class="{ 'is-active': active === {{ $i }} }" @class(['is-active' => $i === 0])></span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="tiedr__top">
                                <span class="tiedr__pill">{{ $check->type->getLabel() }}</span>
                                @if (count($photos) > 0)
                                    <a class="tiedr__zoom" x-bind:href="photos[active]" href="{{ $photos[0] }}" target="_blank" rel="noopener" title="Open full size" x-on:pointerdown.stop>
                                        <x-filament::icon icon="heroicon-m-arrows-pointing-out" />
                                    </a>
                                @endif
                            </div>

                            <div class="tiedr__stamp tiedr__stamp--like" x-bind:style="`opacity: ${likeOpacity}`" style="opacity: 0" aria-hidden="true">TIDY</div>
                            <div class="tiedr__stamp tiedr__stamp--nope" x-bind:style="`opacity: ${nopeOpacity}`" style="opacity: 0" aria-hidden="true">NOPE</div>

                            <div class="tiedr__info">
                                <h2 class="tiedr__name">{{ $reservation->room->name }}</h2>
                                <div class="tiedr__meta">
                                    <x-filament::icon icon="heroicon-m-user" />
                                    <span>{{ $reservation->user->name }}</span>
                                </div>
                                <div class="tiedr__meta">
                                    <x-filament::icon icon="heroicon-m-map-pin" />
                                    <span>{{ ucfirst($this->getTimingLabel()) }}</span>
                                </div>
                                @if ($check->flag_reason)
                                    <div class="tiedr__flagtext" role="note">🚩 <strong>Red flag:</strong> {{ $check->flag_reason }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="tiedr__buttons">
                        {{-- Rendering an action directly doesn't apply its
                            visible()/authorize() - only Filament's own action
                            containers do - so filter here. --}}
                        @foreach (['reject' => $this->rejectAction, 'skip' => $this->skipAction, 'approve' => $this->approveAction] as $name => $action)
                            @if ($action->isVisible())
                                <div data-tiedr-action="{{ $name }}">{{ $action }}</div>
                            @endif
                        @endforeach
                    </div>

                    @if ($isPending && $canLike)
                        <div class="tiedr__keys">
                            <span>Swipe <strong>←</strong> nope · <strong>→</strong> tidy</span>
                            <span><kbd>R</kbd>nope</span>
                            <span><kbd>S</kbd>later</span>
                            <span><kbd>A</kbd>like</span>
                            @if (count($photos) > 1)
                                <span><kbd>←</kbd><kbd>→</kbd>photos</span>
                            @endif
                        </div>
                    @elseif (! $isPending)
                        <div class="tiedr__verdict">
                            <strong>{{ $check->verification_status === \App\Enums\CheckVerificationStatus::VERIFIED ? "Already a match 💚" : 'Already swiped left ✕' }}</strong>
                            Reviewed by {{ $check->reviewer?->name ?? 'the system (they ghosted the check-out)' }}{{ $check->reviewed_at ? ' on '.$check->reviewed_at->format('d/m/y H:i') : '' }}.
                            @if ($check->rejection_note)
                                <br><br>“{{ $check->rejection_note }}”
                            @endif
                        </div>
                    @endif
                </div>

                <div>
                    <section class="tiedr__panel">
                        <h3 class="tiedr__panel-title">About this date</h3>
                        <dl class="tiedr__dl">
                            <dt>Booking</dt>
                            <dd><x-filament::link :href="$this->getReservationUrl()">{{ $reservation->name }}</x-filament::link></dd>
                            <dt>When</dt>
                            <dd>
                                {{ $reservation->start_at->translatedFormat('D j M') }}
                                <small>{{ $reservation->start_at->format('H:i') }} – {{ $reservation->end_at->format('H:i') }}</small>
                            </dd>
                            <dt>Submitted</dt>
                            <dd>
                                {{ $check->occurred_at->format('H:i') }}
                                <small>{{ $this->getTimingLabel() }}</small>
                            </dd>
                        </dl>
                    </section>

                    <section class="tiedr__panel">
                        <h3 class="tiedr__panel-title">{{ $reservation->user->name }}'s dating history</h3>
                        <div class="tiedr__history">
                            <div @class(['is-good' => $history['verified'] > 0])><strong>{{ $history['verified'] }}</strong><span>matches</span></div>
                            <div @class(['is-bad' => $history['rejected'] > 0])><strong>{{ $history['rejected'] }}</strong><span>nopes</span></div>
                            <div @class(['is-bad' => $history['missed'] > 0])><strong>{{ $history['missed'] }}</strong><span>ghosted</span></div>
                        </div>
                    </section>

                    <section class="tiedr__panel">
                        <h3 class="tiedr__panel-title">Expectations vs. reality</h3>
                        <div class="tiedr__compare">
                            @if ($check->type === \App\Enums\CheckType::CHECK_OUT)
                                <div>
                                    <div class="tiedr__compare-head">
                                        <span>At check-in <small>{{ $counterpart ? '· '.$counterpart->occurred_at->format('H:i') : '' }}</small></span>
                                        @if ($counterpart)
                                            <x-filament::badge size="sm" :color="$statusColor($counterpart->verification_status)">{{ $counterpart->verification_status->getLabel() }}</x-filament::badge>
                                        @endif
                                    </div>
                                    @if ($counterpart && count($counterpart->photo_urls) > 0)
                                        <div class="tiedr__gallery">
                                            @foreach ($counterpart->photo_urls as $url)
                                                <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="Check-in photo" loading="lazy"></a>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="tiedr__none">No check-in photos for this reservation.</div>
                                    @endif
                                </div>
                            @endif

                            <div>
                                <div class="tiedr__compare-head"><span>How the room should look <small>· the profile pic</small></span></div>
                                @if (count($examples) > 0)
                                    <div class="tiedr__gallery">
                                        @foreach ($examples as $url)
                                            <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="Reference photo of {{ $reservation->room->name }}" loading="lazy"></a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="tiedr__none">This room has no reference photos yet - add them under the room's check-in examples.</div>
                                @endif
                            </div>
                        </div>
                    </section>

                    @if ($notes->isNotEmpty())
                        <section class="tiedr__panel">
                            <h3 class="tiedr__panel-title">Word on the street</h3>
                            <ul class="tiedr__notes">
                                @foreach ($notes as $note)
                                    <li @class(['is-public' => $note->visible_to_resident])>
                                        {{ $note->body }}
                                        <small>
                                            {{ $note->author?->name ?? 'Unknown' }} · {{ $note->created_at->format('d/m/y H:i') }}{{ $note->visible_to_resident ? ' · visible to resident' : '' }}
                                        </small>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
