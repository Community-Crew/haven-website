<script setup lang="ts">
import ContentCard from '@/components/ContentCard.vue';
import InputError from '@/components/InputError.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    registration_code: '',
});

const submit = () => {
    form.post('/auth/validate', {
        // Optional: Can add onFinish, onError, onSuccess callbacks
        onFinish: () => form.reset('registration_code'),
    });
};

const formatRegistrationCode = (event: Event) => {
    const input = event.target as HTMLInputElement;

    const value = input.value;
    let cleaned = value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();

    if (cleaned.length > 8) {
        cleaned = cleaned.substring(0, 8);
    }

    if (cleaned.length > 4) {
        form.registration_code = cleaned.slice(0, 4) + '-' + cleaned.slice(4);
    } else {
        form.registration_code = cleaned;
    }
};
</script>

<template>
    <Head title="Validate Account" />

    <div
        class="flex h-screen flex-col items-center justify-center bg-haven-white"
    >
        <ContentCard title="Validate Account">
            <div class="flex flex-col items-center">
                <div class="items-center text-haven-black font-semibold">
                    <p>
                        Use the code you have received by the Haven Community
                        Crew!
                    </p>
                </div>
                <form @submit.prevent="submit" class="p-8">
                    <div>
                        <TextInput
                            id="registration_code"
                            v-model="form.registration_code"
                            @input="formatRegistrationCode"
                            type="text"
                            class="mt-1 block h-20 w-[10ch] bg-haven-white text-center font-mono text-5xl font-bold text-haven-black"
                            required
                            autofocus
                            maxlength="9"
                            placeholder="AAAA-1234"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.registration_code"
                        />
                    </div>

                    <div class="mt-4 flex items-center justify-center">
                        <PrimaryButton
                            class="h-2xl bg-haven-blue "
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            <span class="text-haven-yellow">Validate</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </ContentCard>
    </div>
</template>

<style scoped></style>
