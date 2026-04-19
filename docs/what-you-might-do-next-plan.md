# "What You Might Do Next" — Implementation Plan

## Current state

The section lives in `resources/js/components/dashboard/Overview.vue`. It renders four task rows, each with a title, hint, and chevron that navigates to another tab. The intent is to act as a smart checklist — highlighting what the user still needs to do and marking items off as they complete them.

Two of the four tasks partially work. Two are entirely broken.

---

## Problems to fix

### 1. "Write an About section" is always shown as completed

`hasDescription` is currently defined as:

```ts
const hasDescription = computed(() =>
    !!(site?.data?.overrides?.description || site?.data?.editorialSummary?.text)
);
```

`editorialSummary.text` is the description pulled from Google Places. Because almost every listed business has one, this task appears completed the moment the site is created — before the user has written anything themselves. It should only be marked done when the user has explicitly typed their own override.

**Fix:** remove the Google fallback from the completion check.

```ts
const hasDescription = computed(() => !!(site?.data?.overrides?.description?.trim()));
```

---

### 2. "Add a button" has no completion detection

The task always renders with an incomplete icon regardless of what the user has done. Quick-action links are stored in `site.data.quickLinks` (an array of `{ label, link }` objects, populated during the setup wizard and editable in the Components tab).

**Fix:** add a `hasButtons` computed that checks whether at least one quick link exists.

```ts
const hasButtons = computed(() => (site?.data?.quickLinks?.length ?? 0) > 0);
```

Then bind it to the task row the same way `hasLogo` and `hasDescription` are already bound.

---

### 3. "Set up Search & sharing" has no completion detection

The SEO fields `meta_title` and `meta_description` are top-level columns on the `sites` table (not inside the `data` JSON blob). They are available on the injected `site` object. The task should be marked done when both fields are non-empty strings.

**Fix:** add a `hasSeo` computed.

```ts
const hasSeo = computed(() => !!(site?.meta_title?.trim() && site?.meta_description?.trim()));
```

Bind it to the fourth task row.

---

### 4. Clicking a task opens the right tab but drops the user at the top with no context

All three tasks that route to `'edit'` (the Components tab) land the user at the top of the page with no indication of which section they should interact with. There is no mechanism to scroll to or highlight the Logo, Description, or Quick Links card.

**Fix:** extend the `navigate` event payload to carry an optional `section` anchor alongside the tab ID, and handle it in both Dashboard.vue and Components.vue.

#### Step 1 — Update the emit signature in Overview.vue

```ts
const emit = defineEmits<{
    navigate: [id: 'home' | 'address' | 'edit' | 'seo' | 'help', section?: string]
}>();
```

Update each `@click` to pass a section identifier:

| Task | Tab | Section anchor |
|---|---|---|
| Add your logo | `'edit'` | `'logo'` |
| Write an About section | `'edit'` | `'description'` |
| Add a button | `'edit'` | `'buttons'` |
| Set up Search & sharing | `'seo'` | — (no section needed, the tab is single-focus) |

#### Step 2 — Thread the section through Dashboard.vue

Dashboard.vue currently handles the `navigate` event with:

```ts
function navigate(id: NavId) {
    activeNav.value = id;
    mobileMenuOpen.value = false;
    window.scrollTo(0, 0);
}
```

Add a `focusSection` ref and pass it down:

```ts
const focusSection = ref<string | null>(null);

function navigate(id: NavId, section?: string) {
    activeNav.value = id;
    focusSection.value = section ?? null;
    mobileMenuOpen.value = false;
    window.scrollTo(0, 0);
}
```

Pass `focusSection` as a prop to `<Components>`:

```html
<Components v-else-if="activeNav === 'edit'" :focus-section="focusSection" />
```

#### Step 3 — Scroll-and-highlight in Components.vue

Components.vue receives `focusSection` as a prop. On mount (and whenever the prop changes), scroll the corresponding card into view and briefly highlight it.

```ts
const props = defineProps<{ focusSection?: string | null }>();

watch(() => props.focusSection, (section) => {
    if (!section) return;
    nextTick(() => {
        const el = document.getElementById(`section-${section}`);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            el.classList.add('ec-card--highlighted');
            setTimeout(() => el.classList.remove('ec-card--highlighted'), 1800);
        }
    });
}, { immediate: true });
```

Each section card gets a matching `id`:

```html
<div id="section-logo"        class="ec-card"> … Logo card … </div>
<div id="section-description" class="ec-card"> … Description card … </div>
<div id="section-buttons"     class="ec-card"> … Quick links card … </div>
```

Add a highlight style:

```css
.ec-card--highlighted {
    outline: 2.5px solid #1E66F5;
    outline-offset: 2px;
    transition: outline 0.2s ease;
}
```

---

### 5. Task list should hide once all tasks are done

Once every task is complete the card becomes redundant. Replace it with a congratulations state — a green circle icon, "Your site is looking great!", and a prompt to share the link.

```ts
const allDone = computed(() =>
    hasLogo.value && hasDescription.value && hasButtons.value &&
    hasSeo.value && hasContactForm.value && hasCustomDomain.value && hasAnalytics.value
);
```

Use `v-if="!allDone"` on the task card and `v-else` for the congratulations card.

---

## Premium upsell tasks (new additions)

Three extra items are added below the four core tasks, each marked with a **PRO** badge. They use the same row layout but serve as upsell prompts for paid features. Each disappears once the feature is configured, just like the core tasks.

### 6. Turn on your contact form

Lets visitors send enquiries directly from the site. The `contact_form` component is already modelled in `site.data.components` (it's toggled in the Components tab alongside header, gallery, etc.).

**Completion condition:**
```ts
const hasContactForm = computed(() => site?.data?.components?.contact_form?.enabled === true);
```

**Click destination:** `'edit'` tab (Components tab, contact form card)

**Badge:** `PRO`

---

### 7. Add a custom domain

Lets the user use their own `www.yourbusiness.com` address instead of the free subdomain. The domain settings live in `site.domain_type` and `site.custom_domain` (top-level columns, already in the injected `site` object).

**Completion condition:**
```ts
const hasCustomDomain = computed(() =>
    site?.domain_type === 'custom' && !!site?.custom_domain
);
```

**Click destination:** `'address'` tab (Web address tab)

**Badge:** `PRO`

---

### 8. Add Google Analytics

Allows the user to connect a GA4 Measurement ID so they can see visitor counts and traffic sources. The field `google_analytics_id` is already stored in `site.data` and editable in the Search & sharing tab (Settings.vue).

**Completion condition:**
```ts
const hasAnalytics = computed(() => !!(site?.data?.google_analytics_id?.trim()));
```

**Click destination:** `'seo'` tab (Search & sharing tab)

**Badge:** `PRO`

---

### PRO badge styling

A small pill appended inline inside the task title:

```html
<span class="ov-pro-badge">PRO</span>
```

```css
.ov-pro-badge {
    display: inline-flex;
    align-items: center;
    padding: 1px 7px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    background: #111418;
    color: #F6D860;
    margin-left: 7px;
    vertical-align: middle;
}
```

---

## Summary of changes

| File | Change |
|---|---|
| `Overview.vue` | Fix `hasDescription`; add `hasButtons`, `hasSeo`, `hasContactForm`, `hasCustomDomain`, `hasAnalytics` computeds; switch tasks to `v-if` (hide when done rather than strike-through); add 3 PRO tasks; add `allDone` congratulations state; add PRO badge styles |
| `Dashboard.vue` | Update `navigate()` to accept and store `section`; pass `focusSection` prop to `<Components>` |
| `Components.vue` | Accept `focusSection` prop; add `watch` to scroll + highlight; add `id` attributes to section cards; add highlight CSS |

No backend changes are needed — all required data is already present in the Inertia page props: `site.data.quickLinks`, `site.data.overrides.*`, `site.data.components.*`, `site.data.google_analytics_id`, `site.meta_title`, `site.meta_description`, `site.domain_type`, `site.custom_domain`.
