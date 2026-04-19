# 321Sites — Project Instructions

## Project Overview

**321Sites** is a full-stack web application that lets small businesses create a professional one-page website in minutes. The user searches for their Google Business listing, selects it, optionally adds a logo and extra details, then gets a hosted website at a subdomain (e.g. `mybusiness.321sites.test`) — similar to how Google Sites used to work.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3 (Composition API), TypeScript 5.2 |
| Build Tool | Vite 7 with Laravel Vite Plugin |
| Styling | Tailwind CSS 4.1 |
| UI Components | shadcn-Vue, Reka-UI, Flowbite Vue, Lucide icons |
| Routing / Reactivity | Inertia.js (server-driven, no full SPA) |
| Database | SQLite (dev) — swappable to MySQL/PostgreSQL |
| Queues | Laravel database queue (async job batching) |
| Auth | Laravel Sanctum + session-based auth |
| Notifications | vue-sonner (toast) |
| Testing | Pest PHP 4, PHPUnit |
| CI/CD | GitHub Actions (tests + lint on push to `develop`/`main`) |

---

## User Flow

```
1. Search  →  User types their business name/address on the landing page
2. Discover →  App fetches full place details + photos from Google Places API (async jobs)
3. Setup    →  User uploads a logo, adds description, social links, quick links
4. Preview  →  User sees a live preview of their finished one-page site
5. Register →  User creates an account (email + password)
6. Publish  →  Site goes live at their chosen subdomain or custom domain
7. Manage   →  Dashboard lets the user edit meta tags, domain type, and site content
```

---

## Key Directories

```
app/
  Http/Controllers/     # Request handlers (Auth, Dashboard, Preview, Search, Site, Settings)
  Jobs/                 # FetchPlaceDetails, FetchPlacePhoto (async Google Places fetching)
  Models/               # User, Site, TemporarySite
  Http/Requests/        # Form validation classes

resources/js/
  pages/                # Inertia page components (Welcome, Dashboard, Auth, Settings, site/)
  components/           # Reusable Vue components (dashboard/, setup/, site/, ui/)
  composables/          # useAppearance, useInitials
  types/                # TypeScript definitions

routes/
  web.php               # Main web routes
  api.php               # Public API routes (place search & poll)
  auth.php              # Auth routes
  settings.php          # Profile / password / appearance settings

database/migrations/    # 10 migrations (users, sites, temporary_sites, jobs, sessions, etc.)
```

---

## Database Models

| Model | Table | Purpose |
|---|---|---|
| `User` | `users` | Account — has `places_id` linking to their site |
| `Site` | `sites` | A published site — stores `data` (JSON), domain config, meta tags |
| `TemporarySite` | `temporary_sites` | Holds Google place data + user customisations during the setup wizard |

The `Site.data` and `TemporarySite.data` columns are JSON blobs containing the full Google Places payload merged with user-supplied fields (logo path, description, socials, links, etc.).

---

## Important Routes

### Public
| Method | URI | What it does |
|---|---|---|
| GET | `/` | Landing page — place search |
| POST | `/api/search/places` | Search Google Places |
| POST | `/api/search/discover` | Start async place fetch, returns `batchId` |
| GET | `/api/search/discover/{batchId}/poll` | Poll until async jobs complete |
| GET | `/discover/{id}` | Loading screen while jobs run |
| GET | `/setup/{id}` | Customisation form |
| POST | `/setup/{id}` | Save customisation |
| GET | `/preview/{id}` | Preview the finished site |
| POST | `/setup/{id}/complete` | Store `places_id` in session, redirect to register |

### Authenticated
| Method | URI | What it does |
|---|---|---|
| GET | `/dashboard` | User dashboard (Overview, Site, Components, Settings tabs) |
| POST | `/dashboard/site` | Update site settings (domain type, subdomain, custom domain, meta) |
| POST | `/dashboard/settings` | Update global site settings |

### Dynamic (Multi-tenant)
Subdomains like `{slug}.321sites.test` are resolved by `SiteController@index`, which looks up the matching `Site` record and renders the public one-page site.

---

## Async Job Architecture

When a user selects a place, the app cannot call Google Places synchronously in a web request. Instead:

1. `SearchController::discover()` creates a `TemporarySite` record and dispatches a **job batch**:
   - `FetchPlaceDetails` — calls Google Places API v1, saves structured data to `TemporarySite.data`
   - `FetchPlacePhoto` × N — downloads each photo, saves to `storage/app/public/images/{places_id}/`
2. The frontend polls `GET /api/search/discover/{batchId}/poll` until the batch finishes.
3. Once complete, the user is redirected to `/setup/{id}`.

Run the queue worker during development:
```bash
php artisan queue:listen
```

---

## Environment Variables

Key variables in `.env` (see `.env.example` for the full list):

```dotenv
APP_URL=https://321sites.test
APP_DOMAIN=321sites.test          # Root domain used to build subdomains

GOOGLE_API_KEY=...                   # Google Places API v1 key

DB_CONNECTION=sqlite

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
```

---

## Running Locally

All four processes run concurrently (or use `composer run dev` to start all at once):

```bash
php artisan serve          # Laravel on :8000
php artisan queue:listen   # Processes async jobs
php artisan pail           # Tail logs
npm run dev                # Vite dev server
```

Run tests:
```bash
composer test              # or: ./vendor/bin/pest
```

Run linting:
```bash
npm run lint
```

---

## Domain / Multi-tenancy Notes

- The root domain is set by `APP_DOMAIN` in `.env`.
- Subdomains are stored in `sites.subdomain` and served via wildcard DNS / host-file entry pointing `*.321sites.test` to `127.0.0.1` in development.
- Domain types: `subdomain` | `custom` | `draft`. Draft sites are unpublished.

---

## Frontend Conventions

- All pages live in `resources/js/pages/` and are Inertia page components (default exports).
- Shared UI primitives are in `resources/js/components/ui/` (shadcn-Vue).
- Feature-specific components are grouped by feature: `dashboard/`, `setup/`, `site/`.
- Appearance (light/dark/system) is managed by the `useAppearance` composable and persisted to `localStorage`.
- Forms use Inertia's `useForm` for two-way binding, validation errors, and submission state.
- Toast notifications use `vue-sonner` via a globally available `toast` helper.

---

## Production Considerations

For a production deployment, swap these defaults:
- Database: SQLite → MySQL or PostgreSQL
- Queue: `database` → Redis / Beanstalkd
- Cache / Session: `database` → Redis
- File storage: local `public` disk → S3 / R2
- Mail: `log` → SMTP, Resend, or SES
- Enable SSR via the Node.js SSR entry (`resources/js/ssr.ts`) for better performance

---

## Active Upgrade Scope — Laravel & shadcn-vue UI Rebuild

This section defines a concrete, executable scope of work. Work through each phase in order. Complete all tasks in a phase and verify the checklist passes before moving to the next.

> **CRITICAL — DO NOT TOUCH:** `resources/js/pages/site/`, `resources/js/components/site/`, and any Blade/CSS/JS that renders the public-facing one-page business websites. These files must remain completely unchanged throughout this entire upgrade.

---

### Phase 1 — Dependency Audit & Upgrade

**Goal:** Get all dependencies to their latest stable versions and remove Flowbite Vue and any standalone Reka-UI imports.

```bash
# 1. Audit outdated packages
composer outdated
npm outdated

# 2. Upgrade PHP dependencies
composer update

# 3. Upgrade JS dependencies
npx npm-check-updates -u
npm install

# 4. Remove Flowbite Vue
npm uninstall flowbite-vue flowbite

# 5. Verify build still works
npm run build

# 6. Run the full test suite — must pass before proceeding
composer test
npm run lint
```

**After removing Flowbite Vue**, grep for any remaining imports and fix compile errors before continuing:

```bash
grep -r "flowbite" resources/js --include="*.vue" --include="*.ts" -l
grep -r "from 'reka-ui'" resources/js --include="*.vue" --include="*.ts" -l
```

Any `reka-ui` direct imports should be replaced with the equivalent import from `@/components/ui/` (shadcn-vue already re-exports all Reka-UI primitives).

**Phase 1 checklist:**
- [ ] `composer outdated` shows no outdated first-party Laravel packages
- [ ] `npm outdated` shows no outdated packages (or only intentional pins)
- [ ] `flowbite-vue` and `flowbite` are absent from `package.json`
- [ ] No `flowbite` imports remain in `resources/js/`
- [ ] No direct `reka-ui` imports remain in `resources/js/`
- [ ] `npm run build` succeeds with zero errors
- [ ] `composer test` passes
- [ ] `npm run lint` passes

---

### Phase 2 — shadcn-vue Component Audit

**Goal:** Identify every component that needs to be rebuilt and install any missing shadcn-vue components before writing a single line of UI code.

1. List every component currently in use across all in-scope pages:

```bash
grep -r "from '@/components/ui" resources/js --include="*.vue" --include="*.ts" -h \
  | sort -u

grep -r "from 'flowbite-vue'" resources/js --include="*.vue" -h \
  | sort -u
```

2. For each missing shadcn-vue component, add it:

```bash
npx shadcn-vue@latest add <component-name>
# e.g.:
npx shadcn-vue@latest add command        # place search autocomplete
npx shadcn-vue@latest add alert-dialog   # confirm/destructive dialogs
npx shadcn-vue@latest add radio-group    # domain type selector
npx shadcn-vue@latest add switch         # component toggles
npx shadcn-vue@latest add skeleton       # loading states
npx shadcn-vue@latest add progress       # discover loading screen
npx shadcn-vue@latest add separator
npx shadcn-vue@latest add accordion
npx shadcn-vue@latest add sidebar
```

3. Confirm all components needed for Phase 3 exist in `resources/js/components/ui/` before proceeding.

**Component mapping reference** (Flowbite → shadcn-vue):

| Flowbite component | shadcn-vue replacement |
|---|---|
| `FwbInput` | `Input` |
| `FwbButton` | `Button` |
| `FwbDropdown` | `DropdownMenu` |
| `FwbModal` | `Dialog` |
| `FwbTabs` / `FwbTab` | `Tabs` / `TabsList` / `TabsTrigger` / `TabsContent` |
| `FwbBadge` | `Badge` |
| `FwbCard` | `Card` / `CardHeader` / `CardContent` / `CardFooter` |
| `FwbSelect` | `Select` |
| `FwbCheckbox` | `Checkbox` |
| `FwbRadio` | `RadioGroup` / `RadioGroupItem` |
| `FwbToggle` | `Switch` |
| `FwbAlert` | `Alert` / `AlertTitle` / `AlertDescription` |
| `FwbTextarea` | `Textarea` |
| Custom file upload | `Button` + hidden `<input type="file">` |
| Custom place search | `Command` inside a `Popover` |
| Custom confirm dialog | `AlertDialog` |

**Phase 2 checklist:**
- [ ] All Flowbite components have a mapped shadcn-vue equivalent
- [ ] All required shadcn-vue components are installed in `resources/js/components/ui/`
- [ ] `npm run build` still succeeds

---

### Phase 3 — UI Rebuild (Page by Page)

Work through each area below in order. After each item, run `npm run build` and verify the page renders correctly in the browser before moving on.

**Form handling rule:** Do not change any `useForm`, `router.post`, or Inertia submission logic. Only replace the template/presentational layer.

**Theme rule:** Every rebuilt component must support dark mode. Use `dark:` Tailwind variants as needed. Test by toggling the appearance setting after each page.

#### 3.1 Global Layout (`resources/js/layouts/AppLayout.vue`)
- Replace any Flowbite navigation/sidebar primitives with shadcn-vue `Sidebar` and `NavigationMenu`
- Ensure mobile responsive behaviour is preserved

#### 3.2 Shared UI Primitives (`resources/js/components/ui/`)
- Remove any Flowbite-backed component wrappers
- Ensure all exports are pure shadcn-vue components
- Do not delete components that are used by `resources/js/components/site/` — check before removing

#### 3.3 Auth Pages
Files: `resources/js/pages/auth/Login.vue`, `Register.vue`, `ForgotPassword.vue`, `ResetPassword.vue`, `VerifyEmail.vue`, `ConfirmPassword.vue`
- Replace Flowbite form fields with `Input`, `Label`, `Button`, `Alert`
- Preserve all `useForm` bindings and error display logic

#### 3.4 User Settings Pages
Files: `resources/js/pages/settings/Profile.vue`, `Password.vue`, `Appearance.vue`
- `Profile.vue` — rebuild with shadcn-vue `Form`, `Input`, `Button`; keep delete-account flow using `AlertDialog`
- `Password.vue` — rebuild with shadcn-vue `Form`, `Input`, `Button`
- `Appearance.vue` — rebuild theme selector using `ToggleGroup`; keep `useAppearance` composable as-is

#### 3.5 Landing Page (`resources/js/pages/Welcome.vue`)
- Rebuild search input with shadcn-vue `Input` and `Button`
- Replace results list / place cards with `Card` components
- Replace any Flowbite modals or dropdowns with shadcn-vue `Dialog` or `Command`

#### 3.6 Discover Loading Screen (`resources/js/pages/preview/Discover.vue`)
- Replace any Flowbite spinners/loaders with shadcn-vue `Skeleton` and `Progress`

#### 3.7 Setup Wizard (`resources/js/pages/preview/Setup.vue` + `resources/js/components/setup/`)
- Rebuild all form fields with `Input`, `Textarea`, `Label`, `Button`
- Logo upload: use `Button` + hidden file `<input>` (no library component needed)
- Social links / quick links sections: use `Card` + `Input` + `Button`

#### 3.8 Preview Page (`resources/js/pages/preview/Index.vue`)
- Rebuild wrapper layout with shadcn-vue `Card` and `Tabs`
- Keep all preview rendering logic untouched — only the chrome/wrapper changes

#### 3.9 Dashboard — Overview Tab (`resources/js/components/dashboard/Overview.vue` or equivalent)
- Replace Flowbite stat cards with `Card` / `CardHeader` / `CardContent`
- Replace status badges with `Badge`

#### 3.10 Dashboard — Site Settings Tab
- Domain type selector: replace with `RadioGroup` / `RadioGroupItem`
- Subdomain / custom domain inputs: replace with `Input` + `Label`
- Meta title / description fields: replace with `Input` + `Textarea`

#### 3.11 Dashboard — Components Tab
- Component on/off toggles: replace with `Switch`
- Configuration sections: wrap in `Accordion`

#### 3.12 Dashboard — Settings Tab
- Replace remaining Flowbite form fields with `Input`, `Button`, `Label`

**Phase 3 checklist (complete for every rebuilt page):**
- [ ] No `flowbite-vue` imports remain in the file
- [ ] `npm run build` succeeds after each file change
- [ ] Page renders correctly in light mode
- [ ] Page renders correctly in dark mode
- [ ] All form submissions still work (no regressions to `useForm` logic)
- [ ] No TypeScript errors (`tsc --noEmit`)

---

### Phase 4 — QA & Final Verification

Run through this checklist in full before considering the work complete.

**Automated checks:**
```bash
npm run lint          # must return zero errors
npx tsc --noEmit      # must return zero TypeScript errors
composer test         # must pass with no regressions
npm run build         # must succeed
```

**Manual checks — run through the full user flow:**
1. Search for a business on the landing page
2. Select a result and wait for the discover loading screen to complete
3. Fill out the setup wizard (upload a logo, add a description)
4. View the preview page
5. Register a new account
6. Land on the dashboard; verify all four tabs render correctly
7. Update the domain settings and save
8. Visit the profile and password settings pages
9. Toggle between light, dark, and system appearance modes on every page
10. Log out and log back in

**Accessibility spot-checks:**
- Tab through every form — focus order must be logical
- All interactive elements must have visible focus rings
- All form inputs must have associated `<label>` elements

**Protected files verification — confirm these are byte-for-byte unchanged:**
```bash
git diff HEAD -- resources/js/pages/site/
git diff HEAD -- resources/js/components/site/
```

Both commands must produce **no output** (no changes).

**Final checklist:**
- [ ] `npm run lint` — zero errors
- [ ] `tsc --noEmit` — zero errors
- [ ] `composer test` — all tests pass
- [ ] `npm run build` — succeeds
- [ ] Full user flow works end-to-end in Chrome and Firefox
- [ ] Light / dark / system theme works on every rebuilt page
- [ ] No Flowbite Vue imports anywhere in `resources/js/`
- [ ] `git diff HEAD -- resources/js/pages/site/` — no output
- [ ] `git diff HEAD -- resources/js/components/site/` — no output

---

## Phase 5 — Dashboard Content & Component Editor

**Goal:** Replace the placeholder Components tab with a fully functional content editor. Users can toggle site sections on/off, edit all editable content fields, and see a live preview of their site alongside the form — all without leaving the dashboard.

**Approach:** Form-based editing (text inputs, toggles, file upload) with a split-panel live preview. No drag-and-drop or visual editor. The left panel contains the edit form; the right panel renders the published site in an `<iframe>` that refreshes on save.

> **CRITICAL — DO NOT TOUCH:** `resources/js/pages/site/`, `resources/js/components/site/`, and any Blade/CSS/JS that renders the public-facing one-page business websites. These files must remain completely unchanged throughout this entire phase.

---

### 5.1 Data Model — `sites.data` JSON Schema

The `Site.data` column (JSON) must be extended to store component visibility flags and overridable content fields. Define and document the schema before writing any code.

**Required additions to the `data` JSON blob:**

```json
{
  "components": {
    "header":       { "enabled": true },
    "description":  { "enabled": true },
    "gallery":      { "enabled": true },
    "quick_actions":{ "enabled": true },
    "reviews":      { "enabled": true },
    "contact":      { "enabled": true }
  },
  "overrides": {
    "description": "",
    "logo_path":   ""
  }
}
```

- `components.*enabled` — controls whether each section renders on the public site
- `overrides.description` — user-supplied description that replaces the Google Places description
- `overrides.logo_path` — path to the uploaded logo file (already handled in setup wizard; move to this key)

**Migration rule:** When `data.components` or `data.overrides` keys are absent (legacy records), the site renderer must treat all components as enabled and use the Google Places data as-is. No destructive migration needed.

---

### 5.2 Backend

#### 5.2.1 New request class — `UpdateDashboardComponentsRequest`
File: `app/Http/Requests/Admin/UpdateDashboardComponentsRequest.php`

Validate:
- `components` — array, each key must be a known component name
- `components.*.enabled` — boolean
- `overrides.description` — nullable string, max 2000 chars
- `logo` — nullable file, image, max 2 MB

#### 5.2.2 New controller method — `DashboardController::components()`
File: `app/Http/Controllers/Admin/DashboardController.php`

- `POST /dashboard/components` — merge validated `components` and `overrides` into `Site.data`; handle logo upload to `storage/app/public/images/{places_id}/logo.*`; return `redirect()->back()` with a success flash

Add the route to `routes/web.php`:
```php
Route::post('dashboard/components', [DashboardController::class, 'components'])->name('dashboard.components');
```

#### 5.2.3 Pass `data` to the dashboard page
In `DashboardController::index()`, ensure `site.data` is included in the Inertia response (it should already be via `$site` — confirm the `data` cast is working).

---

### 5.3 Frontend

#### 5.3.1 Layout — Split panel
File: `resources/js/components/dashboard/Components.vue`

Replace the current placeholder with a two-column split layout:
- **Left (edit panel, ~40% width):** scrollable form with all editable fields
- **Right (preview panel, ~60% width):** `<iframe>` pointing at the live site URL, with a manual "Refresh preview" button

On mobile (< `lg` breakpoint), stack vertically — form first, preview below.

#### 5.3.2 Component toggles section
Within the edit panel, render one row per site component using shadcn-vue `Switch`:

| Component name | Label |
|---|---|
| `header` | Header |
| `description` | About / Description |
| `gallery` | Photo Gallery |
| `quick_actions` | Quick Actions |
| `reviews` | Reviews |
| `contact` | Contact Info |

Each row: component label on the left, `Switch` on the right. Wrap the list in a shadcn-vue `Card`.

#### 5.3.3 Content fields section
Below the toggles, render editable content fields inside their own `Card`:

- **Logo** — current logo thumbnail (if set) + `Button` + hidden `<input type="file" accept="image/*">` to replace it
- **Description** — `Textarea` bound to `overrides.description`; placeholder text: the Google Places description from `site.data`

#### 5.3.4 Form submission
Use Inertia `useForm` with `post(route('dashboard.components'), { forceFormData: true })` (required for file upload). On success, show a `vue-sonner` toast and refresh the preview iframe.

#### 5.3.5 Live preview iframe
- `src` = the site's current public URL (derived from `domain_type`, `subdomain`, `custom_domain`)
- Expose a `refreshPreview()` method that increments a cache-bust query param to force an iframe reload after save
- Show a `Skeleton` placeholder while the iframe is loading

---

### 5.4 Site Renderer — Respect Component Flags

File: `resources/js/pages/site/Index.vue` (and child components in `resources/js/components/site/`)

> **Read-only change only.** The renderer must check `site.data.components.*enabled` before rendering each section. If the flag is absent (legacy data), default to `true`.

Implementation: pass a computed `components` object down as a prop or via `provide/inject`, then wrap each section with `v-if="components.header.enabled"` etc.

**This is the only permitted change to site/ files.** No layout, styling, or logic changes.

---

### 5.5 Required shadcn-vue Components

Confirm these are already installed; add any that are missing:

```bash
npx shadcn-vue@latest add switch       # component toggles
npx shadcn-vue@latest add accordion    # collapsible sections (if used)
npx shadcn-vue@latest add skeleton     # iframe loading state
```

---

### Phase 5 Checklist

**Backend:**
- [ ] `UpdateDashboardComponentsRequest` created with correct validation rules
- [ ] `DashboardController::components()` saves `data.components` and `data.overrides` correctly
- [ ] Logo upload saves to the correct storage path
- [ ] `POST /dashboard/components` route registered and named `dashboard.components`
- [ ] Legacy sites without `data.components` still render all sections (default-to-enabled)

**Frontend:**
- [ ] Split-panel layout renders correctly at `lg`+ breakpoints
- [ ] Single-column stacked layout renders correctly below `lg`
- [ ] All six component toggles render and persist correctly
- [ ] Description textarea saves and overrides Google Places description on the public site
- [ ] Logo upload replaces the existing logo and updates the public site
- [ ] Preview iframe loads the correct live site URL
- [ ] Preview iframe refreshes after a successful save
- [ ] `Skeleton` shown while iframe is loading
- [ ] `vue-sonner` toast fires on successful save
- [ ] Dark mode renders correctly across the entire Components tab

**Site renderer:**
- [ ] Each site section respects its `data.components.*.enabled` flag
- [ ] Absent flags default to `true` (no broken legacy sites)
- [ ] `git diff HEAD -- resources/js/pages/site/` — only `v-if` guard additions, no other changes
- [ ] `git diff HEAD -- resources/js/components/site/` — only `v-if` guard additions, no other changes

**Automated checks:**
- [ ] `npm run lint` — zero errors
- [ ] `npx tsc --noEmit` — zero TypeScript errors
- [ ] `composer test` — all tests pass
- [ ] `npm run build` — succeeds

---

## Phase 6 — Section Cards with Google Content Preview

**Goal:** Upgrade the Components tab from a flat toggle list + separate content card into a richer per-section layout. Each site section gets its own `Card` that shows the live Google data currently being displayed on the public site, makes it clear that content is managed via Google Business Profile, and surfaces any available override fields inline.

**Prerequisite:** Phase 5 complete (backend, form submission, and iframe preview all working).

> **CRITICAL — DO NOT TOUCH:** `resources/js/pages/site/`, `resources/js/components/site/`, and any Blade/CSS/JS that renders the public-facing one-page business websites.

---

### 6.1 Card structure

Replace the flat "Site Sections" toggle card and the separate "Content" card in `resources/js/components/dashboard/Components.vue` with one `Card` per site section. The order matches the visual order on the public site: Header → About / Description → Photo Gallery → Quick Actions → Reviews → Contact Info.

Each card follows this structure top-to-bottom:

1. **Card header row** — section label (`CardTitle`) on the left, `Switch` toggle on the right. The toggle maps to `form.components[key].enabled` as in Phase 5.
2. **Google-managed notice** — a single line below the header reading _"Managed via Google Business Profile"_ with an `ExternalLink` Lucide icon. Style as `<p class="text-xs text-muted-foreground flex items-center gap-1">`. This line appears on every card.
3. **Read-only content preview** — muted text showing the exact data currently live on the public site, sourced from `site.data`. Not form inputs. Style fields as `<p class="text-sm text-muted-foreground">` with a small label above each field.
4. **`Separator`** — only rendered when override fields are present below.
5. **Override fields** — editable inputs. Only present for Header (logo) and About / Description (description textarea). All other sections have no override fields at this stage.

---

### 6.2 Per-section content spec

Source all read-only values from the injected `site` object (`site.data` for nested fields, top-level `site` properties where applicable).

#### Header
Read-only preview:
- Business name — `site.data.displayName.text`
- Business type — `site.data.primaryTypeDisplayName.text`
- Location — locality + administrative_area_level_1 derived from `site.data.addressComponents` (same logic as `Header.vue`)

Override fields (below `Separator`):
- **Logo** — thumbnail of current logo (`site.data.overrides.logo_path` if set, else no image shown) + `Button` + hidden `<input type="file" accept="image/*">` triggering `form.logo`

#### About / Description
Read-only preview:
- Google Places description — `site.data.editorialSummary.text` (fall back to `site.data.description` if absent); if neither exists show _"No description provided by Google."_ in italic muted text

Override fields (below `Separator`):
- **Description** — `Textarea` bound to `form.overrides.description`; placeholder = Google Places description text; helper text below: _"Leave blank to use the Google Places description."_

#### Photo Gallery
Read-only preview:
- Photo count — `site.data.photos?.length ?? 0` rendered as e.g. _"6 photos from Google"_; if zero show _"No photos available."_

No override fields.

#### Quick Actions
Read-only preview:
- Phone number — `site.data.nationalPhoneNumber` or `site.data.internationalPhoneNumber`; if absent show _"No phone number."_
- Custom quick links — list of `link.label` values from `site.data.quickLinks` (added during setup wizard); if empty show _"No custom links added."_

No override fields.

#### Reviews
Read-only preview:
- Rating — `site.data.rating` rendered as e.g. _"4.5 ★"_; if absent show _"No rating available."_
- Review count — `site.data.userRatingCount` rendered as e.g. _"142 reviews"_

No override fields.

#### Contact Info
Read-only preview:
- Address — `site.data.formattedAddress`; if absent show _"No address available."_
- Phone — `site.data.nationalPhoneNumber`; if absent show _"No phone number."_
- Opening hours — count of configured days derived from `site.data.regularOpeningHours.periods`; rendered as e.g. _"7 days configured"_; if absent show _"No opening hours available."_

No override fields.

---

### 6.3 Form and submission

No backend changes required — the form payload (`components`, `overrides`, `logo`) and the `POST /dashboard/components` endpoint are unchanged from Phase 5. The single Save button below all cards and the `vue-sonner` success toast remain as-is.

---

### 6.4 Required shadcn-vue components

Confirm these are installed; add any missing:

```bash
npx shadcn-vue@latest add separator   # divider between read-only and override fields
npx shadcn-vue@latest add badge       # Google-managed notice (optional alternative to plain text)
```

---

### Phase 6 Checklist

- [ ] Flat "Site Sections" toggle card and separate "Content" card removed
- [ ] Six section cards render in correct order
- [ ] Each card: toggle in header, Google-managed notice, read-only content preview
- [ ] Header card shows business name, type, and location from `site.data`
- [ ] Header card shows logo thumbnail and upload control below a `Separator`
- [ ] About card shows Google description as read-only preview
- [ ] About card shows description `Textarea` override below a `Separator`
- [ ] Gallery card shows photo count; no override fields
- [ ] Quick Actions card shows phone number and custom quick links; no override fields
- [ ] Reviews card shows rating and review count; no override fields
- [ ] Contact card shows address, phone, and opening hours day count; no override fields
- [ ] Absent or null data fields show graceful fallback text (not blank or broken)
- [ ] All six toggles still persist correctly via `form.components[key].enabled`
- [ ] Description override and logo upload still save correctly
- [ ] Dark mode renders correctly across all six cards
- [ ] `npm run lint` — zero errors
- [ ] `npx tsc --noEmit` — zero TypeScript errors
- [ ] `npm run build` — succeeds
