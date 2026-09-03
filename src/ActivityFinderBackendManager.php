<?php

namespace Drupal\openy_activity_finder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\openy_activity_finder\Annotation\ActivityFinderBackend as ActivityFinderBackendAnnotation;
use Drupal\openy_activity_finder\Attribute\ActivityFinderBackend as ActivityFinderBackendAttribute;

/**
 * Manages discovery and instantiation of Activity Finder backend plugins.
 *
 * Backends are discovered from src/Plugin/ActivityFinderBackend/ via the
 * #[ActivityFinderBackend] attribute, with an annotation fallback for cores
 * without attribute discovery (W0b DECISIONS D1, D9).
 */
class ActivityFinderBackendManager extends DefaultPluginManager {

  /**
   * Constructs an ActivityFinderBackendManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ActivityFinderBackend',
      $namespaces,
      $module_handler,
      OpenyActivityFinderBackendInterface::class,
      ActivityFinderBackendAttribute::class,
      ActivityFinderBackendAnnotation::class,
    );

    $this->alterInfo('activity_finder_backend_info');
    $this->setCacheBackend($cache_backend, 'activity_finder_backend_plugins');
  }

}
