# Changelog

All notable changes to this module are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this module adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html)
via its `.info.yml` `version` key.

## [Unreleased]

### AF4: Vue 2 → Vue 3 migration (ITCR-1273)

Migrates Activity Finder v4 (`openy_af4_vue_app/`) from Vue 2.6 (EOL) to Vue
3.4, and turns the search backend into a Drupal plugin type shared by AF4,
AF3, and Camp Finder. See [PR #79](https://github.com/YCloudYUSA/yusaopeny_activity_finder/pull/79)
for full discussion, review history, and measured bundle-size deltas.

#### Added

- **Backend plugin architecture** — Activity Finder search backend (Solr/Mock)
  is now a Drupal plugin type (`ActivityFinderBackend`) instead of a
  hardcoded global service. `runProgramSearch()` decomposed into
  `getResultsCount()` / `getFacets()` / `getResults(offset, limit)`. Shared
  by all three apps (AF4, AF3, Camp Finder) via one factory.
- **Mock backend** — fixture-driven backend requiring no live Solr stack;
  new install default. Enables local dev/QA without infrastructure.
- Per-block backend selector (single `select`: "Site default" or an
  explicit plugin id).
- `getExternals()` on the backend contract — backends can attach extra data
  into the response `externals` map.
- Vite 5 build (`vite.config.js`) replaces vue-cli 4 / webpack; UMD lib mode
  preserves the `activity_finder_4.umd.min.js` contract.
- `openy_activity_finder_solr` submodule — Solr implementation + 8
  search_api processors extracted out of the main module.
- `hook_update_9006` — upgrade path mapping the legacy global
  `openy_activity_finder.solr_backend` service to the new `solr` plugin.
- `openy_af4_vue_app/docs/CACHET_BRANDBOOK.md` and `$af-font-*` SCSS tokens
  centralizing font-family declarations (host-theme `--ylb-font-family-*`
  passthrough, no visual change).
- `lb_activity_finder_demo` submodule — Layout Builder demo page mounting
  real AF4 for dev/QA in an LB context.

#### Changed

- **Vue 2.6 (EOL) → Vue 3.4.** Core swap via `@vue/compat` MODE:2 for
  gradual migration, later fully removed — pure Vue 3, no compat layer, no
  runtime warnings.
- BootstrapVue (no Vue 3 build exists) → hand-rolled components: modal,
  collapsible fieldset/foldable, tooltips.
- ~101 filter-pipe usages (`{{ x | t }}`) across 34 `.vue` files → method
  calls (`{{ t(x) }}`) — Vue 3 dropped template filters.
- Global mixin → `app.config.globalProperties`.
- 27 components: Vue 2 `v-model` (`value`/`input`) → Vue 3
  (`modelValue`/`update:modelValue`).
- `axios` → native `fetch`; same `client(flag).request({params})` call
  contract preserved. Array params re-serialized manually
  (`backend[]=mock`) since `URLSearchParams` doesn't replicate axios's
  bracket notation.
- `@iconify/vue2` → `@iconify/vue` v4 (Vue 3 native, same Icon API);
  FontAwesome v2 → v3.
- `vue-router` removed (it defined zero routes — AF4 navigates via
  step-state, not routing); the 3 leftover `$route`/`$router` usages in
  `App.vue` for URL query state replaced with native `history.pushState` /
  `URLSearchParams`.
- Vue 3 runtime **externalized** to the shared `openy_system/vue3` library
  (cdnjs 3.5.41) instead of bundled in the UMD — cuts
  `activity_finder_4.umd.min.js` from 373.4K to 222.3K (**-40.5%**).
- Block backend selection narrowed from a checkbox list (multi-backend) to
  a single `select` — multi-backend aggregation stays dormant/experimental,
  not shipped as a claimed feature.
- Solr-related `search_api.server`/`search_api.index` config and the
  `hook_activity_finder_program_process_results_alter` doc moved to the
  `openy_activity_finder_solr` submodule (main module no longer references
  Solr).

#### Fixed

- **Mock backend age filter** — `ages` parameter was silently ignored;
  every request returned all fixture sessions regardless of selection.
- **Mock backend facets** — category, weekday/time-of-day, and location
  facet counts were static fixture totals, not recomputed from the
  filtered result set.
- **Mock fixture dates** — captured fixture dates would drift into the past
  as real time advanced; now shift forward by whole weeks from the capture
  anchor, including date *ranges* (e.g. `"Jun 08-Jun 09"`).
- **URL state loss** — dropping `vue-router` silently broke `$route`/
  `$router` reads in `App.vue`; URL params stopped updating and page reload
  lost all filter state. Fixed via native History API, plus edge cases:
  duplicate history entries on same-URL updates, stale re-entry flag on
  back/forward navigation.
- **Bookmarked items modal UI** — double scrollbar, missing body-scroll
  lock while open, oversized close button, backdrop opacity mismatch vs.
  design.
- Empty `externals` PHP array serialized as `[]`, failing the JSON
  schema's `externals:object` — cast to object so it serializes as `{}`.
- `process.env.NODE_ENV` reference in `main.js` threw `ReferenceError` in
  the browser under Vite (webpack/vue-cli auto-injected this; Vite
  doesn't).
- Stale `OpenyActivityFinderSolrBackend` class reference left in
  `openy_activity_finder.module` after the Solr submodule extraction —
  caused a fatal error on `program_subcategory`/`branch` save.
- `openy_activity_finder.info.yml` never declared `openy_system` as a
  module dependency, despite `libraries.yml` depending on it since 2020.
- `openy_system/vue3` didn't exist before `open-y-subprojects/openy_custom`
  3.2.0 — externalizing Vue without bumping this constraint broke sites
  still locked to 3.1.5.
- AF4 block backend now inherits the site default
  (`openy_activity_finder.settings:backend`) when none is selected,
  instead of a hardcoded `'mock'`.

#### Removed

- `axios` dependency (replaced by `fetch`).
- `@vue/compat` and its config once the pure-Vue-3 migration completed.
- Cross-backend routing/merge code (`routeSlice`, `mergeFacets`,
  `collectExternals` over N backends) — descoped to single-backend-per-block;
  kept only as a documented experimental follow-up.

#### Known issues

- AF3/Camp Finder same-page `window.Vue` collision with the externalized
  `openy_system/vue3` global build is unverified.
- `Step.vue`'s `handleSticky()` references `this.$refs.bottom`, which has
  no matching `ref="bottom"` in the template — throws (non-fatal) on step
  navigation; pre-existing gap from the Vue 3 rewrite.
- `vue-router` and `bootstrap-vue` still listed in `package.json` but
  unused — dead dependencies, no functional impact.
- No full visual/functional QA pass recorded across every AF4 screen
  (desktop/tablet/mobile) at this head.

#### Upgrade notes

- Requires `open-y-subprojects/openy_custom ^3.2.0` (adds
  `openy_system/vue3`).
- Sites on the legacy `openy_activity_finder.solr_backend` service
  auto-map to the `solr` plugin via `hook_update_9006`; fresh installs
  default to `mock`.
- Preserved contract: same UMD global (`activity_finder_4`), same mount id
  (`#activity-finder`), same `libraries.yml`/`dist/` paths. AF3 and Camp
  Finder are untouched (still Vue 2, out of scope).
