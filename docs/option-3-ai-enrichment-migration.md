# Option 3 Migration Plan — AI-Powered Business Data Enrichment

## Summary

Replace (or supplement) the Google Places API as the primary data source with an AI agent that researches the business across public web sources — their own website, Yelp, Yell.com, Facebook, Trustpilot, Companies House, etc. — and returns a structured JSON payload matching the existing `site.data` schema.

You already have the pattern for this in `FetchBusinessServices`, which uses Claude's `web_search` tool to find services via Tier 2 scanning. This plan extends that to cover the full business data profile.

---

## When to use this approach

| Scenario | Recommended path |
|---|---|
| Business has no Google listing | Option 3 only |
| Business has a Google listing but you want to avoid Places API storage concerns | Option 3 as primary source |
| As a complement to Option 4 | Option 3 as fallback / enrichment after Google data is confirmed |
| Services and social links | Already using this — no change needed |

---

## What the AI Can Reliably Return

| Field | Quality | Sources |
|---|---|---|
| Business name | ✅ Excellent | Website title, directories |
| Address | ✅ Good | Website footer, Yell, Yelp, Facebook |
| Phone number | ✅ Good | Website, directories |
| Description / about | ✅ Good | Website about page, Google Business snippet |
| Opening hours | ⚠️ Moderate | Website, Yelp, Facebook — can be stale |
| Services / menu | ✅ Good | Website, booking platforms (already handled by `FetchBusinessServices`) |
| Social media links | ✅ Good | Already handled by `FetchSocialLinks` |
| Reviews / rating | ⚠️ Variable | Yelp, Trustpilot, Google snippet — depends on source |
| Business type / category | ✅ Good | Directories, website content |
| Website URL | ✅ Excellent | Search result |
| Photos | ❌ Not reliable | AI cannot reliably find and download photos — needs separate handling (see below) |

---

## Architecture

### Current flow

```
User selects from Google autocomplete
  → SearchController::discover()
  → FetchPlaceDetails (Google Places API v1)
    → FetchPlacePhoto × N
    → FetchSocialLinks
    → FetchBusinessServices
  → TemporarySite.data = raw Google Places response
```

### New flow (Option 3 as primary)

```
User types business name + location (free text)
  → SearchController::discoverWithAI()
  → FetchBusinessDetailsWithAI (new job)
    → Claude web_search: research the business
    → Returns structured JSON → TemporarySite.data
    → FetchBusinessPhotosFromWeb × N  (optional — see Photos section)
    → FetchSocialLinks (existing — re-use as-is)
    → FetchBusinessServices (existing — re-use as-is)
  → TemporarySite.data = AI-generated structured data
```

### Hybrid flow (Option 3 + Option 4)

```
User selects from Google autocomplete (Places API for search only — not stored)
  → SearchController::discover()
  → FetchPlaceDetails (Google Places API — data used transiently)
    → Normalise raw data into site.data schema
    → FetchBusinessDetailsWithAI runs IN PARALLEL to enrich / fill gaps
    → AI output merged with normalised Places data
    → User confirms merged result during setup wizard
  → TemporarySite.data = user-confirmed content (not raw Google data)
```

---

## New Components Required

### 1. New job: `FetchBusinessDetailsWithAI`

File: `app/Jobs/FetchBusinessDetailsWithAI.php`

This job calls Claude (or an equivalent model with web search) and asks it to research the business and return a structured JSON payload. The pattern mirrors `FetchBusinessServices` exactly.

**Input:** business name, location (city/postcode), and optionally their website URL.

**AI prompt structure (abbreviated):**

```
Research the following business and return a JSON object with this exact structure.
Only include fields you are confident about — use null for unknown fields.

Business: {name}
Location: {location}

Return valid JSON with these fields:
{
  "displayName": { "text": "..." },
  "primaryTypeDisplayName": { "text": "..." },
  "formattedAddress": "...",
  "nationalPhoneNumber": "...",
  "websiteUri": "...",
  "editorialSummary": { "text": "..." },
  "regularOpeningHours": {
    "periods": [...],
    "weekdayDescriptions": [...]
  },
  "addressComponents": [...],
  "confidence": {
    "address": "high|medium|low",
    "phone": "high|medium|low",
    "hours": "high|medium|low"
  }
}

Search the business's own website, Google Business Profile snippet, Yelp, Yell.com,
Facebook, and any other relevant directories. Prefer data from the business's own
website when available.
```

The returned JSON merges directly into `TemporarySite.data`, maintaining compatibility with the existing site renderer.

**Implementation notes:**
- Follow the same pattern as `FetchBusinessServices::searchInternetWithAI()` — call the Claude API with `web_search` tool, parse the JSON response
- Add a `confidence` sub-object to `data` so the setup wizard can highlight low-confidence fields for the user to review
- If the AI cannot find the business, mark `data.ai_search_failed = true` so the frontend can show an appropriate message
- Set `timeout = 90` and `tries = 1` (same as `FetchBusinessServices`)

---

### 2. New search entry point (if going full Option 3 without Google)

If replacing Google Places search entirely, the landing page search changes from:

- **Current:** Google autocomplete → user clicks a match → Place ID returned
- **New:** Free-text input (name + location, or just business name) → AI research job dispatched → poll until complete

**`SearchController` additions:**

```php
// POST /api/search/discover-ai
public function discoverWithAI(Request $request): JsonResponse
{
    $request->validate([
        'business_name' => ['required', 'string', 'max:255'],
        'location'      => ['required', 'string', 'max:255'],
        'website'       => ['nullable', 'url'],
    ]);

    // Create TemporarySite immediately with the user's input
    $temporarySite = TemporarySite::create([
        'places_id' => null,  // no Place ID in this flow
        'data'      => [
            'search_input' => [
                'business_name' => $request->input('business_name'),
                'location'      => $request->input('location'),
                'website'       => $request->input('website'),
            ],
        ],
    ]);

    $batch = Bus::batch([
        new FetchBusinessDetailsWithAI(
            $temporarySite,
            $request->input('business_name'),
            $request->input('location'),
            $request->input('website'),
        ),
    ])->dispatch();

    return response()->json([
        'batchId'  => $batch->id,
        'siteId'   => $temporarySite->id,
    ]);
}
```

If running as a **hybrid** (Google Places for search, AI for enrichment), no new route is needed — `FetchBusinessDetailsWithAI` is simply added to the existing batch in `discover()`.

---

### 3. Schema compatibility

The AI job returns JSON in the same `site.data` schema the renderer already reads (`displayName.text`, `regularOpeningHours.periods`, etc.). No renderer changes required.

Two additions to `site.data` that are new:

```json
{
  "data_source": "ai",           // "google" | "ai" | "hybrid"
  "confidence": {
    "address": "high",
    "phone": "medium",
    "hours": "low"
  }
}
```

The setup wizard uses `confidence` to highlight fields the user should verify before confirming.

---

### 4. Photos

AI cannot reliably find and download representative photos of a business. Options:

**Option A — Skip photos entirely in Option 3 flow**
The gallery section is simply disabled by default (`components.gallery.enabled = false`). User can enable it and upload their own photos from the dashboard.

**Option B — Stock photo fallback**
You already have `StockPhotoController`. After AI research completes, run the existing stock photo lookup using the business type as the query. Show these as placeholder photos in the gallery, clearly labelled as "suggested photos — replace with your own". This is what the existing `FetchBusinessServices` Tier 3 does for services.

**Option C — Bing/Unsplash image search**
Use the business name + location as a search query against Bing Images or Unsplash to find real photos of the business. This is more aggressive and carries its own ToS considerations.

**Recommendation:** Option B (stock photos as placeholder) for the initial launch. Add a prominent prompt in the dashboard for the user to upload real photos.

---

### 5. `places_id` field

In the pure Option 3 flow, there is no `places_id`. The `TemporarySite` and `Site` records have `places_id = null`.

Downstream impacts:
- `PublishTemporarySite` looks up TemporarySite by `places_id` — needs to also handle lookup by session `temporary_site_id`
- `DashboardController::refresh()` will not work (no Place ID to re-query) — the refresh option should be hidden for AI-sourced sites, or replaced with a manual "re-research" option that fires `FetchBusinessDetailsWithAI` again
- `RefreshSiteFromGoogle` job: not applicable — skip or guard with `if (!$site->places_id) return;`
- The live reviews API endpoint (from Option 4 plan) won't work without a Place ID — use Yelp/Trustpilot reviews instead, or skip the reviews section by default

**Schema change needed:**
The `sites` table has `places_id` as the unique identifier for looking up existing sites. For AI-sourced sites, add a new column `ai_site_id` or use the `TemporarySite.id` as the identifier. Alternatively, use `places_id = null` and treat these as first-party sites.

---

### 6. Data quality and user confirmation

Because AI-generated data is less reliable than the Google Places API (especially for opening hours), the setup wizard confirmation step becomes more important in Option 3.

**Recommended UX changes to `Setup.vue`:**

- Show a confidence indicator next to each pre-filled field (based on `data.confidence.*`)
- Fields with `low` confidence get a yellow warning icon: _"We weren't sure about this — please verify"_
- Fields with `null` values prompt the user to fill them in manually
- The "Review your business information" heading (from Option 4 Phase 4) fits here too
- Add source attribution: _"Information sourced from your website and public directories"_

---

### 7. AI provider options

| Provider | Pros | Cons |
|---|---|---|
| **Claude (Anthropic)** via `web_search` tool | Already integrated in `FetchBusinessServices` — same pattern, same API key | Cost per search call is higher than a Places API lookup |
| **Perplexity API** | Designed for web-grounded Q&A, returns citations, very good at structured extraction | New dependency, separate API key |
| **Tavily Search API** | Purpose-built for AI agents, returns clean snippets, low cost | Returns raw search results, requires a second LLM call to structure them |
| **OpenAI GPT-4 with browsing** | High quality, widely used | Separate API key, browsing availability inconsistent |

**Recommendation:** Claude via `web_search` tool. Zero new dependencies — `FetchBusinessServices` already uses this exact pattern. One API key, one client library. The only cost consideration is that a full business research call will use more tokens than the services-only call.

---

## Data Migration

No migration needed for existing sites — they were built from Google Places data and `data_source` defaults to `"google"` for records without the field. New AI-sourced sites get `data_source = "ai"`.

---

## Implementation Order

1. **Add `FetchBusinessDetailsWithAI` job** — mirrors `FetchBusinessServices` but covers the full schema. Can be added as an enrichment job to the existing Google Places batch first (hybrid mode), then promoted to primary if Google placement is dropped.
2. **Update `Setup.vue`** to show confidence indicators and make all fields editable.
3. **Add `discoverWithAI` route** if going fully free-text (no Google autocomplete).
4. **Handle `places_id = null`** in `PublishTemporarySite`, `DashboardController::refresh()`, and `RefreshSiteFromGoogle`.
5. **Photos fallback** — wire up stock photo lookup for AI-sourced sites.

---

## Comparison: Option 3 vs Option 4

| Dimension | Option 3 (AI enrichment) | Option 4 (Places API restructured) |
|---|---|---|
| UX friction | Slightly higher — free text entry, no autocomplete | Unchanged — same search experience |
| Data quality | Good for most fields, variable for hours | Excellent — Google Places is authoritative |
| Legal risk | Very low — publicly scraped data, user confirms | Low with correct architecture — needs legal sign-off |
| Photos | Needs workaround (stock or user-uploaded) | Google photos downloaded — already handled |
| Reviews | Yelp/Trustpilot (lower coverage) | Google reviews (live fetch) |
| Businesses without Google listing | ✅ Works | ❌ No listing = no site |
| Implementation effort | Medium — new job, new UX, places_id null handling | Low — mostly data pipeline changes |
| Infrastructure cost | Higher AI token cost per new site | Small — Places API call |
| Already partly built | ✅ `FetchBusinessServices` uses same pattern | ✅ `places_id` column already exists |

**Best combined approach:** Use Option 4 as the primary path (Google autocomplete for UX, restructured data pipeline for compliance) with Option 3 as the fallback for businesses that don't have a Google listing.
