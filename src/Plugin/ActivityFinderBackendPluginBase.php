<?php

namespace Drupal\openy_activity_finder\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\openy_activity_finder\OpenyActivityFinderBackendInterface;

/**
 * Base class for Activity Finder backend plugins.
 *
 * The backend contract (OpenyActivityFinderBackendInterface) is plain PHP and
 * CMS-agnostic; only discovery is Drupal's. Concrete plugins implement the
 * interface — Solr (extracted, no behaviour change), Mock (fixtures), DB.
 */
abstract class ActivityFinderBackendPluginBase extends PluginBase implements OpenyActivityFinderBackendInterface, ContainerFactoryPluginInterface {

  /**
   * Returns the human-readable label of the backend plugin.
   *
   * @return string
   *   The plugin label.
   */
  public function label() {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getExternals(array $parameters): array {
    return [];
  }

}
