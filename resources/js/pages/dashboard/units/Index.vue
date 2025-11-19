<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import Pagination from '@/components/Pagination/Pagination.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { Filters, Paginator, Unit } from '@/types';
import UnitCard from '@/components/Units/UnitCard.vue';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';

const props = defineProps<{
    units: Paginator<Unit>;
    filters: Filters;
    buildings: string[];
    floors: string[];
}>();

const form = ref({
    building: props.filters.building ?? null,
    floor: props.filters.floor ?? null,
});

watch(
    form,
    debounce(() => {
        router.get('/admin/units', form.value, {
            preserveState: true,
            replace: true,
        });
    }, 300),
    { deep: true },
);

const reset = () => {
    form.value = {
        building: null,
        floor: null,
    }
}
</script>

<template>
    <AdminDashboardLayout>
        <ContentCard class="m-6" title="Units">
            <div class="flex flex-col">
                <div class="relative">
                    <div class="flex items-center justify-between ml-8 bg-haven-white p-4 rounded-md shadow-sm">
                        <div class="flex w-full max-w-md items-center text-haven-black">
                            <select v-model="form.building" class="form-select">
                                <option :value="null">All buildings</option>
                                <option v-for="building in buildings" :key="building" :value="building">
                                    {{ building }}
                                </option>
                            </select>
                            <select v-model="form.floor" class="form-select">
                                <option :value="null">All floors</option>
                                <option v-for="floor in floors" :key="floor" :value="floor">
                                    {{ floor }}
                                </option>
                            </select>
                        </div>
                        <button
                            class="ml-3 text-sm text-gray-500 hover:text-gray-700"
                            type="button"
                            @click="reset"
                        >
                            Reset
                        </button>
                    </div>
                    <div
                        class="mt-8 rounded-3xl border-2 border-haven-light-blue bg-haven-light-blue/50 p-8 pt-16"
                    >
                        <div
                            v-if="units.data.length > 0"
                            class="grid grid-cols-1 gap-x-8 gap-y-12 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5"
                        >
                            <UnitCard
                                v-for="unit in units.data"
                                :key="unit.id"
                                :unit="unit"
                            />
                        </div>

                        <div v-else class="py-16 text-center">
                            <p class="font-semibold text-gray-500">
                                No units found.
                            </p>
                        </div>
                    </div>
                </div>
                <Pagination class="justify-center" :links="units.links" />
            </div>
        </ContentCard>
    </AdminDashboardLayout>
</template>

<style scoped></style>
