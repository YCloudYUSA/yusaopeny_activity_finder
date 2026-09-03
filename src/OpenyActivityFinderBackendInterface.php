<?php

namespace Drupal\openy_activity_finder;

/**
 * Contract every Activity Finder backend plugin implements.
 *
 * Search is decomposed into composable parts — count, facets, results — so a
 * block running one OR more backends aggregates them uniformly: count is
 * summed, facets are merged per filter, results are unioned then sorted and
 * paginated once. The composition lives in ActivityFinderBackendAggregator,
 * not here and not per-backend (W0b DECISIONS D10/D11).
 */
interface OpenyActivityFinderBackendInterface {

  /**
   * Number of results per page.
   */
  const RESULTS_PER_PAGE = 25;

  /**
   * Cache tag invalidated when Activity Finder content changes.
   */
  const CACHE_TAG = 'openy_activity_finder:default';

  /**
   * Count matching results for the given search parameters.
   *
   * @param array $parameters
   *   GET parameters for the search.
   *
   * @return int
   *   Total number of matches across all pages.
   */
  public function getResultsCount(array $parameters): int;

  /**
   * Get facets for the given search parameters.
   *
   * Uniform shape so facets are mergeable across backends:
   * [facetKey => [['filter' => string, 'count' => int, 'id' => mixed?], ...]].
   *
   * @param array $parameters
   *   GET parameters for the search.
   *
   * @return array
   *   Facets keyed by facet machine name.
   */
  public function getFacets(array $parameters): array;

  /**
   * Get a slice of matching result rows for the given parameters.
   *
   * Returns the rows in [$offset, $offset + $limit). The aggregator knows each
   * backend's count (getResultsCount) and uses it to route a global page to the
   * right backend(s) and offsets, fetching only the needed slice — no full-set
   * reads. Each row follows the result shape in
   * fixtures/schema/runProgramSearch.schema.json.
   *
   * @param array $parameters
   *   GET parameters for the search.
   * @param int $offset
   *   Zero-based offset into this backend's result set.
   * @param int $limit
   *   Maximum number of rows to return.
   * @param int $log_id
   *   Id of the Search Log for tracking Register / Details actions.
   *
   * @return array
   *   List of result rows.
   */
  public function getResults(array $parameters, int $offset, int $limit, int $log_id): array;

  /**
   * Get list of all locations for filters.
   */
  public function getLocations();

  /**
   * Get list of all sort options.
   */
  public function getSortOptions();

  /**
   * Get the relevance sort option.
   */
  public function getRelevanceSort();

  /**
   * Get ages from configuration.
   */
  public function getAges();

  /**
   * Get the days of week.
   */
  public function getDaysOfWeek();

  /**
   * Get list of parts of day.
   */
  public function getPartsOfDay();

  /**
   * Get list of days with parts of day.
   */
  public function getDaysTimes();

  /**
   * Get list of start session month.
   */
  public function getStartMonths();

  /**
   * Get list of durations.
   */
  public function getDurations();

  /**
   * Get list of weeks.
   */
  public function getWeeks();

  /**
   * Get the activity categories tree.
   */
  public function getCategories();

  /**
   * Get the top-level activity categories (programs).
   */
  public function getCategoriesTopLevel();

  /**
   * Get the category selection type ('single' or 'multiple').
   */
  public function getCategoriesType();

  /**
   * Get the "Group collapse settings" from the AF settings page.
   */
  public function getFiltersSectionConfig();

  /**
   * Get full program information for a deferred (live) availability lookup.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function getProgramsMoreInfo($request);

  /**
   * Get backend-specific extras for the response 'externals' field (D10).
   *
   * An open key-value map for values that do not fit the shared response schema
   * (e.g. Daxko-only fields). Common consumers ignore it; bespoke consumers read
   * it. Defaults to an empty array.
   *
   * @param array $parameters
   *   GET parameters for the search.
   *
   * @return array
   *   Backend-specific key-value extras.
   */
  public function getExternals(array $parameters): array;

}
