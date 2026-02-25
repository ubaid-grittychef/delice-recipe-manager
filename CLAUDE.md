# CLAUDE.md — Delice Recipe Manager

Lessons learned and project rules for AI-assisted development on this WordPress plugin.

---

## 1. Always bump the version

Every commit that changes plugin behaviour (PHP, CSS, JS) **must** bump `DELICE_RECIPE_VERSION` in two places:

```php
// delice-recipe-manager.php — plugin header
 * Version: X.Y.Z

// delice-recipe-manager.php — constant
define( 'DELICE_RECIPE_VERSION', 'X.Y.Z' );
```

Both must always match. `DELICE_RECIPE_DB_VERSION` is separate — only bump it when the database schema actually changes.

---

## 2. Never use semantic HTML inside plugin templates

WordPress themes routinely reset or restyle `<aside>`, `<main>`, `<section>`, `<article>`, `<header>`, `<footer>` with floats, widths, and display overrides.

**Wrong:**
```html
<aside class="delice-modern-sidebar">…</aside>
<main class="delice-modern-main">…</main>
```

**Right:**
```html
<div class="delice-modern-sidebar">…</div>
<div class="delice-modern-main">…</div>
```

Use neutral `<div>` elements for layout columns. Use semantic elements only where they carry meaning for screen readers and won't be clobbered by theme CSS.

---

## 3. Always use `!important` on grid/flex containers in plugin CSS

Plugin CSS competes with theme CSS. Use `display: grid !important` / `display: flex !important` on layout containers to prevent themes from overriding them.

Use class-only selectors (not element+class) for resets so specificity is consistent:

```css
/* Correct — class-only */
.delice-modern .delice-modern-sidebar { float: none !important; }

/* Avoid — element prefix lowers resilience */
.delice-modern aside.delice-modern-sidebar { float: none !important; }
```

---

## 4. Never call `get_the_excerpt()` inside a recipe template

`get_the_excerpt()` fires the `get_the_excerpt` filter → calls `wp_trim_excerpt()` → calls `apply_filters('the_content', $text)` when the post has no manual excerpt → this triggers `display_recipe_content()` → loads the recipe template → calls `get_the_excerpt()` again = **infinite recursion → PHP stack overflow → 503/504**.

Always use the raw DB field instead:

```php
// Safe — reads raw DB column, fires no filters
$excerpt = get_post_field( 'post_excerpt', $recipe_id );
```

---

## 5. Enqueue CSS on admin pages via `wp_enqueue_style`, not inline `<link>` tags

CSS injected inside AJAX-returned HTML via inline `<link>` tags is unreliable (browsers may ignore them or they arrive too late). Instead, enqueue on the correct admin screen:

```php
// In enqueue_styles() — admin class
if ( strpos( $screen->id, 'delice-recipe-ai-generator' ) !== false ) {
    wp_enqueue_style( 'delice-recipe-public-preview', DELICE_RECIPE_PLUGIN_URL . 'public/css/delice-recipe-public.css', [], DELICE_RECIPE_VERSION );
}
```

---

## 6. Cache skeleton HTML before first AJAX replace

Once jQuery `.html(responseHtml)` replaces the skeleton with real content, the original skeleton DOM node is gone. Always cache it:

```js
// Cache once on init
const skeletonHtml = $('#delice-recipe-preview .delice-recipe-skeleton-loading').clone();

// On each generate
$('#delice-recipe-preview').html(skeletonHtml.clone());
```

---

## 7. Hero image inside padded header — use negative margin

If a hero image lives inside a padded `<header>`, it will appear inset. Break it out to full-width with a negative horizontal margin equal to the header's padding:

```css
.delice-elegant-header { padding: 40px 48px 0; }

/* Break the image out of that 48 px padding */
.delice-elegant-hero-image { margin: 0 -48px; border-radius: 0; }

/* Update responsive rules to match the mobile padding */
@media (max-width: 700px) {
    .delice-elegant-header      { padding: 28px 24px 0; }
    .delice-elegant-hero-image  { margin: 0 -24px; }
}
```

---

## 8. Sections outside `.body` need explicit vertical padding

Elements rendered outside the main `.body` padding zone (notes, nutrition, FAQs) must declare their own padding including vertical space, otherwise they appear squished:

```css
/* Not just horizontal */
.delice-elegant-notes,
.delice-elegant-nutrition,
.delice-elegant-faqs {
    padding: 28px 48px; /* top/bottom AND left/right */
}
```

---

## Development branch

All changes go to: `claude/wordpress-plugin-audit-daRbA`

Push with:
```bash
git push -u origin claude/wordpress-plugin-audit-daRbA
```
