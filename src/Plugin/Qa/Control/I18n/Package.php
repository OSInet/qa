<?php
/**
 * @file
 * OSInet QA Plugin for i18n (internationalization) module
 *
 * @copyright Copyright (C) 2011 Frederic G. MARAND for Ouest Systèmes Informatiques (OSInet)
 *
 * @since DRUPAL-6
 *
 * @license Licensed under the disjunction of the CeCILL, version 2 and General Public License version 2 and later
 */

namespace Drupal\qa\Plugin\Qa\Control\I18n;

use Drupal\qa\Plugin\Qa\Control\BasePackage;

class Package extends BasePackage {
  public function init() {
    $this->title = t('i18n');
    $this->description = t('Inconsistent variables translation');
  }
}
