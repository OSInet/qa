<?php

namespace Drupal\qa\Plugin\Qa\Control\Cache;

use Drupal\qa\Plugin\Qa\Control\BasePackage;

/**
 * @file
 * OSInet QA Plugin for cache checks.
 *
 * @copyright Copyright (C) 2014-2018 Frederic G. MARAND for Ouest Systèmes Informatiques (OSInet)
 *
 * @since DRUPAL-7
 *
 * @license Licensed under the disjunction of the CeCILL, version 2 and General Public License version 2 and later
 */

class Package extends BasePackage {
  public function init() {
    $this->title = t('Cache');
    $this->description = t('Look for suspicious content in database cache. Do NOT use with other cache types.');
  }
}
