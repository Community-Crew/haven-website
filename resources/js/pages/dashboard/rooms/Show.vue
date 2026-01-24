<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import S3Image from '@/components/S3Image.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { Room, RoomStatus } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface RoomForm {
    _method: string;
    name: string;
    description: string;
    location: string;
    image: File | null;
    status: RoomStatus;
}

const props = defineProps<{
    room: Room;
    statusOptions: RoomStatus[];
}>();

const form = useForm<RoomForm>({
    _method: 'PUT',
    name: props.room.name,
    description: props.room.description,
    location: props.room.location,
    image: null,
    status: props.room.status.value,
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.image = target.files[0];
    }
};

const fileName = computed(() => {
    if (form.image) {
        return form.image.name;
    }
    return props.room.image_url ? 'Current Image' : 'Untitled.jpg';
});

const submit = () => {
    form.post(`/admin/rooms/${props.room.slug}`, {
        onSuccess: () => form.reset('image'),
    });
};
</script>

<template>
    <AdminDashboardLayout>
        <div class="mt-8 grid w-full grid-cols-2 gap-8">
            <ContentCard position="start">
                <div class="relative pb-2">
                    <S3Image
                        class="aspect-video rounded-2xl bg-background"
                        :src="room.image_url"
                    />
                    <div
                        class="absolute bottom-8 left-0 z-1 h-12 w-fit content-center rounded-r-2xl bg-haven-blue/85 pl-10"
                    >
                        <span
                            class="m-4 text-2xl font-extrabold text-haven-white"
                        >
                            {{ room.name }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-4 px-2 pt-2">
                    <p
                        class="line-clamp-3 flex-1 pl-2 text-lg leading-relaxed font-normal text-haven-black"
                    >
                        {{ room.description }}
                    </p>

                    <div class="shrink-0 pr-2">
                        <div
                            class="flex items-center justify-center rounded-full px-4 py-2 shadow-sm"
                            :class="room.status.background_color"
                        >
                            <span
                                :class="room.status.text_color"
                                class="text-sm font-bold tracking-wide uppercase"
                            >
                                {{ room.status.label }}
                            </span>
                        </div>
                    </div>
                </div>
            </ContentCard>

            <ContentCard position="end" title="Edit" class="overflow-hidden">
                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Name Input -->
                        <div class="group">
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-700">Name</label>
                            <input
                                type="text"
                                v-model="form.name"
                                class="w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black placeholder-white/70 shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                                placeholder="Room Name"
                            />
                            <div v-if="form.errors.name" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.name }}</div>
                        </div>

                        <!-- Description Input -->
                        <div class="group">
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-700">Description</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full resize-none rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black placeholder-white/70 shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                                placeholder="Description..."
                            ></textarea>
                        </div>

                        <!-- Location Input -->
                        <div class="group">
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-700">Location</label>
                            <input
                                type="text"
                                v-model="form.location"
                                class="w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black placeholder-white/70 shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                            />
                        </div>

                        <!-- Status Select -->
                        <div class="group">
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-700">Status</label>
                            <div class="relative">
                                <select
                                    v-model="form.status"
                                    class="w-full appearance-none rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                                >
                                    <option
                                        v-for="option in statusOptions"
                                        :key="option.value"
                                        :value="option.value"
                                        class="text-gray-900 bg-white"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-haven-black">
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                        </div>

                        <!-- Custom File Upload -->
                        <div class="group">
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-gray-700">Room Image</label>
                            <div class="flex items-center gap-4 pt-1">
                                <label class="cursor-pointer rounded-full bg-white px-5 py-1.5 text-sm font-bold text-blue-600 shadow-sm transition hover:bg-gray-50 active:scale-95">
                                    Browse...
                                    <input
                                        type="file"
                                        class="hidden"
                                        accept="image/*"
                                        @change="handleFileChange"
                                    />
                                </label>
                                <span class="text-sm font-medium text-gray-600 truncate max-w-[200px]">
                                    {{ fileName }}
                                </span>
                            </div>
                            <div v-if="form.errors.image" class="mt-1 text-xs text-red-600 font-bold">{{ form.errors.image }}</div>

                            <!-- Progress Bar -->
                            <div v-if="form.progress" class="mt-2 h-1.5 w-full rounded-full bg-white/30">
                                <div
                                    class="h-1.5 rounded-full bg-blue-600 transition-all duration-300"
                                    :style="{ width: form.progress.percentage + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-500/50 disabled:opacity-70"
                            >
                                <span v-if="form.processing">Saving...</span>
                                <span v-else>Save Changes</span>
                            </button>
                        </div>

                    </form>
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>

<style scoped></style>
