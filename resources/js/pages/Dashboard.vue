<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import DocumentUploadInput from '@/components/form/DocumentUploadInput.vue';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

import { store } from '@/routes/image';



const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const form = useForm({
    photo: null as File | null,
});

function handleSubmit() {
    form.post(store(), {
        onSuccess: () => {
            console.log('Photo uploadée avec succès');
        },
        onError: () => {
            console.log("Erreur lors de l'upload");
        },
    });
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
                >
                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <DocumentUploadInput
                            v-model="form.photo"
                            label="Photo de profil"
                            :error="form.errors.photo"
                        />

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
                            {{
                                form.processing
                                    ? 'Envoi en cours...'
                                    : 'Enregistrer'
                            }}
                        </button>

                        <div v-if="form.progress" class="w-full">
                            <div
                                class="h-2 w-full overflow-hidden rounded-full bg-secondary"
                            >
                                <div
                                    class="h-full bg-primary transition-all"
                                    :style="{
                                        width: `${form.progress.percentage}%`,
                                    }"
                                ></div>
                            </div>
                            <p
                                class="mt-1 text-center text-xs text-muted-foreground"
                            >
                                Upload : {{ form.progress.percentage }}%
                            </p>
                        </div>
                    </form>
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
            </div>
            <div
                class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
