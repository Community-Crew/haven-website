<script setup lang="ts">
import ObjectListEntry from '@/components/Layouts/List/Entries/ObjectListEntry.vue';
import ObjectListLayout from '@/components/Layouts/List/ObjectListLayout.vue';
import { ReservationPolicy } from '@/types';

defineProps<{
    policy: ReservationPolicy[];
}>();
</script>

<template>
    <div class="w-full">
        <ObjectListLayout class="text-haven-black border-none shadow-none">
            <ObjectListEntry
                class="flex items-center text-haven-white font-bold py-3 px-4"
                color="bg-haven-blue"
            >
                <div class="w-20 shrink-0">Day</div>
                <div class="flex-1">Timeslots <span class="text-xs font-normal opacity-70">(max. advance)</span></div>
            </ObjectListEntry>

            <ObjectListEntry
                v-for="day in policy"
                :key="day.date"
                class="flex items-center py-4 px-4 border-b border-gray-100 last:border-0"
                :color="day.is_today ? 'bg-blue-50/50' : (day.is_past ? 'bg-gray-100 opacity-60' : 'bg-haven-white')"
            >
                <div class="w-20 shrink-0 font-bold text-haven-black">
                    {{ day.day_name.substring(0, 3) }}
                </div>

                <div class="flex flex-wrap gap-2 flex-1">
                    <div
                        v-for="slot in day.entries"
                        :key="slot.start"
                        class="flex items-center bg-white border border-gray-200 rounded-lg px-3 py-1.5 shadow-sm"
                    >
                        <span class="text-sm font-medium">{{ slot.label }}</span>
                        <span class="ml-2 text-[10px] font-bold bg-blue-100 text-haven-blue px-1.5 py-0.5 rounded">
                            {{ slot.max_days }}d
                        </span>
                    </div>
                </div>
            </ObjectListEntry>
        </ObjectListLayout>
    </div>
</template>
