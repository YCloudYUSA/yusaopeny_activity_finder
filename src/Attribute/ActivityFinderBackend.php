<?php

namespace Drupal\openy_activity_finder\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an ActivityFinderBackend attribute.
 *
 * Discovered from src/Plugin/ActivityFinderBackend/. Native on Drupal 10.2+;
 * the manager keeps an annotation fallback for older cores (W0b DECISIONS D9).
 *
 * @ingroup openy_activity_finder
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ActivityFinderBackend extends Plugin {

  /**
   * Constructs an ActivityFinderBackend attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   The human-readable name shown in the block backend selector.
   * @param string|null $deriver
   *   The deriver class, if any.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
