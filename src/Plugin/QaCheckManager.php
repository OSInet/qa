<?php

namespace Drupal\qa\Plugin;

use Drupal\Component\Discovery\DiscoveryException;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\qa\Annotation\QaCheck;

/**
 * Provides the QA Check plugin manager.
 */
class QaCheckManager extends DefaultPluginManager {

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
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/QaCheck', $namespaces, $module_handler, QaCheckInterface::class, QaCheck::class);

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

}
