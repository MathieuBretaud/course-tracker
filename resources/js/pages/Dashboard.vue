<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

import DocumentUploadInput from '@/components/form/DocumentUploadInput.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

import { store, show } from '@/routes/receipts';

interface Receipt {
    id: number;
    date: string;
    store: string | null;
    total: number | null;
    articles_count: number;
}

interface TopArticle {
    id: number;
    name: string;
    total_quantity: number;
}

interface PriceChange {
    name: string;
    first_price: number;
    last_price: number;
    change: number;
    change_percent: number;
}

const props = defineProps<{
    receipts: Receipt[];
    topArticles: TopArticle[];
    priceChanges: PriceChange[];
}>();

const maxQuantity = computed(() =>
    Math.max(...props.topArticles.map(a => Number(a.total_quantity)), 1)
);

const maxChangePercent = computed(() =>
    Math.max(...props.priceChanges.map(p => Math.abs(p.change_percent)), 1)
);

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

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
                    class="overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <h3 class="mb-3 text-sm font-medium text-muted-foreground">Top articles achetés</h3>
                    <div class="space-y-2">
                        <div v-for="article in topArticles" :key="article.id" class="flex items-center gap-2">
                            <div class="w-24 truncate text-xs" :title="article.name">{{ article.name }}</div>
                            <div class="flex-1">
                                <div
                                    class="h-4 rounded bg-primary/80"
                                    :style="{ width: `${(Number(article.total_quantity) / maxQuantity) * 100}%` }"
                                ></div>
                            </div>
                            <div class="w-8 text-right text-xs text-muted-foreground">{{ article.total_quantity }}</div>
                        </div>
                        <p v-if="topArticles.length === 0" class="text-center text-xs text-muted-foreground">Aucune donnée</p>
                    </div>
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
                    class="overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <h3 class="mb-3 text-sm font-medium text-muted-foreground">Hausses de prix</h3>
                    <div class="space-y-2">
                        <div v-for="(item, index) in priceChanges" :key="index" class="flex items-center gap-2">
                            <div class="w-24 truncate text-xs" :title="item.name">{{ item.name }}</div>
                            <div class="flex-1">
                                <div
                                    class="h-4 rounded"
                                    :class="item.change_percent >= 0 ? 'bg-red-500/80' : 'bg-green-500/80'"
                                    :style="{ width: `${(Math.abs(item.change_percent) / maxChangePercent) * 100}%` }"
                                ></div>
                            </div>
                            <div class="w-16 text-right text-xs" :class="item.change_percent >= 0 ? 'text-red-500' : 'text-green-500'">
                                {{ item.change_percent >= 0 ? '+' : '' }}{{ item.change_percent }}%
                            </div>
                        </div>
                        <p v-if="priceChanges.length === 0" class="text-center text-xs text-muted-foreground">Pas assez de données</p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-sidebar-border/70 dark:border-sidebar-border">
                            <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Date</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Magasin</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Articles</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="receipt in receipts"
                            :key="receipt.id"
                            class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                        >
                            <td class="px-4 py-3 text-sm">
                                <Link :href="show(receipt.id).url" class="hover:underline">
                                    {{ formatDate(receipt.date) }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ receipt.store ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ receipt.articles_count }}</td>
                            <td class="px-4 py-3 text-right text-sm">{{ receipt.total ? Number(receipt.total).toFixed(2) + ' €' : '-' }}</td>
                        </tr>
                        <tr v-if="receipts.length === 0">
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                Aucun ticket enregistré
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
