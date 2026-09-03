<?php

namespace Drupal\openy_activity_finder;

/**
 * Resolves a block's backend and assembles the runProgramSearch response.
 *
 * A block runs a **single** backend (W0b DECISIONS — "Single backend"). This is
 * the one place the response shape (`count`, `facets`, `pager`, `pager_info`,
 * `table`, `groupedLocations`, `sort`, `externals`) is assembled, from that
 * backend's `getResultsCount` / `getFacets` / `getResults`. Running several
 * backends at once and merging them is an experimental Follow-up — not here.
 */
class ActivityFinderBackendAggregator {

  /**
   * The backend plugin manager.
   *
   * @var \Drupal\openy_activity_finder\ActivityFinderBackendManager
   */
  protected ActivityFinderBackendManager $backendManager;

  /**
   * Constructs the aggregator.
   *
   * @param \Drupal\openy_activity_finder\ActivityFinderBackendManager $backend_manager
   *   The backend plugin manager.
   */
  public function __construct(ActivityFinderBackendManager $backend_manager) {
    $this->backendManager = $backend_manager;
  }

  /**
   * Resolves the backend for the given plugin id(s).
   *
   * Accepts a list for forward-compatibility but uses the first registered id —
   * a block runs one backend.
   *
   * @param string[] $plugin_ids
   *   Backend plugin id(s).
   *
   * @return \Drupal\openy_activity_finder\OpenyActivityFinderBackendInterface|null
   *   The backend, or NULL when none resolve.
   */
  public function getPrimaryBackend(array $plugin_ids): ?OpenyActivityFinderBackendInterface {
    foreach ($plugin_ids as $id) {
      if ($this->backendManager->hasDefinition($id)) {
        return $this->backendManager->createInstance($id);
      }
    }
    return NULL;
  }

  /**
   * Runs a program search on the block's backend and assembles the response.
   *
   * @param string[] $plugin_ids
   *   Backend plugin id(s); the first registered one is used.
   * @param array $parameters
   *   GET parameters for the search.
   * @param int $log_id
   *   Search log id.
   *
   * @return array
   *   The response, or ['error' => ...] when no backend is available.
   */
  public function search(array $plugin_ids, array $parameters, int $log_id): array {
    $backend = $this->getPrimaryBackend($plugin_ids);
    if (!$backend) {
      return ['error' => (string) t('Activity Finder is not available now.')];
    }

    $id = $backend->getPluginId();
    $per_page = OpenyActivityFinderBackendInterface::RESULTS_PER_PAGE;
    $page = (int) ($parameters['page'] ?? 0);
    $offset = $page > 0 ? ($page - 1) * $per_page : 0;
    $count = $backend->getResultsCount($parameters);

    $rows = [];
    foreach ($backend->getResults($parameters, $offset, $per_page, $log_id) as $row) {
      // Stamp provenance so a saved item can be routed back to its backend.
      $row['backend'] = $id;
      $rows[] = $row;
    }

    $facets = $backend->getFacets($parameters);
    $externals = $backend->getExternals($parameters);
    return [
      'count' => $count,
      'facets' => $facets,
      'pager' => ($page && $count > $per_page) ? $page : 0,
      'pager_info' => $this->pagerInfo($count, $per_page),
      'table' => $rows,
      'groupedLocations' => $this->groupedLocations($backend, $facets),
      'sort' => $parameters['sort'] ?? 'title__ASC',
      // Cast so an empty map serialises as {} (schema: object).
      'externals' => (object) ($externals ? [$id => $externals] : []),
    ];
  }

  /**
   * Builds the location filter groups enriched with facet counts.
   */
  protected function groupedLocations(OpenyActivityFinderBackendInterface $backend, array $facets): array {
    $locations = $backend->getLocations();
    foreach ($locations as $key => $group) {
      $locations[$key]['count'] = 0;
      foreach ($group['value'] ?? [] as $location) {
        foreach ($facets['locations'] ?? [] as $facet) {
          if (isset($facet['id'], $location['value']) && $facet['id'] == $location['value']) {
            $locations[$key]['count'] += $facet['count'];
          }
        }
      }
    }
    return $locations;
  }

  /**
   * Builds the pager_info structure for a total result count.
   */
  protected function pagerInfo(int $count, int $per_page): array {
    $total_pages = (int) ceil($count / $per_page);
    $pages = [];
    for ($i = 1; $i <= $total_pages; $i++) {
      $pages[$i] = $i;
    }
    return ['total_pages' => $total_pages, 'pages' => $pages];
  }

}
