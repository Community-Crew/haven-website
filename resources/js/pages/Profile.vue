<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';

import { Unit } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const user = computed(() => page.props.auth.user);

const created_at = new Date(user.value.created_at).toISOString().split('T')[0];

const props = defineProps<{
    unit: Unit;
    groups: string;
}>();

</script>

<template>
    <PublicAppLayout>
        <div class="h-10" />
        <div class="mx-auto">
            <div class="mb-12 grid grid-cols-1 gap-6 md:grid-cols-2 md:gap-8">
                <ContentCard title="Profile" position="start">
                    <div class="text-haven-black">
                        <h2 class="mt-4 text-2xl font-bold">{{ user.name }}</h2>
                        <p class="text-haven-blue/70">{{ user.email }}</p>
                    </div>
                    <hr class="my-6 border-t border-haven-blue/20" />
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Status:</span>
                            <span
                                class="rounded-full bg-green-200 px-2.5 py-0.5 text-sm font-medium text-green-800"
                                >{{ 'Validated' }}</span
                            >
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Member Since:</span>
                            <span class="text-brand-dark-blue/80">{{
                                created_at
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Groups:</span>
                            <span class="text-brand-dark-blue/80">{{ groups }}</span>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard title="Unit" position="end">
                    <div class="text-haven-black">
                        <h2 class="mt-4 text-2xl font-bold">
                            {{ props.unit.building + " " + props.unit.floor.padStart(2, '0') + props.unit.unit.padStart(2, '0') + props.unit.subunit }}
                        </h2>
                    </div>
                    <hr class="my-6 border-t border-haven-blue/20" />
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Building:</span>
                            <span
                                class="rounded-full bg-green-200 px-2.5 py-0.5 text-sm font-medium text-green-800"
                                >{{ props.unit.building }}</span
                            >
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Floor:</span>
                            <span class="text-brand-dark-blue/80">{{ props.unit.floor.padStart(2, '0') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Unit:</span>
                            <span class="text-brand-dark-blue/80">{{ props.unit.unit.padStart(2, '0') }}</span>
                        </div>
                        <div
                            v-if="props.unit.subunit"
                            class="flex items-center justify-between"
                        >
                            <span class="font-semibold">Room:</span>
                            <span class="text-brand-dark-blue/80">{{ props.unit.subunit }}</span>
                        </div>
                    </div>
                </ContentCard>
            </div>
        </div>
    </PublicAppLayout>
</template>

<style scoped></style>
