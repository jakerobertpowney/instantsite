<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import { show } from '@/actions/App/Http/Controllers/PreviewController'
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search, Globe, Clock, Star } from 'lucide-vue-next';
import { places as searchPlacesRoute } from '@/routes/search';

const placesQuery = ref('')
const places = ref<any[]>([])
const isSearching = ref(false)
const hasSearched = ref(false)

const searchPlaces = async () => {
    if (!placesQuery.value.trim()) return
    isSearching.value = true
    hasSearched.value = true

    try {
        const response = await axios.post(searchPlacesRoute.url(), {
            query: placesQuery.value
        })
        places.value = response.data.places ?? []
    } catch {
        places.value = []
    } finally {
        isSearching.value = false
    }
}
</script>

<template>
    <Head title="Get your business online — InstantSite">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-background text-foreground flex flex-col">

        <!-- Hero -->
        <div class="flex flex-col items-center text-center px-6 pt-16 pb-10 lg:pt-24">
            <h1 class="text-4xl font-bold tracking-tight lg:text-5xl max-w-xl">
                Your business, online in minutes
            </h1>
            <p class="mt-4 text-lg text-muted-foreground max-w-sm leading-relaxed">
                We use your Google Business listing to build a professional website for you — automatically and for free.
            </p>
        </div>

        <!-- Search -->
        <div class="px-6 w-full max-w-lg mx-auto">
            <form @submit.prevent="searchPlaces">
                <label for="business-search" class="block text-sm font-medium mb-2 text-center text-muted-foreground">
                    Type your business name to get started
                </label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                        <Input
                            v-model="placesQuery"
                            type="search"
                            id="business-search"
                            class="pl-9 h-12 text-base"
                            placeholder="e.g. Dave's Painting, Manchester"
                            required
                            autofocus
                        />
                    </div>
                    <Button type="submit" class="h-12 px-6 text-base" :disabled="isSearching">
                        {{ isSearching ? 'Searching…' : 'Search' }}
                    </Button>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div class="px-6 w-full max-w-lg mx-auto mt-6">

            <!-- Loading -->
            <div v-if="isSearching" class="flex flex-col gap-3">
                <div v-for="i in 3" :key="i" class="h-24 rounded-xl bg-muted animate-pulse" />
            </div>

            <!-- Results list -->
            <div v-else-if="places.length > 0" class="flex flex-col gap-3">
                <p class="text-sm text-muted-foreground text-center mb-1">
                    Is one of these your business?
                </p>
                <div
                    v-for="place in places"
                    :key="place.id"
                    class="flex items-center justify-between gap-4 rounded-xl border bg-card p-4 shadow-sm hover:shadow-md transition-shadow"
                >
                    <div class="min-w-0">
                        <p class="font-semibold text-base leading-tight truncate">{{ place.displayName.text }}</p>
                        <p class="text-muted-foreground text-sm mt-0.5 truncate">{{ place.formattedAddress }}</p>
                    </div>
                    <Button as-child size="sm" class="shrink-0">
                        <Link :href="show(place.id)">
                            This is me →
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- No results -->
            <div
                v-else-if="hasSearched && !isSearching"
                class="text-center py-8 text-muted-foreground"
            >
                <p class="font-medium">No results found</p>
                <p class="text-sm mt-1">Try searching with your postcode, or the full business name.</p>
            </div>

        </div>

        <!-- Trust signals -->
        <div v-if="!hasSearched" class="mt-12 px-6 pb-16">
            <div class="max-w-lg mx-auto grid grid-cols-3 gap-4 text-center">
                <div class="flex flex-col items-center gap-2 p-4">
                    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                        <Clock class="h-5 w-5 text-primary" />
                    </div>
                    <p class="text-sm font-medium">Ready in minutes</p>
                    <p class="text-xs text-muted-foreground">No technical skills needed</p>
                </div>
                <div class="flex flex-col items-center gap-2 p-4">
                    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                        <Globe class="h-5 w-5 text-primary" />
                    </div>
                    <p class="text-sm font-medium">Free website</p>
                    <p class="text-xs text-muted-foreground">Your own web address included</p>
                </div>
                <div class="flex flex-col items-center gap-2 p-4">
                    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                        <Star class="h-5 w-5 text-primary" />
                    </div>
                    <p class="text-sm font-medium">Uses Google</p>
                    <p class="text-xs text-muted-foreground">Built from your existing listing</p>
                </div>
            </div>
        </div>

    </div>
</template>
