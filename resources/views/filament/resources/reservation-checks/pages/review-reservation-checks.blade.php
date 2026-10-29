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
        with html.dark overrides where the light/dark shade differs. --}}
    <style>
        .check-review { --cr-border: var(--gray-200); --cr-muted: var(--gray-500); --cr-surface: #fff; --cr-subtle: var(--gray-50); }
        html.dark .check-review { --cr-border: rgba(255, 255, 255, 0.1); --cr-muted: var(--gray-400); --cr-surface: var(--gray-900); --cr-subtle: rgba(255, 255, 255, 0.03); }

        .check-review__toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem 1.5rem; margin-bottom: 1.25rem; }
        .check-review__stat { display: flex; align-items: baseline; gap: 0.375rem; font-size: 0.875rem; color: var(--cr-muted); }
        .check-review__stat strong { font-size: 1.375rem; font-weight: 600; color: var(--gray-950); font-variant-numeric: tabular-nums; }
        html.dark .check-review__stat strong { color: #fff; }
        .check-review__stat--danger strong { color: var(--danger-600); }
        html.dark .check-review__stat--danger strong { color: var(--danger-400); }
        .check-review__toolbar-end { margin-left: auto; display: flex; align-items: center; gap: 1rem; font-size: 0.875rem; }
        .check-review__toggle { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; user-select: none; }

        .check-review__layout { display: grid; grid-template-columns: minmax(0, 1fr) 21rem; gap: 1.25rem; align-items: start; }
        @media (max-width: 1100px) { .check-review__layout { grid-template-columns: minmax(0, 1fr); } }

        .check-review__card { background: var(--cr-surface); border: 1px solid var(--cr-border); border-radius: 0.75rem; padding: 1.25rem; }
        .check-review__card + .check-review__card { margin-top: 1.25rem; }
        .check-review__card-title { margin: 0 0 0.75rem; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: var(--cr-muted); }

        .check-review__heading { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 0.75rem; margin-bottom: 1rem; }
        .check-review__heading h2 { margin: 0; font-size: 1.25rem; font-weight: 600; }
        .check-review__heading span.check-review__sub { color: var(--cr-muted); font-size: 0.875rem; }

        .check-review__flag { display: flex; gap: 0.625rem; align-items: flex-start; margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem;
            background: color-mix(in oklab, var(--danger-500) 10%, transparent); color: var(--danger-700); border: 1px solid color-mix(in oklab, var(--danger-500) 25%, transparent); }
        html.dark .check-review__flag { color: var(--danger-300); }
        .check-review__flag svg { width: 1.25rem; height: 1.25rem; flex: none; }

        .check-review__stage { position: relative; display: flex; align-items: center; justify-content: center; aspect-ratio: 4 / 3; max-height: 34rem; width: 100%;
            background: var(--gray-950); border-radius: 0.5rem; overflow: hidden; }
        .check-review__stage img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .check-review__stage a { display: contents; cursor: zoom-in; }
        .check-review__stage-count { position: absolute; right: 0.75rem; bottom: 0.75rem; padding: 0.125rem 0.5rem; border-radius: 999px; font-size: 0.75rem; color: #fff; background: rgba(0, 0, 0, 0.6); }

        .check-review__thumbs { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; }
        .check-review__thumb { width: 5rem; height: 5rem; padding: 0; border-radius: 0.375rem; overflow: hidden; border: 2px solid transparent; background: var(--cr-subtle); cursor: pointer; opacity: 0.7; transition: opacity 120ms, border-color 120ms; }
        .check-review__thumb:hover { opacity: 1; }
        .check-review__thumb[aria-current="true"] { opacity: 1; border-color: var(--primary-500); }
        .check-review__thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .check-review__compare { display: grid; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); gap: 1.25rem; }
        .check-review__compare-head { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; }
        .check-review__gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(6.5rem, 1fr)); gap: 0.5rem; }
        .check-review__gallery a { display: block; aspect-ratio: 1; border-radius: 0.375rem; overflow: hidden; background: var(--cr-subtle); }
        .check-review__gallery img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 160ms; }
        .check-review__gallery a:hover img { transform: scale(1.04); }
        .check-review__none { padding: 1rem; border: 1px dashed var(--cr-border); border-radius: 0.5rem; font-size: 0.875rem; color: var(--cr-muted); text-align: center; }

        .check-review__side { position: sticky; top: 5rem; }
        .check-review__actions { display: grid; gap: 0.5rem; }
        .check-review__actions > * { width: 100%; }
        .check-review__actions .fi-btn { width: 100%; justify-content: center; }
        .check-review__keys { display: flex; justify-content: center; gap: 1rem; margin-top: 0.75rem; font-size: 0.75rem; color: var(--cr-muted); }
        .check-review__keys kbd { display: inline-block; min-width: 1.25rem; padding: 0 0.3rem; margin-right: 0.25rem; border: 1px solid var(--cr-border); border-bottom-width: 2px; border-radius: 0.25rem; font-family: inherit; font-size: 0.6875rem; text-align: center; }

        .check-review__dl { display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem; margin: 0; font-size: 0.875rem; }
        .check-review__dl dt { color: var(--cr-muted); }
        .check-review__dl dd { margin: 0; min-width: 0; overflow-wrap: anywhere; }
        .check-review__dl dd small { display: block; color: var(--cr-muted); }

        .check-review__history { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; text-align: center; }
        .check-review__history div { padding: 0.5rem 0.25rem; border-radius: 0.5rem; background: var(--cr-subtle); }
        .check-review__history strong { display: block; font-size: 1.125rem; font-variant-numeric: tabular-nums; }
        .check-review__history span { font-size: 0.75rem; color: var(--cr-muted); }
        .check-review__history .is-bad strong { color: var(--danger-600); }
        html.dark .check-review__history .is-bad strong { color: var(--danger-400); }

        .check-review__notes { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.75rem; font-size: 0.875rem; }
        .check-review__notes li { padding-left: 0.75rem; border-left: 2px solid var(--cr-border); }
        .check-review__notes li.is-public { border-left-color: var(--primary-500); }
        .check-review__notes small { display: block; margin-top: 0.125rem; color: var(--cr-muted); font-size: 0.75rem; }
        .check-review__rejection { margin-top: 0.75rem; padding: 0.75rem; border-radius: 0.5rem; background: var(--cr-subtle); font-size: 0.875rem; white-space: pre-line; }
    </style>

    <div class="check-review">
        <div class="check-review__toolbar">
            <div class="check-review__stat"><strong>{{ $pending }}</strong> pending</div>
            <div @class(['check-review__stat', 'check-review__stat--danger' => $flagged > 0])><strong>{{ $flagged }}</strong> flagged</div>
            @if ($this->reviewedCount > 0)
                <div class="check-review__stat"><strong>{{ $this->reviewedCount }}</strong> reviewed this session</div>
            @endif

            <div class="check-review__toolbar-end">
                @if ($this->resetSkippedAction->isVisible())
                    {{ $this->resetSkippedAction }}
                @endif
                <label class="check-review__toggle">
                    <input type="checkbox" class="fi-checkbox-input" wire:model.live="flaggedOnly">
                    Flagged only
                </label>
            </div>
        </div>

        @if (! $check)
            <x-filament::empty-state
                icon="heroicon-o-check-badge"
                icon-color="success"
                :heading="$flaggedOnly ? 'No flagged photos left to review' : 'All caught up'"
                :description="count($skipped) > 0
                    ? count($skipped).' skipped this session - use \'Show skipped again\' to go back to them.'
                    : 'There are no check-in or check-out photos waiting for review.'"
            />
        @else
            @php
                $reservation = $check->reservation;
                $counterpart = $this->getCounterpart();
                $history = $this->getResidentHistory();
                $notes = $this->getNotes();
                $photos = $check->photo_urls;
                $examples = $reservation->room->checkin_example_photo_urls;
                $statusColor = match ($check->verification_status) {
                    \App\Enums\CheckVerificationStatus::VERIFIED => 'success',
                    \App\Enums\CheckVerificationStatus::REJECTED => 'danger',
                    default => 'warning',
                };
            @endphp

            {{-- wire:key per check so Alpine's selected-photo state resets
                when the queue advances instead of carrying an index over. --}}
            <div class="check-review__layout" wire:key="check-{{ $check->id }}">
                <div>
                    <section class="check-review__card">
                        <div class="check-review__heading">
                            <x-filament::badge :color="$check->type === \App\Enums\CheckType::CHECK_IN ? 'info' : 'primary'" size="lg">
                                {{ $check->type->getLabel() }}
                            </x-filament::badge>
                            <h2>{{ $reservation->room->name }}</h2>
                            <span class="check-review__sub">by {{ $reservation->user->name }}</span>
                            @if ($check->verification_status !== \App\Enums\CheckVerificationStatus::PENDING)
                                <x-filament::badge :color="$statusColor">{{ $check->verification_status->getLabel() }}</x-filament::badge>
                            @endif
                        </div>

                        @if ($check->flag_reason)
                            <div class="check-review__flag" role="note">
                                <x-filament::icon icon="heroicon-m-flag" />
                                <div><strong>Flagged:</strong> {{ $check->flag_reason }}</div>
                            </div>
                        @endif

                        @if (count($photos) > 0)
                            <div x-data="{ active: 0, photos: @js($photos) }"
                                 x-on:keydown.window.arrow-right="if (! $event.target.closest('input, textarea, [contenteditable]')) active = (active + 1) % photos.length"
                                 x-on:keydown.window.arrow-left="if (! $event.target.closest('input, textarea, [contenteditable]')) active = (active - 1 + photos.length) % photos.length">
                                <div class="check-review__stage">
                                    <a x-bind:href="photos[active]" target="_blank" rel="noopener" title="Open full size">
                                        <img x-bind:src="photos[active]" src="{{ $photos[0] }}" alt="Submitted {{ strtolower($check->type->getLabel()) }} photo">
                                    </a>
                                    @if (count($photos) > 1)
                                        <span class="check-review__stage-count" x-text="(active + 1) + ' / ' + photos.length">1 / {{ count($photos) }}</span>
                                    @endif
                                </div>

                                @if (count($photos) > 1)
                                    <div class="check-review__thumbs">
                                        @foreach ($photos as $i => $url)
                                            <button type="button" class="check-review__thumb" x-on:click="active = {{ $i }}" x-bind:aria-current="active === {{ $i }}" aria-label="Photo {{ $i + 1 }}">
                                                <img src="{{ $url }}" alt="" loading="lazy">
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="check-review__none">No photos were submitted with this {{ strtolower($check->type->getLabel()) }}.</div>
                        @endif
                    </section>

                    <section class="check-review__card">
                        <h3 class="check-review__card-title">Compare with</h3>
                        <div class="check-review__compare">
                            @if ($check->type === \App\Enums\CheckType::CHECK_OUT)
                                <div>
                                    <div class="check-review__compare-head">
                                        <span>At check-in{{ $counterpart ? ' · '.$counterpart->occurred_at->format('H:i') : '' }}</span>
                                        @if ($counterpart)
                                            <x-filament::badge size="sm" :color="match ($counterpart->verification_status) {
                                                \App\Enums\CheckVerificationStatus::VERIFIED => 'success',
                                                \App\Enums\CheckVerificationStatus::REJECTED => 'danger',
                                                default => 'warning',
                                            }">{{ $counterpart->verification_status->getLabel() }}</x-filament::badge>
                                        @endif
                                    </div>
                                    @if ($counterpart && count($counterpart->photo_urls) > 0)
                                        <div class="check-review__gallery">
                                            @foreach ($counterpart->photo_urls as $url)
                                                <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="Check-in photo" loading="lazy"></a>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="check-review__none">No check-in photos for this reservation.</div>
                                    @endif
                                </div>
                            @endif

                            <div>
                                <div class="check-review__compare-head"><span>How the room should look</span></div>
                                @if (count($examples) > 0)
                                    <div class="check-review__gallery">
                                        @foreach ($examples as $url)
                                            <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="Reference photo of {{ $reservation->room->name }}" loading="lazy"></a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="check-review__none">This room has no reference photos yet - add them under the room's check-in examples.</div>
                                @endif
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="check-review__side">
                    <section class="check-review__card">
                        <div class="check-review__actions">
                            {{-- Rendering an action directly doesn't apply its
                                visible()/authorize() - only Filament's own action
                                containers do - so filter here. --}}
                            @foreach ([$this->approveAction, $this->rejectAction, $this->skipAction] as $action)
                                @if ($action->isVisible())
                                    {{ $action }}
                                @endif
                            @endforeach
                        </div>
                        @if ($check->verification_status === \App\Enums\CheckVerificationStatus::PENDING && $this->approveAction->isVisible())
                            <div class="check-review__keys">
                                <span><kbd>A</kbd>approve</span>
                                <span><kbd>R</kbd>reject</span>
                                <span><kbd>S</kbd>skip</span>
                                @if (count($photos) > 1)
                                    <span><kbd>←</kbd><kbd>→</kbd>photos</span>
                                @endif
                            </div>
                        @else
                            <div class="check-review__rejection">
                                Reviewed by {{ $check->reviewer?->name ?? 'the system (missed check-out)' }}{{ $check->reviewed_at ? ' on '.$check->reviewed_at->format('d/m/y H:i') : '' }}.
                                @if ($check->rejection_note)
                                    <br><br>“{{ $check->rejection_note }}”
                                @endif
                            </div>
                        @endif
                    </section>

                    <section class="check-review__card">
                        <h3 class="check-review__card-title">Reservation</h3>
                        <dl class="check-review__dl">
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

                    <section class="check-review__card">
                        <h3 class="check-review__card-title">{{ $reservation->user->name }}'s history</h3>
                        <div class="check-review__history">
                            <div><strong>{{ $history['verified'] }}</strong><span>approved</span></div>
                            <div @class(['is-bad' => $history['rejected'] > 0])><strong>{{ $history['rejected'] }}</strong><span>rejected</span></div>
                            <div @class(['is-bad' => $history['missed'] > 0])><strong>{{ $history['missed'] }}</strong><span>missed</span></div>
                        </div>
                    </section>

                    @if ($notes->isNotEmpty())
                        <section class="check-review__card">
                            <h3 class="check-review__card-title">Notes on this reservation</h3>
                            <ul class="check-review__notes">
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
                </aside>
            </div>
        @endif
    </div>
</x-filament-panels::page>
