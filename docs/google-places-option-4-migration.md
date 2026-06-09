# Option 4 Migration Plan — Google Places Data Architecture

## Summary

The goal is to move from storing Google's raw API response verbatim in `site.data` to storing user-confirmed website content that was _generated using_ Google Places data. The `places_id` is the only Google identifier we need to keep indefinitely; everything else must either be treated as user-owned content (confirmed by the user) or fetched fresh on demand.

The conversation framing: _"You're not storing Google's data, you're storing the website you built using it."_

---

## What Needs to Change

### The core problem

`FetchPlaceDetails` stores the complete raw API response in `TemporarySite.data`, and `PublishTemporarySite` copies that blob directly into `Site.data`. So `sites.data` is currently a verbatim mirror of Google's API response, persisted indefinitely — which is what Google objects to.

### What we can keep storing

| Data | Why it's fine |
|---|---|
| `places_id` | Google explicitly permits permanent storage of Place IDs |
| User-uploaded logo | User owns this |
| User-written description | User owns this |
| Social links, quick links, services | User provided during setup |
| Component visibility flags | User configuration |
| Palette, header background, favicon | User configuration |
| Downloaded photo files in `storage/app/public/images/` | These are our files once downloaded |
| `meta_title`, `meta_description` | We derived and own these |

### What needs to change

| Data | Current state | Target state |
|---|---|---|
| Business name, address, hours, phone, type | Raw Google API response stored permanently | User-confirmed content (user edits/approves during setup) |
| Google photos array (`data.photos`) | Raw photo references stored permanently | Strip once photos are downloaded to our storage |
| Reviews (`data.reviews`, `data.reviewSummary`) | Stored permanently in `site.data` | **Never stored** — fetched live per-request with short-lived server-side cache |
| Rating + review count (`data.rating`, `data.userRatingCount`) | Stored permanently | Re-fetched with reviews |
| `data.websiteUri` | Stored permanently | Strip or move to user-owned field |
| `RefreshSiteFromGoogle` | Overwrites `site.data` with fresh raw API response | Re-fetch → normalize → present changes to user |

---

## Migration Phases

### Phase 1 — Make reviews live (highest priority, lowest effort)

Reviews are the field Google is most protective of. Stop storing them at all.

**Backend changes:**

1. Create `app/Http/Controllers/Api/ReviewsController.php`:
   - `GET /api/sites/{placesId}/reviews` — fetches live from Google Places API
   - Cache the response in Laravel's cache store for **30 minutes** using `cache()->remember()`
   - Returns `{ rating, userRatingCount, reviews: [...] }`

2. Add the route to `routes/api.php`.

3. Remove `reviews`, `reviewSummary`, `rating`, `userRatingCount` from the `X-Goog-FieldMask` in `FetchPlaceDetails.php` and `RefreshSiteFromGoogle.php`.

**Frontend changes:**

The site renderer in `resources/js/components/site/` reads `data.reviews`, `data.rating`, etc. from the `data` prop. This is the one change allowed to site renderer files: swap from prop-based to a live API fetch.

> **Note:** This is the only required change to `resources/js/components/site/`. The Reviews component should fetch from `/api/sites/{placesId}/reviews` on mount, with a loading state. The `placesId` should be passed down from the root `site/Index.vue` (it's already available via `data.id`).

**Data migration:**

Run a one-time migration to strip reviews from existing `sites.data` records:

```php
// artisan command: php artisan app:strip-reviews-from-sites
Site::all()->each(function (Site $site) {
    $data = $site->data ?? [];
    unset($data['reviews'], $data['reviewSummary'], $data['rating'], $data['userRatingCount']);
    $site->update(['data' => $data]);
});
```

---

### Phase 2 — Strip unused raw fields from the ingestion pipeline

Clean up `FetchPlaceDetails` so it only stores fields that the renderer actually uses, and strips the raw Google structures we don't need.

**Update `FetchPlaceDetails::handle()`:**

After fetching the API response, pass it through a normalisation function before storing in `TemporarySite`:

```php
private function normaliseGoogleData(array $raw): array
{
    // Only keep fields the site renderer actually reads
    $allowed = [
        'id',
        'displayName',
        'primaryTypeDisplayName',
        'formattedAddress',
        'addressComponents',
        'regularOpeningHours',
        'nationalPhoneNumber',
        'internationalPhoneNumber',
        'editorialSummary',
        'websiteUri',
        // 'photos' is kept transiently so FetchPlacePhoto can iterate it,
        // but is stripped from Site.data after photos are downloaded
        'photos',
    ];

    return array_intersect_key($raw, array_flip($allowed));
}
```

**Update `RefreshSiteFromGoogle::handle()`** to apply the same normalisation before writing to `Site.data`. Also remove reviews from the field mask (done in Phase 1).

---

### Phase 3 — Strip the raw photos array after download

Once `FetchSitePhoto` downloads a photo to local storage and adds its path to `site.data.images`, the raw Google photo reference in `site.data.photos` is no longer needed.

**Update `FetchSitePhoto`** (and `FetchPlacePhoto` for TemporarySite) to unset the `photos` array from `data` once the last photo in the batch has been downloaded:

The cleanest way: after each job completes, check `count(data.images) >= count(data.photos)`. If true, unset `data.photos`.

Alternatively, add a new job `StripGooglePhotosFromSiteData` that dispatches after the batch finishes and removes `data.photos` from the record.

**Data migration:**

Strip `photos` from all existing `sites.data` records where `images` is already populated:

```php
Site::all()->each(function (Site $site) {
    $data = $site->data ?? [];
    if (!empty($data['images'])) {
        unset($data['photos']);
        $site->update(['data' => $data]);
    }
});
```

---

### Phase 4 — Explicit user confirmation step in the setup wizard

This is the semantic linchpin. When the user clicks "Build My Site" at the end of the setup wizard, they are confirming all pre-filled business information as their own website content. After this point, what's stored in `site.data` is their content, not Google's data.

**Changes required:**

1. **Setup wizard UI (`resources/js/pages/preview/Setup.vue`):** The existing setup form already shows the pre-filled data. Make every pre-filled field editable — business name, address, phone, opening hours, description. Add a visible "Review your business information before publishing" heading to this step.

2. **`PreviewController::store()`:** Extend the `StoreSetupRequest` to accept edits to the core business fields (name, address, phone, hours, description). Merge any user edits back into `TemporarySite.data` before proceeding.

3. **Store a `content_confirmed_at` timestamp:** Add a `content_confirmed_at` field to `TemporarySite.data` (and subsequently `Site.data`) when the user submits the setup form. This documents that the user explicitly confirmed their content.

4. **`PublishTemporarySite` listener:** When the user confirms and registers, the data written to `Site.data` includes the user's edits and the `content_confirmed_at` timestamp. It is no longer a copy of the raw Google API response — it's the user's confirmed website content.

---

### Phase 5 — Rethink the refresh flow

Currently `RefreshSiteFromGoogle` overwrites `site.data` with a fresh raw API response. Post-migration, a refresh should:

1. Fetch fresh data from Google using the stored `places_id`
2. Normalise it (Phase 2)
3. Compute a diff against the user's stored content
4. Present the diff to the user in the dashboard ("Google says your phone number has changed — update your site?")
5. Only update on explicit user confirmation

**This changes the refresh from automatic overwrite to a user-driven update flow.** The `DashboardController::refresh()` route can dispatch a `RefreshSiteFromGoogle` job that stores the diff in a new `sites.pending_refresh` JSON column rather than writing directly to `sites.data`.

A new dashboard component shows the pending changes with "Accept" / "Ignore" per-field controls.

---

### Phase 6 — TemporarySite TTL

`TemporarySite` records accumulate indefinitely. They're only needed during the setup-to-registration flow — typically a few minutes to a few hours.

Add a scheduled artisan command to delete `TemporarySite` records older than **7 days**:

```php
// app/Console/Commands/PruneTemporarySites.php
TemporarySite::where('created_at', '<', now()->subDays(7))->delete();
```

Schedule it in `routes/console.php`:

```php
Schedule::command('app:prune-temporary-sites')->daily();
```

---

## Database Changes Required

| Change | How |
|---|---|
| Add `content_confirmed_at` to `sites.data` JSON | No migration needed — JSON column, set at publish time |
| Add `pending_refresh` column to `sites` table | New migration: `$table->json('pending_refresh')->nullable()` |
| No schema changes to `sites` table otherwise | The `data` column keeps the same shape |

---

## What This Does NOT Change

- The site renderer in `resources/js/pages/site/` and `resources/js/components/site/` reads the same field names from `site.data` as today. The `displayName.text`, `formattedAddress`, `regularOpeningHours`, `nationalPhoneNumber`, and `editorialSummary` structures remain in `site.data` — they're just now treated as user-confirmed content rather than cached Google data.
- The `places_id` column stays. It's used for refreshes and for the live reviews API endpoint.
- The downloaded photo files in `storage/app/public/images/` stay. These are ours.

---

## Recommended Implementation Order

1. **Phase 1 (reviews live)** — highest legal priority, isolated change, can ship independently
2. **Phase 6 (TemporarySite TTL)** — trivial, deploy at any time
3. **Phase 2 (field stripping in ingestion)** — forward-looking, affects new sites only
4. **Phase 3 (strip photos array)** — small cleanup, run data migration
5. **Phase 4 (confirmation step)** — UX change, ship after Phase 2 is stable
6. **Phase 5 (refresh diff flow)** — largest change, ship last

---

## Open Questions to Resolve with Legal Counsel

1. **Is `editorialSummary` (Google's description) subject to the same restriction as reviews?** If so, it should also be fetched live or excluded and replaced by the user's own description.
2. **Does the user confirmation step in Phase 4 satisfy Google's specific concern?** Ideally, get this confirmed before implementing Phase 4.
3. **Opening hours from Google** — are these considered the business owner's data (since they set them on Google Business Profile) or Google's data? This affects whether they need live-fetching or confirmation.
4. **Does Google's conversation with Jake concern the Places API ToS specifically, or the Business Profile API ToS?** These have different rules. If it's the Business Profile API, the OAuth path (Option 5 from the conversation) may be a cleaner long-term fix.
