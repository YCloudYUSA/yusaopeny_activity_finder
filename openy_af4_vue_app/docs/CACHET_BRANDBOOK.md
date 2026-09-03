# Cachet Brandbook — Activity Finder

> The Cachet typeface is the YMCA's primary brand font. This brandbook codifies how
> Cachet is defined, loaded, and applied across YMCA Website Services (Open Y) so the
> Activity Finder design stays on-brand and consistent with the surrounding site.
>
> Sources: verified against `y_fonts`, `y_lb`, `openy_carnation`, and the
> `ws_small_y` / `ws_colorway_*` / `lb_*` module ecosystem (YMCA Website Services,
> "small-y" profile).

---

## 1. The typeface

| Property | Value |
| --- | --- |
| Family name | `Cachet` |
| Role | Brand display + UI font (headings, buttons, nav, labels, badges) |
| Body counterpart | `Verdana` (long-form copy is **not** set in Cachet) |
| Licensing | Commercial — licensed (fonts.com). **Not** redistributable: enforced by keeping the font module **private / undistributed** (`y_fonts` info.yml: "NOT FOR DISTRIBUTION DUE TO FONT LICENSING"), **not** by omitting the files. |
| Delivery | Shipped as WOFF2/WOFF inside the **`y_fonts`** module (`y_fonts/css/cachet.css` + `y_fonts/fonts/CachetW05-*.woff2`), injected as `@font-face` by `y_fonts_page_attachments()`. (A separate `openy_font` module also exists in the stack — see §2.) |

### Weight system

`y_fonts/css/cachet.css` declares a **single `@font-face` family `'Cachet'`** in **four**
weights, selected by `font-weight` — there is **no** per-weight family name:

| Weight | `font-weight` | WOFF2 face | Typical use |
| --- | --- | --- | --- |
| ExtraLight | `300` | `CachetW05-ExtraLight` | rare / legacy |
| Book | `400` | `CachetW05-Book` | body-adjacent UI, nav, breadcrumbs, filter tags, default buttons |
| Medium | `500` | `CachetW05-Medium` | headings (h1/h2/h5/h6), form labels, card links, event badges, CTAs |
| Bold | `700` | `CachetW05-Bold` | strong card titles, emphasis |

> **There is no `"Cachet Medium"` / `"Cachet Book"` / `"Cachet W01 Book"` family rendered
> on screen.** `y_fonts` registers only `'Cachet'`; weight is chosen via `font-weight`.
> The named families inside `--ylb-font-family-medium` / `-book` match no rendered
> `@font-face` (they appear only in `openy_repeat`'s print stylesheet) and resolve down to
> `'Cachet'`. **Differentiate weight with `font-weight` — not by choosing a
> `$af-font-book` vs `$af-font-medium` token** (both resolve to `'Cachet'`).
>
> `openy_carnation` uses one `800` instance on the global-search pager; `y_fonts` ships no
> `800` face (the browser synthesizes it). **Do not** introduce new `800` usages.

---

## 2. How Cachet is loaded (the injection chain)

The AF Vue app never declares `@font-face`. The actual chain (verified against the code):

```
y_fonts module — ships CachetW05-*.woff2 in-repo (NOT admin-uploaded)
   └─ y_fonts_page_attachments() attaches the 'y_fonts/cachet' library CONDITIONALLY:
        non-admin route AND a node/user/webform/exception context,
        and for nodes only when the node uses Layout Builder
        (field OverridesSectionStorage present, field_use_layout_builder not disabled)
        -> @font-face family 'Cachet' in weights 300/400/500/700
   └─ y_lb (assets/css/page.css :root) defines the font custom properties:
        --ylb-font-family-cachet : Cachet, Verdana, Geneva, sans-serif
        --ylb-font-family-verdana: Verdana, Geneva, sans-serif
        --ylb-font-family-medium : "Cachet Medium", Cachet, Verdana, sans-serif
        --ylb-font-family-book   : "Cachet Book", "Cachet W01 Book", Cachet, Verdana, sans-serif
        (colorway modules, e.g. ws_colorway_canada/assets/css/canada-typography.css,
         REDEFINE these to var(--ylb-font-family-montserrat) — host font becomes Montserrat)
   └─ AF components consume var(--ylb-font-family-*, <fallback>)
```

**Consequences for Activity Finder:**
- Consume the `--ylb-*` custom properties; never declare `@font-face` or hard-code a face.
- `y_fonts` loads Cachet **only on Layout Builder pages** (with a node/user context), so AF
  rendered outside that context falls back to Verdana — **verify both states**.
- AF inherits whatever the colorway sets: on a Montserrat colorway the AF chrome is
  Montserrat. The token system is **host-font-agnostic**, not Cachet-specific.

### Canonical font stacks

```css
/* Cachet (default brand UI) */
font-family: var(--ylb-font-family-cachet, Cachet), Verdana, sans-serif;

/* Cachet Book (400) */
font-family: var(--ylb-font-family-book, "Cachet W01 Book"), Verdana, sans-serif;

/* Cachet Medium (500) */
font-family: var(--ylb-font-family-medium, "Cachet Medium"), Verdana, sans-serif;

/* Body / long-form copy */
font-family: var(--ylb-font-family-verdana, Verdana), sans-serif;
```

---

## 3. Type scale (from `openy_carnation`)

Reference scale the Activity Finder should align to. Cachet headings are **uppercase**
with **tight negative tracking**.

| Element | Weight | Size | Letter-spacing | Transform | Color |
| --- | --- | --- | --- | --- | --- |
| h1 | 500 | 50px | -2px | uppercase | `#2f2f2f` (gray-700) |
| h2 | 500 | 40px | -2px | uppercase | `#2f2f2f` |
| h3 | 400 | 30px | -0.5px | — | inherit |
| h4 | 400 | 24px | -0.5px | — | `#636466` (gray-400) |
| h5 | 500 | 20px | -0.5px | — | inherit |
| h6 | 500 | 20px | — | — | inherit |
| Body | — (Verdana) | base | — | — | inherit |

> Headings carry **negative letter-spacing** (`-0.5px` to `-2px`) — this is a defining
> trait of the YMCA look. Buttons/labels often add `text-transform: uppercase`.

---

## 4. Element application map

How each UI primitive uses Cachet in the YMCA design language. The Activity Finder
should mirror this so its controls feel native inside an Open Y page.

| UI element | Cachet weight | Notes |
| --- | --- | --- |
| Page / step titles (h1–h2) | 500 | uppercase, `-2px` tracking |
| Sub-titles (h3–h4) | 400 | `-0.5px` tracking |
| Buttons (default) | 400 (`cachet-book`) | uppercase on CTAs |
| Buttons (CTA / primary) | 500 | uppercase, e.g. LTO button 18px |
| Primary / mobile nav | 400 | mixed transform |
| Breadcrumbs | 400 | 20px, no transform |
| Form labels | 500 | 14px, `#4f4f4f` (gray-500) |
| Form controls / selects | 400 | 14px, uppercase on selects |
| Filter tags / pills | 400 | 12px, uppercase, 30px pill |
| Card titles | 500 / 700 | bold for branch/location names |
| Event date badges | 500 | uppercase, tight tracking |
| Status / alert messages | 500 | 18px |
| Body copy / descriptions | — | **Verdana**, not Cachet |

> **Rule of thumb:** structural and interactive chrome → Cachet; reading content
> (paragraphs, result descriptions) → Verdana.

---

## 5. Brand color pairing

Cachet typography is paired with the Y brand palette (exposed as `--ylb-color-*` /
`--y-color-*` custom properties). The Activity Finder already defines an aligned
palette in `openy_af4_vue_app/src/scss/_variables.scss`.

| Token | Hex | Pairs with |
| --- | --- | --- |
| Primary blue | `#0060af` (`$af-blue`) | links, primary buttons |
| Light red / brand red | `#ed1c24` (`$af-light-red`) | accents |
| Purple | `#92278f` (`$af-violet`) | section headings, captions |
| Teal | `#006b6b` | breadcrumb links |
| Pink | `#c6168d` | badges, pager, event date |
| Dark gray | `#2f2f2f` / `#636466` | heading text |
| White | `#fff` | text on dark/brand backgrounds |

The `$af-ages` map in `_variables.scss` already encodes the age-group brand colors used
on result cards — keep Cachet labels legible against each (contrast checked per pair).

---

## 6. Applying this to Activity Finder (7.x)

### Current state
- `openy_af4_vue_app/src/scss/_variables.scss` defines **colors and border-radius only** —
  there are **no font tokens**.
- Vue components hard-code `font-family: var(--ylb-font-family-cachet, Cachet), Verdana, sans-serif;`
  inline, repeated across `Step.vue`, `SelectPath.vue`, `Fieldset.vue`, `Results.vue`, etc.
- AF CSS is loaded via the `theme` category, so a host theme can override it.

### Recommended direction (to be implemented in follow-up PRs)
1. **Add font tokens to `_variables.scss`** so the inline `var(...)` duplication has a
   single source of truth:
   ```scss
   $af-font-cachet:  var(--ylb-font-family-cachet, Cachet), Verdana, sans-serif;
   $af-font-medium:  var(--ylb-font-family-medium, "Cachet Medium"), Verdana, sans-serif;
   $af-font-book:    var(--ylb-font-family-book, "Cachet W01 Book"), Verdana, sans-serif;
   $af-font-body:    var(--ylb-font-family-verdana, Verdana), sans-serif;
   ```
2. **Replace hard-coded `font-family` declarations** in the Vue SFCs with these tokens.
3. **Align the type scale** of AF step/result titles to §3 (weight 500, uppercase,
   negative tracking) so headings match the surrounding Open Y page.
4. **Never add `@font-face`** to the AF app — rely on `y_fonts` (@font-face) + `y_lb`
   (custom properties) injection. Set weight via `font-weight`, since `y_fonts` exposes
   weight through the single `'Cachet'` family, not per-weight family names.
5. **Keep result/description body copy in Verdana**, headings/labels/badges in Cachet.
6. **Accessibility:** because Cachet may be absent (unlicensed sites), verify every
   layout against the Verdana fallback — line lengths and button widths must not break.

---

## 7. Do / Don't

**Do**
- Consume `--ylb-font-family-*` custom properties.
- Use 400/500/700 only.
- Uppercase + negative tracking on display headings and CTAs.
- Test with and without Cachet present.

**Don't**
- Ship or `@import` Cachet font files (licensing).
- Hard-code `font-family: Cachet` without the `--ylb-*` var and Verdana fallback.
- Set body/reading copy in Cachet.
- Introduce new `800` weight usages.
