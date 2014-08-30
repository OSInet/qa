<?php

namespace OSInet\DrupalQA\Cache;

use OSInet\DrupalQA\BasePackage;

/**
 * @file
 * OSInet QA Plugin for cache checks.
 *
 * @copyright Copyright (C) 2014 Frederic G. MARAND for Ouest Systèmes Informatiques (OSInet)
 *
 * @since DRUPAL-7
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

class Package extends BasePackage {
  public function init() {
    $this->title = t('Cache');
    $this->description = t('Look for suspicious content in database cache. Do NOT use with other cache types.');
  }
}