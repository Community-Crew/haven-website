<script setup lang="ts">
import { computed } from 'vue';

interface TimeRange {
    start: string;
    end: string;
}

type SlotTuple = [string, string];

const CONFIG = {
    startHour: 8,
    slotCount: 32,
    defaultClass: 'bg-red-500',
    defaultText: '',
    activeClass: 'bg-green-500',
    activeText: '',
};

const props = defineProps<{
    policy: TimeRange[][];
    maxDaysInAdvance: number[];
}>();

const dayOfWeek = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

const rounded = (index: number, length: number) => {
    if (index == length - 1) {
        return 'rounded-b-2xl';
    } else {
        return '';
    }
};

const getIndexFromTime = (timeStr: string): number => {
    const [hours, minutes] = timeStr.split(':').map(Number);
    const hourDiff = hours - CONFIG.startHour;
    const minuteOffset = minutes >= 30 ? 1 : 0;

    return hourDiff * 2 + minuteOffset;
};

const timeLabels = computed(() => {
    return Array.from({ length: CONFIG.slotCount }, (_, index) => {
        const totalMinutes = CONFIG.startHour * 60 + index * 30;
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        const displayH = (hours % 24).toString().padStart(2, '0');
        const displayM = minutes.toString().padStart(2, '0');
        return `${displayH}:${displayM}`;
    });
});

const processedGrid = computed(() => {
    if (!props.policy || props.policy.length === 0) return [];

    // --- THE FIX ---
    // The Input is [Sun, Mon, Tue, Wed, Thu, Fri, Sat]
    // We want [Mon, Tue, Wed, Thu, Fri, Sat, Sun] to match your labels.
    // So we slice 1..end (Mon-Sat) and add index 0 (Sun) to the end.
    const reorderedPolicy = [...props.policy.slice(1), props.policy[0]];

    return reorderedPolicy.map((dayEvents) => {
        const slots: SlotTuple[] = Array.from(
            { length: CONFIG.slotCount },
            () => [CONFIG.defaultClass, CONFIG.defaultText],
        );

        // Safety check if dayEvents is null/undefined
        if (dayEvents) {
            dayEvents.forEach((event) => {
                const startIndex = getIndexFromTime(event.start);
                const endIndex = getIndexFromTime(event.end);

                const safeStart = Math.max(0, startIndex);
                const safeEnd = Math.min(CONFIG.slotCount, endIndex);

                for (let i = safeStart; i < safeEnd; i++) {
                    slots[i] = [CONFIG.activeClass, CONFIG.activeText];
                }
            });
        }
        return slots;
    });
});

const getRangeString = (days: number[]) => {
    const daysSet = [...new Set(days)];
    if (daysSet.length == 0) {
        return 'Unlimited'; // Changed from 'GeEn?!' to something cleaner
    } else if (daysSet.length == 1) {
        return `${daysSet[0]}`;
    }
    const max = Math.max(...daysSet);
    const min = Math.min(...daysSet);

    return `${min}-${max}`;
}
</script>

<template>
    <div class="flex flex-col h-full">
        <div
            class="grid h-full grid-cols-8 gap-2 overflow-y-auto scroll-smooth"
        >
            <div>
                <div
                    class="sticky top-0 h-8 border-b-2 bg-haven-light-blue p-2 text-center text-xs font-semibold text-haven-black"
                >
                    <div
                        class="absolute bottom-0 left-0 z-0 h-full w-full rounded-t-2xl bg-white/30"
                    />
                    Time
                </div>
                <div
                    v-for="(time, timeIndex) in timeLabels"
                    v-bind:key="timeIndex"
                    class="h-8 bg-white/30 p-2 text-center text-xs text-haven-black"
                    :class="rounded(timeIndex, timeLabels.length)"
                >
                    {{ time }}
                </div>
            </div>
            <div
                v-for="(day, dayIndex) in processedGrid"
                v-bind:key="dayIndex"
                class=""
            >
                <div
                    class="sticky top-0 h-8 border-b-2 bg-haven-light-blue p-2 text-center text-xs font-semibold text-haven-black"
                >
                    <div
                        class="absolute bottom-0 left-0 z-0 h-full w-full rounded-t-2xl bg-white/30"
                    />
                    {{ dayOfWeek[dayIndex] }}
                </div>
                <div
                    v-for="(slot, slotIndex) in day"
                    v-bind:key="slotIndex"
                    :class="slot[0] + ' ' + rounded(slotIndex, day.length)"
                    class="h-8 p-1 text-center"
                >
                    {{ slot[1] }}
                </div>
            </div>
        </div>
        <div>
            <hr class="my-6 border-t border-haven-blue/20" />
            <div class="flex items-center justify-end h-10 text-xl text-haven-black font-semibold">
                <p>Max days in advance: </p> <p class="pl-1 font-semibold text-haven-black"> {{ getRangeString(maxDaysInAdvance) }} days</p>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
