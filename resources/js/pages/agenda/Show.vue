<script setup lang="ts">
import AgendaCard from '@/components/Agenda/AgendaCard.vue';
import ContentCard from '@/components/ContentCard.vue';
import Pagination from '@/components/Pagination/Pagination.vue';
import PublicAppLayout from '@/layouts/PublicAppLayout.vue';
import { Agenda, AgendaItem, Paginator } from '@/types';

defineProps<{
    agenda: Agenda;
    agendaItems: Paginator<AgendaItem>;
}>();
</script>

<template>
    <PublicAppLayout>
        <div class="m-8">
            <ContentCard :title="agenda.name">
                <div class="mb-10 flex text-lg font-medium text-haven-black">
                    <p>
                        {{agenda.description}}
                    </p>
                </div>

                <div
                    class="grid grid-cols-1 gap-6 md:grid-cols-2"
                >
                    <AgendaCard
                        v-for="item in agendaItems.data"
                        :key="item.id"
                        :item="item"
                        class="transition-shadow duration-200 hover:shadow-md"
                    />
                </div>

                <div
                    v-if="agendaItems.data.length === 0"
                    class="py-12 text-center"
                >
                    <p class="text-gray-500 italic">
                        No items found in this agenda.
                    </p>
                </div>

                <div class="mt-10 flex justify-center">
                    <Pagination :links="agendaItems.links" />
                </div>
            </ContentCard>
        </div>
    </PublicAppLayout>
</template>
