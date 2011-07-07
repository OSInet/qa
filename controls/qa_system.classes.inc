<?php
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

class QaPackageSystem extends QaPackage {
  function __construct() {
    parent::__construct();
    $this->title = t('System');
    // $this->description = t('Look for unused code on disk');
  }
}

/**
 * Find views containing PHP code
 */
class QaControlSystem extends QaControl {

  protected function __construct() {
    parent::__construct();
    $this->package_name = 'QaPackageSystem';
    $this->title = t('Unused non-core packages');
    $this->description = t('Unused modules and themes present on disk can represent a useless cost on most dimensions. Packages entirely unused should usually be removed. This does not necessarily hold in a multi-site filesystem layout.');
    parent::__construct();
  }

  /**
   * Identify packages entirely consisting of not-enabled modules.
   */
  function checkModules() {
    $ret = array('status' => 1, 'result' => array());
    return $ret;
  }

  /**
   * Identify disabled themes not used as base themes for an enabled theme.
   */
  function checkThemes() {
    $ret = array('status' => 1, 'result' => array());
    return $ret;
  }

  static function getDependencies() {
    $ret = parent::getDependencies();
    return $ret;
  }

  function run() {
    $pass = parent::run();
    $pass->record($this->checkModules());
    $pass->life->modify();
    $pass->record($this->checkThemes());
    $pass->life->end();
  }
}
