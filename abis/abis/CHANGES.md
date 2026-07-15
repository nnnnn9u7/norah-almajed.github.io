# ABIS Frontend Redesign — Change Log & Migration Guide

This package delivers the **unified design-system foundation** for the ABIS portal:
consolidated tokens, fixed motion + component CSS, a unified responsive shell, and
data-driven `.abis-*` PHP components — plus a live preview (`ABIS Unified Shell.html`)
that loads the *real* CSS and renders all 8 roles at every breakpoint.

> The 8 dashboard PHP files are **not yet converted** — that is a mechanical follow-up
> (recipe at the bottom). They can't be visually verified without a PHP runtime, so they
> should be migrated and reviewed one at a time against this system.

---

## 1. `assets/colors-system.css` — single source of truth

| Before | After | Why |
|--------|-------|-----|
| 3 duration tokens (`--transition-fast/base/slow`) | 6 precise duration steps (`--dur-instant…--dur-crawl`) + the 3 transitions recomposed from them | One scale for every motion decision; old names still resolve so nothing breaks |
| No easing tokens | `--ease-out`, `--ease-in-out`, `--ease-spring` | Shared, intentional easing instead of ad-hoc `ease` everywhere |
| Spacing was Tailwind-only | `--space-1…--space-12` (4→48px) | Rhythm is tokenized and reusable in plain CSS/PHP |
| 4 competing radius values across files | `--radius-sm/md/lg/xl/full` | One radius language: badges→sm, inputs→md, panels→lg, sidebar/modal→xl |
| Devs used `999`/`9999` | `--z-dropdown…--z-tooltip` (100→600) | No arbitrary z-index; predictable stacking |
| No fluid type | `--text-xs…--text-3xl` via `clamp()` | Type scales smoothly 320→1920px |
| `--gm-*` tokens lived in a 2nd `:root` (dashboard-chrome.css) | Absorbed here, aliased onto canonical tokens | Single `:root`; legacy `.gm-*` markup keeps working during migration |
| device/responsive tokens lived in responsive-system.css `:root` | Moved here | Only one `:root` block exists in the codebase now |

## 2. `components/components.css` — motion + unified components

| Before | After | Why |
|--------|-------|-----|
| `transition: all var(--transition-base)` on cards, buttons, inputs, rows | Property-specific transitions (`transform`, `box-shadow`, `background-color`, `border-color`) | Never animate layout/paint simultaneously; cheaper, intentional |
| No press feedback | `.btn:active { transform: scale(0.97) }` at `--dur-instant` | Tactile confirmation on every button |
| `:hover` lift fired on touch | Lift gated by `@media (hover: hover) and (pointer: fine)` | No sticky hover states on touch devices |
| `slideUp` from `translateY(20px)` | `enter-up` from `translateY(10px) scale(0.98)` + `enter-fade` | Smaller, physical entrances |
| `z-index: 1000` on modal | `var(--z-modal-backdrop)` / `var(--z-modal)` | Tokenized stacking |
| No reduced-motion handling | `@media (prefers-reduced-motion: reduce)` kills durations | Accessibility / vestibular safety |
| No shared shell | Full `.abis-*` system: shell, sidebar, topbar, stat card, table, badges, progress, empty state | One visual language for all 8 dashboards |
| Entrances could leave content invisible | `.abis-reveal` gated behind `.js-anim` | Content is **always** visible without JS or if animation is throttled |

## 3. `assets/responsive-system.css` — fix every broken surface

| Before | After | Why |
|--------|-------|-----|
| Sidebar dropped inline above content on ≤1023px | `.abis-sidebar` becomes an off-canvas drawer (`translateX(-100%)→0`) with backdrop, Escape-to-close, sticky mobile bar + hamburger | Real mobile pattern, not a giant header |
| Escape closed with animation | Escape adds `.no-anim` → instant | No animation on keyboard shortcut |
| Form grids overflowed on mobile | `.two`/`.three` collapse to 1 column ≤640px | No horizontal overflow |
| Modals not bottom-anchored | ≤479px modals dock as a bottom sheet (`enter-up`, top-rounded) | Native-feeling on phones |
| Tables broke layout | `.abis-table-wrap` scrolls; first column sticky (in components.css) | Name/ID stays visible while scrolling |
| Full-bleed on huge screens | `.abis-shell` caps at 1600px and centers ≥1601px | Comfortable desktop density |
| 2nd `:root` block here | Removed (tokens moved to colors-system.css) | Single source of truth |

## 4. `components/components.php` — unified, data-driven shell

New functions (identical markup for every dashboard):

- `abisShellStart($nav, $active, $user, $brand)` / `abisShellEnd()` — mobile bar, backdrop, `.abis-sidebar`, `<main>`.
- `abisTopbar($title, $subtitle, $crumbs, $actionsHtml)` — breadcrumb + title + CTAs.
- `abisStatCard($label, $value, $trend, $dir, $icon)` — numeric values carry `data-value` for the counter animation.
- `abisBadge($text, $tone)` — semantic status colors (active/pending/rejected/submitted/draft…).
- `abisTableStart($cols)` / `abisTableEnd()` — scroll-wrapped, sticky-first-column table.
- `abisShellScript()` — one JS block: drawer (tap/Escape/backdrop), counters, scroll-triggered progress, table stagger; adds `.js-anim` only when motion is allowed.

## 5. `assets/dashboard-chrome.css` — transitional

`:root` block removed (tokens absorbed + aliased in colors-system.css). `.gm-*` component
classes retained so un-migrated dashboards keep rendering. **Delete** once all dashboards
use `.abis-shell`.

---

## Dashboard migration recipe (per file)

For each `dashboard_*.php`:

1. **Head:** ensure these load (in order): `colors-system.css`, `components/components.css`,
   `assets/responsive-system.css`. Remove any `file_get_contents('dashboard-chrome.css')`
   inline block and any per-file `:root`/`.gm-*`/`.pm-*` `<style>` chrome.
2. **Sidebar + shell:** replace the hand-rolled `<aside class="gm-sidebar">` / Tailwind `<aside>`
   / `.pm-sidebar` with:
   ```php
   require_once __DIR__.'/components/components.php';
   $nav = [
     ['key'=>'overview','label'=>'Overview','href'=>'dashboard_tech.php','icon'=>'fa-solid fa-gauge-high'],
     // …role-specific items
   ];
   abisShellStart($nav, 'overview', ['name'=>$user['name'],'role'=>'Tech Supervisor','initials'=>'FO']);
   abisTopbar('Tech Supervisor', 'Crew readiness…', [['label'=>'Portal','href'=>'dashboard.php'],['label'=>'Tech']], $ctaHtml);
   ```
3. **Stat cards:** replace `.metric-card` / `.kpi-card` / `.stat-card` / inline Tailwind with
   `echo '<section class="abis-stat-grid">'; abisStatCard('Active jobs', 18, '+3 this week', 'up', 'fa-solid fa-helmet-safety'); … echo '</section>';`
4. **Tables:** wrap with `abisTableStart([...])` / row `<tr class="abis-reveal">` / `abisTableEnd()`; use `abisBadge()` in the status cell.
5. **Close + script:** `abisShellEnd(); abisShellScript();` before `</body>`.

Quality gates to re-check after each conversion: no hardcoded hex, no `transition: all`,
no arbitrary z-index, all interactive elements ≥44px, all stat numbers have `data-value`,
identical sidebar structure, semantic badge colors only.
