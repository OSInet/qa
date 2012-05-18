<?php

namespace OSInet\DrupalQA\Views;

use OSInet\DrupalQA\BaseControl;

/**
 * Shared abstract ancestor class for Views controls, to hold common dependencies.
 */
abstract class Views extends BaseControl {

  public function __construct() {
    parent::__construct();
    $this->package_name = __NAMESPACE__;
  }

  static function getDependencies() {
    $ret = parent::getDependencies();
    $ret = array_merge($ret, array('views'));
    return $ret;
  }
}
