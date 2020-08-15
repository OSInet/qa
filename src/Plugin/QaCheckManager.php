<?php

namespace Drupal\qa\Plugin;

use Drupal\Component\Discovery\DiscoveryException;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\qa\Annotation\QaCheck;
use ReflectionFunction;
use Traversable;

/**
 * Provides the QA Check plugin manager.
 */
class QaCheckManager extends DefaultPluginManager {

  /**
   * The list of internal functions. Not loaded by default.
   *
   * @var array
   *
   * @see \Drupal\qa\Plugin\QaCheckManager::initInternalFunction()
   */
  public $internalFunctions;

  /**
   * The kernel base root, aka as base_path() and $GLOBALS['app_root'].
   *
   * @var string
   */
  public $root;

  /**
   * The vendor dir.
   *
   * @var string
   */
  protected $vendor;

  /**
   * Constructs a new QaCheckManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The kernel service.
   */
  public function __construct(
    Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    DrupalKernelInterface $kernel
  ) {
    parent::__construct('Plugin/QaCheck', $namespaces, $module_handler,
      QaCheckInterface::class, QaCheck::class);
    $this->root = realpath($kernel->getAppRoot());
    $this->vendor = self::getVendorDir();

    $this->alterInfo('qa_check_info');
    $this->setCacheBackend($cache_backend, 'qa_check_plugins');
  }

  /**
   * {@inheritdoc}
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param array $configuration
   *   The plugin configuration.
   *
   * @return \Drupal\qa\Plugin\QaCheckInterface
   *   Plugins implement this interface instead of being plain objects.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function createInstance($plugin_id, array $configuration = []) {
    /** @var \Drupal\qa\Plugin\QaCheckInterface $res */
    $res = parent::createInstance($plugin_id, $configuration);
    return $res;
  }

  /**
   * Extract the package ID from a QaCheck plugin ID.
   *
   * @param string $pluginId
   *   The QaCheck plugin ID.
   *
   * @return string
   *   The package ID.
   *
   * @throws \Drupal\Component\Discovery\DiscoveryException
   */
  public static function getPackageId(string $pluginId): string {
    $id = strtok($pluginId, '.');
    if ($id === FALSE) {
      throw new DiscoveryException("Ill-formed QaCheck plugin ID: ${pluginId}.");
    }
    return $id;
  }

  /**
   * Get the path to the vendor directory.
   *
   * Drupal projects use various layouts, so the position of the vendor
   * directory relative to the app_root is not fixed.
   *
   * @return string
   *   The absolute path to the vendor directory.
   */
  public static function getVendorDir(): string {
    $rf = new ReflectionFunction('composer\autoload\includefile');
    return dirname(dirname($rf->getFileName()));
  }

  /**
   * Return the check plugin definitions, indexed by their check package ID.
   *
   * @return array
   *   The definitions.
   */
  public function getPluginsByPackage(): array {
    $defs = $this->getDefinitions();
    $packages = [];
    foreach ($defs as $id => $def) {
      $packageId = self::getPackageId($id);
      $packages[$packageId][] = $def;
    }
    return $packages;
  }

  /**
   * Initialize the internal functions list.
   *
   * Internal functions are those provided by Drupal core except its modules,
   * and by vendor dependencies.
   *
   * @throws \ReflectionException
   */
  public function initInternalFunctions(): void {
    $all = get_defined_functions();
    $internal = $all['internal'];
    $user = [];
    foreach ($all['user'] as $func) {
      $rf = new \ReflectionFunction($func);
      $path = $rf->getFileName();
      $isInternal = $this->isInternal($path);
      if ($isInternal) {
        $user[] = $func;
      }
    }
    $merged = array_merge($internal, $user);
    sort($merged);
    $this->internalFunctions = array_flip($merged);
  }

  /**
   * Is the file located in the always loaded directories of Drupal ?
   *
   * @param string $file
   *   An absolute path.
   *
   * @return bool
   *   Is it ?
   */
  public function isInternal($file) {
    $internal = [
      $this->root . "/core/lib",
      $this->root . "/core/includes",
      $this->vendor,
    ];

    foreach ($internal as $root) {
      if (strpos($file, $root) !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
