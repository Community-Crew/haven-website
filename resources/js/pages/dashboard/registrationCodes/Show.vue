<script setup lang="ts">
import { registrationCode, Unit } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    regCode: registrationCode;
    unit: Unit;
}>();

const unit_name: string = computed(() => props.unit.name).value;
console.log(unit_name);
const unit_split = unit_name.split(' ');
const building = unit_split[0];
let unit_formatted = "";

if (unit_split.length < 3) {
    unit_formatted = unit_split[1];
} else {
    unit_formatted = unit_split[1] + unit_split[2];
}
</script>

<template>
    <div
        id="label"
        class="flex h-[100px] w-[340px] items-center justify-between overflow-hidden border border-gray-300 bg-white p-2 text-black shadow-lg"
    >
        <div class="flex h-full w-full items-center justify-between px-0">
            <div
                class="font-mono text-[44px] font-bold tracking-wider text-gray-800"
            >
                {{ regCode.code }}
            </div>
            <div class="-rotate-90">
                <div class="flex flex-col items-center">
                    <div
                        class="text-l leading-tight font-semibold whitespace-nowrap text-gray-900"
                    >
                        {{ building }}
                    </div>
                    <div
                        class="text-l font-mono leading-tight whitespace-nowrap text-gray-700"
                    >
                        {{ unit_formatted }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@page {
    size: 90mm 29mm;
    margin: 0;
}

@media print {
    body > *:not(#label-print-area) {
        display: none;
    }

    #label-print-area,
    #label-print-area * {
        display: block;
        visibility: visible;
    }

    #label {
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
    }
}
</style>
