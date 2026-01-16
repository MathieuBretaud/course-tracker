<script setup lang="ts">

import { ImagePlus, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

interface Props {
    id?: string;
    name?: string;
    label?: string;
    maxFileSize?: number;
    existingFile?: string | null;
    fileTypes?: string[];
    error?: string;
}

const props = withDefaults(defineProps<Props>(), {
    id: 'photo-upload',
    maxFileSize: 5242880,
    existingFile: null,
    fileTypes: () => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
});

const file = defineModel<File | null>({ default: null });
const previewUrl = ref<string | null>(null);

const hasNewOrExistingFile = computed(() => {
    return file.value !== null || props.existingFile !== null;
});

const displayableMaxFileSize = computed(() => {
    if (props.maxFileSize === null) {
        return '';
    }

    const sizeInMB = (props.maxFileSize / (1024 * 1024)).toFixed(1);
    return `${sizeInMB} MB`;
});

watch(file, (newFile) => {
    if (newFile) {
        previewUrl.value = URL.createObjectURL(newFile);
    } else {
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
        }
        previewUrl.value = null;
    }
});

function handleUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    const selectedFile = target.files?.[0];

    if (!selectedFile) return;

    file.value = selectedFile;
}

function removeFile() {
    file.value = null;
}
</script>

<template>
    <div class="grid gap-2">
        <Label v-if="label" :for="id" class="mb-2 text-lg">{{ label }}</Label>

        <div class="flex items-center">
            <label
                :for="id"
                class="relative block w-full cursor-pointer overflow-hidden rounded-md"
                :class="previewUrl || existingFile ? 'h-64' : 'h-32'"
            >
                <input
                    :id="id"
                    :name="name"
                    :accept="fileTypes.map((type) => 'image/' + type).join(',')"
                    class="absolute inset-0 cursor-pointer opacity-0"
                    type="file"
                    @change="handleUpload"
                />

                <div
                    v-if="previewUrl || existingFile"
                    class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-md border-2 border-input bg-muted/30"
                >
                    <img
                        :src="previewUrl || existingFile"
                        :alt="file?.name || 'Photo preview'"
                        class="h-full w-full object-contain"
                    />

                    <button
                        class="pointer-events-auto absolute right-2 top-2 z-10 inline-flex items-center gap-1 rounded-md bg-destructive px-3 py-2 text-xs text-destructive-foreground shadow-lg hover:bg-destructive/90"
                        type="button"
                        @click.stop="removeFile"
                    >
                        <X class="h-4 w-4" />
                        Supprimer
                    </button>
                </div>

                <div
                    v-else
                    class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-3 rounded-md border-2 border-dashed border-input bg-muted/30 p-6 text-center transition-colors hover:bg-muted/50"
                >
                    <ImagePlus class="h-8 w-8 text-muted-foreground" />

                    <div class="flex flex-col gap-1">
                        <span class="text-sm font-medium">
                            Cliquez ou glissez une photo ici
                        </span>
                        <span class="text-xs text-muted-foreground">
                            Formats acceptés : {{ fileTypes.join(', ').toUpperCase() }}
                        </span>
                    </div>
                </div>
            </label>
        </div>

        <p v-if="maxFileSize" class="text-end text-xs text-primary">
            Taille maximale : {{ displayableMaxFileSize }}
        </p>

        <InputError v-if="error" :message="error" />
    </div>
</template>
