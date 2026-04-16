<script setup lang="ts">
import { inject } from 'vue';
import { Trash, Link, Plus } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

const form = inject('form')

const addQuicklink = () => {
    form.quicklinks.push({ label: '', link: '' })
}

const removeQuicklink = (index: number) => {
    form.quicklinks.splice(index, 1)
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div v-for="(link, index) in form.quicklinks" :key="index" class="flex flex-col gap-2">
            <Label :for="`quicklink-label-${index}`" class="flex items-center gap-1.5">
                <Link class="h-4 w-4" /> Quick Link #{{ index + 1 }}
            </Label>
            <Input
                type="text"
                :id="`quicklink-label-${index}`"
                name="quicklink[]"
                placeholder="Book Appointment"
                v-model="form.quicklinks[index].label"
            />
            <div class="flex gap-2">
                <Input
                    type="text"
                    name="quicklink[]"
                    placeholder="https://www.yourbookingsoftware.com/yourcompanyname"
                    v-model="form.quicklinks[index].link"
                    class="flex-1"
                />
                <Button
                    v-if="index >= 1"
                    @click="removeQuicklink(index)"
                    type="button"
                    variant="destructive"
                    size="icon"
                >
                    <Trash class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <Button
            @click="addQuicklink"
            type="button"
            variant="outline"
            class="w-full mt-2"
        >
            Add <Plus class="h-4 w-4" />
        </Button>
    </div>
</template>
