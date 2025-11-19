<script setup lang="ts">
import type { Unit } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    unit: Unit;
}>();

const formatNumber = (num: string) => num.toString().padStart(2, '0');
</script>

<template>
    <div class="relative pt-6">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-haven-blue text-white text-sm font-bold px-6 py-2 rounded-full shadow-md">
            <span>{{ unit.name }}</span>
        </div>

        <Link :href="route('admin.units.show', unit.id)">
        <div class="bg-haven-light-blue rounded-2xl shadow-lg p-6 text-haven-blue">
            <div class="space-y-3 pt-4">
                <div class="flex justify-between items-center">
                    <span class="font-medium text-gray-700">Building:</span>
                    <span class="font-bold">{{ unit.building }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium text-gray-700">Floor:</span>
                    <span class="font-bold px-3 py-1 bg-white/50 rounded-md">{{ formatNumber(unit.floor) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium text-gray-700">Unit:</span>
                    <span class="font-bold px-3 py-1 bg-white/50 rounded-md">{{ formatNumber(unit.unit) }}</span>
                </div>
                <div v-if="unit.subunit" class="flex justify-between items-center">
                    <span class="font-medium text-gray-700">Sub-Unit:</span>
                    <span class="font-bold px-3 py-1 bg-white/50 rounded-md">{{ unit.subunit }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium text-gray-700">Residents:</span>
                    <span class="font-bold text-lg">{{ unit.residents }} / {{ unit.max_residents }}</span>
                </div>
            </div>
        </div>
        </Link>
    </div>
</template>

