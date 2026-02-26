# Delice Recipe Manager — Design & SEO Implementation Plan

**Plugin version at time of writing:** 3.3.0  
**Document date:** 2026-02-26  
**Branch:** `claude/wordpress-plugin-audit-daRbA`

---

## Table of Contents

1. [Section 1 — Design Issues to Fix](#section-1--design-issues-to-fix)
   - [1A. "Did You Make This Recipe" Review Section](#1a-did-you-make-this-recipe-review-section)
   - [1B. Modern Template Design](#1b-modern-template-design)
   - [1C. Default Template Design](#1c-default-template-design)
   - [1D. Button Hover and Active States](#1d-button-hover-and-active-states)
   - [1E. Print CSS Gaps](#1e-print-css-gaps)
2. [Section 2 — SEO Content Plan](#section-2--seo-content-plan-google-1-ranking-strategy)
   - [2A. Recipe Page Content Structure](#2a-recipe-page-content-structure)
   - [2B. Schema.org Recipe Markup](#2b-schemaorg-recipe-markup)
   - [2C. Keyword Strategy](#2c-keyword-strategy)
   - [2D. E-E-A-T Signals](#2d-e-e-a-t-signals)
   - [2E. Featured Snippet Optimization](#2e-featured-snippet-optimization)
   - [2F. FAQ Content Guidelines](#2f-faq-content-guidelines)
   - [2G. Meta Title and Description Templates](#2g-meta-title-and-description-templates)
   - [2H. Internal Linking Strategy](#2h-internal-linking-strategy)
   - [2I. Image Optimization Guidelines](#2i-image-optimization-guidelines)
   - [2J. Core Web Vitals Considerations](#2j-core-web-vitals-considerations)
   - [2K. Content Length Benchmarks](#2k-content-length-benchmarks)

---

# Section 1 — Design Issues to Fix

---

## 1A. "Did You Make This Recipe" Review Section

### Problem Audit

**File:** `public/css/components/recipe-reviews.css`

#### Problem 1 — Wrong color overrides for template-specific rate buttons (lines 130–147)

The following blocks inject blue and purple into the rate button for the Modern and Elegant templates. Both are wrong: the site color system is orange-based.

```css
/* lines 130–136 — WRONG: forces blue onto Modern template */
.delice-recipe-modern .delice-recipe-rate-btn {
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%) !important;
  border-color: #0ea5e9 !important;
}
.delice-recipe-modern .delice-recipe-rate-btn:hover {
  background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
}

/* lines 140–147 — WRONG: forces purple onto Elegant template */
.delice-recipe-elegant .delice-recipe-rate-btn {
  background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%) !important;
  border-color: #a855f7 !important;
}
.delice-recipe-elegant .delice-recipe-rate-btn:hover {
  background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%) !important;
}
```

**Action:** Delete lines 130–147 entirely. The base `.delice-recipe-rate-btn` rule (lines 101–122) already supplies correct orange styling and must not be overridden per-template.

#### Problem 2 — Wrong border/background on Modern and Elegant review wrapper classes (lines 501–512)

```css
/* line 501–504 — WRONG: blue border on Modern reviews */
.delice-recipe-modern-reviews {
  border-top: 3px solid #0ea5e9;
}

/* line 506–508 — WRONG: purple background on Elegant reviews */
.delice-recipe-elegant-reviews {
  border-color: #a855f7;
}
.delice-recipe-elegant-reviews .delice-recipe-review-header h3 {
  color: #a855f7;
}
```

**Action:** Remove `.delice-recipe-modern-reviews` and `.delice-recipe-elegant-reviews` overrides. If template-specific styling is genuinely needed, use the site's orange palette (`#f97316`, `#ea580c`).

#### Problem 3 — Duplicate `.delice-recipe-review-section` definition

Defined at:
- `public/css/components/recipe-reviews.css` line 70
- `public/css/delice-recipe-public.css` line 471

The two definitions conflict on `padding`, `background`, and `border-radius`. Browser cascade order determines which wins, and that order can change when stylesheets are enqueued in different sequences.

**Action:**
1. Keep the canonical definition in `recipe-reviews.css` line 70.
2. Delete the duplicate block at `delice-recipe-public.css` line 471.
3. If `delice-recipe-public.css` needs review-section spacing, add only a margin override, not a full re-definition.

#### Problem 4 — Stale Font Awesome selector (line 405)

```css
/* line 405 — targets <i> icon that was removed */
.delice-recipe-review-success i {
  font-size: 48px;
  color: #22c55e;
}
```

Font Awesome `<i>` elements were removed from the success state. The selector now targets nothing and will cause confusion during future maintenance.

**Action:** Delete line 405 and replace the success icon with an inline SVG checkmark in the PHP template, styled via a class such as `.delice-recipe-review-success-icon`.

#### Problem 5 — Plain, uninviting visual design

The review section currently renders as an unstyled form. There is no visual prompt, no compelling call to action, and no design that makes a visitor want to interact.

### Fix Plan for 1A

**Step 1 — Strip wrong-color overrides**

In `recipe-reviews.css`, delete:
- Lines 130–147 (template-specific blue/purple rate button overrides)
- Lines 501–512 (`.delice-recipe-modern-reviews` and `.delice-recipe-elegant-reviews` border overrides)

**Step 2 — Fix hover color on rate button (see also 1D)**

Change line 118 from:
```css
background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%) !important;
```
To:
```css
background: linear-gradient(135deg, #c2410c 0%, #9a3412 100%) !important;
```
This keeps the orange family on hover instead of jarring red.

**Step 3 — Redesign the review section card**

Replace the plain form with a visually inviting card. The HTML structure target:

```html
<div class="delice-recipe-review-section">
  <div class="delice-recipe-review-inner">
    <div class="delice-recipe-review-cta-header">
      <!-- Decorative fork/spoon SVG icon -->
      <div class="delice-recipe-review-icon">
        <svg ...><!-- utensil or chef hat SVG --></svg>
      </div>
      <h3 class="delice-recipe-review-headline">Did You Make This Recipe?</h3>
      <p class="delice-recipe-review-subline">
        We'd love to hear how it turned out. Leave a star rating and tell us your experience!
      </p>
    </div>

    <!-- Star rating row — large, tappable -->
    <div class="delice-recipe-star-row" role="group" aria-label="Rate this recipe">
      <span class="delice-recipe-star" data-value="1" role="radio" aria-label="1 star">★</span>
      <span class="delice-recipe-star" data-value="2" role="radio" aria-label="2 stars">★</span>
      <span class="delice-recipe-star" data-value="3" role="radio" aria-label="3 stars">★</span>
      <span class="delice-recipe-star" data-value="4" role="radio" aria-label="4 stars">★</span>
      <span class="delice-recipe-star" data-value="5" role="radio" aria-label="5 stars">★</span>
    </div>
    <p class="delice-recipe-star-label" aria-live="polite">Tap a star to rate</p>

    <!-- Comment form -->
    <form class="delice-recipe-review-form">
      ...
    </form>
  </div>
</div>
```

**Step 4 — CSS rules for the redesigned section**

```css
/* ── Review section card ───────────────────────────── */
.delice-recipe-review-section {
  margin: 0;
  padding: 48px 40px;
  background: linear-gradient(160deg, #fff7ed 0%, #ffffff 60%);
  border-top: 1px solid #fed7aa;
}

.delice-recipe-review-inner {
  max-width: 640px;
  margin: 0 auto;
  text-align: center;
}

/* ── Decorative header ─────────────────────────────── */
.delice-recipe-review-icon {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  width: 64px;
  height: 64px;
  background: #fff;
  border: 2px solid #fed7aa;
  border-radius: 50%;
  margin-bottom: 16px;
  box-shadow: 0 2px 8px rgba(249,115,22,0.12);
}

.delice-recipe-review-icon svg {
  width: 32px;
  height: 32px;
  color: #f97316;
  fill: currentColor;
}

.delice-recipe-review-headline {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1c1917;
  margin: 0 0 8px;
  letter-spacing: -0.02em;
}

.delice-recipe-review-subline {
  font-size: 0.95rem;
  color: #78716c;
  margin: 0 0 28px;
  line-height: 1.6;
}

/* ── Star row ──────────────────────────────────────── */
.delice-recipe-star-row {
  display: flex !important;
  justify-content: center !important;
  gap: 8px;
  margin-bottom: 8px;
}

.delice-recipe-star {
  font-size: 2rem;          /* 32px minimum */
  line-height: 1;
  color: #d6d3d1;           /* unselected: warm gray */
  cursor: pointer;
  transition: color 0.15s ease, transform 0.1s ease;
  user-select: none;
}

.delice-recipe-star:hover,
.delice-recipe-star.is-active {
  color: #f97316;
  transform: scale(1.15);
}

.delice-recipe-star-label {
  font-size: 0.8rem;
  color: #a8a29e;
  margin: 0 0 24px;
  min-height: 1.2em;
}

/* ── Form fields ───────────────────────────────────── */
.delice-recipe-review-form {
  text-align: left;
}

.delice-recipe-review-form input,
.delice-recipe-review-form textarea {
  width: 100%;
  box-sizing: border-box;
  border: 1.5px solid #e7e5e4;
  border-radius: 8px;
  padding: 12px 14px;
  font-size: 0.95rem;
  color: #1c1917;
  background: #fff;
  transition: border-color 0.2s;
  margin-bottom: 12px;
}

.delice-recipe-review-form input:focus,
.delice-recipe-review-form textarea:focus {
  outline: none;
  border-color: #f97316;
  box-shadow: 0 0 0 3px rgba(249,115,22,0.12);
}

/* ── Submit button (primary CTA) ───────────────────── */
.delice-recipe-rate-btn {
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px;
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
  color: #fff !important;
  border: none !important;
  border-radius: 8px !important;
  padding: 14px 32px !important;
  font-size: 1rem !important;
  font-weight: 600 !important;
  cursor: pointer !important;
  transition: background 0.2s ease, transform 0.1s ease !important;
  width: 100%;
  justify-content: center !important;
  margin-top: 4px;
}

.delice-recipe-rate-btn:hover {
  background: linear-gradient(135deg, #c2410c 0%, #9a3412 100%) !important;
  transform: translateY(-1px) !important;
}

.delice-recipe-rate-btn:active {
  transform: translateY(0) !important;
}

.delice-recipe-rate-btn:disabled {
  opacity: 0.55 !important;
  cursor: not-allowed !important;
  transform: none !important;
}

/* ── Success state ─────────────────────────────────── */
.delice-recipe-review-success {
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  gap: 12px;
  padding: 32px 16px;
  text-align: center;
}

.delice-recipe-review-success-icon {
  width: 56px;
  height: 56px;
  background: #dcfce7;
  border-radius: 50%;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.delice-recipe-review-success-icon svg {
  width: 28px;
  height: 28px;
  color: #16a34a;
  stroke: currentColor;
  fill: none;
}

.delice-recipe-review-success p {
  font-size: 1.05rem;
  color: #15803d;
  font-weight: 500;
  margin: 0;
}

/* ── Responsive ────────────────────────────────────── */
@media (max-width: 600px) {
  .delice-recipe-review-section { padding: 32px 20px; }
  .delice-recipe-star { font-size: 1.75rem; }
}
```

**Step 5 — Delete duplicate definition**

In `public/css/delice-recipe-public.css`, remove the block at line 471 that re-defines `.delice-recipe-review-section`. Any spacing adjustments needed there should be expressed only as margin overrides on the parent container.

---

## 1B. Modern Template Design

### Problem Audit

**File:** `public/css/delice-modern.css`

- Body section cards (white on gray) are visually undifferentiated — every section looks the same.
- Section titles (`h3`, `h4`) use default weight and size with no visual accent.
- The toolbar author block (`delice-modern-author`) occupies excessive vertical space relative to the action buttons alongside it.
- The page as a whole lacks the premium feel of competitive recipe sites.

### Fix Plan for 1B

**Step 1 — Section header accent strips**

Each content section card should carry a left-border accent strip of a distinct hue per section type. This creates visual rhythm and helps users skim to the section they need.

```css
/* Base card already exists — add left-border accent */
.delice-modern-section {
  border-left: 4px solid transparent;
  transition: border-color 0.2s;
}

/* Section-type accent colors */
.delice-modern-section--ingredients { border-left-color: #f97316; }  /* orange */
.delice-modern-section--instructions { border-left-color: #0ea5e9; }  /* blue   */
.delice-modern-section--notes        { border-left-color: #a3e635; }  /* lime   */
.delice-modern-section--nutrition    { border-left-color: #22c55e; }  /* green  */
.delice-modern-section--faqs         { border-left-color: #a78bfa; }  /* violet */
```

The section-type class must be added in the PHP template by checking which section is being rendered.

**Step 2 — Section title typography upgrade**

```css
.delice-modern-section-title {
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #44403c;
  display: flex !important;
  align-items: center !important;
  gap: 10px;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f5f5f4;
}

/* Icon dot — colored per section type */
.delice-modern-section-title::before {
  content: '';
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
}

.delice-modern-section--ingredients .delice-modern-section-title { color: #ea580c; }
.delice-modern-section--instructions .delice-modern-section-title { color: #0284c7; }
.delice-modern-section--notes        .delice-modern-section-title { color: #65a30d; }
.delice-modern-section--nutrition    .delice-modern-section-title { color: #16a34a; }
.delice-modern-section--faqs         .delice-modern-section-title { color: #7c3aed; }
```

**Step 3 — Compact the toolbar author block**

```css
.delice-modern-author {
  display: flex !important;
  align-items: center !important;
  gap: 8px;
  /* Remove min-height — let content dictate */
  min-height: unset;
}

.delice-modern-author-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  flex-shrink: 0;
}

.delice-modern-author-meta {
  line-height: 1.3;
}

.delice-modern-author-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: #1c1917;
}

.delice-modern-author-label {
  font-size: 0.72rem;
  color: #a8a29e;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}
```

**Step 4 — Section card hover state**

```css
.delice-modern-section {
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.delice-modern-section:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  transform: translateY(-1px);
}
```

**Version bump required:** When these changes are implemented, bump `DELICE_RECIPE_VERSION` from `3.3.0` to `3.4.0` in both the plugin header comment and the `define()` constant in `delice-recipe-manager.php`.

---

## 1C. Default Template Design

### Problem Audit

**File:** `public/css/delice-recipe-public.css`

- `.delice-recipe-container` uses `backdrop-filter: blur()` — a glassmorphism pattern that looked modern in 2021 but now reads as dated and adds GPU cost on mobile.
- Action buttons all use the same filled orange style — there is no visual hierarchy between primary and secondary actions.
- Ingredient and instruction panels (`background: #fff5eb`) are plain boxes with no visual sophistication.
- Multiple CSS files define overlapping rules for the same selectors, creating maintenance risk.

### Fix Plan for 1C

**Step 1 — Replace glassmorphism container with clean card**

```css
/* Old — remove these properties */
.delice-recipe-container {
  background: rgba(255,255,255,0.85);   /* REMOVE */
  backdrop-filter: blur(12px);           /* REMOVE */
  -webkit-backdrop-filter: blur(12px);  /* REMOVE */
  border: 1px solid rgba(255,255,255,0.3); /* REMOVE */
}

/* New — clean card */
.delice-recipe-container {
  background: #ffffff;
  border: 1px solid #e7e5e4;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.06);
  overflow: hidden;
}
```

**Step 2 — Visual hierarchy for the action bar**

The Print button should use a neutral outline style, the Share button a neutral outline style, and the Rate button should be the filled primary CTA. This provides clear visual hierarchy: one dominant action, two secondary.

```css
/* ── Primary CTA: Rate button ──────────────────────── */
.delice-recipe-action-btn--rate {
  background: linear-gradient(135deg, #f97316, #ea580c) !important;
  color: #fff !important;
  border: none !important;
  font-weight: 600 !important;
}

/* ── Secondary: Print / Share ──────────────────────── */
.delice-recipe-action-btn--print,
.delice-recipe-action-btn--share {
  background: #fff !important;
  color: #44403c !important;
  border: 1.5px solid #e7e5e4 !important;
  font-weight: 500 !important;
}

.delice-recipe-action-btn--print:hover,
.delice-recipe-action-btn--share:hover {
  background: #fafaf9 !important;
  border-color: #d6d3d1 !important;
  color: #1c1917 !important;
}
```

**Step 3 — Upgrade ingredient and instruction panels**

```css
/* Old background — replace */
.delice-recipe-ingredients-panel,
.delice-recipe-instructions-panel {
  background: #fff5eb;   /* REPLACE */
}

/* New — cleaner with section header strip */
.delice-recipe-ingredients-panel,
.delice-recipe-instructions-panel {
  background: #fafaf9;
  border: 1px solid #f5f5f4;
  border-radius: 12px;
  overflow: hidden;
}

.delice-recipe-panel-header {
  background: #fff7ed;
  border-bottom: 1px solid #fed7aa;
  padding: 14px 20px;
  display: flex !important;
  align-items: center !important;
  gap: 10px;
}

.delice-recipe-panel-header h3 {
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: #9a3412;
  margin: 0;
}

.delice-recipe-panel-body {
  padding: 20px;
}
```

**Step 4 — Consolidate overlapping CSS**

Audit all files that define `.delice-recipe-container`, `.delice-recipe-action-btn`, and `.delice-recipe-review-section`. Establish one canonical location per class:

| Class | Canonical file |
|---|---|
| `.delice-recipe-container` | `delice-recipe-public.css` |
| `.delice-recipe-action-btn` | `components/recipe-action-buttons.css` |
| `.delice-recipe-review-section` | `components/recipe-reviews.css` |
| `.delice-recipe-star` | `components/recipe-reviews.css` |

Remove all duplicate definitions from non-canonical files, leaving only targeted overrides where genuinely needed.

---

## 1D. Button Hover and Active States

### Problem Audit

**Files:** `public/css/components/recipe-reviews.css`, `public/css/components/recipe-action-buttons.css`

- `recipe-reviews.css` line 118: `.delice-recipe-rate-btn:hover` transitions from orange to red (`#dc2626`). This is jarring — users perceive red as an error or danger signal, not a hover confirmation.
- `recipe-action-buttons.css`: Print and Share buttons are identical orange fills to the Rate button, eliminating visual hierarchy. See 1C Step 2 for the fix.

### Fix Plan for 1D

**Rate button — hover state fix**

```css
/* Replace the gradient at line 118 of recipe-reviews.css */

/* BEFORE */
.delice-recipe-rate-btn:hover {
  background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%) !important;
}

/* AFTER — stays in orange family */
.delice-recipe-rate-btn:hover {
  background: linear-gradient(135deg, #c2410c 0%, #9a3412 100%) !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(234,88,12,0.35) !important;
}

.delice-recipe-rate-btn:active {
  transform: translateY(0) !important;
  box-shadow: none !important;
}
```

**Print button — neutral outline hover**

```css
.delice-recipe-print-btn:hover {
  background: #f5f5f4 !important;
  border-color: #a8a29e !important;
  color: #1c1917 !important;
  /* No red, no orange — it's a utility action */
}
```

**Share button — neutral outline hover**

```css
.delice-recipe-share-btn:hover {
  background: #f5f5f4 !important;
  border-color: #a8a29e !important;
  color: #1c1917 !important;
}
```

**Rationale for the visual hierarchy:**

| Button | Default style | Hover style | Reasoning |
|---|---|---|---|
| Rate | Filled orange (primary) | Darker orange | Primary CTA — stands out |
| Print | White outline (secondary) | Light gray fill | Utility — recedes visually |
| Share | White outline (secondary) | Light gray fill | Utility — recedes visually |

---

## 1E. Print CSS Gaps

### Problem Audit

**File:** `public/css/components/recipe-action-buttons.css` — no `@media print` block found.  
**File:** `public/css/components/recipe-reviews.css` — no `@media print` block found (the existing `@media print` at line 460 is in a different file).

When a visitor prints a recipe page, the action buttons and the review form are both included in the printout, wasting paper and looking unprofessional.

### Fix Plan for 1E

**Add to `public/css/components/recipe-action-buttons.css` (append at end of file):**

```css
/* ── Print — hide action bar ───────────────────────── */
@media print {
  .delice-recipe-action-buttons,
  .delice-recipe-action-btn,
  .delice-recipe-print-btn,
  .delice-recipe-share-btn,
  .delice-recipe-rate-btn {
    display: none !important;
  }
}
```

**Add to `public/css/components/recipe-reviews.css` (append at end of file):**

```css
/* ── Print — hide review form ──────────────────────── */
@media print {
  .delice-recipe-review-section,
  .delice-recipe-review-inner,
  .delice-recipe-review-form,
  .delice-recipe-star-row,
  .delice-recipe-rate-btn {
    display: none !important;
  }
}
```

**Note:** If existing reviews (star ratings already submitted by users) should appear in print, keep `.delice-recipe-existing-reviews` visible. Only hide the interactive form elements.

---

# Section 2 — SEO Content Plan: Google #1 Ranking Strategy

---

## 2A. Recipe Page Content Structure

Competitive recipe pages that rank #1 on Google for high-volume terms follow a consistent, information-dense structure. The following blueprint should be used as the canonical page structure for every recipe in the plugin.

### Full Page Content Blueprint

```
1. Pre-content (above the recipe card)
   ├── Hero image (full-width, WebP, with alt text)
   ├── Post title (H1) — matches primary keyword exactly
   ├── Author byline with schema author markup
   ├── Star rating summary (aggregate + review count)
   ├── Publication date + Last updated date
   └── Jump-to-recipe button (improves UX, accepted by Google)

2. Introduction section (150–250 words)
   ├── Hook: Why THIS recipe is worth making (personal story or context)
   ├── What makes it special (unique technique, ingredient, origin)
   ├── One-sentence promise: "In 30 minutes you'll have..."
   └── Natural inclusion of primary keyword in first 100 words

3. Why This Recipe Works (100–200 words)
   ├── 3–5 bullet points explaining the science or technique
   ├── Specific, verifiable claims ("the Maillard reaction creates...")
   └── Author's personal testing notes ("After 12 test batches...")

4. Ingredients section (in recipe card)
   ├── Complete list with precise measurements
   ├── Substitution notes inline ("or use X if you don't have Y")
   └── Notes on quality ("use good-quality butter here")

5. Instructions section (in recipe card)
   ├── Numbered steps — one action per step
   ├── Visual cues ("until golden, about 4 minutes")
   └── Temperature, time, and texture markers at critical points

6. Tips and Tricks (200–300 words)
   ├── Make-ahead instructions
   ├── Common mistakes and how to avoid them
   └── Equipment recommendations with reasons

7. Variations (150–250 words)
   ├── 3–5 named variations with brief instructions
   ├── Dietary adaptation notes (gluten-free, vegan, etc.)
   └── Seasonal/regional variations

8. Storage and Reheating (100–150 words)
   ├── Refrigerator: how to store + how long
   ├── Freezer: how to freeze + how long + thaw instructions
   └── Reheating: best method for texture

9. FAQ section (see 2F for full guidelines)
   └── 10–15 Q&A pairs targeting long-tail queries

10. Related recipes (internal links)
    └── 3–5 thematically related recipes with thumbnails
```

### Content Tone Guidelines

- Write in first person with genuine personal voice: "I tested this three times before I was happy."
- Include sensory language: smell, texture, sound, appearance at each stage.
- Be specific and falsifiable. "Bake for 22–25 minutes" beats "bake until done."
- Cite sources when making health or nutritional claims.
- Never pad content with repetitive restatements — every paragraph must add new information.

---

## 2B. Schema.org Recipe Markup

Google uses structured data to power Rich Results (star ratings in SERPs, cooking time chips, calorie information). Missing or incomplete schema directly reduces click-through rates.

### Required Fields (Google Rich Results Test will fail without these)

```json
{
  "@context": "https://schema.org/",
  "@type": "Recipe",
  "name": "Classic Beef Lasagna",
  "image": [
    "https://example.com/photos/lasagna-16x9.jpg",
    "https://example.com/photos/lasagna-4x3.jpg",
    "https://example.com/photos/lasagna-1x1.jpg"
  ],
  "author": {
    "@type": "Person",
    "name": "Jane Smith"
  },
  "datePublished": "2025-03-15",
  "description": "A rich, meaty classic beef lasagna with homemade bolognese and béchamel sauce...",
  "prepTime": "PT30M",
  "cookTime": "PT1H",
  "totalTime": "PT1H30M",
  "keywords": "lasagna, beef lasagna, classic lasagna, Italian pasta",
  "recipeYield": "8 servings",
  "recipeCategory": "Main Course",
  "recipeCuisine": "Italian",
  "nutrition": {
    "@type": "NutritionInformation",
    "calories": "520 calories",
    "fatContent": "28g",
    "saturatedFatContent": "12g",
    "carbohydrateContent": "38g",
    "fiberContent": "3g",
    "sugarContent": "6g",
    "proteinContent": "31g",
    "sodiumContent": "740mg"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "ratingCount": "127",
    "bestRating": "5",
    "worstRating": "1"
  },
  "recipeIngredient": [
    "500g ground beef",
    "12 lasagna sheets",
    "400g crushed tomatoes",
    "..."
  ],
  "recipeInstructions": [
    {
      "@type": "HowToStep",
      "name": "Brown the beef",
      "text": "Heat oil in a large skillet over medium-high heat. Add ground beef and cook, breaking it up, until no pink remains, about 8 minutes.",
      "image": "https://example.com/photos/step1-brown-beef.jpg",
      "url": "https://example.com/classic-beef-lasagna/#step1"
    }
  ],
  "video": {
    "@type": "VideoObject",
    "name": "How to make Classic Beef Lasagna",
    "description": "Watch this step-by-step video...",
    "thumbnailUrl": "https://example.com/photos/lasagna-video-thumb.jpg",
    "contentUrl": "https://example.com/videos/lasagna.mp4",
    "uploadDate": "2025-03-15"
  }
}
```

### Implementation in the Plugin

The plugin must output this JSON-LD block in the `<head>` of every recipe single page, generated dynamically from the stored recipe data. Key implementation rules:

1. **All three image aspect ratios (16:9, 4:3, 1:1) must be provided** in the `image` array. Google uses all three in different contexts.
2. **`recipeInstructions` must use `HowToStep` objects**, not plain strings — this enables rich step display in Google Search.
3. **`aggregateRating` must not be hard-coded** — it must pull live from the plugin's ratings table. Google penalizes sites that display ratings not generated by real users.
4. **`dateModified`** should be added alongside `datePublished` whenever the recipe is updated.
5. **`nutrition` values must be accurate** — if they cannot be verified, omit the block rather than show estimates without disclosure.

### Schema Validation Process

After implementing or updating schema:
1. Run the URL through Google's Rich Results Test: `https://search.google.com/test/rich-results`
2. Run through Schema.org Validator: `https://validator.schema.org/`
3. Check Google Search Console > Enhancements > Recipes for any reported errors.

---

## 2C. Keyword Strategy

### Keyword Tier Definitions

**Tier 1 — Primary keyword:** The single keyword the recipe page is optimized for. Must appear in H1, URL slug, meta title, first 100 words of content, and at least one image alt attribute.

**Tier 2 — Secondary keywords:** 3–5 closely related terms. Must appear naturally in body copy, subheadings, and schema description.

**Tier 3 — Long-tail keywords:** 10–20 question-form and comparison queries. Addressed directly in the FAQ section and "Why This Recipe Works" section.

### Keyword Research Process

1. Enter the dish name into Google and study the "People also ask" (PAA) boxes — these are the exact long-tail keywords to target in the FAQ section.
2. Use the autocomplete in Google Search to find modifier patterns: "lasagna [easy/from scratch/no boil/make ahead/freezer]".
3. Examine the top-ranking competitor URLs and H2 headings to identify gaps in their content that can be captured.
4. Target recipes where the top results are from generalist sites (food.com, allrecipes.com) rather than specialist recipe creators — these are easier to displace.

### Example Keyword Map: Classic Beef Lasagna

| Tier | Keyword | Monthly Searches (US) | Placement |
|---|---|---|---|
| 1 | classic beef lasagna recipe | 14,800 | H1, slug, meta title |
| 2 | homemade lasagna | 9,900 | Intro paragraph |
| 2 | beef lasagna from scratch | 3,600 | H2, Why It Works |
| 2 | best lasagna recipe | 8,100 | Meta description |
| 3 | can you make lasagna ahead of time | 1,200 | FAQ #1 |
| 3 | how long does lasagna last in the fridge | 880 | FAQ #2 |
| 3 | can you freeze lasagna | 2,400 | Storage section + FAQ |
| 3 | lasagna without ricotta | 1,600 | Variations section |
| 3 | why does my lasagna fall apart | 390 | FAQ + Tips |

### URL Slug Rules

- Use the primary keyword verbatim: `/classic-beef-lasagna-recipe/`
- Keep slugs under 60 characters.
- Never include dates in recipe slugs — they become stale and signal content age.
- Do not use stop words (a, the, and) unless removing them changes meaning.

### Cannibalization Prevention

Track which keyword each recipe page targets in a spreadsheet. Never create two pages targeting the same Tier 1 keyword. If a related recipe exists, differentiate clearly: "quick 30-minute lasagna" vs. "classic slow-cooked lasagna" are distinct targets.

---

## 2D. E-E-A-T Signals

Google's Search Quality Evaluator Guidelines require that pages demonstrate Experience, Expertise, Authoritativeness, and Trustworthiness (E-E-A-T). For recipe sites, this means:

### Experience

**Definition:** The author has personally made the recipe and can speak to it from direct experience.

**Implementation:**
- Every recipe must include an "I made this" statement: "I've made this lasagna at least twenty times over the past three years."
- Include specific failures and what was learned: "My first attempt fell apart because I didn't rest it long enough — 20 minutes on the counter before slicing is not optional."
- Process photos that show the actual cooking environment (home kitchen, not studio) build credibility.
- Recipe testing notes: "After five batches, I found that 180°C gives better crust than 200°C."

### Expertise

**Definition:** The author demonstrates knowledge beyond the recipe steps.

**Implementation:**
- Explain the "why" behind techniques: "Salting eggplant draws out bitterness via osmosis — skip this and the dish tastes harsh."
- Reference culinary history or cultural context where relevant.
- Nutritional or dietary commentary backed by accurate data.
- Include author bio with credentials prominently on every recipe page, linked to a comprehensive author profile page.
- Author profile page should list: years of cooking experience, culinary training (formal or self-taught), any publications or media appearances, social proof.

### Authoritativeness

**Definition:** Other reputable sources reference and link to the site.

**Implementation:**
- Publish guest content or collaboration posts with recognized recipe developers.
- Build a "As Seen In" section on the About page if the site has been referenced by food media.
- Earn backlinks by creating genuinely useful resources: comparison posts ("We Tested 7 Lasagna Recipes — Here's the Best"), definitive guides, free downloadable recipe cards.
- Schema markup `Organization` with `sameAs` links to verified social profiles signals authority to Google.

### Trustworthiness

**Definition:** The site is honest, transparent, and safe to use.

**Implementation:**
- Accurate nutrition data — if estimates, say so explicitly.
- Allergy warnings are prominently displayed.
- Affiliate disclosures where applicable (FTC requirement).
- HTTPS across the entire site (WordPress setting + hosting).
- Privacy policy linked from recipe pages.
- Recipe last-updated date is displayed — signals active maintenance.
- Comments/reviews are moderated but not censored — negative reviews that are addressed publicly signal trustworthiness.

---

## 2E. Featured Snippet Optimization

Google's Featured Snippets (the "position zero" answer boxes) appear for question-form queries. Recipe sites can capture these for both informational and procedural queries.

### Types of Snippets Available for Recipe Content

**Paragraph snippet** — appears for "what" and "why" questions:
- Query: "What is the difference between ricotta and béchamel lasagna?"
- Target with a direct 40–60 word answer in the first paragraph of the relevant section.

**Numbered list snippet** — appears for "how to" queries:
- Query: "How to make lasagna from scratch"
- Target with a numbered `<ol>` list of steps (or the HowToStep schema). Keep each step to one sentence.

**Table snippet** — appears for comparison queries:
- Query: "How long does lasagna last"
- Target with a `<table>` showing storage method vs. duration.

### Formatting Rules for Snippet Capture

1. **Answer immediately after the H2/H3 heading.** Do not bury the answer in paragraph three.
2. **Match the query format exactly.** If the query is "Can you freeze lasagna?", the H3 should be "Can You Freeze Lasagna?" and the first sentence should be a direct yes/no answer.
3. **Keep paragraph answers to 40–60 words.** Google truncates longer passages.
4. **Use plain HTML — no JavaScript-rendered content.** Google's snippet extraction works on the static HTML it crawls.
5. **Mark up FAQ content with `FAQPage` schema** (see 2F) to get PAA integration.

### Example — Snippet-optimized answer block

```html
<h3>Can You Freeze Beef Lasagna?</h3>
<p>
  Yes, beef lasagna freezes exceptionally well for up to 3 months. 
  Freeze it unbaked or fully baked, tightly wrapped in two layers of 
  plastic wrap and one layer of foil. Thaw overnight in the refrigerator 
  before baking from chilled at 180°C for 45 minutes.
</p>
<table>
  <thead>
    <tr><th>Storage method</th><th>Duration</th></tr>
  </thead>
  <tbody>
    <tr><td>Refrigerator (covered)</td><td>4–5 days</td></tr>
    <tr><td>Freezer (unbaked)</td><td>Up to 3 months</td></tr>
    <tr><td>Freezer (baked)</td><td>Up to 3 months</td></tr>
  </tbody>
</table>
```

---

## 2F. FAQ Content Guidelines

FAQs are the highest-leverage content investment for recipe SEO. Each FAQ entry directly targets a long-tail query that appears in Google's "People Also Ask" boxes.

### Volume Target

Every recipe page should have 10–15 FAQ entries. Fewer provides insufficient long-tail coverage. More risks appearing padded. The optimal range is 12 questions.

### Question Sourcing Process

1. Search the primary keyword in Google. Screenshot the PAA box — every question in it is a target.
2. Repeat the search in an incognito window from a mobile device (PAA results differ by device).
3. Search the primary keyword in Google autocomplete and note the suggestions.
4. Search "site:reddit.com [recipe name]" — Reddit questions reveal what real users struggle with.
5. Check the comments on the top 3 competitor recipe pages — reader questions are long-tail goldmines.

### Question Categories Required for Every Recipe

| Category | Example questions |
|---|---|
| Make-ahead | Can you make lasagna the day before? How far in advance? |
| Storage | How long does it last? Best way to store? |
| Freezing | Can you freeze it? Baked or unbaked? How to reheat from frozen? |
| Substitutions | Can I use no-boil noodles? Can I substitute cottage cheese? |
| Troubleshooting | Why is my lasagna watery? Why does it fall apart? |
| Dietary | Is this gluten-free? Can I make it vegetarian? |
| Scaling | Can I double this recipe? What size pan for half the recipe? |
| Technique | Do I need to pre-cook the noodles? What temperature? |
| Equipment | What pan size? Can I use a glass dish? |
| Serving | What to serve with lasagna? How many does it serve? |

### Answer Format Rules

- **First sentence must be the direct answer.** Never start with "Great question!" or restating the question.
- **Ideal answer length: 50–120 words.** Long enough to be useful, short enough for snippet extraction.
- **Be specific.** "4–5 days in the refrigerator" beats "a few days."
- **Include a practical tip in every answer.** This differentiates the answer from generic results.
- **Use `FAQPage` schema** wrapping all FAQ entries:

```json
{
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Can you make beef lasagna the day before?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes — lasagna is actually better made a day ahead. Assemble it fully, cover tightly with plastic wrap, and refrigerate overnight. The pasta sheets absorb the sauce and the flavors meld. When ready to bake, remove from the refrigerator 30 minutes before putting it in the oven, then bake at 180°C covered for 45 minutes, uncovered for a further 15 minutes until bubbling."
      }
    }
  ]
}
```

### FAQ HTML Structure

Use a single `<details>`/`<summary>` accordion pattern. This keeps the FAQ scannable without requiring JavaScript, and Google can extract answers from collapsed `<details>` content.

```html
<section class="delice-recipe-faqs" aria-label="Frequently asked questions">
  <h2>Frequently Asked Questions</h2>
  <dl class="delice-recipe-faq-list">
    <div class="delice-recipe-faq-item">
      <dt class="delice-recipe-faq-question">Can you make beef lasagna the day before?</dt>
      <dd class="delice-recipe-faq-answer">
        Yes — lasagna is actually better made a day ahead...
      </dd>
    </div>
  </dl>
</section>
```

---

## 2G. Meta Title and Description Templates

Meta titles and descriptions are the primary levers for click-through rate (CTR) in search results. A 1% CTR improvement on a high-volume keyword translates directly to traffic.

### Meta Title Templates

**Primary format:**
```
[Recipe Name] Recipe - [Key Benefit] | [Site Name]
```

**Examples:**
```
Classic Beef Lasagna Recipe - Rich, Cheesy & Freezer-Friendly | Delice
Easy Homemade Lasagna Recipe - Better Than Takeout | Delice
30-Minute Chicken Stir Fry Recipe - One Pan, No Fuss | Delice
```

**Rules:**
- Keep under 60 characters (Google truncates at ~580px display width).
- Primary keyword as close to the front as possible.
- One specific benefit in the middle ("Rich, Cheesy", "One Pan").
- Site name at the end.
- Never start with the site name — waste of the most prominent position.
- Do not use all caps or excessive punctuation.

### Meta Description Templates

**Primary format:**
```
[Compelling first sentence with primary keyword]. [Key benefit or differentiator]. [Call to action].
```

**Examples:**
```
This classic beef lasagna recipe has been tested dozens of times for 
the perfect layering of rich bolognese, creamy béchamel, and 
mozzarella. Make it ahead and it tastes even better the next day. 
Get the recipe with step-by-step photos.
```

```
The best homemade lasagna recipe from scratch — tender pasta sheets, 
slow-cooked meat sauce, and a silky béchamel. Includes freezer 
instructions and make-ahead tips. Ready in 90 minutes.
```

**Rules:**
- Keep under 155 characters.
- Include primary keyword naturally.
- State one specific, concrete benefit that competitors do not emphasize.
- End with a soft call to action: "Get the recipe", "See the full instructions", "Jump to recipe."
- Never use generic descriptions like "Check out this great recipe on our site."
- Each recipe must have a unique description — duplicate meta descriptions cause cannibalization.

### Yoast/RankMath Integration

If the site uses Yoast SEO or RankMath, the plugin should populate these fields via:
```php
// Example for Yoast
add_filter( 'wpseo_title', 'delice_filter_recipe_seo_title' );
add_filter( 'wpseo_metadesc', 'delice_filter_recipe_meta_desc' );
```

The plugin's recipe data (name, description, key benefits) should be used to auto-populate a sensible default that users can then override.

---

## 2H. Internal Linking Strategy

Internal links distribute link equity, improve crawl depth, and keep visitors on-site longer. Recipe sites have natural opportunities for internal linking that are frequently underused.

### Link Types Required on Every Recipe Page

**1. Related recipes block**
Display 3–5 thematically related recipes at the bottom of every recipe page. Selection criteria in priority order:
- Same cuisine
- Same main ingredient
- Same cooking method
- Same difficulty / time level
- Same dietary profile (vegetarian, gluten-free)

The plugin should automatically suggest related recipes based on taxonomy overlap (categories, tags, ingredients).

**2. Ingredient-level links**
When a recipe uses a sub-recipe (homemade pasta, homemade stock, homemade bechamel), link the ingredient name to that sub-recipe's page:
```html
<li>2 cups <a href="/classic-bechamel-sauce/">béchamel sauce</a></li>
```

**3. In-content contextual links**
Within the Tips section or Variations section, naturally link to related technique guides or recipe variants:
```
If you prefer a vegetarian version, try our 
<a href="/roasted-vegetable-lasagna/">Roasted Vegetable Lasagna</a>.
```

**4. Category/collection links**
Every recipe page should link to its parent category (e.g., "See all Italian recipes →") to help users browse and to reinforce category page authority.

### Internal Link Anchor Text Rules

- **Descriptive:** Use the recipe name or descriptive phrase, never "click here" or "this recipe."
- **Natural:** Link text should fit grammatically into the sentence without sounding forced.
- **Varied:** Do not use identical anchor text for the same page across multiple recipes — vary between "beef lasagna recipe," "classic lasagna," "homemade lasagna," etc.

### Linking Volume per Page

- Minimum: 3 internal links per recipe page.
- Maximum: 10 contextual internal links (excluding navigation and footer).
- The "Related Recipes" block links do not count toward the contextual limit.

### Pillar Content Strategy

Create 3–5 "pillar" category pages that serve as topic hubs:
- "Italian Pasta Recipes" — links to all pasta recipes on the site
- "Make-Ahead Dinner Recipes" — links to all freezer-friendly recipes
- "30-Minute Weeknight Meals" — links to all quick recipes

Every individual recipe page must link to its relevant pillar page(s). This concentrates authority on pages targeting high-volume category terms.

---

## 2I. Image Optimization Guidelines

Images are a substantial share of recipe page weight and the primary vehicle for visual engagement. Poor image optimization directly harms both Core Web Vitals and search rankings.

### Format: WebP Required

Serve all recipe images in WebP format. WebP delivers:
- 25–35% smaller file size than JPEG at equivalent visual quality
- Native support in all modern browsers (Chrome, Firefox, Safari 14+, Edge)
- Better transparency support than JPEG (useful for ingredient cutouts)

**Implementation in the plugin:**
The plugin should convert uploaded images to WebP on the server side, or instruct users to use a WebP conversion plugin (Imagify, ShortPixel, Smush). The plugin's schema output must reference the WebP URL.

If serving both WebP and JPEG for legacy browser support:
```html
<picture>
  <source srcset="lasagna.webp" type="image/webp">
  <img src="lasagna.jpg" alt="Classic beef lasagna in a white baking dish, golden and bubbling" 
       width="1200" height="675" loading="lazy">
</picture>
```

### Required Image Sizes Per Recipe

| Image | Dimensions | Aspect ratio | Use |
|---|---|---|---|
| Hero / social share | 1200 × 630 | 16:9 | OG/Twitter cards, schema |
| Recipe card thumbnail | 600 × 450 | 4:3 | Recipe card, archive |
| Schema square | 700 × 700 | 1:1 | Google image search |
| Step process photos | 800 × 600 | 4:3 | In-content |

### Alt Text Guidelines

Alt text is used by screen readers (accessibility) and by Google Image Search (SEO). Both audiences benefit from descriptive, accurate alt text.

**Format:**
```
[Dish name] [presentation detail] [context detail]
```

**Examples:**
```
<!-- Hero image -->
alt="Classic beef lasagna in a white ceramic baking dish, golden and bubbling, 
     resting on a wooden serving board"

<!-- Process step -->
alt="Ground beef browning in a stainless steel skillet over medium-high heat, 
     broken into small pieces with a wooden spoon"

<!-- Ingredient shot -->
alt="Fresh lasagna sheet ingredients laid out: 00 flour, eggs, olive oil, 
     and a pinch of salt on a marble surface"
```

**Rules:**
- Never use "image of" or "photo of" — screen readers already announce the element type.
- Never stuff keywords repetitively: "beef lasagna recipe best beef lasagna" is wrong.
- Be specific about the visual: golden, bubbling, sliced, plated, overhead, cross-section.
- Keep under 125 characters.

### File Naming

Name image files descriptively before upload:
```
classic-beef-lasagna-hero.webp        ✓
classic-beef-lasagna-step1-brown-beef.webp  ✓
IMG_20250315_084231.jpg               ✗
recipe-photo.jpg                      ✗
```

Google uses file names as a relevance signal for Image Search.

### Lazy Loading

All below-the-fold images must use `loading="lazy"`:
```html
<img src="lasagna-slice.webp" alt="..." loading="lazy" width="800" height="600">
```

The hero image (above the fold) must NOT use `loading="lazy"` — it should load immediately to avoid LCP degradation.

### `width` and `height` Attributes

All `<img>` tags must have explicit `width` and `height` attributes matching the actual image dimensions. This allows the browser to reserve space before the image loads, preventing Cumulative Layout Shift (CLS).

---

## 2J. Core Web Vitals Considerations

Core Web Vitals (CWV) are confirmed Google ranking signals. Recipe pages are particularly vulnerable to CWV problems because they are image-heavy and often embed third-party scripts.

### Largest Contentful Paint (LCP) — Target: under 2.5 seconds

LCP measures how long the largest visible element (usually the hero image) takes to appear.

**Critical actions:**
1. Preload the hero image in `<head>`:
   ```html
   <link rel="preload" as="image" href="/lasagna-hero.webp" fetchpriority="high">
   ```
2. Do NOT lazy-load the hero image.
3. Serve the hero image from the same domain (not a third-party CDN with high TTFB).
4. Optimize hero image file size: target under 150 KB at 1200px wide.
5. Use a CDN or enable server-side caching (LiteSpeed Cache, WP Rocket, etc.).

### Cumulative Layout Shift (CLS) — Target: under 0.1

CLS measures unexpected layout movements that happen after initial page render.

**Critical actions:**
1. All images must have `width` and `height` attributes (see 2I).
2. The recipe card plugin output must not inject content that shifts the page after JS loads.
3. Web fonts must be loaded with `font-display: swap` and preloaded for the primary typeface.
4. Avoid late-loading ad units or widgets that push content down.
5. The skeleton loading state in the recipe preview (see CLAUDE.md rule 6) must match the final content dimensions to prevent shift.

### Interaction to Next Paint (INP) — Target: under 200ms

INP replaces First Input Delay in 2024. It measures responsiveness to user interactions across the full page session.

**Critical actions:**
1. JavaScript event handlers (star rating, review form submission) must respond in under 200ms.
2. Avoid long-running JS tasks on the main thread during page load. Defer all non-critical scripts.
3. The rating modal (recipe-rating-modal.css) should be pre-rendered in the DOM but hidden, not injected on click, to avoid a slow layout computation on first interaction.

### Script Loading Strategy

```html
<!-- Critical CSS inline (above the fold only) -->
<style>/* inlined critical CSS */</style>

<!-- Defer non-critical CSS -->
<link rel="preload" href="recipe-reviews.css" as="style" 
      onload="this.onload=null;this.rel='stylesheet'">

<!-- Defer all non-critical JS -->
<script src="recipe-interactions.js" defer></script>

<!-- No synchronous third-party scripts above the fold -->
```

### CWV Monitoring

- Install Google Search Console and monitor the Core Web Vitals report weekly.
- Use PageSpeed Insights (`https://pagespeed.web.dev/`) to test individual recipe pages.
- Target recipe pages specifically — they are heavier than the homepage and most commonly the entry point from search.

---

## 2K. Content Length Benchmarks

Content length is not a direct ranking factor, but it correlates with ranking because longer content tends to answer more questions, earn more backlinks, and satisfy searcher intent more completely.

### Benchmarks by Competition Level

| Recipe competition level | Minimum word count | Target word count | Notes |
|---|---|---|---|
| Low (rare dish, <1k monthly searches) | 600 | 800–1,200 | FAQ section optional |
| Medium (1k–10k monthly searches) | 1,000 | 1,500–2,500 | FAQ section required |
| High (10k–50k monthly searches) | 1,500 | 2,500–4,000 | Full blueprint required |
| Very high (50k+ monthly searches) | 2,500 | 4,000–6,000 | Video required, full blueprint |

Word counts include all body copy on the page (introduction, why it works, tips, storage, variations, FAQ) but exclude the recipe card ingredient list and instruction steps (these are structured data, not prose).

### Content Audit Process

For existing recipes that are underperforming in search:
1. Identify the primary keyword from the recipe title or slug.
2. Search that keyword and count the word count of the top 3 ranking results using a word counter extension or `wc` on the rendered HTML.
3. If the site's page has fewer than 75% of the word count of the top competitor, add content sections until it matches.
4. Focus additions on the FAQ section first (highest SEO ROI per word) and then on Tips and Variations.

### Content Density (Not Just Length)

Word count without information density is counterproductive. Each additional 100 words must contain:
- A fact, technique, or tip not covered elsewhere on the page.
- A direct answer to a real reader question.
- Or a variation/substitution that serves a distinct audience segment.

Never pad content with restatements of the recipe steps, filler phrases ("This recipe is so delicious and your family will love it!"), or thin transitions.

### Content Freshness Signal

Google tracks `dateModified` in both the HTML (`<time>` element) and in the JSON-LD schema. Recipes that are periodically reviewed and updated retain their rankings better than static pages.

**Update triggers — review any recipe for content additions when:**
- A new ingredient or tool becomes mainstream (e.g., air fryer adaptation).
- A related trend emerges in search (e.g., protein-rich variation during fitness trend).
- A top competitor publishes a page that outranks the site's page.
- More than 12 months have passed since the last content review.

When updating, always increment `dateModified` in the schema output and the visible "Last updated" date on the page.

---

*End of DESIGN-SEO-PLAN.md*

*This document covers all implementation tasks for the v3.4.0 design pass and the ongoing SEO content program. Each section is independent and can be assigned to separate implementation tickets.*
