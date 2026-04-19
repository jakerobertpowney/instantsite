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
        <div class="ha-breadcrumb">
            <Link href="/help" class="ha-breadcrumb__back">
                <ArrowLeft :size="16" aria-hidden="true" />
                Back to help centre
            </Link>
            <span class="ha-breadcrumb__sep" aria-hidden="true">/</span>
            <span class="ha-badge">{{ article.category_title }}</span>
            <span class="ha-breadcrumb__time">{{ article.read_time }}</span>
        </div>

        <!-- Body -->
        <div class="ha-body">
            <!-- Article content -->
            <article class="ha-article">
                <section
                    v-for="section in article.sections"
                    :key="section.id"
                    :id="section.id"
                    class="ha-section"
                >
                    <h2 class="ha-section__title">{{ section.title }}</h2>
                    <p
                        v-for="paragraph in section.paragraphs"
                        :key="paragraph"
                        class="ha-section__para"
                    >
                        {{ paragraph }}
                    </p>
                    <ul v-if="section.bullets?.length" class="ha-section__bullets">
                        <li v-for="bullet in section.bullets" :key="bullet">{{ bullet }}</li>
                    </ul>
                    <div class="ha-section__rule" />
                </section>
            </article>

            <!-- Sidebar -->
            <aside class="ha-sidebar">
                <!-- On this page -->
                <div class="ha-sidebar-card">
                    <h3 class="ha-sidebar-card__title">On this page</h3>
                    <p class="ha-sidebar-card__desc">Jump to a section in this guide.</p>
                    <div class="ha-toc">
                        <a
                            v-for="section in article.sections"
                            :key="section.id"
                            :href="`#${section.id}`"
                            class="ha-toc__item"
                        >
                            <span>{{ section.title }}</span>
                            <ChevronRight :size="15" class="ha-toc__chevron" aria-hidden="true" />
                        </a>
                    </div>
                </div>

                <!-- Need more help -->
                <div class="ha-sidebar-card ha-sidebar-card--support">
                    <h3 class="ha-sidebar-card__title">Need more help?</h3>
                    <p class="ha-sidebar-card__body">
                        Email <a href="mailto:help@321sites.com" class="ha-link">help@321sites.com</a> and tell us what you're trying to do.
                    </p>
                </div>
            </aside>
        </div>

        <!-- Related articles -->
        <section v-if="relatedArticles.length" class="ha-related">
            <div class="ha-related__header">
                <h2 class="ha-related__title">Related guides</h2>
                <p class="ha-related__desc">A few more articles that usually help next.</p>
            </div>
            <div class="ha-related__grid">
                <Link
                    v-for="related in relatedArticles"
                    :key="related.slug"
                    :href="`/help/${related.slug}`"
                    class="ha-article-card"
                >
                    <div class="ha-article-card__meta">
                        <span class="ha-badge ha-badge--outline">{{ related.category_title }}</span>
                        <span class="ha-article-card__time">{{ related.read_time }}</span>
                    </div>
                    <h3 class="ha-article-card__title">{{ related.title }}</h3>
                    <p class="ha-article-card__summary">{{ related.summary }}</p>
                    <span class="ha-article-card__arrow" aria-hidden="true">→</span>
                </Link>
            </div>
        </section>
    </HelpLayout>
</template>

<style scoped>
/* ── Breadcrumb ─────────────────────────────────────────────────────────────── */
.ha-breadcrumb {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.ha-breadcrumb__back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    font-weight: 600;
    color: var(--mk-ink-mid);
    text-decoration: none;
    padding: 6px 10px;
    border-radius: 8px;
    margin-left: -10px;
    transition: background 0.1s ease, color 0.1s ease;
}
.ha-breadcrumb__back:hover {
    background: var(--mk-panel);
    color: var(--mk-ink);
}
.ha-breadcrumb__sep {
    color: var(--mk-line);
    font-size: 16px;
}
.ha-breadcrumb__time {
    font-size: 13px;
    color: var(--mk-ink-soft);
}

/* ── Badges ─────────────────────────────────────────────────────────────────── */
.ha-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    background: var(--mk-accent-soft);
    color: var(--mk-accent);
}
.ha-badge--outline {
    background: var(--mk-panel);
    color: var(--mk-ink-mid);
}

/* ── Body layout ────────────────────────────────────────────────────────────── */
.ha-body {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 48px;
    align-items: start;
}

/* ── Article ────────────────────────────────────────────────────────────────── */
.ha-article {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.ha-section {
    scroll-margin-top: 100px;
    padding-bottom: 40px;
}
.ha-section__title {
    font-size: 22px;
    font-weight: 900;
    letter-spacing: -0.4px;
    color: var(--mk-ink);
    margin-bottom: 16px;
}
.ha-section__para {
    font-size: 16px;
    line-height: 1.8;
    color: var(--mk-ink-mid);
    margin-bottom: 12px;
}
.ha-section__bullets {
    margin: 8px 0 12px 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.ha-section__bullets li {
    font-size: 15px;
    line-height: 1.7;
    color: var(--mk-ink-mid);
    list-style-type: disc;
}
.ha-section__rule {
    height: 1px;
    background: var(--mk-line-soft);
    margin-top: 32px;
}

/* ── Sidebar ────────────────────────────────────────────────────────────────── */
.ha-sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
    position: sticky;
    top: 88px;
}
.ha-sidebar-card {
    background: var(--mk-surface);
    border: 1px solid var(--mk-line);
    border-radius: 14px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.ha-sidebar-card__title {
    font-size: 15px;
    font-weight: 800;
    color: var(--mk-ink);
    letter-spacing: -0.2px;
}
.ha-sidebar-card__desc {
    font-size: 13px;
    color: var(--mk-ink-soft);
    margin-top: -4px;
}
.ha-sidebar-card__body {
    font-size: 14px;
    line-height: 1.65;
    color: var(--mk-ink-mid);
}
.ha-sidebar-card--support {
    background: var(--mk-panel);
    border-color: var(--mk-line-soft);
}

/* ── ToC ────────────────────────────────────────────────────────────────────── */
.ha-toc {
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin-top: 4px;
}
.ha-toc__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 8px 10px;
    margin: 0 -10px;
    border-radius: 8px;
    font-size: 13px;
    color: var(--mk-ink-mid);
    text-decoration: none;
    transition: background 0.1s ease, color 0.1s ease;
}
.ha-toc__item:hover {
    background: var(--mk-panel);
    color: var(--mk-ink);
}
.ha-toc__chevron {
    flex-shrink: 0;
    opacity: 0.4;
}

/* ── Link ───────────────────────────────────────────────────────────────────── */
.ha-link {
    color: var(--mk-accent);
    text-decoration: underline;
    text-underline-offset: 3px;
}

/* ── Related articles ───────────────────────────────────────────────────────── */
.ha-related__header {
    margin-bottom: 24px;
}
.ha-related__title {
    font-size: 26px;
    font-weight: 900;
    letter-spacing: -0.5px;
    color: var(--mk-ink);
}
.ha-related__desc {
    margin-top: 6px;
    font-size: 15px;
    color: var(--mk-ink-soft);
}
.ha-related__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.ha-article-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 22px;
    background: var(--mk-surface);
    border: 1px solid var(--mk-line);
    border-radius: 14px;
    text-decoration: none;
    color: var(--mk-ink);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.ha-article-card:hover {
    border-color: var(--mk-accent);
    box-shadow: 0 4px 20px rgba(30,102,245,0.08);
}
.ha-article-card__meta {
    display: flex;
    align-items: center;
    gap: 10px;
}
.ha-article-card__time {
    font-size: 12px;
    color: var(--mk-ink-soft);
}
.ha-article-card__title {
    font-size: 16px;
    font-weight: 800;
    color: var(--mk-ink);
    letter-spacing: -0.2px;
    line-height: 1.3;
}
.ha-article-card__summary {
    font-size: 13px;
    color: var(--mk-ink-soft);
    line-height: 1.6;
    flex: 1;
}
.ha-article-card__arrow {
    font-size: 16px;
    color: var(--mk-line);
    align-self: flex-end;
    transition: color 0.15s ease, transform 0.15s ease;
    display: inline-block;
}
.ha-article-card:hover .ha-article-card__arrow {
    color: var(--mk-accent);
    transform: translateX(3px);
}

/* ── Responsive ─────────────────────────────────────────────────────────────── */
@media (max-width: 1024px) {
    .ha-body { grid-template-columns: 1fr; }
    .ha-sidebar { position: static; flex-direction: row; flex-wrap: wrap; }
    .ha-sidebar-card { flex: 1; min-width: 240px; }
    .ha-related__grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .ha-related__grid { grid-template-columns: 1fr; }
    .ha-sidebar { flex-direction: column; }
}
</style>
