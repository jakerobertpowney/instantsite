<script setup lang="ts">

import { inject, ref } from 'vue';
import { Instagram, Facebook, Twitter, Linkedin } from 'lucide-vue-next';

const form = inject('form')

const enabled = ref(false)

</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="sm:col-span-2">
            <label class="inline-flex items-center mb-5 cursor-pointer">
                <input type="checkbox" value="" class="sr-only peer" v-model="enabled">
                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:w-5 after:h-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600 dark:peer-checked:bg-blue-600"></div>
                <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Enable contact form</span>
            </label>
        </div>

        <div
            v-if="enabled"
            class="sm:col-span-2"
        >
            <label for="to" class="mb-2 text-sm font-medium text-gray-900 dark:text-white flex flex-row items-center gap-1.5">
                To Address
            </label>
            <input
                type="text"
                name="to"
                id="to"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                placeholder="yourname@domain.com"
                required
                v-model="form.contact"
            />
        </div>
    </div>
</template>
