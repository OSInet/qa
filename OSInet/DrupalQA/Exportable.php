<?php

namespace OSInet\DrupalQA;

abstract class Exportable {

  /**
   * The directory containing the file containing this class.
   *
   * @var string
   */
  public $dir;

  /**
   * Machine name
   *
   * @var string
   */
  public $name;

  /**
   * The namespace for the class.
   *
   * @var string
   */
  public $namespace;

  /**
   * Description: translatable
   *
   * @var string
   */
  public $description;

  /**
   * Human readable name, used for titles. Translatable.
   *
   * @var string
   */
  public $title;

  /**
   * @param $base
   * @param $ancestor
   *
   * @return array
   */
  public static function getClasses($base, $ancestor) {
    $realBase = realpath($base);
    $rdi = new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS);
    $rii = new \RecursiveIteratorIterator($rdi);
    $ri = new \RegexIterator($rii, '/.+\.php$/', \RegexIterator::GET_MATCH);
    foreach ($ri as $k => $v) {
      include_once $k;
    }
    $packageNames = array_filter(get_declared_classes(), function ($name) use ($realBase, $ancestor) {
      $ret = is_subclass_of($name, $ancestor);
      if ($ret) {
        $rc = new \ReflectionClass($name);
        if (!$rc->isInstantiable()) {
          $ret = FALSE;
        }
        else {
          $dir = dirname($rc->getFileName());
          if (strpos($dir, $realBase) !== 0) {
            $ret = FALSE;
          }
        }
      }
      return $ret;
    });
    $packageNames = array_combine($packageNames, $packageNames);
    $ret = array_map(function ($name) {
      $instance = new $name();
      return $instance;
    }, $packageNames);
    return $ret;
  }

  /**
   * Singleton protected constructor
   */
  public function __construct() {
    $this->name = get_called_class();
    $rc = new \ReflectionClass($this->name);
    $this->dir = dirname($rc->getFileName());
    $this->namespace = $rc->getNamespaceName();
    $this->init();
  }

  /**
   * Initializer: must be implemented in concrete classes.
   *
   * Assignment to $this->package_name cannot be factored because it uses a
   * per-class magic constant.
   *
   * @return void
   */
  abstract public function init();
}
