<?php

namespace Drupal\qa\Plugin\Qa\Control;

use Drupal\qa\Exportable;

/**
 * An instantiable Exportable.
 */
abstract class BasePackage extends Exportable {

  protected static $instances = array();

  public static function getInstance() {
    $name = get_called_class();
    if (!isset(self::$instances[$name])) {
      self::$instances[$name] = new $name();;
    }
    return self::$instances[$name];
  }

}

