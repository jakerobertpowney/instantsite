<script setup lang="ts">

import { inject } from 'vue';

const form = inject('form')

</script>

<template>
    <div class="sm:col-span-2">
        <label for="logo" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Please upload your logo</label>
        <input type="file"
               name="logo"
               id="logo"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
               @input="form.logo = $event.target.files[0]"
        >
    </div>
</template>
