<script setup lang="ts">
import LinkButton from '@/components/Buttons/LinkButton.vue';
import ContentCard from '@/components/ContentCard.vue';
import GridContainer from '@/components/Layouts/GridContainer.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { User } from '@/types';

defineProps<{ target_user: User }>();
</script>

<template>
    <AdminDashboardLayout>
        <div>
            <GridContainer cols="4">
                <LinkButton name="Back" :link="route('admin.users.index')" />
            </GridContainer>
            <ContentCard
                position="middle"
                class="mx-6"
                :title="'edit: ' + target_user.name"
            >
                Editing is only possible through keycloak.
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>

<style scoped></style>
