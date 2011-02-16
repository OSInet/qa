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

class QaPackageViews extends QaPackage {
  function __construct() {
    parent::__construct();
    $this->title = t('Views quality controls');
    $this->description = t('Look for overridden views and views containing embedded PHP');
  }
}

/**
 * Find views containing PHP code
 */
class QaControlViewsPhp extends QaControl {

  /**
   * The package to which the control belongs
   *
   * @var string
   */
  public $package_name;

  protected function __construct() {
    parent::__construct();
    $this->package_name = 'QaPackageViews';
    $this->title = t('Check for PHP code within site views');
    $this->description = t('Check within views definitions to make sure they do not contain embedded PHP, which is both a security risk and a performance issue.');
    parent::__construct();
  }

  function checkViewPhp($view) {
    $ret = array('status' => 1, 'result' => array());
    return $ret;
  }

  function run() {
    $pass = parent::run();
    $views = views_get_all_views(TRUE);
    foreach ($views as $view) {
      $pass->record($this->checkViewPhp($view));
    }
    $pass->life->end();
  }
}
