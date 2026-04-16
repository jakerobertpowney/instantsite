<script setup lang="ts">
import { inject, ref } from 'vue';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Upload } from 'lucide-vue-next';

const form = inject('form')
const fileInput = ref<HTMLInputElement | null>(null)
const fileName = ref<string | null>(null)

const triggerUpload = () => {
    fileInput.value?.click()
}

const handleFile = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0]
    if (file) {
        form.logo = file
        fileName.value = file.name
    }
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <Label for="logo">Please upload your logo</Label>
        <input
            ref="fileInput"
            type="file"
            name="logo"
            id="logo"
            class="hidden"
            accept="image/*"
            @change="handleFile"
        />
        <Button type="button" variant="outline" @click="triggerUpload" class="w-full justify-start gap-2">
            <Upload class="h-4 w-4" />
            {{ fileName ?? 'Choose file…' }}
        </Button>
    </div>
</template>
