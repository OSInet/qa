<?php

declare(strict_types = 1);

namespace Drupal\qa\Cache;

use Drupal\qa\BasePackage;

/**
 * OSInet QA Plugin for cache check.
 */
class Package extends BasePackage {

  /**
   * {@inheritdoc}
   */
  public function init() {
    $this->title = $this->t('Cache');
    $this->description = $this->t('Look for suspicious content in database cache. Do NOT use with other cache types.');
  }

}
