<?php
// $Id$
/**
 * @file
 * OSInet QA Plugin for Views 6.3
 *
 * @copyright Copyright (C) 2011 Frederic G. MARAND for Ouest Systèmes Informatiques (OSInet)
 *
 * @since DRUPAL-6
 *
 * @license Licensed under the disjunction of the CeCILL, version 2 and General Public License version 2 and later
 *
 * License note: QA is distributed by OSInet to its customers under the
 * CeCILL 2.0 license. OSInet support services only apply to the module
 * when distributed by OSInet, not by any third-party further down the
 * distribution chain.
 *
 * If you obtained QA from drupal.org, that site received it under the
 * GPLv2 license and can therefore distribute it under the GPLv2, and
 * so can you and just anyone down the chain as long as the GPLv2 terms
 * are abided by, the module distributor in that case being the
 * drupal.org organization or the downstream distributor, not OSInet.
 */

class QaPackageReferences extends QaPackage {
  function __construct() {
    parent::__construct();
    $this->title = t('Node/User References');
    $this->description = t('Look for references to missing nodes or users');
  }
}

/**
 * Find views containing PHP code
 */
class QaControlReferences extends QaControl {

  protected function __construct() {
    parent::__construct();
    $this->package_name = 'QaPackageReferences';
    $this->title = t('References to missing nodes or users');
    $this->description = t('Missing nodes or references mean broken links and a bad user experience. These should usually be edited.');
    parent::__construct();
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

  function run() {
    $pass = parent::run();
    $pass->record($this->checkNodes());
    $pass->life->modify();
    $pass->record($this->checkUsers());
    $pass->life->end();
  }
}
