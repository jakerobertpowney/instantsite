<script setup lang="ts">
import HelpLayout from '@/components/help/HelpLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, Compass, Globe2, LifeBuoy, Mail, Search, Sparkles } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Category {
    slug: string;
    title: string;
    description: string;
    count: number;
}

interface ArticleSummary {
    slug: string;
    title: string;
    summary: string;
    read_time: string;
    category_slug: string;
    category_title: string;
    category_description: string;
    search_terms: string[];
}

interface FaqItem {
    question: string;
    answer: string;
}

const props = defineProps<{
    categories: Category[];
    articles: ArticleSummary[];
    featuredArticles: ArticleSummary[];
    faqs: FaqItem[];
}>();

const searchQuery = ref('');
const activeCategory = ref('all');
const faqOpen = ref<number | null>(null);

const filteredArticles = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.articles.filter((article) => {
        const matchesCategory = activeCategory.value === 'all' || article.category_slug === activeCategory.value;
        if (!matchesCategory) return false;

        if (!query) return true;

        const haystack = [
            article.title,
            article.summary,
            article.category_title,
            ...article.search_terms,
        ].join(' ').toLowerCase();

        return haystack.includes(query);
    });
});

const categoryCards = computed(() => [
    {
        slug: 'all',
        title: 'All articles',
        description: 'Browse the full help centre.',
        count: props.articles.length,
    },
    ...props.categories,
]);

const emptyState = computed(() => searchQuery.value || activeCategory.value !== 'all');

const iconForCategory = (slug: string) => {
    if (slug === 'getting-started') return Sparkles;
    if (slug === 'editing-your-site') return BookOpen;
    if (slug === 'domains-and-billing') return Globe2;
    if (slug === 'seo-and-visibility') return Compass;
    if (slug === 'messages-and-leads') return LifeBuoy;
    return BookOpen;
};

const toggleFaq = (i: number) => { faqOpen.value = faqOpen.value === i ? null : i; };

const selectCategory = (slug: string) => {
    activeCategory.value = slug;
    const el = window.document.getElementById('all-guides');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
</script>

<template>
    <Head title="Help Centre" />

    <HelpLayout
        eyebrow="Documentation"
        title="Help centre"
        description="Step-by-step guides for setting up your site, connecting your domain, managing customer enquiries, and understanding the key settings inside 321Sites."
    >

        <!-- ══════════════════════════════════════════════
             SEARCH + SUPPORT ROW
        ══════════════════════════════════════════════ -->
        <div class="grid gap-5 md:gap-5 lg:grid-cols-[1.4fr_0.6fr] lg:items-start">
            <!-- Search card -->
            <div class="rounded-2xl border border-[#dde1e8] bg-white p-7 flex flex-col gap-4">
                <h2 class="text-lg font-bold text-[#0f172a] tracking-tight">Find an answer fast</h2>
                <p class="text-sm text-[#64748b] leading-relaxed -mt-1.5">Search guides, setup steps, and common questions.</p>
                <div class="relative">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#64748b] pointer-events-none" :size="18" aria-hidden="true" />
                    <input
                        v-model="searchQuery"
                        type="search"
                        class="w-full h-12 pl-11 pr-4 border-[1.5px] border-[#dde1e8] rounded-[10px] bg-white font-inherit text-sm text-[#0f172a] outline-none transition-colors focus:border-[#1e66f5]"
                        placeholder="Search domains, SEO, contact forms, Google Business Profile…"
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="category in categoryCards"
                        :key="category.slug"
                        type="button"
                        class="h-8.5 px-3.5 rounded-lg border-[1.5px] border-[#dde1e8] bg-transparent font-inherit text-xs font-semibold text-[#3d4a5c] cursor-pointer transition-all hover:bg-[#edf1f8] hover:text-[#0f172a]"
                        :class="{ 'bg-[#0f172a] text-white border-[#0f172a]': activeCategory === category.slug }"
                        @click="activeCategory = category.slug"
                    >
                        {{ category.title }}
                    </button>
                </div>
            </div>

            <!-- Support card -->
            <div class="rounded-2xl border border-[#dde1e8] bg-white p-7 flex flex-col gap-4">
                <div class="w-11 h-11 rounded-[10px] bg-[#e6eefe] text-[#1e66f5] flex items-center justify-center">
                    <Mail :size="22" />
                </div>
                <h2 class="text-lg font-bold text-[#0f172a] tracking-tight">Still stuck?</h2>
                <p class="text-sm text-[#64748b] leading-relaxed -mt-1.5">Email our support inbox and tell us what you're trying to do. We'll come back to you in plain English.</p>
                <a href="mailto:help@321sites.com" class="inline-flex items-center justify-center h-11 w-full rounded-[10px] bg-[#1e66f5] text-white font-inherit text-sm font-bold hover:opacity-90 transition-opacity mt-1">
                    Email support
                </a>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════
             POPULAR GUIDES
        ══════════════════════════════════════════════ -->
        <section v-if="!searchQuery && activeCategory === 'all'">
            <div class="mb-6">
                <h2 class="text-3xl font-black text-[#0f172a] tracking-tight">Popular guides</h2>
                <p class="mt-1.5 text-sm text-[#64748b]">A good place to start if you're setting up your site for the first time.</p>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                <Link
                    v-for="article in featuredArticles"
                    :key="article.slug"
                    :href="`/help/${article.slug}`"
                    class="flex flex-col gap-2.5 p-7 bg-white border border-[#dde1e8] rounded-[14px] text-[#0f172a] no-underline transition-all hover:border-[#1e66f5] hover:shadow-[0_4px_20px_rgba(30,102,245,0.08)] relative"
                >
                    <div class="flex items-center gap-2.5">
                        <span class="inline-block px-2.5 py-0.75 rounded text-xs font-bold bg-[#e6eefe] text-[#1e66f5]">{{ article.category_title }}</span>
                        <span class="text-xs text-[#64748b]">{{ article.read_time }}</span>
                    </div>
                    <h3 class="text-xl font-bold text-[#0f172a] tracking-tight leading-tight">{{ article.title }}</h3>
                    <p class="text-sm text-[#64748b] leading-relaxed flex-1">{{ article.summary }}</p>
                    <span class="text-lg text-[#dde1e8] self-end transition-all group-hover:text-[#1e66f5] inline-block" aria-hidden="true">→</span>
                </Link>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             BROWSE BY TOPIC
        ══════════════════════════════════════════════ -->
        <section v-if="!searchQuery && activeCategory === 'all'">
            <div class="mb-6">
                <h2 class="text-3xl font-black text-[#0f172a] tracking-tight">Browse by topic</h2>
                <p class="mt-1.5 text-sm text-[#64748b]">Choose the area you need help with.</p>
            </div>
            <div class="grid gap-4 lg:grid-cols-3 md:grid-cols-2">
                <button
                    v-for="category in props.categories"
                    :key="category.slug"
                    type="button"
                    class="flex flex-col gap-2.5 p-6 bg-white border border-[#dde1e8] rounded-[14px] text-left cursor-pointer font-inherit transition-all hover:border-[#1e66f5] hover:shadow-[0_4px_20px_rgba(30,102,245,0.08)]"
                    @click="selectCategory(category.slug)"
                >
                    <div class="w-10 h-10 rounded-[10px] bg-[#e6eefe] text-[#1e66f5] flex items-center justify-center">
                        <component :is="iconForCategory(category.slug)" :size="20" />
                    </div>
                    <h3 class="text-base font-bold text-[#0f172a] tracking-tight">{{ category.title }}</h3>
                    <p class="text-sm text-[#64748b] leading-relaxed flex-1">{{ category.description }}</p>
                    <p class="text-sm font-bold text-[#1e66f5]">{{ category.count }} article{{ category.count === 1 ? '' : 's' }}</p>
                </button>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             ALL GUIDES
        ══════════════════════════════════════════════ -->
        <section id="all-guides">
            <div class="mb-6">
                <h2 class="text-3xl font-black text-[#0f172a] tracking-tight">All guides</h2>
                <p class="mt-1.5 text-sm text-[#64748b]">Everything currently available in the 321Sites help centre.</p>
            </div>

            <div v-if="filteredArticles.length > 0" class="grid gap-4 lg:grid-cols-2">
                <Link
                    v-for="article in filteredArticles"
                    :key="article.slug"
                    :href="`/help/${article.slug}`"
                    class="flex flex-col gap-2.5 p-6 bg-white border border-[#dde1e8] rounded-[14px] no-underline text-[#0f172a] transition-all hover:border-[#1e66f5] hover:shadow-[0_4px_20px_rgba(30,102,245,0.08)] relative"
                >
                    <div class="flex items-center gap-2.5">
                        <span class="inline-block px-2.5 py-0.75 rounded text-xs font-bold bg-[#edf1f8] text-[#3d4a5c]">{{ article.category_title }}</span>
                        <span class="text-xs text-[#64748b]">{{ article.read_time }}</span>
                    </div>
                    <h3 class="text-base font-bold text-[#0f172a] tracking-tight leading-tight">{{ article.title }}</h3>
                    <p class="text-sm text-[#64748b] leading-relaxed flex-1">{{ article.summary }}</p>
                    <span class="text-base text-[#dde1e8] self-end transition-all inline-block" aria-hidden="true">→</span>
                </Link>
            </div>

            <div v-else-if="emptyState" class="py-16 px-8 bg-white border border-[#dde1e8] rounded-2xl text-center flex flex-col items-center gap-2.5">
                <p class="text-lg font-bold text-[#0f172a]">No guides matched that search.</p>
                <p class="text-sm text-[#64748b]">Try a broader search term or clear the filters.</p>
                <button
                    type="button"
                    class="mt-2 h-10 px-5 rounded-lg border-[1.5px] border-[#dde1e8] bg-transparent font-inherit text-sm font-semibold text-[#3d4a5c] cursor-pointer transition-colors hover:bg-[#edf1f8]"
                    @click="searchQuery = ''; activeCategory = 'all'"
                >
                    Clear filters
                </button>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             FAQ
        ══════════════════════════════════════════════ -->
        <section>
            <div class="mb-6">
                <h2 class="text-3xl font-black text-[#0f172a] tracking-tight">Common questions</h2>
                <p class="mt-1.5 text-sm text-[#64748b]">Short answers to the questions we hear most often.</p>
            </div>
            <div class="border border-[#dde1e8] rounded-2xl overflow-hidden bg-white">
                <div
                    v-for="(faq, i) in faqs"
                    :key="faq.question"
                    class="border-b border-[#e8ecf1] last:border-b-0"
                    :class="{ 'bg-white': faqOpen === i }"
                >
                    <button type="button" class="w-full flex items-center justify-between gap-4 px-7 py-5.5 bg-transparent border-0 cursor-pointer font-inherit text-base font-bold text-[#0f172a] text-left transition-colors hover:bg-[#ffffff]" @click="toggleFaq(i)">
                        <span>{{ faq.question }}</span>
                        <svg class="flex-shrink-0 text-[#64748b] transition-transform" :class="{ 'rotate-180': faqOpen === i }" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div v-show="faqOpen === i" class="px-7 pb-6 text-sm leading-relaxed text-[#3d4a5c]">
                        <p>{{ faq.answer }}</p>
                    </div>
                </div>
            </div>
        </section>

    </HelpLayout>
</template>
