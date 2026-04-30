<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import FormLayout from '@/components/Forms/FormLayout.vue';
import SelectInput from '@/components/Forms/SelectInput.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { ReservationPolicy } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    reservationPolicy: ReservationPolicy;
}>();

const daysOfWeek = [
    { id: 0, name: 'Sunday' },
    { id: 1, name: 'Monday' },
    { id: 2, name: 'Tuesday' },
    { id: 3, name: 'Wednesday' },
    { id: 4, name: 'Thursday' },
    { id: 5, name: 'Friday' },
    { id: 6, name: 'Saturday' },
    { id: 8, name: 'All Week' },
];

const generateTimeOptions = () => {
    const options = [];
    for (let h = 0; h <= 24; h++) {
        const hourLabel = String(h).padStart(2, '0');
        options.push({ id: `${hourLabel}:00`, name: `${hourLabel}:00` });
        if (h < 24) {
            options.push({ id: `${hourLabel}:30`, name: `${hourLabel}:30` });
        }
    }
    return options;
};

const timeOptions = generateTimeOptions();

const form = useForm({
    start_time: '',
    end_time: '',
    day_of_week: 1,
    reservation_policy_id: props.reservationPolicy.id,
});

const filteredEndTimeOptions = computed(() => {
    return timeOptions.filter((time) => time.id > form.start_time);
});
</script>

<template>
    <AdminDashboardLayout>
        <div class="m-6">
            <ContentCard
                :title="'Create rule for: ' + reservationPolicy.role_name"
            >
                <FormLayout
                    :form="form"
                    :action="
                        route(
                            'admin.reservation-policies.entries.store',
                            props.reservationPolicy.id,
                        )
                    "
                    method="post"
                >
                    <SelectInput
                        label="Day of week"
                        v-model="form.day_of_week"
                        :error="form.errors.day_of_week"
                    >
                        <option
                            v-for="item in daysOfWeek"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ item.name }}
                        </option>
                    </SelectInput>

                    <div class="grid grid-cols-2 gap-4">
                        <SelectInput
                            v-model="form.start_time"
                            label="Start Time"
                            :error="form.errors.start_time"
                        >
                            <option
                                v-for="time in timeOptions"
                                :key="time.id"
                                :value="time.id"
                            >
                                {{ time.name }}
                            </option>
                        </SelectInput>

                        <SelectInput
                            v-model="form.end_time"
                            label="End Time"
                            :error="form.errors.end_time"
                        >
                            <option
                                v-for="time in filteredEndTimeOptions"
                                :key="time.id"
                                :value="time.id"
                            >
                                {{ time.name }}
                            </option>
                        </SelectInput>
                    </div>
                </FormLayout>
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>

<style scoped></style>
