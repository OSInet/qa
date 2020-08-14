<?php

namespace Drupal\qa;

abstract class BaseControl extends Exportable {

  /**
   * The package to which the control belongs
   *
   * @var string
   */
  public $package_name;

  /**
   * An options hash
   *
   * @var array
   */
  public $options;

  /**
   * The hash of passes for that control.
   *
   * @var array
   */
  public $passes;

  /**
   * Singleton-per-child-class data holder.
   *
   * @var array
   */
  protected static $instances = [];

  /**
   * Per-package list of instances
   *
   * @var array
   */
  protected static $packages = [];

  public function __construct() {
    parent::__construct();
    $this->package_name = $this->namespace;
  }

  /**
   * Return an array of module dependencies.
   *
   * @return array
   */
  public static function getDependencies() {
    return [];
  }

  /**
   * @return \Drupal\qa\BaseControl
   */
  public static function getInstance() {
    $name = get_called_class();
    if (!isset(self::$instances[$name])) {
      $instance = new $name();
      self::$instances[$name] = $instance;
      if (!isset(self::$packages[$instance->package_name])) {
        $package = new $instance->package_name();
        self::$packages[get_class($package)] = [
          'package' => $package,
          'controls' => [],
        ];
      }
      self::$packages[$instance->package_name]['controls'][$instance->name] = $instance;
    }
    $ret = self::$instances[$name];
    return $ret;
  }

  /**
   * Returns per-package controls.
   *
   * @param string $package_name
   *   If given, only return the list of controls belonging to that package
   *
   * @return array
   *   - if $package_name is given, an array of control instances
   *   - else a package-name-indexed hash of arrays of control instances
   */
  public static function getControls($package_name = NULL) {
    if (isset($package_name)) {
      $ret = isset(self::$packages[$package_name])
        ? self::$packages[$package_name]
        : NULL;
    }
    else {
      $ret = self::$packages;
    }
    return $ret;
  }

  /**
   * Run the control.
   *
   * @return \Drupal\qa\Pass
   *   - 0: failure
   *   - 1: success
   */
  public function run() {
    $key = uniqid(variable_get('site_key', NULL));
    $pass = new Pass($this);
    $this->passes[$key] = $pass;
    return $pass;
  }

}
