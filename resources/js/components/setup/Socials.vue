<script setup lang="ts">
import { inject } from 'vue';
import { Instagram, Facebook, Twitter, Linkedin } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const form = inject('form')
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
            <Label for="instagram" class="flex items-center gap-1.5">
                <Instagram class="h-4 w-4" /> Instagram
            </Label>
            <Input
                type="text"
                name="instagram"
                id="instagram"
                placeholder="https://www.instagram.com/yourcompanyname"
                v-model="form.socials.instagram"
            />
        </div>

        <div class="flex flex-col gap-2">
            <Label for="facebook" class="flex items-center gap-1.5">
                <Facebook class="h-4 w-4" /> Facebook
            </Label>
            <Input
                type="text"
                name="facebook"
                id="facebook"
                placeholder="https://www.facebook.com/yourcompanyname"
                v-model="form.socials.facebook"
            />
        </div>

        <div class="flex flex-col gap-2">
            <Label for="x" class="flex items-center gap-1.5">
                <Twitter class="h-4 w-4" /> X (Twitter)
            </Label>
            <Input
                type="text"
                name="x"
                id="x"
                placeholder="https://x.com/yourcompanyname"
                v-model="form.socials.x"
            />
        </div>

        <div class="flex flex-col gap-2">
            <Label for="linkedin" class="flex items-center gap-1.5">
                <Linkedin class="h-4 w-4" /> LinkedIn
            </Label>
            <Input
                type="text"
                name="linkedin"
                id="linkedin"
                placeholder="https://www.linkedin.com/in/yourcompanyname/"
                v-model="form.socials.linkedin"
            />
        </div>
    </div>
</template>
