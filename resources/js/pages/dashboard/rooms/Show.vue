<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import S3Image from '@/components/S3Image.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { Room, RoomStatus } from '@/types';
import { useForm } from '@inertiajs/vue3';

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
}>();

const form = useForm<RoomForm>({
    _method: 'PUT',
    name: props.room.name,
    description: props.room.description,
    location: props.room.location,
    image: null,
    status: props.room.status,
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

            <ContentCard position="end" title="Edit">
                <div>
                    <form @submit.prevent="submit" class="space-y-4 p-4">

                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" v-model="form.name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                            <div v-if="form.errors.name" class="text-red-500 text-sm">{{ form.errors.name }}</div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        </div>

                        <!-- Location -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <input type="text" v-model="form.location" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="reserved">Reserved</option>
                                <option value="maintenance">Out of Order</option>
                                <option value="cleaning">Deep Cleaning</option>
                            </select>
                        </div>


                        <!-- Image Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Room Image</label>
                            <input
                                type="file"
                                @input="form.image = ($event.target as HTMLInputElement).files?.[0] || null"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <div v-if="form.errors.image" class="text-red-500 text-sm">{{ form.errors.image }}</div>
                            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="w-full mt-2"></progress>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Save Changes
                            </button>
                        </div>

                    </form>
                </div>
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>

<style scoped></style>
