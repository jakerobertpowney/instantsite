<script setup lang="ts">

import { inject } from 'vue';
import { Trash, Link, Plus } from 'lucide-vue-next';

const form = inject('form')

const addQuicklink = () => {
    form.quicklinks.push({
        label: '',
        link: ''
        }
    )
}

const removeQuicklink = (index: number) => {
    form.quicklinks.splice(index, 1)
}

</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            v-for="(link, index) in form.quicklinks"
            class="sm:col-span-2"
        >
            <label for="name" class="mb-2 text-sm font-medium text-gray-900 dark:text-white flex flex-row items-center gap-1.5">
                <Link class="h-4 w-4"/>
                Quick Link #{{ index + 1 }}
            </label>
            <div class="flex flex-col gap-2">
                <input
                    type="text"
                    name="quicklink[]"
                    id="quicklink"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                    placeholder="Book Appointment"
                    required
                    v-model="form.quicklinks[index].label"
                />
                <div class="flex flex-row gap-4">
                    <input
                        type="text"
                        name="quicklink[]"
                        id="quicklink"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                        placeholder="https://www.yourbookingsoftware.com/yourcompanyname"
                        required
                        v-model="form.quicklinks[index].link"
                    />
                    <button
                        v-if="index >= 1"
                        @click="removeQuicklink(index)"
                        type="button"
                        class="flex flex-grow cursor-pointer items-center justify-center gap-2 rounded-lg bg-red-500 px-3 py-3 text-white"
                    >
                        <Trash class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
        <button
            @click="addQuicklink"
            type="button"
            class="flex flex-grow cursor-pointer items-center justify-center gap-2 rounded-lg bg-white border px-5 py-3 text-black mt-4 w-full"
        >
            Add <Plus class="h-4 w-4" />
        </button>
    </div>
</template>
