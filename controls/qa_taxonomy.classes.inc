<?php
// $Id$
/**
 * @file
 * OSInet QA Plugin for Taxonomy 6.x
 *
 * @copyright Copyright (C) 2006-2011 Frederic G. MARAND for Ouest Systèmes Informatiques (OSInet)
 *
 * @since DRUPAL-4.7
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

class QaPackageTaxonomy extends QaPackage {
  function __construct() {
    parent::__construct();
    $this->title = t('Taxonomy quality controls');
    $this->description = t('Look for orphan freetags, and inconsistent node tagging');
  }
}

/**
 * Find views containing PHP code
 */
class QaControlTaxonomyFreetagging extends QaControl {

  protected function __construct() {
    parent::__construct();
    $this->package_name = 'QaPackageTaxonomy';
    $this->title = t('Unused freetagging terms');
    $this->description = t('Unused freetagging terms mean useless volume. Removing them helps makin term autocompletes more relevant.');
    parent::__construct();
  }

  static function getDependencies() {
    $ret = parent::getDependencies();
    $ret = array_merge($ret, array('taxonomy'));
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

class QaControlTaxonomyOrphans extends QaControl {

  protected function __construct() {
    parent::__construct();
    $this->package_name = 'QaPackageTaxonomy';
    $this->title = t('Inconsistent node tagging');
    $this->description = t('Check for term_node entries pointing to a missing node or term. These should never happen, and should be removed when they do.');
    parent::__construct();
  }

  static function getDependencies() {
    $ret = parent::getDependencies();
    $ret = array_merge($ret, array('taxonomy'));
    return $ret;
  }

  function run() {}
}
