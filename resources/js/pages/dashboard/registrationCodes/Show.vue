<script setup lang="ts">
import RegistrationCodeLabel from '@/components/Units/RegistrationCodeLabel.vue';
import { registrationCode, Unit } from '@/types';

defineProps<{
    regCode: registrationCode;
    unit: Unit;
}>();
</script>

<template>
    <RegistrationCodeLabel :reg-code="regCode" :unit="unit" />
</template>

<style scoped></style>
