<script setup lang="ts">
import { registrationCode, Unit } from '@/types';

defineProps<{
    regCode: registrationCode;
    unit: Unit;
}>();
</script>

<template>
    <div
        id="label"
        class="flex h-[110px] w-[340px] items-center justify-between overflow-hidden border border-gray-300 bg-white p-2 text-black shadow-lg"
    >
        <div class="flex h-full w-full items-center justify-between px-3">
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
                        {{ unit.name.split(' ')[0] }}
                    </div>
                    <div
                        class="font-mono text-l leading-tight whitespace-nowrap text-gray-700"
                    >
                        {{ unit.name.split(' ')[1] + unit.name.split(' ')[2] }}
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

    #label-print-area, #label-print-area * {
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
