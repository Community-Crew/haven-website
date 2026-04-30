<script setup lang="ts">
import LinkButton from '@/components/Buttons/LinkButton.vue';
import ContentCard from '@/components/ContentCard.vue';
import GridContainer from '@/components/Layouts/GridContainer.vue';
import ObjectListEntry from '@/components/Layouts/List/Entries/ObjectListEntry.vue';
import ObjectListLayout from '@/components/Layouts/List/ObjectListLayout.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { Paginator, ReservationPolicy } from '@/types';

defineProps<{
    policies: Paginator<ReservationPolicy>;
}>();
</script>

<template>
    <AdminDashboardLayout>
        <GridContainer cols="3">
            <LinkButton
                :link="route('admin.reservation-policies.create')"
                name="Create Policy"
            ></LinkButton>
        </GridContainer>
        <ContentCard class="p-6" title="Reservation Policies">
            <ObjectListLayout>
                <ObjectListEntry
                    v-for="reservationPolicy in policies.data"
                    v-bind:key="reservationPolicy.id"
                    class="grid grid-cols-16 gap-2 text-haven-black"
                >
                    <span class="font-bold col-span-3">{{
                        reservationPolicy.role_name
                    }}</span>
                    <span class="col-span-3"><b>Max days in advance:</b>
                        {{ reservationPolicy.max_days_in_advance }}
                    </span>
                    <div class="col-span-9">
                        <span class="font-bold">Rooms: </span>
                        <span>
                            {{
                                reservationPolicy.rooms.length > 0
                                    ? reservationPolicy.rooms.join(', ')
                                    : 'No rooms assigned'
                            }}
                        </span>
                    </div>
                    <div class="place-self-end">
                        <a
                            :href="
                                route(
                                    'admin.reservation-policies.edit',
                                    reservationPolicy.id,
                                )
                            "
                        >
                            Edit
                        </a>
                    </div>
                </ObjectListEntry>
            </ObjectListLayout>
        </ContentCard>
    </AdminDashboardLayout>
</template>

<style scoped></style>
