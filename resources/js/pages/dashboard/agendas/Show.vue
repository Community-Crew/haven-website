<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import Pagination from '@/components/Pagination/Pagination.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { Agenda, AgendaItem, Paginator } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    agenda: Agenda;
    items: Paginator<AgendaItem>;
}>();
</script>

<template>
    <AdminDashboardLayout>
        <div
            class="grid grid-cols-4 content-center justify-items-center gap-8 p-6"
        >
            <a
                :href="
                    route('admin.agendas.items.create', { agenda: agenda.slug })
                "
                class="flex h-12 w-full cursor-pointer items-center justify-center rounded-2xl bg-haven-blue shadow-2xl transition-transform hover:scale-105"
            >
                <span class="font-semibold text-white">New Agenda Item</span>
            </a>
            <div
                class="flex h-12 w-full cursor-pointer items-center justify-center rounded-2xl bg-haven-blue shadow-2xl transition-transform hover:scale-105"
            >
                <span class="font-semibold text-white">Visibility</span>
            </div>
            <div
                class="flex h-12 w-full cursor-pointer items-center justify-center rounded-2xl bg-haven-blue shadow-2xl transition-transform hover:scale-105"
            >
                <span class="font-semibold text-white">Change Name</span>
            </div>
            <div
                class="flex h-12 w-full cursor-pointer items-center justify-center rounded-2xl bg-haven-red shadow-2xl transition-transform hover:scale-105"
            >
                <span class="font-semibold text-white">Delete Agenda</span>
            </div>
        </div>
        <div class="mx-4">
            <ContentCard :title="'Agenda: ' + agenda.name">
                <div class="flex flex-col">
                    <div>// TODO: filters</div>
                    <div>
                        <div>
                            <div
                                v-if="items.data.length > 0"
                                class="grid grid-cols-1 gap-x-8 gap-y-2"
                            >
                                <Link
                                    class="place w-full rounded-2xl bg-haven-white/45 p-3 shadow"
                                    v-for="item in items.data" :key="item.id"
                                    :href="route('admin.agendas.items.show', [agenda.slug, item.slug])"
                                >
                                    <div class="grid grid-cols-4">
                                        <p
                                            class="mr-2 line-clamp-1 truncate font-medium text-haven-black"
                                        >
                                            {{ item.title }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ item.short_description }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ item.start_date }}
                                        </p>
                                    </div>
                                </Link>
                            </div>

                            <div v-else class="py-16 text-center">
                                <p class="font-semibold text-gray-500">
                                    No items found.
                                </p>
                            </div>
                        </div>
                    </div>
                    <Pagination :links="items.links" />
                </div>
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>

<style scoped></style>
