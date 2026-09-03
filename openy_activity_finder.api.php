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
 * Alter a single processed result row.
 *
 * Invoked by a backend while building the result table, once per row.
 *
 * @param array $data
 *   The processed result item for the program search.
 * @param \Drupal\node\NodeInterface $entity
 *   The node that has just been processed.
 *
 * @see \Drupal\openy_activity_finder\OpenyActivityFinderBackendInterface
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
