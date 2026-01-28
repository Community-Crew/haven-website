<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { Organisation } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    organisations: Organisation[];
}>();
</script>

<template>
    <AdminDashboardLayout>
        <div class="m-8">
            <ContentCard title="Organisations">
                <div class="grid grid-cols-1 gap-x-8 gap-y-2">
                    <Link
                        v-for="organisation in organisations"
                        :key="organisation.slug"
                        :href="
                            route('admin.organisations.show', organisation.slug)
                        "
                        class="place w-full rounded-2xl bg-haven-white/75 p-3 shadow"
                    >
                        <div class="grid grid-cols-5">
                            <p
                                class="mr-2 line-clamp-1 truncate font-medium text-haven-black"
                            >
                                {{ organisation.name }}
                            </p>
                            <p
                                class="mr-2 line-clamp-1 truncate font-medium text-haven-black"
                            >
                                {{ organisation.about }}
                            </p>
                        </div>
                    </Link>
                </div>
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>

<style scoped></style>
