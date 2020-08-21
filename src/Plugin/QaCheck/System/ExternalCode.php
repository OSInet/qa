<?php

declare(strict_types = 1);

namespace Drupal\qa\Plugin\QaCheck\System;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\qa\Data;
use Drupal\qa\Pass;
use Drupal\qa\Plugin\QaCheckBase;
use Drupal\qa\Plugin\QaCheckInterface;
use Drupal\qa\Plugin\QaCheckManager;
use Drupal\qa\Result;
use ReflectionFunction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ExternalCode identifies code loaded from outside the project root and vendor.
 *
 * @QaCheck(
 *   id = "system.external",
 *   label = @Translation("System: external code"),
 *   details = @Translation("External code may be the result of an exploit."),
 *   usesBatch = false,
 *   steps = 1,
 * )
 */
class ExternalCode extends QaCheckBase implements QaCheckInterface {

  const NAME = 'system.external';

  /**
   * The element_list.module service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $elm;

  /**
   * The extension.list.theme service.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $elt;

  /**
   * The list of internal functions.
   *
   * @var array
   */
  protected $internalFunctions;

  /**
   * The plugin_manager.qa_check service.
   *
   * @var \Drupal\qa\Plugin\QaCheckManager
   */
  protected $qam;

  /**
   * ExternalCode constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $id
   *   The plugin ID.
   * @param array $definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleExtensionList $elm
   *   The extension.list.module service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $elt
   *   The extension.list.theme service.
   * @param \Drupal\qa\Plugin\QaCheckManager $qam
   *   The plugin_manager.qa_check service.
   */
  public function __construct(
    array $configuration,
    string $id,
    array $definition,
    ModuleExtensionList $elm,
    ThemeExtensionList $elt,
    QaCheckManager $qam
  ) {
    parent::__construct($configuration, $id, $definition);
    $this->elm = $elm;
    $this->elt = $elt;
    $this->qam = $qam;

    $this->qam->initInternalFunctions();
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
    $elm = $container->get('extension.list.module');
    assert($elm instanceof ModuleExtensionList);
    $elt = $container->get('extension.list.theme');
    assert($elt instanceof ThemeExtensionList);
    $qam = $container->get(Data::MANAGER);
    assert($qam instanceof QaCheckManager);
    return new static($configuration, $id, $definition, $elm, $elt, $qam);
  }

  /**
   * Identify code loaded from outside the web root and vendor directory.
   *
   * This is not necessarily an issue, but warrants a manual verification.
   *
   * @return \Drupal\qa\Result
   *   The check result.
   */
  protected function checkExternal(): Result {
    $external = [];
    $funcs = array_flip(get_defined_functions()['user']);
    foreach ($funcs as $func => $_) {
      if (isset($this->qam->internalFunctions[$func])) {
        continue;
      }
      try {
        $rf = new ReflectionFunction($func);
      }
      catch (\ReflectionException $e) {
        // XXX probably cannot happen since the function is loaded.
        $external[$func] = ('function not found');
        continue;
      }
      $file = realpath($rf->getFileName());
      // TODO improve resilience, probably at the cost of computing time.
      if (strpos($file, $this->qam->root) === FALSE) {
        $external[$func] = $file;
      }
    }
    $res = new Result('external', empty($external), $external);
    return $res;

  }

  /**
   * {@inheritdoc}
   */
  public function run(): Pass {
    $pass = parent::run();
    $pass->record($this->checkExternal());
    $pass->life->end();
    return $pass;
  }

}
