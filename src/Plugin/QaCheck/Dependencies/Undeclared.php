<?php

declare(strict_types=1);

namespace Drupal\qa\Plugin\QaCheck\Dependencies;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\qa\Data;
use Drupal\qa\Pass;
use Drupal\qa\Plugin\QaCheckBase;
use Drupal\qa\Plugin\QaCheckInterface;
use Drupal\qa\Plugin\QaCheckManager;
use Drupal\qa\Result;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Undeclared checks undeclared module dependencies based on function calls.
 *
 * It only covers:
 * - function calls (not method calls),
 * - in .module files,
 * - to functions located in other module files.
 *
 * Later versions could go further.
 *
 * @QaCheck(
 *   id = "dependencies.undeclared",
 *   label = @Translation("Undeclared dependencies"),
 *   details = @Translation("This check finds modules doing cross-module function calls to other modules not declared as dependencies."),
 *   usesBatch = false,
 *   steps = 1,
 * )
 */
class Undeclared extends QaCheckBase implements QaCheckInterface {

  const NAME = 'dependencies.undeclared';

  /**
   * The extension.list.modules service.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $elm;

  /**
   * A PHP-Parser parser.
   *
   * @var \PhpParser\Parser
   */
  protected $parser;

  /**
   * The plugin_manager.qa_check service.
   *
   * @var \Drupal\qa\Plugin\QaCheckManager
   */
  protected $qam;

  /**
   * Undeclared constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $id
   *   The plugin ID.
   * @param array $definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleExtensionList $elm
   *   The extension.list.module service.
   * @param \Drupal\qa\Plugin\QaCheckManager $qam
   *   The plugin_manager.qa_check service.
   */
  public function __construct(
    array $configuration,
    string $id,
    array $definition,
    ModuleExtensionList $elm,
    QaCheckManager $qam
  ) {
    parent::__construct($configuration, $id, $definition);
    $this->elm = $elm;
    $this->qam = $qam;

    $this->qam->initInternalFunctions();

    // For the sake of evolution, ignore PHP5-only code.
    $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
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
    $qam = $container->get(Data::MANAGER);
    assert($qam instanceof QaCheckManager);
    return new static($configuration, $id, $definition, $elm, $qam);
  }

  /**
   * Build the list of module names regardless of module installation status.
   *
   * @return array
   *   A map of installed status by module name.
   */
  protected function getModulesToScan(): array {
    $list = $this->elm->getList() ?? [];
    $list = array_filter($list, function ($ext) {
      $isCore = substr($ext->getPath(), 0, 5) === 'core/';
      return !$isCore;
    });
    $list = array_flip(array_keys($list));
    $installed = $this->elm->getAllInstalledInfo();
    foreach ($list as $module => &$on) {
      $on = isset($installed[$module]);
    }
    return $list;
  }

  /**
   * Build the list of function calls in a single source file.
   *
   * @param string $path
   *   The absolute path to the module file.
   *
   * @return array
   *   An array of function names.
   */
  protected function functionCalls(string $path): array {
    $code = file_get_contents($path);
    try {
      $stmts = $this->parser->parse($code);
      assert(is_array($stmts));
    }
    catch (Error $e) {
      echo "Skipping ${path} for parse error: " . $e->getMessage();
      return [];
    }

    $traverser = new NodeTraverser();
    $traverser->addVisitor($visitor = new FunctionCallVisitor());
    $traverser->traverse($stmts);
    $pad = array_unique($visitor->pad);
    // Ignore builtin/extension functions.
    $pad = array_filter($pad, function ($name) {
      return empty($this->qam->internalFunctions[$name]);
    });
    return $pad;
  }

  /**
   * Build the list of actual module dependencies in all modules based on calls.
   *
   * @param string $name
   *   The name of the module for which to list dependencies.
   * @param array $calls
   *   The function calls performed by that module.
   *
   * @return array
   *   A map of modules names by module name.
   */
  protected function moduleActualDependencies(string $name, array $calls): array {
    $modules = [];
    foreach ($calls as $called) {
      try {
        $rf = new \ReflectionFunction($called);
      }
      catch (\ReflectionException $e) {
        $modules[] = "(${called})";
        continue;
      }

      // Drupal name-based magic.
      $module = basename($rf->getFileName(), '.module');
      if ($module !== $name) {
        $modules[] = $module;
      }
    }
    return $modules;
  }

  /**
   * Build the list of module dependencies in all modules based on module info.
   *
   * @param string $name
   *   The name of the module for which to list dependencies.
   *
   * @return array
   *   A map of modules names by module name.
   */
  protected function moduleDeclaredDependencies(string $name): array {
    $info = $this->elm->getExtensionInfo($name);
    $deps = $info['dependencies'] ?? [];
    $res = [];
    foreach ($deps as $dep) {
      $ar = explode(":", $dep);
      $res[] = array_pop($ar);
    }
    return $res;
  }

  /**
   * Perform the undeclared calls check.
   *
   * @return \Drupal\qa\Result
   *   The result.
   */
  public function check(): Result {
    $modules = $this->getModulesToScan();
    $all = $this->elm->getList();
    $undeclared = [];
    foreach ($modules as $module => $installed) {
      $path = $all[$module]->getExtensionPathname();
      if (!$installed || empty($path)) {
        continue;
      }
      $calls = $this->functionCalls($path);
      $actual = $this->moduleActualDependencies($module, $calls);
      $declared = $this->moduleDeclaredDependencies($module);
      $missing = array_diff($actual, $declared);
      if (!empty($missing)) {
        $undeclared[$module] = $missing;
      }
    }

    return new Result('function_calls', empty($undeclared), $undeclared);
  }

  /**
   * {@inheritdoc}
   */
  public function run(): Pass {
    $pass = parent::run();
    $pass->record($this->check());
    $pass->life->end();
    return $pass;
  }

}
