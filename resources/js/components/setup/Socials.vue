<script setup lang="ts">

import { inject } from 'vue';
import { Instagram, Facebook, Twitter, Linkedin } from 'lucide-vue-next';

const form = inject('form')

</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="sm:col-span-2">
            <label for="name" class="mb-2 text-sm font-medium text-gray-900 dark:text-white flex flex-row items-center gap-1.5">
                <Instagram class="h-4 w-4"/>
                Instagram
            </label>
            <input
                type="text"
                name="instagram"
                id="instagram"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                placeholder="https://www.instagram.com/yourcompanyname"
                required
                v-model="form.socials.instagram"
            />
        </div>

        <div class="sm:col-span-2">
            <label for="name" class="mb-2 text-sm font-medium text-gray-900 dark:text-white flex flex-row items-center gap-1.5">
                <Facebook class="h-4 w-4"/>
                Facebook
            </label>
            <input
                type="text"
                name="facebook"
                id="facebook"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                placeholder="https://www.facebook.com/yourcompanyname"
                required
                v-model="form.socials.facebook"
            />
        </div>

        <div class="sm:col-span-2">
            <label for="name" class="mb-2 text-sm font-medium text-gray-900 dark:text-white flex flex-row items-center gap-1.5">
                <Twitter class="h-4 w-4"/>
                X (Twitter)
            </label>
            <input
                type="text"
                name="x"
                id="x"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                placeholder="https://x.com/yourcompanyname"
                required
                v-model="form.socials.x"
            />
        </div>

        <div class="sm:col-span-2">
            <label for="name" class="mb-2 text-sm font-medium text-gray-900 dark:text-white flex flex-row items-center gap-1.5">
                <Linkedin class="h-4 w-4"/>
                LinkedIn
            </label>
            <input
                type="text"
                name="linkedin"
                id="linkedin"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                placeholder="https://www.linkedin.com/in/yourcompanyname/"
                required
                v-model="form.socials.linkedin"
            />
        </div>
    </div>
</template>
