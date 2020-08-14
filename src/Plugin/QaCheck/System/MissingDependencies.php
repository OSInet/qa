<?php

declare(strict_types=1);

namespace Drupal\qa\Plugin\QaCheck\System;

use Drupal\Core\DrupalKernel;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\qa\Pass;
use Drupal\qa\Plugin\QaCheckBase;
use Drupal\qa\Plugin\QaCheckInterface;
use Drupal\qa\Result;
use ReflectionFunction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * MissingDependencies identifies undeclared dependencies in module code.
 *
 * @QaCheck(
 *   id = "system.missing_dependencies",
 *   label=@Translation("System: missing dependencies"),
 *   details=@Translation("Custom code is prone to using other custom code
 *   without declaring a dependency on it, making the code brittle.")
 * )
 */
class MissingDependencies extends QaCheckBase implements QaCheckInterface {

  /**
   * The element_list.module service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $elm;

  /**
   * The extension_list.theme service.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $elt;

  /**
   * The user-space functions visible from code.
   *
   * @var array
   */
  protected $functions;

  /**
   * The kernel base root, usually also available as base_path() and $GLOBALS['app_root'].
   *
   * @var string
   */
  protected $root;

  /**
   * SystemUnusedExtensions constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $id
   *   The plugin ID.
   * @param array $definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleExtensionList $elm
   *   The extension_list.module service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $elt
   *   The extension_list.theme service.
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The kernel service.
   */
  public function __construct(
    array $configuration,
    string $id,
    array $definition,
    ModuleExtensionList $elm,
    ThemeExtensionList $elt,
    DrupalKernelInterface $kernel
  ) {
    parent::__construct($configuration, $id, $definition);
    $this->elm = $elm;
    $this->elt = $elt;
    $this->root = realpath($kernel->getAppRoot());

    $functions = get_defined_functions(TRUE)['user'];
    sort($functions);
    $this->functions = $functions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $id,
    $definition
  ) {
    $kernel = $container->get('kernel');
    $elm = $container->get('extension.list.module');
    $elt = $container->get('extension.list.theme');
    return new static($configuration, $id, $definition, $elm, $elt, $kernel);
  }

  /**
   * Identify code loaded from outside the web root and vendor directory.
   *
   * This is not necessarily an issue, but warrants a manual verification.
   *
   * @return \Drupal\qa\Result
   *
   * @throws \ReflectionException
   */
  protected function checkExternal(): Result {
    // TODO support alternate layouts.
    $vendor = realpath($this->root . "/../vendor");
    $external = [];
    foreach ($this->functions as $func) {
      // TODO handle exceptions.
      $rf = new ReflectionFunction($func);
      $file = realpath($rf->getFileName());
      // TODO improve resilience, probably at the cost of computing time.
      if (strpos($file, $this->root) === FALSE && strpos($file, $vendor) === FALSE) {
        $external[$func] = $file;
        unset($this->functions[$func]);
      }
      $data = [
        'root' => $this->root,
        'vendor' => $vendor,
        'external' => $external,
      ];
    }
    $res = new Result('external', empty($external), $data);
    return $res;

  }

  protected function stripFramework(): void {
    // TODO support alternate layouts.
    $core = [
      'core/includes',
      'core/lib',
      '../vendor',
    ];
    foreach ($core as &$dir) {
      $dir = realpath($this->root . "/$dir");
    }
    foreach ($this->functions as $func) {
      // TODO handle exceptions.
      $rf = new ReflectionFunction($func);

      $file = realpath($rf->getFileName());
      foreach ($core as $dir) {
        if (strpos($file, $dir) === 0) {
          $key = array_search($func, $this->functions);
          if ($key >= 0) {
            unset($this->functions[$key]);
            continue 2;
          }
        }
      }
    }
  }

  protected function checkModules(): Result {
    $modulesByPaths = [];
    $internalFuncs = count($this->functions);
    $this->stripFramework();
    $coreFuncs = $internalFuncs - count($this->functions);

    foreach ($this->elm->getAllAvailableInfo() as $ext => $info) {
      $modulesByPaths[$this->elm->getPath($ext)] = $ext;
    }

    $rf = new ReflectionFunction('base_path');
    $file = $rf->getFileName();
    foreach ($this->functions as $func) {
      $rf = new ReflectionFunction($func);
      $file = realpath($rf->getFileName());
      $data = [
      ];
    }

    $data = [
      'internal_functions' => $internalFuncs,
      'core_functions' => $coreFuncs,
    ];
    $res = new Result('modules', FALSE, $data);
    return $res;
  }

  public function run(): Pass {
    $pass = parent::run();
    // $pass->record($this->checkExternal());
    $pass->life->modify();
    $pass->record($this->checkModules());
    $pass->life->end();
    return $pass;
  }
}
