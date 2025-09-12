<script setup lang="ts">
import Gallery from '@/components/site/Gallery.vue';
import Contact from '@/components/site/Contact.vue';
import QuickActions from '@/components/site/QuickActions.vue';
import Description from '@/components/site/Description.vue';
import Header from '@/components/site/Header.vue';
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

                <QuickActions/>

                <div class="bg-white overflow-hidden sm:rounded-lg py-16 px-32 flex flex-col gap-8 relative">

                    <Header/>

                    <Description/>

                    <Gallery/>

                    <hr>

                    <Contact/>

                    <hr>

                    <Reviews/>

                </div>
            </div>
        </div>
    </div>
</template>
