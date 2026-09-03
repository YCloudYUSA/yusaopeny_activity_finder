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

- **Backend Plugin Architecture** — Converted the search backend from a hardcoded service into an extensible Drupal plugin type (`ActivityFinderBackend`). Search execution is now split into distinct count, facet, and paged result steps, shared across AF4, AF3, and Camp Finder via a unified backend factory.
- **Mock Search Backend** — Added a zero-infrastructure, fixture-driven backend (`mock`) as the default for new installations, enabling local development and QA without requiring a running Solr stack.
  - **In-Memory Filtering**: Filters sample session data across locations, categories, ages, keywords, days, time slots, and block restrictions.
  - **Dynamic Facet Recomputation**: Dynamically recalculates facets (sub-category, top-level group, location, weekday/time-of-day) on filtered results using Solr-compatible slot overlap rules.
  - **Automatic Date Shifting**: Shifts sample session dates forward in whole-week increments from a capture anchor so fixture data never drifts into the past while maintaining correct weekdays.
  - **Schema Compatibility**: Enforces JSON schema validation to guarantee wire compatibility with the Solr backend contract.
  - **Best-Effort Scope**: Uses direct substring matching for keyword search and returns empty results for deferred live availability lookups (`getProgramsMoreInfo`).
- **Per-Block Backend Selection** — Added a backend selector dropdown to block configuration to toggle between "Site default" and explicit backend plugins.
- **Backend Response Extensions** — Added `getExternals()` to the plugin contract, allowing backends to include supplemental metadata in response payloads.
- **Vite 5 Build Pipeline** — Replaced Vue CLI 4 / Webpack with Vite 5, preserving the UMD library output contract.
- **Solr Submodule Extraction** — Extracted Solr integration and Search API processors into a dedicated `openy_activity_finder_solr` submodule to keep the core module lightweight.
- **Automated Upgrade Path** — Added `hook_update_9006` to automatically transition existing sites from the legacy global Solr service to the new `solr` plugin.
- **Layout Builder Demo Submodule** — Added the `lb_activity_finder_demo` submodule to test AF4 rendering within Layout Builder environments.
- **Brand & Styling Tokens** — Added `CACHET_BRANDBOOK.md` documentation and `$af-font-*` SCSS tokens to standardize theme font variable pass-throughs without visual regressions.

#### Changed

- **Vue 3.4 Upgrade** — Upgraded core app from Vue 2.6 (EOL) to Vue 3.4, completely removing `@vue/compat` for a clean, warning-free Vue 3 runtime.
- **Custom Accessible UI Components** — Replaced BootstrapVue with lightweight, native Vue 3 components for modals, collapsible fieldsets/accordion folds, and tooltips.
- **Vue 3 Template & API Modernization**:
  - Converted Vue 2 template filters (`{{ x | t }}`) to helper method calls (`{{ t(x) }}`).
  - Refactored component two-way bindings to Vue 3 `v-model` props (`modelValue` / `update:modelValue`).
  - Replaced global mixins with `app.config.globalProperties`.
- **Native Browser Fetch & Icon Libraries**:
  - Replaced `axios` with native browser `fetch`, maintaining custom bracket array serialization for query parameters.
  - Upgraded Iconify to `@iconify/vue` v4 and updated FontAwesome packages.
- **Simplified Navigation & State Management** — Removed `vue-router` (AF4 uses step-based state) and replaced URL query parameter handling with the native Browser History API (`pushState`).
- **Externalized Vue Runtime & Bundle Optimization** — Externalized the Vue 3 runtime to the shared `openy_system/vue3` library, shrinking the UMD bundle (`activity_finder_4.umd.min.js`) from 373.4 KB to 222.3 KB (**-40.5%**).
- **Single-Backend Block Configuration** — Simplified block configuration from multi-select backend aggregation to a single backend selector.
- **Solr Configuration Separation** — Moved Solr Search API server/index configurations and alter hook documentation into the `openy_activity_finder_solr` submodule.

#### Fixed

- **Mock Backend Age Filtering** — Fixed an issue where the `ages` search parameter was ignored and returned all fixture sessions.
- **Mock Backend Dynamic Facets** — Fixed static facet counts for categories, locations, and time slots by dynamically updating counts based on active search filters.
- **Mock Backend Date Drifting** — Fixed sample session dates drifting into the past by automatically shifting fixture dates forward in whole-week increments.
- **URL Query State & History Navigation** — Resolved lost search state on page reloads and back/forward browser navigation, preventing duplicate history entries.
- **Bookmarked Items Modal Styling** — Resolved UI bugs in the bookmarks modal, including double scrollbars, missing body scroll lock, oversized close icons, and backdrop opacity.
- **JSON Schema Externals Serialization** — Fixed schema validation errors when `externals` is empty by serializing empty PHP arrays as objects (`{}`).
- **Vite Environment Variable Crash** — Fixed a browser `ReferenceError` caused by un-replaced `process.env.NODE_ENV` references under Vite.
- **Solr Class Reference Fatal Error** — Removed a stale `OpenyActivityFinderSolrBackend` class reference in module hooks that triggered fatal errors when saving program subcategories or branches.
- **Module Dependency Constraints** — Added missing `openy_system` module dependency and bumped `openy_custom` constraint to `^3.2.0` to ensure Vue 3 library availability.
- **Block Fallback Configuration** — Fixed AF4 blocks fallback handling to correctly inherit the site default backend setting when unselected.

#### Removed

- Deprecated dependencies including `axios` (replaced by `fetch`) and `@vue/compat`.
- Legacy multi-backend aggregation and merge logic (`routeSlice`, `mergeFacets`, `collectExternals`), focusing block execution on a single active backend.

#### Known issues

- Potential global `window.Vue` namespace collision when rendering legacy AF3 or Camp Finder on the same page as AF4 Vue 3.
- Non-fatal console error in `Step.vue` (`this.$refs.bottom`) during step navigation.
- Unused legacy packages (`vue-router`, `bootstrap-vue`) remaining in `package.json`.
- Comprehensive visual and responsive QA pass across all mobile and tablet breakpoints is pending.
- Mock backend date shifting for multi-day date ranges spanning a new year boundary (e.g. Dec 30 – Jan 2) is untested and may miscalculate target years.
- Mock backend time-slot boundary definitions are duplicated across helper methods and require synchronized updates if Solr boundaries change.

#### Upgrade notes

- **Dependency Requirements** — Requires `open-y-subprojects/openy_custom ^3.2.0` to provide the shared Vue 3 library.
- **Backend Migration** — Existing sites using the legacy `openy_activity_finder.solr_backend` service will automatically migrate to the `solr` plugin via `hook_update_9006`. New installations default to `mock`.
- **Integration Backwards Compatibility** — Maintains the existing UMD global (`activity_finder_4`), mount selector (`#activity-finder`), and asset paths. AF3 and Camp Finder remain on Vue 2 and are unaffected.
