<?php

namespace Drupal\openy_activity_finder_solr\Plugin\ActivityFinderBackend;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\openy_activity_finder\Attribute\ActivityFinderBackend;
use Drupal\openy_activity_finder_solr\OpenyActivityFinderSolrBackend;
use Drupal\openy_activity_finder\Plugin\ActivityFinderBackendPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Solr backend plugin.
 *
 * Owns the Solr search implementation (OpenyActivityFinderSolrBackend), which
 * is no longer a global service — the plugin instantiates it. Default backend;
 * no behaviour change versus the legacy service path (W0b DECISIONS D3, D5).
 */
#[ActivityFinderBackend(
  id: 'solr',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Solr'),
)]
class SolrBackend extends ActivityFinderBackendPluginBase {

  use StringTranslationTrait;

  /**
   * The Solr search implementation.
   *
   * @var \Drupal\openy_activity_finder\OpenyActivityFinderSolrBackend
   */
  protected $backend;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->backend = new OpenyActivityFinderSolrBackend(
      $container->get('config.factory'),
      $container->get('cache.default'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('datetime.time'),
      $container->get('logger.channel.default'),
      $container->get('module_handler')
    );
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultsCount(array $parameters): int {
    $parameters['af_count_only'] = TRUE;
    return (int) $this->backend->doSearchRequest($parameters)->getResultCount();
  }

  /**
   * {@inheritdoc}
   */
  public function getFacets(array $parameters): array {
    $parameters['af_count_only'] = TRUE;
    return $this->backend->getFacets($this->backend->doSearchRequest($parameters));
  }

  /**
   * {@inheritdoc}
   */
  public function getResults(array $parameters, int $offset, int $limit, int $log_id): array {
    $parameters['af_offset'] = $offset;
    $parameters['af_limit'] = $limit;
    return $this->backend->processResults($this->backend->doSearchRequest($parameters), $log_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getLocations() {
    return $this->backend->getLocations();
  }

  /**
   * {@inheritdoc}
   */
  public function getSortOptions() {
    return $this->backend->getSortOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function getRelevanceSort() {
    return $this->backend->getRelevanceSort();
  }

  /**
   * {@inheritdoc}
   */
  public function getAges() {
    return $this->backend->getAges();
  }

  /**
   * {@inheritdoc}
   */
  public function getDaysOfWeek() {
    return $this->backend->getDaysOfWeek();
  }

  /**
   * {@inheritdoc}
   */
  public function getPartsOfDay() {
    return $this->backend->getPartsOfDay();
  }

  /**
   * {@inheritdoc}
   */
  public function getDaysTimes() {
    return $this->backend->getDaysTimes();
  }

  /**
   * {@inheritdoc}
   */
  public function getStartMonths() {
    return $this->backend->getStartMonths();
  }

  /**
   * {@inheritdoc}
   */
  public function getDurations() {
    return $this->backend->getDurations();
  }

  /**
   * {@inheritdoc}
   */
  public function getWeeks() {
    return $this->backend->getWeeks();
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    return $this->backend->getCategories();
  }

  /**
   * {@inheritdoc}
   */
  public function getCategoriesTopLevel() {
    return $this->backend->getCategoriesTopLevel();
  }

  /**
   * {@inheritdoc}
   */
  public function getCategoriesType() {
    return $this->backend->getCategoriesType();
  }

  /**
   * {@inheritdoc}
   */
  public function getFiltersSectionConfig() {
    return $this->backend->getFiltersSectionConfig();
  }

  /**
   * {@inheritdoc}
   */
  public function getProgramsMoreInfo($request) {
    return $this->backend->getProgramsMoreInfo($request);
  }

}
