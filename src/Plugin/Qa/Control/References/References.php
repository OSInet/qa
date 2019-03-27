<?php

namespace Drupal\qa\Plugin\Qa\Control\References;

use Drupal\qa\Pass;
use Drupal\qa\Plugin\Qa\Control\BaseControl;

/**
 * Find views containing PHP code
 */
class References extends BaseControl {

  /**
   * {@inheritdoc]
   */
  public function init() {
    $this->package_name = __NAMESPACE__;
    $this->title = t('References to missing nodes or users');
    $this->description = t('Missing nodes or references mean broken links and a bad user experience. These should usually be edited.');
  }

  function checkNodes() {
    $ret = array('status' => 1, 'result' => array());
    return $ret;
  }

  function checkUsers() {
    $ret = array('status' => 1, 'result' => array());
    return $ret;
  }

  static function getDependencies() {
    $ret = parent::getDependencies();
    $ret = array_merge($ret, array('content', 'nodereference', 'userreference'));
    return $ret;
  }

  function run(): Pass {
    $pass = parent::run();
    $pass->record($this->checkNodes());
    $pass->life->modify();
    $pass->record($this->checkUsers());
    $pass->life->end();
  }
}
