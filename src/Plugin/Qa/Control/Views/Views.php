<?php

namespace Drupal\qa\Plugin\Qa\Control\Views;

use Drupal\qa\Plugin\Qa\Control\BaseControl;

/**
 * Shared abstract ancestor class for Views controls, to hold common dependencies.
 */
abstract class Views extends BaseControl {

  static function getDependencies() {
    $ret = parent::getDependencies();
    $ret = array_merge($ret, ['views']);
    return $ret;
  }
}
