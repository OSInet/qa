<?php

namespace OSInet\DrupalQA\Taxonomy;

use OSInet\DrupalQA\BaseControl;

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
    $ret = array_merge($ret, array('taxonomy'));
    return $ret;
  }
}
