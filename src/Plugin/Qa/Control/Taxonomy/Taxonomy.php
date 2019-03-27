<?php

namespace Drupal\qa\Plugin\Qa\Control\Taxonomy;

use Drupal\qa\Plugin\Qa\Control\BaseControl;

abstract class Taxonomy extends BaseControl {

  /**
   * {@inheritdoc]
   */
  public function __construct() {
    parent::__construct();
    $this->package_name = __NAMESPACE__;
  }

  static function getDependencies() {
    $ret = parent::getDependencies();
    $ret = array_merge($ret, ['taxonomy']);
    return $ret;
  }
}
