<?php

namespace Drupal\qa\Plugin;

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

    $this->alterInfo('qa_qa_check_info');
    $this->setCacheBackend($cache_backend, 'qa_qa_check_plugins');
  }

}
