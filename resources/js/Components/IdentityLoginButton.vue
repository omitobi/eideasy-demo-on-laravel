<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";

defineProps({
    form: {
        type: Object,
        required: true,
    },
    label: {
        type: String,
        default: "Identity Login",
        required: false,
    }
});

const identityLogin = () => window.location.href = route('identity.login');
</script>

<template>
    <hr class="m-3">

    <div class="flex items-center justify-end mt-4">
        <PrimaryButton
            type="button"
            class="ml-4"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
            @click.prevent="identityLogin"
        >
            {{ label }}
        </PrimaryButton>
    </div>
</template>
