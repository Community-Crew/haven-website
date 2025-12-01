<script setup lang="ts">
import RegistrationCodeLabel from '@/components/Units/RegistrationCodeLabel.vue';
import { Unit } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    units: Unit[];
}>();

const flatLabelData = computed(() => {
    return props.units.flatMap(unit =>
        (unit.registration_codes ?? []).map(regCode => ({
            unit: unit,
            regCode: regCode,
        }))
    );
});
</script>

<template>
    <div
        id="label"
        class="flex h-[110px] w-[340px] items-center justify-between overflow-hidden border border-gray-300 bg-white p-2 text-black shadow-lg"
    >
        <div class="flex h-full w-full items-center justify-between px-0">
            <div
                class="font-mono text-[44px] font-bold tracking-wider text-gray-800"
            >
                {{ units[0].building + ' ' + units[0].floor }}
            </div>
        </div>
    </div>
    <RegistrationCodeLabel
        v-for="label in flatLabelData"
        :key="label.regCode.id"
        :regCode="label.regCode"
        :unit="label.unit"
    />
</template>

<style scoped></style>
