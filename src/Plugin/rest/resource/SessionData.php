<?php

namespace Drupal\openy_activity_finder\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\openy_activity_finder\ActivityFinderBackendAggregator;
use Drupal\openy_activity_finder\ActivityFinderBackendManager;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides Session Data resource.
 *
 * Refreshes saved items: given item ids and the block's backend(s), returns the
 * current rows. Backend-agnostic — items are fetched via getResults() with an id
 * filter through the aggregator, so each of the block's backends returns only
 * the items it owns (rows carry a 'backend' provenance field).
 *
 * @RestResource(
 *   id = "openy_activity_finder_session_data",
 *   label = @Translation("Session Data"),
 *   uri_paths = {
 *     "canonical" = "/af/api/v1/session-data"
 *   }
 * )
 */
class SessionData extends ResourceBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The backend aggregator.
   *
   * @var \Drupal\openy_activity_finder\ActivityFinderBackendAggregator
   */
  protected $aggregator;

  /**
   * The backend plugin manager.
   *
   * @var \Drupal\openy_activity_finder\ActivityFinderBackendManager
   */
  protected $backendManager;

  /**
   * Constructs a Session Data resource object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    RequestStack $request_stack,
    ActivityFinderBackendAggregator $aggregator,
    ActivityFinderBackendManager $backend_manager
  ) {
    parent::__construct($configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );
    $this->requestStack = $request_stack;
    $this->aggregator = $aggregator;
    $this->backendManager = $backend_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('openy_activity_finder'),
      $container->get('request_stack'),
      $container->get('openy_activity_finder.backend_aggregator'),
      $container->get('plugin.manager.activity_finder_backend')
    );
  }

  /**
   * Handles GET request.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Response.
   */
  public function get() {
    $request = $this->requestStack->getCurrentRequest();
    $disable_cache = new CacheableMetadata();
    $disable_cache->setCacheMaxAge(0)->addCacheContexts(['url.query_args', 'url.path']);

    $session_ids = $request->query->get('session_ids');
    if (empty($session_ids)) {
      $response = new ResourceResponse(['error' => 'Required parameter session_ids is missing.'], 400);
      $response->addCacheableDependency($disable_cache);
      return $response;
    }

    $registered = array_keys($this->backendManager->getDefinitions());
    $backend_ids = array_values(array_intersect(array_filter((array) $request->query->all('backend')), $registered));
    $data = $this->aggregator->search($backend_ids, ['ids' => $session_ids], 0);

    $response = new ResourceResponse(['sessions' => $data['table'] ?? []]);
    $response->addCacheableDependency($disable_cache);
    return $response;
  }

}
