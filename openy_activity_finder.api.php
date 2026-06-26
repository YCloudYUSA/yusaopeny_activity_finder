<?php

/**
 * @file
 * Hooks specific to the openy_activity_finder module.
 */

use Drupal\node\NodeInterface;

/**
 * Alter the search results.
 */
function hook_activity_finder_program_search_results_alter(&$data) {

}

/**
 * Alter the process results.
 *
 * @param array $data
 *   The array of processed result item for program search.
 * @param \Drupal\node\NodeInterface $entity
 *   The node that has just been processed.
 *
 * @see Drupal\openy_activity_finder\OpenyActivityFinderSolrBackend
 */
function hook_activity_finder_program_process_results_alter(array &$data, NodeInterface $entity) {
  $data['description'] = t('Test session description');
}

/**
 * Alter more info request results.
 */
function hook_activity_finder_program_more_info_alter(&$data) {

}

/**
 * Alter location list.
 */
function hook_activity_finder_location_list_alter(array &$data) {

}

/**
 * Alter the register link URL for a program result item.
 *
 * Invoked when the 'bypass_register_redirect' setting is enabled on the
 * Activity Finder settings page. In that mode the raw external registration
 * URL is embedded directly in the search-result JSON instead of routing
 * through /af/register-redirect, so GA4's native cross-domain linker can
 * decorate the link client-side.
 *
 * The URL has already passed the same trusted-host validation enforced by
 * the /af/register-redirect route before this hook is invoked. Returning an
 * empty string from an implementation will suppress the link entirely.
 *
 * Implementations may append static query parameters to the URL (e.g. UTM
 * tags). Note that per-user / per-request data such as analytics cookies
 * should NOT be injected here because the /af/get-data response is cached
 * and shared across users.
 *
 * @param string $link
 *   The raw external registration URL, passed by reference.
 * @param array $context
 *   Associative array with the following keys:
 *   - url: (string) The original external URL from the registration field.
 *   - log_id: (string|int) The program-search log ID for this request.
 *   - entity: (\Drupal\Core\Entity\EntityInterface|null) The source entity,
 *     if available from the backend (null for non-Solr backends).
 */
function hook_activity_finder_register_link_alter(string &$link, array $context) {
  // Example: append a static campaign parameter.
  // $link .= (str_contains($link, '?') ? '&' : '?') . 'utm_source=ymca';
}
