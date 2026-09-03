# Upgrading — Activity Finder backend plugin system (7.x)

This release turns the Activity Finder data backend into a **Drupal plugin
type** and **decomposes the search contract**. If you have custom code that
implements, configures, or calls an Activity Finder backend, read this.

## What changed

### 1. Backend is now a plugin type

Before, the backend was a service selected by a global config service id
(`openy_activity_finder.settings:backend` → `\Drupal::service(...)`). There was
no per-block choice and no discovery.

Now backends are **plugins** of type `ActivityFinderBackend`, discovered from
`src/Plugin/ActivityFinderBackend/` via the `#[ActivityFinderBackend(...)]`
attribute (annotation fallback below Drupal 10.2). Selection is **per block** —
a single backend (block config key `backend_plugin`, a plugin id; empty inherits
the site default) resolved through the plugin manager
`plugin.manager.activity_finder_backend`.

Shipped plugins: `mock` (static fixtures, no Solr — the default) and `solr`
(the search index).

### 2. The backend contract was decomposed (BREAKING)

`OpenyActivityFinderBackendInterface` no longer declares `runProgramSearch()`.
Search is now composable so several backends can be aggregated uniformly:

```php
public function getResultsCount(array $parameters): int;                       // cheap, rows-free
public function getFacets(array $parameters): array;                           // uniform facet shape
public function getResults(array $parameters, int $offset, int $limit, int $log_id): array;  // a slice
```

The old `runProgramSearch()` **response** shape (`count`, `facets`, `pager`,
`pager_info`, `table`, `groupedLocations`, `sort`) is unchanged — it is now
assembled in one place, `ActivityFinderBackendAggregator`, from the three
methods above. A JSON Schema of that response lives at
`fixtures/schema/runProgramSearch.schema.json`.

The interface keeps the option getters (`getLocations`, `getSortOptions`,
`getAges`, `getDaysOfWeek`, `getPartsOfDay`, `getDaysTimes`, `getStartMonths`,
`getDurations`, `getWeeks`, `getCategories`, `getCategoriesType`,
`getRelevanceSort`, `getFiltersSectionConfig`, `getProgramsMoreInfo`).

### 3. `OpenyActivityFinderBackend` no longer implements the interface

The legacy base class is now a plain Solr helper. The `OpenyActivityFinderSolrBackend`
service still exists and keeps `runProgramSearch()`/`doSearchRequest()` etc.; the
new `solr` **plugin** wraps that service and exposes the granular contract.

## How to update your code

### You call the backend

Old:

```php
$backend = \Drupal::service($config->get('backend'));
$data = $backend->runProgramSearch($parameters, $log_id);
```

New — go through the aggregator with the block's selected backend ids:

```php
$aggregator = \Drupal::service('openy_activity_finder.backend_aggregator');
$data = $aggregator->search($plugin_ids, $parameters, $log_id);
```

The AJAX endpoint `/af/get-data` reads the backend ids from the `backend[]`
query parameter (the block forwards its selection to the JS app). It validates
them against the registered plugins and returns `['error' => ...]` when none
resolve — it never substitutes a backend silently.

### You implement a custom backend (e.g. Daxko)

Convert your backend into a plugin:

1. Move the class to `src/Plugin/ActivityFinderBackend/` in your module.
2. Add the attribute and extend the base:

   ```php
   use Drupal\openy_activity_finder\Attribute\ActivityFinderBackend;
   use Drupal\openy_activity_finder\Plugin\ActivityFinderBackendPluginBase;

   #[ActivityFinderBackend(
     id: 'daxko',
     label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Daxko'),
   )]
   class DaxkoBackend extends ActivityFinderBackendPluginBase { /* ... */ }
   ```

3. Replace `runProgramSearch()` with `getResultsCount()`, `getFacets()` and
   `getResults($parameters, $offset, $limit, $log_id)`, and implement the option
   getters. Keep counts and facets cheap (rows-free) and have `getResults()`
   honour `$offset`/`$limit` so the aggregator can paginate across backends.

The plugin is then auto-discovered and appears in the block's backend selector;
no service-id wiring needed. (The bundled Daxko module has **not** been
converted in this release — a Daxko site must ship its own plugin.)

### Existing blocks / sites

The block default is now `mock`. A `hook_update_N` stamps existing
`activity_finder_4` blocks with `backend_plugin: ['solr']` so live sites keep
using Solr. Mapping the legacy global `settings.backend` service id to a plugin
id for blocks with no stored value is deferred (the legacy fallback).
