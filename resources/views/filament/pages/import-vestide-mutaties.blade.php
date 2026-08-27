<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit.prevent>
            {{ $this->form }}
        </form>
    </x-filament::section>

    @if ($rows)
        <x-filament::section heading="Preview">
            <div class="overflow-x-auto">
                <table class="w-full text-start">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400">Active from</th>
                            <th class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400">Address</th>
                            <th class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400">Unit</th>
                            <th class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400">Current resident(s)</th>
                            <th class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                {{ $applied ? 'New code' : 'Status' }}
                            </th>
                            @if ($applied)
                                <th class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400">Label printed</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($rows as $index => $row)
                            <tr>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">{{ $row['date'] ?? '—' }}</td>
                                <td class="px-3 py-2 text-sm">{{ $row['address'] }}</td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    @if ($row['unit_name'] ?? null)
                                        {{ $row['unit_name'] }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-sm">
                                    @if (filled($row['current_residents'] ?? null))
                                        {{ implode(', ', $row['current_residents']) }}
                                    @else
                                        <span class="text-gray-400">vacant</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-sm">
                                    @if ($row['error'] ?? null)
                                        <x-filament::badge color="danger">{{ $row['error'] }}</x-filament::badge>
                                    @elseif ($row['applied'] ?? false)
                                        <span class="font-mono">{{ $row['registration_code'] }}</span>
                                    @else
                                        <x-filament::badge color="success">Matched</x-filament::badge>
                                    @endif
                                </td>
                                @if ($applied)
                                    <td class="px-3 py-2 text-sm">
                                        @if ($row['applied'] ?? false)
                                            <x-filament::button
                                                size="xs"
                                                :color="$row['printed_at'] ? 'success' : 'gray'"
                                                wire:click="togglePrinted({{ $index }})"
                                            >
                                                {{ $row['printed_at'] ? 'Printed' : 'Mark printed' }}
                                            </x-filament::button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
