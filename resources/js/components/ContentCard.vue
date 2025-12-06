<script setup lang="ts">
import { computed } from 'vue';

type CardPosition = 'start' | 'end' | 'middle' | 'full';
interface Props {
    title?: string;
    position?: CardPosition;
}

const props = withDefaults(defineProps<Props>(), {
    position: 'middle',
});

// right rounding.
const cardBodyClasses = computed<string>(() => {
    const baseClasses = 'bg-haven-light-blue p-6 h-full ' + (props.title ? 'pt-12' : 'p-6');

    let roundingClasses = 'rounded-none';

    switch (props.position) {
        case 'start':
            roundingClasses += ' md:rounded-r-3xl';
            break;
        case 'end':
            roundingClasses += ' md:rounded-l-3xl';
            break;
        case 'full':
            roundingClasses += ' md:rounded-none';
            break;
        case 'middle':
            roundingClasses += ' md:rounded-3xl';
            break;
    }
    return `${baseClasses} ${roundingClasses}`;
});

//Pseudo random wobble generator by Tim Kolijn (PRWGBTK)

//Seed from title
function createSeededRandom(seed: string) {
    let h = 1779033703 ^ seed.length;
    for (let i = 0; i < seed.length; i++) {
        h = Math.imul(h ^ seed.charCodeAt(i), 3432918353);
        h = (h << 13) | (h >>> 19);
    }

    return function () {
        h = Math.imul(h ^ (h >>> 16), 2246822507);
        h = Math.imul(h ^ (h >>> 13), 3266489909);
        return ((h ^= h >>> 16) >>> 0) / 4294967296;
    };
}

//Style from seed
const headerStyle = computed(() => {
    const random = createSeededRandom(props.title);

    const getRandomValue = (min: number, max: number) =>
        Math.floor(random() * (max - min + 1) + min);

    const radii = Array.from({ length: 8 }, () => getRandomValue(40, 70));

    const borderRadiusValue = `${radii.slice(0, 4).join('% ')}% / ${radii.slice(4, 8).join('% ')}%`;

    return {
        borderRadius: borderRadiusValue,
    };
});
</script>

<template>
    <div class="relative pt-6">
        <div
            v-if="props.title"
            class="absolute top-0 left-1/2 z-10 w-max -translate-x-1/2">
            <div
                class="bg-haven-blue px-10 py-3 font-bold text-haven-yellow shadow-md"
                :style="headerStyle"
            >
                {{ title }}
            </div>
        </div>

        <div :class="cardBodyClasses">
            <slot />
        </div>
    </div>
</template>

<style scoped></style>
