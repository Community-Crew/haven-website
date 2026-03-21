<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import type { Agenda, AgendaItem, Organisation } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { defineAsyncComponent, ref } from 'vue';

const TextEditor = defineAsyncComponent(
    () => import('@/components/TextEditor.vue'),
);
const props = defineProps<{
    agenda: Agenda;
    agendaItem: AgendaItem;
    organisations: Organisation[];
}>();

const isLoading = ref(false);

const form = useForm({
    title: props.agendaItem.title,
    description: props.agendaItem.description,
    shortDescription: props.agendaItem.short_description,
    image: null as File | null,
    start_date: props.agendaItem.start_date,
    end_date: props.agendaItem.end_date,
    organisation: props.agendaItem.organisation.id,
    agenda_id: props.agenda.id,
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.image = target.files[0];
    }
};

const submitForm = async () => {
    isLoading.value = true;

    try {
        form.put(
            `/admin/agendas/${props.agenda.slug}/items/${props.agendaItem.slug}`,
            {},
        );
    } catch (error) {
        console.error('Error submitting data:', error);
    } finally {
        isLoading.value = false;
    }
};

</script>

<template>
    <AdminDashboardLayout>
        <form
            @submit.prevent="submitForm"
            class="mt-8 grid grid-cols-1 items-start gap-8 lg:grid-cols-2"
        >
            <ContentCard
                :title="`Edit: ${props.agendaItem.title}`"
                position="start"
            >
                <div class="flex flex-col gap-4">
                    <div class="group">
                        <label
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >Title</label
                        >
                        <input
                            type="text"
                            v-model="form.title"
                            class="placeholder-gray/70 w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            placeholder="Game night"
                        />
                    </div>
                    <div class="group">
                        <label
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >Start</label
                        >
                        <input
                            v-model="form.start_date"
                            type="datetime-local"
                            :min="new Date().toISOString().slice(0, 17)"
                            class="placeholder-gray/70 w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                        />
                    </div>
                    <div class="group">
                        <label
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >end</label
                        >
                        <input
                            v-model="form.end_date"
                            type="datetime-local"
                            :min="new Date().toISOString().slice(0, 17)"
                            class="placeholder-gray/70 w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                        />
                    </div>
                    <div class="group">
                        <label
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >organisation</label
                        >
                        <select
                            class="placeholder-gray/70 w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            v-model="form.organisation"
                        >
                            <option
                                v-for="organisation in organisations"
                                v-bind:key="organisation.id"
                                :value="organisation.id"
                            >
                                {{ organisation.name }}
                            </option>
                        </select>
                    </div>
                    <div class="group">
                        <label
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >cover image</label
                        >
                        <input
                            type="file"
                            accept="image/*"
                            @change="handleFileChange"
                            class="placeholder-gray/70 w-1/2 rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                        />
                    </div>
                </div>
            </ContentCard>

            <ContentCard title="Description" position="end" class="h-full">
                <div class="grid h-full grid-cols-1 gap-4">
                    <TextEditor
                        class="h-full"
                        v-model="form.description"
                        placeholder="This is an epic agenda item!"
                    />
                    <div class="group">
                        <label
                            class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                            >Short Description</label
                        >
                        <input
                            type="text"
                            v-model="form.shortDescription"
                            class="placeholder-gray/70 w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            placeholder="This is an epic agenda item! But shorter."
                        />
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="isLoading"
                            class="h-fit self-end rounded-lg px-6 py-2.5 font-medium text-white transition-colors focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                            :class="[
                                isLoading
                                    ? 'cursor-not-allowed bg-blue-300'
                                    : 'bg-blue-600 hover:bg-blue-700',
                            ]"
                        >
                            <span v-if="isLoading">Saving...</span>
                            <span v-else>Save Item</span>
                        </button>
                    </div>
                </div>
            </ContentCard>
        </form>
    </AdminDashboardLayout>
</template>
