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
        <div class="hc-top-row">
            <!-- Search card -->
            <div class="hc-card hc-search-card">
                <h2 class="hc-card__title">Find an answer fast</h2>
                <p class="hc-card__desc">Search guides, setup steps, and common questions.</p>
                <div class="hc-search-wrap">
                    <Search class="hc-search-icon" :size="18" aria-hidden="true" />
                    <input
                        v-model="searchQuery"
                        type="search"
                        class="hc-search-input"
                        placeholder="Search domains, SEO, contact forms, Google Business Profile…"
                    />
                </div>
                <div class="hc-filter-row">
                    <button
                        v-for="category in categoryCards"
                        :key="category.slug"
                        type="button"
                        class="hc-filter-btn"
                        :class="{ 'hc-filter-btn--active': activeCategory === category.slug }"
                        @click="activeCategory = category.slug"
                    >
                        {{ category.title }}
                    </button>
                </div>
            </div>

            <!-- Support card -->
            <div class="hc-card hc-support-card">
                <div class="hc-support-icon">
                    <Mail :size="22" />
                </div>
                <h2 class="hc-card__title">Still stuck?</h2>
                <p class="hc-card__desc">Email our support inbox and tell us what you're trying to do. We'll come back to you in plain English.</p>
                <a href="mailto:help@321sites.com" class="hc-support-btn">
                    Email support
                </a>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════
             POPULAR GUIDES
        ══════════════════════════════════════════════ -->
        <section v-if="!searchQuery && activeCategory === 'all'">
            <div class="hc-section-header">
                <h2 class="hc-section-title">Popular guides</h2>
                <p class="hc-section-desc">A good place to start if you're setting up your site for the first time.</p>
            </div>
            <div class="hc-articles-grid hc-articles-grid--2">
                <Link
                    v-for="article in featuredArticles"
                    :key="article.slug"
                    :href="`/help/${article.slug}`"
                    class="hc-article-card hc-article-card--featured"
                >
                    <div class="hc-article-card__meta">
                        <span class="hc-badge">{{ article.category_title }}</span>
                        <span class="hc-article-card__time">{{ article.read_time }}</span>
                    </div>
                    <h3 class="hc-article-card__title">{{ article.title }}</h3>
                    <p class="hc-article-card__summary">{{ article.summary }}</p>
                    <span class="hc-article-card__arrow" aria-hidden="true">→</span>
                </Link>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             BROWSE BY TOPIC
        ══════════════════════════════════════════════ -->
        <section v-if="!searchQuery && activeCategory === 'all'">
            <div class="hc-section-header">
                <h2 class="hc-section-title">Browse by topic</h2>
                <p class="hc-section-desc">Choose the area you need help with.</p>
            </div>
            <div class="hc-topics-grid">
                <button
                    v-for="category in props.categories"
                    :key="category.slug"
                    type="button"
                    class="hc-topic-card"
                    @click="selectCategory(category.slug)"
                >
                    <div class="hc-topic-card__icon">
                        <component :is="iconForCategory(category.slug)" :size="20" />
                    </div>
                    <h3 class="hc-topic-card__title">{{ category.title }}</h3>
                    <p class="hc-topic-card__desc">{{ category.description }}</p>
                    <p class="hc-topic-card__count">{{ category.count }} article{{ category.count === 1 ? '' : 's' }}</p>
                </button>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════
             ALL GUIDES
        ══════════════════════════════════════════════ -->
        <section id="all-guides">
            <div class="hc-section-header">
                <h2 class="hc-section-title">All guides</h2>
                <p class="hc-section-desc">Everything currently available in the 321Sites help centre.</p>
            </div>

            <div v-if="filteredArticles.length > 0" class="hc-articles-grid hc-articles-grid--2">
                <Link
                    v-for="article in filteredArticles"
                    :key="article.slug"
                    :href="`/help/${article.slug}`"
                    class="hc-article-card"
                >
                    <div class="hc-article-card__meta">
                        <span class="hc-badge hc-badge--outline">{{ article.category_title }}</span>
                        <span class="hc-article-card__time">{{ article.read_time }}</span>
                    </div>
                    <h3 class="hc-article-card__title">{{ article.title }}</h3>
                    <p class="hc-article-card__summary">{{ article.summary }}</p>
                    <span class="hc-article-card__arrow" aria-hidden="true">→</span>
                </Link>
            </div>

            <div v-else-if="emptyState" class="hc-empty">
                <p class="hc-empty__title">No guides matched that search.</p>
                <p class="hc-empty__desc">Try a broader search term or clear the filters.</p>
                <button
                    type="button"
                    class="hc-empty__reset"
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
            <div class="hc-section-header">
                <h2 class="hc-section-title">Common questions</h2>
                <p class="hc-section-desc">Short answers to the questions we hear most often.</p>
            </div>
            <div class="hc-faq">
                <div
                    v-for="(faq, i) in faqs"
                    :key="faq.question"
                    class="hc-faq__item"
                    :class="{ 'hc-faq__item--open': faqOpen === i }"
                >
                    <button type="button" class="hc-faq__trigger" @click="toggleFaq(i)">
                        <span>{{ faq.question }}</span>
                        <svg class="hc-faq__chevron" :class="{ 'hc-faq__chevron--open': faqOpen === i }" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div v-show="faqOpen === i" class="hc-faq__body">
                        <p>{{ faq.answer }}</p>
                    </div>
                </div>
            </div>
        </section>

    </HelpLayout>
</template>

<style scoped>
/* ── Layout ─────────────────────────────────────────────────────────────────── */
.hc-top-row {
    display: grid;
    grid-template-columns: 1.4fr 0.6fr;
    gap: 20px;
    align-items: start;
}

/* ── Cards ──────────────────────────────────────────────────────────────────── */
.hc-card {
    background: var(--mk-surface);
    border: 1px solid var(--mk-line);
    border-radius: 16px;
    padding: 28px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.hc-card__title {
    font-size: 18px;
    font-weight: 800;
    color: var(--mk-ink);
    letter-spacing: -0.3px;
}
.hc-card__desc {
    font-size: 14px;
    color: var(--mk-ink-soft);
    line-height: 1.6;
    margin-top: -6px;
}

/* ── Search ─────────────────────────────────────────────────────────────────── */
.hc-search-wrap {
    position: relative;
}
.hc-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--mk-ink-soft);
    pointer-events: none;
}
.hc-search-input {
    width: 100%;
    height: 48px;
    padding: 0 16px 0 44px;
    border: 1.5px solid var(--mk-line);
    border-radius: 10px;
    background: var(--mk-bg);
    font-family: inherit;
    font-size: 15px;
    color: var(--mk-ink);
    outline: none;
    transition: border-color 0.15s ease;
    box-sizing: border-box;
}
.hc-search-input::placeholder { color: var(--mk-ink-soft); }
.hc-search-input:focus { border-color: var(--mk-accent); }

.hc-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.hc-filter-btn {
    height: 34px;
    padding: 0 14px;
    border-radius: 8px;
    border: 1.5px solid var(--mk-line);
    background: transparent;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    color: var(--mk-ink-mid);
    cursor: pointer;
    transition: all 0.1s ease;
}
.hc-filter-btn:hover {
    background: var(--mk-panel);
    color: var(--mk-ink);
}
.hc-filter-btn--active {
    background: var(--mk-ink);
    color: #fff;
    border-color: var(--mk-ink);
}

/* ── Support card ───────────────────────────────────────────────────────────── */
.hc-support-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: var(--mk-accent-soft);
    color: var(--mk-accent);
    display: flex;
    align-items: center;
    justify-content: center;
}
.hc-support-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 44px;
    width: 100%;
    border-radius: 10px;
    background: var(--mk-accent);
    color: #fff;
    font-family: inherit;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    transition: opacity 0.1s ease;
    margin-top: 4px;
}
.hc-support-btn:hover { opacity: 0.9; }

/* ── Section headers ────────────────────────────────────────────────────────── */
.hc-section-header {
    margin-bottom: 24px;
}
.hc-section-title {
    font-size: 26px;
    font-weight: 900;
    letter-spacing: -0.5px;
    color: var(--mk-ink);
}
.hc-section-desc {
    margin-top: 6px;
    font-size: 15px;
    color: var(--mk-ink-soft);
}

/* ── Badges ─────────────────────────────────────────────────────────────────── */
.hc-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    background: var(--mk-accent-soft);
    color: var(--mk-accent);
}
.hc-badge--outline {
    background: var(--mk-panel);
    color: var(--mk-ink-mid);
}

/* ── Article cards ──────────────────────────────────────────────────────────── */
.hc-articles-grid {
    display: grid;
    gap: 16px;
}
.hc-articles-grid--2 {
    grid-template-columns: repeat(2, 1fr);
}

.hc-article-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 24px;
    background: var(--mk-surface);
    border: 1px solid var(--mk-line);
    border-radius: 14px;
    text-decoration: none;
    color: var(--mk-ink);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    position: relative;
}
.hc-article-card:hover {
    border-color: var(--mk-accent);
    box-shadow: 0 4px 20px rgba(30,102,245,0.08);
}
.hc-article-card--featured {
    padding: 28px;
}
.hc-article-card__meta {
    display: flex;
    align-items: center;
    gap: 10px;
}
.hc-article-card__time {
    font-size: 12px;
    color: var(--mk-ink-soft);
}
.hc-article-card__title {
    font-size: 17px;
    font-weight: 800;
    color: var(--mk-ink);
    letter-spacing: -0.3px;
    line-height: 1.3;
}
.hc-article-card--featured .hc-article-card__title {
    font-size: 20px;
}
.hc-article-card__summary {
    font-size: 14px;
    color: var(--mk-ink-soft);
    line-height: 1.6;
    flex: 1;
}
.hc-article-card__arrow {
    font-size: 18px;
    color: var(--mk-line);
    align-self: flex-end;
    transition: color 0.15s ease, transform 0.15s ease;
    display: inline-block;
}
.hc-article-card:hover .hc-article-card__arrow {
    color: var(--mk-accent);
    transform: translateX(3px);
}

/* ── Topic cards ────────────────────────────────────────────────────────────── */
.hc-topics-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
.hc-topic-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 24px;
    background: var(--mk-surface);
    border: 1px solid var(--mk-line);
    border-radius: 14px;
    text-align: left;
    cursor: pointer;
    font-family: inherit;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.hc-topic-card:hover {
    border-color: var(--mk-accent);
    box-shadow: 0 4px 20px rgba(30,102,245,0.08);
}
.hc-topic-card__icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--mk-accent-soft);
    color: var(--mk-accent);
    display: flex;
    align-items: center;
    justify-content: center;
}
.hc-topic-card__title {
    font-size: 16px;
    font-weight: 800;
    color: var(--mk-ink);
    letter-spacing: -0.2px;
}
.hc-topic-card__desc {
    font-size: 13px;
    color: var(--mk-ink-soft);
    line-height: 1.6;
    flex: 1;
}
.hc-topic-card__count {
    font-size: 13px;
    font-weight: 700;
    color: var(--mk-accent);
}

/* ── Empty state ────────────────────────────────────────────────────────────── */
.hc-empty {
    padding: 64px 32px;
    background: var(--mk-surface);
    border: 1px solid var(--mk-line);
    border-radius: 16px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}
.hc-empty__title {
    font-size: 18px;
    font-weight: 800;
    color: var(--mk-ink);
}
.hc-empty__desc {
    font-size: 14px;
    color: var(--mk-ink-soft);
}
.hc-empty__reset {
    margin-top: 8px;
    height: 40px;
    padding: 0 20px;
    border-radius: 8px;
    border: 1.5px solid var(--mk-line);
    background: transparent;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    color: var(--mk-ink-mid);
    cursor: pointer;
    transition: background 0.1s ease;
}
.hc-empty__reset:hover { background: var(--mk-panel); }

/* ── FAQ ────────────────────────────────────────────────────────────────────── */
.hc-faq {
    border: 1px solid var(--mk-line);
    border-radius: 16px;
    overflow: hidden;
    background: var(--mk-surface);
}
.hc-faq__item {
    border-bottom: 1px solid var(--mk-line-soft);
}
.hc-faq__item:last-child { border-bottom: none; }
.hc-faq__trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 22px 28px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-family: inherit;
    font-size: 16px;
    font-weight: 700;
    color: var(--mk-ink);
    text-align: left;
    transition: background 0.1s ease;
}
.hc-faq__trigger:hover { background: var(--mk-bg); }
.hc-faq__chevron {
    flex-shrink: 0;
    color: var(--mk-ink-soft);
    transition: transform 0.2s ease;
}
.hc-faq__chevron--open { transform: rotate(180deg); }
.hc-faq__body {
    padding: 0 28px 24px;
    font-size: 15px;
    line-height: 1.75;
    color: var(--mk-ink-mid);
}

/* ── Responsive ─────────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
    .hc-top-row { grid-template-columns: 1fr; }
    .hc-topics-grid { grid-template-columns: repeat(2, 1fr); }
    .hc-articles-grid--2 { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .hc-topics-grid { grid-template-columns: 1fr; }
    .hc-faq__trigger { padding: 18px 20px; font-size: 15px; }
    .hc-faq__body { padding: 0 20px 20px; }
}
</style>
