<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import InputLabel from '@/components/InputLabel.vue';
import InputError from '@/components/InputError.vue';

const form = useForm({
    registration_code: '',
});

const submit = () => {
    form.post('/auth/validate', {
        // Optional: Can add onFinish, onError, onSuccess callbacks
        onFinish: () => form.reset('registration_code'),
    });
};

</script>

<template>
    <Head title="Validate Account" />

    <form @submit.prevent="submit">
        <div>
            <InputLabel for="registration_code" value="Registration Code" />
            <TextInput
                id="registration_code"
                v-model="form.registration_code"
                type="text"
                class="mt-1 block w-full"
                required
                autofocus
            />
            <InputError class="mt-2" :message="form.errors.registration_code" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Validate Account
            </PrimaryButton>
        </div>
    </form>
</template>

<style scoped></style>
