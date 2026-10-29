<?php

namespace Drupal\lb_activity_finder\EventSubscriber;

use Drupal\y_lb\WSStyleGroupAlterAbstract;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Registers Activity Finder Layout style group for lb_activity_finder block.
 */
class WSStyleGroupAlter extends WSStyleGroupAlterAbstract implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  protected function getAllowedStyleGroups() {
    return [
      'inline_block:lb_activity_finder' => [
        'af4_layout',
      ],
    ];
  }

}
