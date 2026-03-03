<script setup lang="ts">
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { ref, watch } from 'vue';

// 1. Types
const props = withDefaults(
    defineProps<{
        modelValue?: string;
        placeholder?: string;
        readOnly?: boolean;
    }>(),
    {
        modelValue: '',
        placeholder: '',
        readOnly: false,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

// 2. Local State
const editorContent = ref(props.modelValue);

// 3. Watch for external changes (Parent -> Child)
// If the parent updates modelValue (e.g., API load), update the local editor
watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue !== editorContent.value) {
            editorContent.value = newValue;
        }
    },
);

// 4. Handle internal changes (Child -> Parent)
const onUpdate = () => {
    emit('update:modelValue', editorContent.value);
};

</script>

<template>
    <div
        class="quill-wrapper overflow-hidden rounded-2xl border border-gray-200 bg-haven-white text-haven-black"
    >
        <QuillEditor
            v-model:content="editorContent"
            contentType="html"
            theme="snow"
            toolbar="full"
            :placeholder="placeholder"
            :read-only="readOnly"
            @update:content="onUpdate"
        />
    </div>
</template>

<style scoped></style>
