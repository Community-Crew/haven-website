<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import S3Image from '@/components/S3Image.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { Organisation } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface OrganisationForm {
    _method: string;
    name: string;
    about: string;
    image: File | null;
}

const props = defineProps<{
    organisation: Organisation;
}>();

const form = useForm<OrganisationForm>({
    _method: 'PUT',
    name: props.organisation.name,
    about: props.organisation.about,
    image: null,
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
    return props.organisation.image_path ? 'Current Image' : 'Untitled.jpg';
});

const submit = () => {
    form.post(`/admin/organisations/${props.organisation.slug}`, {
        onSuccess: () => form.reset('image'),
    });
};

const detachUser = (userId: number) => {
    form.delete(
        `/admin/organisations/${props.organisation.slug}/users/${userId}`,
        {},
    );
};
</script>

<template>
    <AdminDashboardLayout>
        <div class="m-8">
            <ContentCard :title="organisation.name">
                <div class="grid grid-cols-2">
                    <S3Image class="max-w-xl" :src="organisation.image_url" />
                    <div class="grid-rows-2">
                        <form @submit.prevent="submit" class="space-y-5">
                            <!-- Name Input -->
                            <div class="group">
                                <label
                                    class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                                    >Name</label
                                >
                                <input
                                    type="text"
                                    v-model="form.name"
                                    class="w-full rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black placeholder-white/70 shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                                    placeholder="Room Name"
                                />
                                <div
                                    v-if="form.errors.name"
                                    class="mt-1 text-xs font-bold text-red-600"
                                >
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <!-- Description Input -->
                            <div class="group">
                                <label
                                    class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                                    >Description</label
                                >
                                <textarea
                                    v-model="form.about"
                                    rows="3"
                                    class="w-full resize-none rounded-lg border-none bg-white/25 px-4 py-3 text-haven-black placeholder-white/70 shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                                    placeholder="About"
                                ></textarea>
                            </div>

                            <div class="group">
                                <label
                                    class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                                    >Room Image</label
                                >
                                <div class="flex items-center gap-4 pt-1">
                                    <label
                                        class="cursor-pointer rounded-full bg-white px-5 py-1.5 text-sm font-bold text-blue-600 shadow-sm transition hover:bg-gray-50 active:scale-95"
                                    >
                                        Browse...
                                        <input
                                            type="file"
                                            class="hidden"
                                            accept="image/*"
                                            @change="handleFileChange"
                                        />
                                    </label>
                                    <span
                                        class="max-w-[200px] truncate text-sm font-medium text-gray-600"
                                    >
                                        {{ fileName }}
                                    </span>
                                </div>
                                <div
                                    v-if="form.errors.image"
                                    class="mt-1 text-xs font-bold text-red-600"
                                >
                                    {{ form.errors.image }}
                                </div>

                                <!-- Progress Bar -->
                                <div
                                    v-if="form.progress"
                                    class="mt-2 h-1.5 w-full rounded-full bg-white/30"
                                >
                                    <div
                                        class="h-1.5 rounded-full bg-blue-600 transition-all duration-300"
                                        :style="{
                                            width:
                                                form.progress.percentage + '%',
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <div class="group">
                                <label
                                    class="mb-1 block text-xs font-bold tracking-wide text-gray-700 uppercase"
                                    >Keycloak role</label
                                >
                                <div
                                    class="w-full resize-none rounded-lg border-none bg-white/30 px-4 py-3 text-haven-black placeholder-white/70 shadow-sm focus:bg-white/30 focus:ring-2 focus:ring-white/50"
                                >
                                    <span
                                        >organisation-<b>{{
                                            organisation.id
                                        }}</b
                                        >-member</span
                                    >
                                </div>
                                <div
                                    class="mt-1 text-xs font-bold text-gray-600"
                                >
                                    Add this role to an user in keycloak to
                                    become member.
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end pt-4">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-blue-700 focus:ring-4 focus:ring-blue-500/50 disabled:opacity-70"
                                >
                                    <span v-if="form.processing"
                                        >Saving...</span
                                    >
                                    <span v-else>Save Changes</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </ContentCard>
            <ContentCard class="mt-4" title="Members">
                <div class="grid grid-cols-2">
                    <div
                        v-for="member in organisation.users"
                        v-bind:key="member.id"
                        class="place grid w-full grid-cols-3 rounded-2xl bg-haven-white/75 p-3 text-haven-black shadow"
                    >
                        <p>{{ member.name }}</p>
                        <p>{{ member.email }}</p>
                        <button
                            class="flex items-center justify-self-end text-red-600"
                            @click="detachUser(member.id)"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                class="bi bi-trash-fill"
                                viewBox="0 0 16 16"
                            >
                                <path
                                    d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>

<style scoped></style>
