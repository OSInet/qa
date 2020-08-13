<?php

namespace Drupal\qa\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\qa\Pass;

/**
 * Base class for QA Check plugins.
 */
abstract class QaCheckBase extends PluginBase implements QaCheckInterface, ContainerFactoryPluginInterface {

  // Add common methods and abstract methods for your plugin type here.
  public function run(): Pass {
    return new Pass($this);
  }

}
