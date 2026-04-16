<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import { show } from '@/actions/App/Http/Controllers/PreviewController'
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Search } from 'lucide-vue-next';

const placesQuery = ref('')
const places = ref([])

const searchPlaces = () => {
    axios.post(route('search.places'), {
        query: placesQuery.value
    }).then(response => {
        places.value = response.data.places
    })
}
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col items-center bg-background p-6 text-foreground lg:justify-center lg:p-8">

        <form @submit.prevent="searchPlaces" class="max-w-md w-full mx-auto">
            <label for="default-search" class="sr-only">Search</label>
            <div class="relative flex gap-2">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <Search class="w-4 h-4 text-muted-foreground" />
                </div>
                <Input
                    v-model="placesQuery"
                    type="search"
                    id="default-search"
                    class="ps-10"
                    placeholder="Search for your business"
                    required
                />
                <Button type="submit">Search</Button>
            </div>
        </form>

        <div class="flex flex-col gap-4 max-w-md w-full mx-auto mt-6">
            <Card
                v-for="place in places"
                :key="place.id"
            >
                <CardContent class="pt-6">
                    <p class="text-xl font-semibold">{{ place.displayName.text }}</p>
                    <p class="text-muted-foreground text-sm mt-1">{{ place.formattedAddress }}</p>
                    <Button as-child class="mt-4">
                        <Link :href="show(place.id)">Start</Link>
                    </Button>
                </CardContent>
            </Card>
        </div>

    </div>
</template>
