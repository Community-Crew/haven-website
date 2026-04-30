<script setup lang="ts">
const model = defineModel<any[]>({ default: () => [] });

defineProps<{
    label: string;
    options: { id: any; name: string }[];
    error?: string;
}>();

const toggleSelection = (id: any) => {
    if (model.value.includes(id)) {
        model.value = model.value.filter((item) => item !== id);
    } else {
        model.value = [...model.value, id];
    }
};
</script>

<template>
    <div class="flex flex-col gap-2">
        <label class="text-xs font-bold tracking-wide text-gray-700 uppercase">
            {{ label }}
        </label>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <button
                v-for="option in options"
                :key="option.id"
                type="button"
                @click="toggleSelection(option.id)"
                :class="[
                    'flex items-center justify-between rounded-lg border px-4 py-3 text-sm transition-all',
                    model.includes(option.id)
                        ? 'border-blue-500 bg-blue-50 text-blue-700 ring-2 ring-blue-500/20'
                        : 'border-gray-200 bg-white/25 text-gray-600 hover:bg-white/40',
                ]"
            >
                {{ option.name }}

                <span v-if="model.includes(option.id)" class="text-blue-600">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </span>
            </button>
        </div>

        <span v-if="error" class="text-xs text-red-500">{{ error }}</span>
    </div>
</template>

<style scoped></style>
