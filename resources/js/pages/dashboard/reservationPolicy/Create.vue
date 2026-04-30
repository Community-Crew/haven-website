<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import FormLayout from '@/components/Forms/FormLayout.vue';
import MultiSelect from '@/components/Forms/MultiSelect.vue';
import TextInput from '@/components/Forms/TextInput.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { Room } from '@/types';
import { useForm } from '@inertiajs/vue3';

defineProps<{
    rooms: Room[];
}>();

const form = useForm<{
    role_name: string;
    max_days_in_advance: number;
    room_ids: [];
}>({
    role_name:  '',
    max_days_in_advance: 14,
    room_ids: []
});
</script>

<template>
    <AdminDashboardLayout>
        <div class="p-6">
            <ContentCard>
                <FormLayout
                    :form="form"
                    :action="route('admin.reservation-policies.store')"
                    method="post"
                >
                    <TextInput
                        v-model="form.role_name"
                        label="Name"
                        placeholder="default-policy"
                        :error="form.errors.role_name"
                    />

                    <TextInput
                        v-model="form.max_days_in_advance"
                        label="Days in advance"
                        placeholder="14"
                        type="number"
                        :error="form.errors.max_days_in_advance"
                    />

                    <MultiSelect
                        v-model="form.room_ids"
                        label="Rooms"
                        :options="rooms"
                        :error="form.errors.room_ids"
                    />
                </FormLayout>
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>

<style scoped></style>
