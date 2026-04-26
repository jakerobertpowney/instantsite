<script setup lang="ts">
import HelpLayout from '@/components/help/HelpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ChevronRight } from 'lucide-vue-next';

interface Section {
    id: string;
    title: string;
    paragraphs: string[];
    bullets?: string[];
}

interface Article {
    slug: string;
    title: string;
    summary: string;
    read_time: string;
    category_slug: string;
    category_title: string;
    category_description: string;
    sections: Section[];
}

interface RelatedArticle {
    slug: string;
    title: string;
    summary: string;
    read_time: string;
    category_title: string;
}

defineProps<{
    article: Article;
    relatedArticles: RelatedArticle[];
}>();
</script>

<template>
    <Head :title="article.title" />

    <HelpLayout
        eyebrow="Help article"
        :title="article.title"
        :description="article.summary"
        :page-title="article.title"
    >
        <!-- Breadcrumb -->
        <div class="flex items-center gap-3 flex-wrap">
            <Link href="/help" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#3d4a5c] no-underline px-2.5 py-1.5 rounded-lg -ml-2.5 transition-all hover:bg-[#edf1f8] hover:text-[#0f172a]">
                <ArrowLeft :size="16" aria-hidden="true" />
                Back to help centre
            </Link>
            <span class="text-[#dde1e8] text-base" aria-hidden="true">/</span>
            <span class="inline-block px-2.5 py-0.75 rounded text-xs font-bold bg-[#e6eefe] text-[#1e66f5]">{{ article.category_title }}</span>
            <span class="text-xs text-[#64748b]">{{ article.read_time }}</span>
        </div>

        <!-- Body -->
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-12 lg:gap-12 lg:items-start">
            <!-- Article content -->
            <article class="flex flex-col gap-0">
                <section
                    v-for="section in article.sections"
                    :key="section.id"
                    :id="section.id"
                    class="pb-10 scroll-mt-[100px]"
                >
                    <h2 class="text-2xl font-black text-[#0f172a] tracking-tight mb-4 leading-tight">{{ section.title }}</h2>
                    <p
                        v-for="paragraph in section.paragraphs"
                        :key="paragraph"
                        class="text-base leading-[1.8] text-[#3d4a5c] mb-3"
                    >
                        {{ paragraph }}
                    </p>
                    <ul v-if="section.bullets?.length" class="my-2 ml-5 flex flex-col gap-2">
                        <li v-for="bullet in section.bullets" :key="bullet" class="text-sm leading-[1.7] text-[#3d4a5c] list-disc">{{ bullet }}</li>
                    </ul>
                    <div class="h-px bg-[#e8ecf1] mt-8" />
                </section>
            </article>

            <!-- Sidebar -->
            <aside class="flex flex-col gap-4 lg:sticky lg:top-[88px]">
                <!-- On this page -->
                <div class="bg-white border border-[#dde1e8] rounded-[14px] p-5.5 flex flex-col gap-2.5">
                    <h3 class="text-sm font-bold text-[#0f172a] tracking-tight">On this page</h3>
                    <p class="text-xs text-[#64748b] -mt-1">Jump to a section in this guide.</p>
                    <div class="flex flex-col gap-0.5 mt-1">
                        <a
                            v-for="section in article.sections"
                            :key="section.id"
                            :href="`#${section.id}`"
                            class="flex items-center justify-between gap-2 px-2.5 py-2 -mx-2.5 rounded-lg text-xs text-[#3d4a5c] no-underline transition-all hover:bg-[#edf1f8] hover:text-[#0f172a]"
                        >
                            <span>{{ section.title }}</span>
                            <ChevronRight :size="15" class="flex-shrink-0 opacity-40" aria-hidden="true" />
                        </a>
                    </div>
                </div>

                <!-- Need more help -->
                <div class="bg-[#edf1f8] border border-[#e8ecf1] rounded-[14px] p-5.5 flex flex-col gap-2.5">
                    <h3 class="text-sm font-bold text-[#0f172a] tracking-tight">Need more help?</h3>
                    <p class="text-xs leading-[1.65] text-[#3d4a5c]">
                        Email <a href="mailto:help@321sites.com" class="text-[#1e66f5] underline underline-offset-[3px]">help@321sites.com</a> and tell us what you're trying to do.
                    </p>
                </div>
            </aside>
        </div>

        <!-- Related articles -->
        <section v-if="relatedArticles.length" class="">
            <div class="mb-6">
                <h2 class="text-3xl font-black text-[#0f172a] tracking-tight">Related guides</h2>
                <p class="mt-1.5 text-sm text-[#64748b]">A few more articles that usually help next.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    v-for="related in relatedArticles"
                    :key="related.slug"
                    :href="`/help/${related.slug}`"
                    class="flex flex-col gap-2.5 p-5.5 bg-white border border-[#dde1e8] rounded-[14px] no-underline text-[#0f172a] transition-all hover:border-[#1e66f5] hover:shadow-[0_4px_20px_rgba(30,102,245,0.08)]"
                >
                    <div class="flex items-center gap-2.5">
                        <span class="inline-block px-2.5 py-0.75 rounded text-xs font-bold bg-[#edf1f8] text-[#3d4a5c]">{{ related.category_title }}</span>
                        <span class="text-xs text-[#64748b]">{{ related.read_time }}</span>
                    </div>
                    <h3 class="text-base font-bold text-[#0f172a] tracking-tight leading-tight">{{ related.title }}</h3>
                    <p class="text-xs text-[#64748b] leading-relaxed flex-1">{{ related.summary }}</p>
                    <span class="text-base text-[#dde1e8] self-end transition-all inline-block" aria-hidden="true">→</span>
                </Link>
            </div>
        </section>
    </HelpLayout>
</template>
