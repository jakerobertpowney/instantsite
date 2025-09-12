<script setup lang="ts">
import { Copy } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
</script>

<template>
    <div class="flex flex-col gap-8">
        <div>
            <h1 class="text-2xl font-semibold">My Site</h1>
            <p class="text-gray-500">Overview</p>
        </div>

        <div class="flex flex-col gap-4">
            <div class="bg-gray-50 border-2 rounded-xl py-3 px-5 flex justify-between items-center gap-3">
                <p>https://missbramhall.instantsite.test</p>
                <Button class="bg-black" size="icon">
                    <Copy/>
                </Button>
            </div>
            <p class="text-gray-500">This is your sites URL, feel free to share this with your customers. If you'd like to change this, please click here.</p>

        </div>

        <div class="bg-gray-50 py-3 px-5 flex flex-col gap-4">

            <h2 class="text-xl font-semibold">Premium</h2>

            <p>You are on the free plan.</p>

            <Button class="bg-black" size="lg">
                Upgrade
            </Button>

        </div>
    </div>
</template>
