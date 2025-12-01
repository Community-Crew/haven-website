<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import AdminDashboardLayout from '@/layouts/AdminDashboardLayout.vue';
import { registrationCode, Unit, User } from '@/types';
import { getCurrentInstance, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';


const app = getCurrentInstance();
const route = app?.appContext.config.globalProperties.route;

const props = defineProps<{
    unit: Unit;
    users: User[];
    registrationCodes: registrationCode[];
}>();

const form = useForm({
    unit_id: props.unit.id,
});

const copiedCode = ref<string | null>(null);

const createCode = () => {
    form.post(route('admin.registration-codes.store'), {
        preserveScroll: true,
    });
};

const deleteCode = (codeId: number) => {
    if (confirm('Are you sure you want to delete this registration code?')) {
        if (!route) return;
        router.delete(route('admin.registration-codes.destroy', codeId), {
            preserveScroll: true,
        });
    }
};

const copyToClipboard = (code: string) => {
    navigator.clipboard.writeText(code);
    copiedCode.value = code;
    setTimeout(() => (copiedCode.value = null), 2000);
};
</script>

<template>
    <AdminDashboardLayout>
        <a :href="route('profile')">test</a>
        <div class="mt-4 grid grid-cols-2 gap-6">
            <ContentCard :title="unit.name" position="start">
                <div class="text-haven-black">
                    <h2 class="mt-4 text-2xl font-bold">
                        {{ props.unit.name }}
                    </h2>
                </div>
                <hr class="my-6 border-t border-haven-blue/20" />
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold">Building:</span>
                        <span
                            class="rounded-full bg-green-200 px-2.5 py-0.5 text-sm font-medium text-green-800"
                            >{{ props.unit.building }}</span
                        >
                    </div>
                    <div class="flex items-center justify-between">
                        <span v-if="unit.building == 'Terra'" class="font-semibold">House Number:</span>
                        <span v-else class="font-semibold">Floor:</span>
                        <span class="text-brand-dark-blue/80">{{
                                props.unit.floor.toString().padStart(2, '0')
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-semibold">Unit:</span>
                        <span v-if="unit.building == 'Terra'" class="text-brand-dark-blue/80">{{
                                props.unit.unit
                        }}</span>
                        <span v-else class="text-brand-dark-blue/80">{{
                                props.unit.unit.padStart(2, '0')
                            }}</span>
                    </div>
                    <div
                        v-if="props.unit.subunit"
                        class="flex items-center justify-between"
                    >
                        <span class="font-semibold">Room:</span>
                        <span class="text-brand-dark-blue/80">{{
                            props.unit.subunit
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-semibold">Residents:</span>
                        <span class="text-brand-dark-blue/80"
                            >{{ users.length }} / {{ unit.max_residents }}</span
                        >
                    </div>
                </div>
            </ContentCard>
            <ContentCard title="Users">
                <div v-if="users.length > 0" class="space-y-6">
                    <div v-for="user in users" :key="user.id">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-700">Name</div>
                                <div class="text-lg font-semibold">
                                    {{ user.name }}
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-700">Email</div>
                                <div class="text-lg font-semibold">
                                    {{ user.email }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Message to show when there are no users -->
                <div v-else class="py-8 text-center text-gray-600">
                    No residents found in this unit.
                </div>
                <hr class="my-6 border-t border-haven-blue/20" />
                <div v-if="registrationCodes.length > 0" class="space-y-3">
                    <div
                        v-for="regCode in registrationCodes"
                        :key="regCode.id"
                        class="flex items-center justify-between rounded-md bg-gray-50 p-3"
                    >
                        <div>
                        <span class="font-mono text-base font-bold text-gray-800">
                            {{ regCode.code }}
                        </span>
                        </div>

                        <!-- Right side: The action buttons -->
                        <div class="flex items-center space-x-6">
                            <button
                                @click="copyToClipboard(regCode.code)"
                                class="text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors"
                            >
                                {{ copiedCode === regCode.code ? 'Copied!' : 'Copy' }}
                            </button>
                            <a
                                :href="route('admin.registration-codes.show', regCode.id)"
                                class="text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors"
                            >
                                Print
                            </a>

                            <button
                                @click="deleteCode(regCode.id)"
                                class="text-sm font-semibold text-red-600 hover:text-red-800 transition-colors"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="py-6 text-center text-gray-500">
                    No active registration codes.
                </div>

                <hr class="my-6 border-t border-haven-blue/20" />

                <form
                    @submit.prevent="createCode"
                    class="flex items-center justify-between"
                >
                    <p class="text-sm text-gray-600">
                        Generate a registration code for this unit.
                    </p>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:bg-blue-300"
                    >
                        {{
                            form.processing
                                ? 'Generating...'
                                : 'Generate New Code'
                        }}
                    </button>
                </form>
            </ContentCard>
        </div>
    </AdminDashboardLayout>
</template>

<style scoped></style>
