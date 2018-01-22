<?php

/**
 * @file
 * OSInet QA Plugin for References module.
 *
 * @copyright Copyright (C) 2011-2018 Frederic G. MARAND for Ouest Systèmes Informatiques (OSInet)
 *
 * @since DRUPAL-6
 *
 * @license Licensed under the disjunction of the CeCILL, version 2 and General Public License version 2 and later
 */

namespace Drupal\qa\Plugin\Qa\Control\References;

use Drupal\qa\Plugin\Qa\Control\BasePackage;

class Package extends BasePackage {
  public function init() {
    $this->title = t('Node/User References');
    $this->description = t('Look for references to missing nodes or users');
  }
}
