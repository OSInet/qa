<?php

declare(strict_types = 1);

namespace Drupal\qa\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\qa\Pass;

/**
 * Defines an interface for QA Check plugins.
 */
interface QaCheckInterface extends PluginInspectionInterface {

  // The default result set: empty and successful.
  const DEFAULT_RESULT = ['status' => 0];

  /**
   * Run the check on the current site.
   *
   * @return \Drupal\qa\Pass
   *   The results wrapped in Pass information.
   */
  public function run(): Pass;

}
