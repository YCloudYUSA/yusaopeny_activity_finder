<?php

namespace Drupal\openy_activity_finder\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an ActivityFinderBackend annotation object.
 *
 * Annotation fallback for cores without attribute discovery (< Drupal 10.2).
 * On Drupal 10.2+ the PHP attribute is used instead (W0b DECISIONS D9).
 *
 * @Annotation
 *
 * @ingroup openy_activity_finder
 */
class ActivityFinderBackend extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name shown in the block backend selector.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
