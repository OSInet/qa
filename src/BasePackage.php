<?php

declare(strict_types = 1);

namespace Drupal\qa;

/**
 * An instantiable Exportable.
 */
abstract class BasePackage extends Exportable {

  /**
   * The plugin instances.
   *
   * @var array
   */
  protected static $instances = [];

  /**
   * Get a new or existing plugin instance.
   *
   * @return mixed
   *   The plugin.
   */
  public static function getInstance() {
    $name = get_called_class();
    if (!isset(self::$instances[$name])) {
      self::$instances[$name] = new $name();;
    }
    return self::$instances[$name];
  }

}
