<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { show } from '@/routes/receipts';
import { type BreadcrumbItem } from '@/types';

interface Article {
    id: number;
    name: string;
    pivot: {
        price: number;
        quantity: number;
    };
}

interface Receipt {
    id: number;
    date: string;
    store: string | null;
    total: number | null;
    articles: Article[];
}

const props = defineProps<{
    receipt: Receipt;
}>();

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
    {
        title: 'Ticket',
        href: show(props.receipt.id).url,
    },
];
</script>

<template>
    <Head :title="`Ticket du ${formatDate(receipt.date)}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ formatDate(receipt.date) }}</h1>
                    <p class="text-muted-foreground">{{ receipt.store ?? 'Magasin inconnu' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-muted-foreground">Total</p>
                    <p class="text-2xl font-bold">{{ receipt.total ? Number(receipt.total).toFixed(2) + ' €' : '-' }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-sidebar-border/70 dark:border-sidebar-border">
                            <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Article</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-muted-foreground">Quantité</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Prix</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="article in receipt.articles"
                            :key="article.id"
                            class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                        >
                            <td class="px-4 py-3 text-sm">{{ article.name }}</td>
                            <td class="px-4 py-3 text-center text-sm">{{ article.pivot.quantity }}</td>
                            <td class="px-4 py-3 text-right text-sm">{{ Number(article.pivot.price).toFixed(2) }} €</td>
                        </tr>
                        <tr v-if="receipt.articles.length === 0">
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                Aucun article
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Link
                :href="dashboard().url"
                class="inline-flex w-fit items-center text-sm text-muted-foreground hover:text-foreground"
            >
                &larr; Retour au dashboard
            </Link>
        </div>
    </AppLayout>
</template>
