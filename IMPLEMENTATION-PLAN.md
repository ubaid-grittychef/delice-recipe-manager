# Implementation Plan — v3.6.0 Feature Batch

**Branch:** `claude/wordpress-plugin-audit-daRbA`
**Version bump:** 3.5.1 → 3.6.0
**Multilingual:** Every user-facing string goes through `Delice_Recipe_Language::get_all_texts()`.
**Theme safety:** All layout CSS uses `!important` on containers + inline ID-scoped `<style>` shield in every template.

---

## Features Grouped by Effort

### Group A — Pure PHP/schema additions (no new files, no JS)
1. Canonical tag
2. Open Graph + Twitter Card meta tags
3. `<link rel="preload">` for hero image
4. "Last Updated" date in recipe card header
5. Nutrition accuracy disclaimer
6. Breadcrumb schema (JSON-LD) + visible breadcrumb HTML

### Group B — Template additions (PHP + CSS only)
7. Aggregate star rating display in recipe card header
8. Dietary badges + `suitableForDiet` schema
9. WebP `<picture>` element wrapping hero image

### Group C — JS features (new JS files + PHP hooks)
10. Ingredient checklist localStorage persistence
11. Jump to Recipe button
12. Cook Mode (Screen Wake Lock API)
13. Inline step timers

### Group D — Complex feature
14. Servings scaler
15. Related recipes block (smart reciprocal interlinking)

---

## Group A — PHP/Schema Only

### A1. Canonical Tag

**File:** `includes/class-delice-recipe-schema.php`
**Where:** Inside `output_recipe_meta_tags()` (already exists, runs at `wp_head` priority 5).
**Logic:** Skip if Yoast (`WPSEO_VERSION`) or RankMath (`RANK_MATH_VERSION`) is active.
**Output:**
```html
<link rel="canonical" href="https://example.com/classic-beef-lasagna/">
```

---

### A2. Open Graph + Twitter Card

**File:** `includes/class-delice-recipe-schema.php`
**Where:** New method `output_og_meta_tags()` hooked to `wp_head` at priority 4.
**Skip when:** `WPSEO_VERSION` or `RANK_MATH_VERSION` defined (those plugins handle OG).
**Tags to output:**
```html
<meta property="og:type"        content="article">
<meta property="og:url"         content="{permalink}">
<meta property="og:title"       content="{recipe title}">
<meta property="og:description" content="{meta description (reuse build_recipe_description())}">
<meta property="og:image"       content="{featured image full URL}">
<meta property="og:image:width"  content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="{recipe title}">
<meta name="twitter:description" content="{meta description}">
<meta name="twitter:image"       content="{featured image full URL}">
```
**Data:** `get_the_title()`, `get_permalink()`, `build_recipe_description()` (already exists), `wp_get_attachment_image_src($thumb_id, array(1200, 630))`.

---

### A3. `<link rel="preload">` for Hero Image

**File:** `includes/class-delice-recipe-schema.php`
**Where:** New method `output_hero_preload()` hooked to `wp_head` at priority 1 (must run before everything else).
**Logic:** Only on recipe pages. Get featured image URL at 'large' size. Output:
```html
<link rel="preload" as="image" href="{hero_url}" fetchpriority="high">
```
This fires before the browser parses `<body>`, giving the maximum possible LCP benefit.

---

### A4. "Last Updated" Date Display

**Files:** All 3 templates (`recipe-template-default.php`, `recipe-template-modern.php`, `recipe-template-elegant.php`)
**Where:** Inside the meta bar section (alongside prep time, cook time, servings).
**PHP:**
```php
$last_updated = get_the_modified_date( 'M j, Y', $recipe_id );
// Show only if different from published date
$published = get_the_date( 'M j, Y', $recipe_id );
```
**Display:**
```html
<div class="delice-recipe-meta-item delice-recipe-updated">
  <span class="delice-recipe-meta-label"><?= $lang_texts['updated'] ?></span>
  <span class="delice-recipe-meta-value"><?= esc_html($last_updated) ?></span>
</div>
```
**New language key:** `'updated' => 'Updated'`
**Show condition:** Only output when `$last_updated !== $published` (no point showing it if never edited).

---

### A5. Nutrition Disclaimer

**Files:** All 3 templates, inside the nutrition section (below the nutrition grid).
**Setting:** `get_option('delice_recipe_show_nutrition_disclaimer', true)`.
**Display:**
```html
<p class="delice-recipe-nutrition-disclaimer">
  <?= esc_html($lang_texts['nutrition_disclaimer']) ?>
</p>
```
**New language key:** `'nutrition_disclaimer' => 'Nutrition values are estimates and may vary based on ingredients used.'`
**CSS:** Small font, muted gray, centered. Added to `delice-recipe-public.css` and `delice-modern.css`.

---

### A6. Breadcrumb Schema + Visible Breadcrumb

**Schema file:** `includes/class-delice-recipe-schema.php`
**New method:** `output_breadcrumb_schema($recipe_id)` called from `output_recipe_schema()`.
**Logic:**
1. Home (position 1)
2. Primary cuisine term if exists → link to term archive (position 2)
3. Primary course term if exists → link to term archive (position 3, or 2 if no cuisine)
4. Recipe name (final position, no URL needed)

**JSON-LD output:**
```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem", "position": 1, "name": "Home", "item": "https://example.com/"},
    {"@type": "ListItem", "position": 2, "name": "Italian", "item": "https://example.com/cuisine/italian/"},
    {"@type": "ListItem", "position": 3, "name": "Classic Beef Lasagna"}
  ]
}
```

**Visible breadcrumb HTML:** New PHP helper `delice_recipe_breadcrumb_html($recipe_id)` — output before the recipe card wrapper in each template:
```html
<nav class="delice-recipe-breadcrumb" aria-label="Breadcrumb">
  <ol class="delice-recipe-breadcrumb-list">
    <li><a href="/"><?= $lang_texts['home'] ?></a></li>
    <li><a href="{cuisine_url}">{cuisine_name}</a></li>
    <li aria-current="page">{recipe_name}</li>
  </ol>
</nav>
```
**New language key:** `'home' => 'Home'`
**CSS:** New block in `delice-recipe-public.css`. No separator images — pure CSS `::before` chevron.
**Skip breadcrumb schema:** When Yoast/RankMath active (they output their own breadcrumb schema).

---

## Group B — Template + CSS

### B1. Aggregate Rating Display in Card Header

**Files:** All 3 templates.
**Data:**
```php
$rating_avg   = floatval( get_post_meta( $recipe_id, '_delice_recipe_rating_average', true ) );
$rating_count = intval( get_post_meta( $recipe_id, '_delice_recipe_rating_count', true ) );
```
**Where to insert:** In the meta bar area (after title/image, before prep time).
**HTML:**
```html
<?php if ( $rating_count > 0 ) : ?>
  <div class="delice-recipe-rating-summary" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
    <div class="delice-recipe-rating-stars-display" aria-hidden="true">
      <?php for ($i=1;$i<=5;$i++): ?>
        <span class="delice-rating-star-display <?= $i <= round($rating_avg) ? 'filled' : '' ?>">★</span>
      <?php endfor; ?>
    </div>
    <span class="delice-recipe-rating-score" itemprop="ratingValue"><?= number_format($rating_avg,1) ?></span>
    <span class="delice-recipe-rating-count">(<span itemprop="ratingCount"><?= $rating_count ?></span> <?= $lang_texts['ratings'] ?>)</span>
    <meta itemprop="bestRating" content="5">
    <meta itemprop="worstRating" content="1">
  </div>
<?php endif; ?>
```
**New language key:** `'ratings' => 'ratings'`
**CSS:** Stars use `color: #f59e0b` (amber) for filled, `color: #d6d3d1` for empty. Component in `delice-recipe-public.css`.

---

### B2. Dietary Badges + `suitableForDiet` Schema

**Admin meta storage:** `_delice_recipe_dietary` — stored as array e.g. `['vegetarian', 'gluten-free']`.
**Admin field:** Saved via existing `save_post_delice_recipe` hook in `admin/class-delice-recipe-admin.php`. Add checkboxes to the existing recipe meta box partial.

**Badge definitions:**
| Key | Label | Color |
|-----|-------|-------|
| `vegetarian` | Vegetarian | `#16a34a` (green) |
| `vegan` | Vegan | `#15803d` (dark green) |
| `gluten-free` | Gluten-Free | `#d97706` (amber) |
| `dairy-free` | Dairy-Free | `#0284c7` (blue) |
| `nut-free` | Nut-Free | `#7c3aed` (violet) |
| `low-carb` | Low-Carb | `#db2777` (pink) |
| `keto` | Keto | `#9333ea` (purple) |
| `paleo` | Paleo | `#b45309` (brown) |

**Schema:** Map to `suitableForDiet` field in `generate_recipe_schema()`:
```php
$diet_map = [
  'vegetarian' => 'https://schema.org/VegetarianDiet',
  'vegan'      => 'https://schema.org/VeganDiet',
  'gluten-free'=> 'https://schema.org/GlutenFreeDiet',
  'low-carb'   => 'https://schema.org/LowCalorieDiet',
];
```
**CSS file:** New `public/css/components/recipe-badges.css`. Badges are inline-flex pill shapes.
**Multilingual labels:** Added to language system under `dietary_labels` sub-array.

---

### B3. WebP `<picture>` Element

**PHP helper function:** `delice_recipe_hero_picture( $recipe_id, $class, $attrs = [] )` in a new `includes/class-delice-recipe-image-helper.php`.

**Logic:**
```php
function delice_recipe_hero_picture( $recipe_id, $class, $attrs = [] ) {
    $thumb_id = get_post_thumbnail_id( $recipe_id );
    // WordPress 5.8+ stores WebP if enabled; check for .webp attachment
    $full = wp_get_attachment_image_src( $thumb_id, 'full' );
    $large = wp_get_attachment_image_src( $thumb_id, 'large' );
    $srcset = wp_get_attachment_image_srcset( $thumb_id, 'large' );
    $sizes  = wp_get_attachment_image_sizes( $thumb_id, 'large' );
    // Build <picture> with srcset for responsive + WebP source if metadata contains webp mime
    $meta = wp_get_attachment_metadata( $thumb_id );
    $has_webp = isset($meta['sizes']) && array_reduce($meta['sizes'], fn($c,$s) => $c || ($s['mime-type'] ?? '') === 'image/webp', false);
    // Output picture element
}
```

**Templates:** Replace `get_the_post_thumbnail()` calls for the hero with `delice_recipe_hero_picture()` in all 3 templates.

**Fallback:** If WebP not available, outputs standard `<img>` with srcset/sizes — no regression.

---

## Group C — JavaScript Features

### C1. Ingredient Checklist localStorage

**New file:** `public/js/delice-checklist-persist.js`
**Storage key:** `delice_checklist_{recipe_id}`
**Logic:**
```js
// On page load: restore checked state
// On checkbox change: save all states to localStorage
// Clear button: wipe localStorage for this recipe ID
```
**Enqueue:** Added in `public/includes/class-delice-recipe-scripts.php` alongside existing scripts.
**No PHP changes needed** — checkboxes already exist in all templates with class `delice-recipe-ingredient-checkbox`.

---

### C2. Jump to Recipe Button

**New file:** `public/js/delice-jump-btn.js` — handles smooth scroll + hides button when recipe card is in viewport (IntersectionObserver).
**New CSS:** `public/css/components/recipe-jump-btn.css` — orange pill button, fixed position on mobile.
**PHP:** New shortcode `[delice_jump_to_recipe]` registered in `public/includes/class-delice-recipe-shortcode.php`.
Also: auto-inject above the recipe card shortcode output via a filter on `the_content` — adds the button before any `[delice_recipe ...]` shortcode.
**Target anchor:** `#recipe-card-{id}` — add `id="recipe-card-{recipe_id}"` to the outer wrapper div of each template.
**New language key:** `'jump_to_recipe' => 'Jump to Recipe'`
**Multilingual:** Button text from `deliceRecipe.texts.jumpToRecipe` passed via the inline `<script>` in `wp_head`.

---

### C3. Cook Mode (Screen Wake Lock)

**New file:** `public/js/delice-cook-mode.js`
**PHP:** Add "Start Cooking" button to the action buttons bar in all 3 templates.
**JS logic:**
```js
// On click: request wakeLock, change button to "Stop Cooking", add active class
// On visibilitychange (tab hidden): release lock
// On visibilitychange (tab visible again): re-acquire lock if cook mode still active
// Graceful degradation: if wakeLock API not supported, hide the button
```
**New language keys:** `'cook_mode_start' => 'Start Cooking'`, `'cook_mode_stop' => 'Stop Cooking'`
**CSS:** Cook mode button in `recipe-action-buttons.css` — green background when active.
**HTML button:**
```html
<button class="delice-recipe-cook-mode-btn delice-recipe-action-button" type="button">
  <svg ...><!-- flame icon --></svg>
  <span class="delice-recipe-action-button-text"><?= $lang_texts['cook_mode_start'] ?></span>
</button>
```

---

### C4. Inline Step Timers

**New file:** `public/js/delice-step-timers.js`
**Approach:** JS-side detection (not PHP) — runs after DOM ready, finds time patterns in step text.
**Regex pattern:** `/(\d+)\s*(hour|hr|minute|min|second|sec)s?/gi` — also handles "1 hour 30 minutes".
**Behaviour:**
- Finds matches, wraps them in `<span class="delice-timer-trigger" data-seconds="{total_seconds}">` in-place
- Timer trigger shows a ⏱ icon
- On click: starts countdown in a small fixed popup (bottom-right corner)
- Multiple timers can run simultaneously (stored in a `timers[]` array)
- Audio alert on completion: Web Audio API beep (no external dependency)
- Each timer shows recipe step number + remaining time

**New language keys:** `'start_timer' => 'Start Timer'`, `'timer_done' => 'Timer done!'`
**CSS:** Timer popup in `delice-recipe-public.css`. No new file needed.

---

## Group D — Complex Features

### D1. Servings Scaler

**PHP changes (all 3 templates):**
- Add `data-base-servings="{servings}"` to the recipe outer wrapper
- Add servings control UI inside the ingredients section header:
```html
<div class="delice-servings-control">
  <button class="delice-servings-btn delice-servings-minus" type="button" aria-label="Decrease servings">−</button>
  <span class="delice-servings-value" data-base="{servings}"><?= $servings ?></span>
  <button class="delice-servings-btn delice-servings-plus" type="button" aria-label="Increase servings">+</button>
  <span class="delice-servings-label"><?= $lang_texts['servings'] ?></span>
</div>
```
- Each ingredient quantity span gets `data-base-amount="{amount}"` and `data-base-unit="{unit}"`:
```html
<span class="delice-recipe-ingredient-quantity"
      data-base-amount="<?= esc_attr($ing['amount'] ?? '') ?>"
      data-base-unit="<?= esc_attr($ing['unit'] ?? '') ?>">
  <?= esc_html(trim(($ing['amount']??'').' '.($ing['unit']??''))) ?>
</span>
```

**New file:** `public/js/delice-servings-scaler.js`
**JS logic:**
- Min servings: 1. Max: 100.
- On +/−: recalculate `(new_servings / base_servings) * base_amount`, round to 2 decimal places or nearest fraction (¼ ½ ¾)
- Handle fractional display: amounts like 0.25 → ¼, 0.5 → ½, 0.75 → ¾
- Update `aria-live` region with new servings count for screen readers

**New language keys:** (none — "servings" already exists)
**CSS:** Servings control in `delice-recipe-public.css`.

---

### D2. Related Recipes Block

**Interlinking algorithm (reciprocal by taxonomy):**
```
1. Get current recipe's taxonomy terms (delice_cuisine, delice_course)
2. Query: WP_Query for delice_recipe posts NOT the current one,
   ordered by: (cuisine match × 3) + (course match × 2) + (recency × 1)
3. Limit to 3 results
4. This is naturally reciprocal: if Recipe B shares Italian cuisine with A,
   then A appears in B's related set AND B appears in A's related set.
   The "web of mutual links" emerges from shared taxonomy without
   needing to store explicit back-references.
```

**Manual override:** Admin can set `_delice_related_recipes` meta (array of IDs) to pin specific related recipes. If this meta exists and has ≥ 2 entries, skip the auto-query.

**PHP function:** `Delice_Recipe_Templates::get_related_recipes( $recipe_id, $limit = 3 )` in `includes/class-delice-recipe-templates.php`.

**New partial:** `public/partials/recipe-related.php`
**Output per card:**
```html
<a href="{permalink}" class="delice-related-recipe-card">
  <div class="delice-related-recipe-img"><!-- thumbnail --></div>
  <div class="delice-related-recipe-info">
    <h4 class="delice-related-recipe-title">{title}</h4>
    <div class="delice-related-recipe-meta">
      <span>{total_time} min</span>
      <?php if ($rating_avg): ?><span>★ {rating_avg}</span><?php endif; ?>
    </div>
  </div>
</a>
```

**New CSS file:** `public/css/components/recipe-related.css`
**Card grid:** `display: grid; grid-template-columns: repeat(3, 1fr)` — collapses to 1 column below 480px.
**New language key:** `'related_recipes' => 'You Might Also Like'`
**Placement:** After FAQs section, before the review section, in all 3 templates.
**Performance:** Result cached per recipe ID using `get_transient("delice_related_{$recipe_id}")` with 12-hour expiry. Cache cleared on `save_post` for the recipe.

---

## Language Keys to Add

All added to `get_all_texts()` default array in `includes/class-delice-recipe-language.php`:

| Key | Default English value |
|-----|-----------------------|
| `updated` | `Updated` |
| `nutrition_disclaimer` | `Nutrition values are estimates and may vary based on ingredients used.` |
| `home` | `Home` |
| `ratings` | `ratings` |
| `jump_to_recipe` | `Jump to Recipe` |
| `cook_mode_start` | `Start Cooking` |
| `cook_mode_stop` | `Stop Cooking` |
| `start_timer` | `Start Timer` |
| `timer_done` | `Timer done!` |
| `related_recipes` | `You Might Also Like` |

Plus JS-accessible subset passed via the inline `<script>` in `delice_recipe_frontend_script_data()` in `delice-recipe-manager.php`:
```php
'jumpToRecipe'  => $lang_texts['jump_to_recipe'],
'cookModeStart' => $lang_texts['cook_mode_start'],
'cookModeStop'  => $lang_texts['cook_mode_stop'],
'startTimer'    => $lang_texts['start_timer'],
'timerDone'     => $lang_texts['timer_done'],
```

---

## New Files

| File | Purpose |
|------|---------|
| `public/js/delice-checklist-persist.js` | localStorage checkbox state |
| `public/js/delice-jump-btn.js` | Jump to Recipe smooth scroll + hide-when-visible |
| `public/js/delice-cook-mode.js` | Screen Wake Lock API |
| `public/js/delice-step-timers.js` | Time detection + countdown timer UI |
| `public/js/delice-servings-scaler.js` | Live ingredient quantity scaling |
| `public/css/components/recipe-related.css` | Related recipes card grid |
| `public/css/components/recipe-badges.css` | Dietary badge pills |
| `public/css/components/recipe-jump-btn.css` | Jump to Recipe button |
| `includes/class-delice-recipe-image-helper.php` | WebP `<picture>` helper function |
| `public/partials/recipe-related.php` | Related recipes card partial |

---

## Modified Files

| File | Changes |
|------|---------|
| `includes/class-delice-recipe-schema.php` | Canonical, OG tags, preload, breadcrumb schema, suitableForDiet |
| `includes/class-delice-recipe-language.php` | 10 new language keys |
| `includes/class-delice-recipe-templates.php` | `get_related_recipes()` method |
| `public/includes/class-delice-recipe-scripts.php` | Enqueue 5 new JS files + 3 new CSS files |
| `public/partials/recipe-template-default.php` | Rating summary, updated date, dietary badges, breadcrumb, cook mode btn, nutrition disclaimer, related recipes, WebP picture, recipe-card anchor ID |
| `public/partials/recipe-template-modern.php` | Same as default |
| `public/partials/recipe-template-elegant.php` | Same as default |
| `public/css/delice-recipe-public.css` | Breadcrumb, rating summary, servings control, nutrition disclaimer |
| `public/css/delice-modern.css` | Breadcrumb, rating summary for modern template variant |
| `public/css/components/recipe-action-buttons.css` | Cook Mode button state |
| `admin/class-delice-recipe-admin.php` | Dietary checkboxes in meta box + save logic |
| `delice-recipe-manager.php` | Version → 3.6.0, new lang keys in frontend script data |

---

## Implementation Order

1. **Language keys** — add all 10 new keys to `get_all_texts()` and `delice_recipe_frontend_script_data()` first so every subsequent step can use them.
2. **Schema additions** — canonical, OG, preload, breadcrumb schema, `suitableForDiet` (all in `class-delice-recipe-schema.php`, one pass).
3. **Image helper** — `class-delice-recipe-image-helper.php` so templates can use it.
4. **CSS files** — create all 3 new component CSS files.
5. **Template additions** — add all PHP changes to all 3 templates in one pass per template: anchor ID, breadcrumb, rating summary, updated date, dietary badges, cook mode button, nutrition disclaimer, related recipes call, WebP picture.
6. **Admin dietary field** — meta box checkboxes + save.
7. **New JS files** — create all 5 JS files.
8. **Enqueue** — register all new CSS + JS in `class-delice-recipe-scripts.php`.
9. **Version bump** — `delice-recipe-manager.php` header + constant → `3.6.0`.
10. **Commit + push.**
