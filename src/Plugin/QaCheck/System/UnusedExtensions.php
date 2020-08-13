<?php

declare(strict_types = 1);

namespace Drupal\qa\Plugin\QaCheck\System;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\qa\Pass;
use Drupal\qa\Plugin\QaCheckBase;
use Drupal\qa\Plugin\QaCheckInterface;
use Drupal\qa\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SystemUnusedExtensions checks for useless modules or themes.
 *
 * They are the ones which are present in a project of which all members are
 * uninstalled. Due to the project mechanism, many modules may be uninstalled
 * without a standard way to remove them because they are deployed as part of a
 * project.
 *
 * This check does not cover install profiles, nor theme engines, which are in
 * limited quantity, especially theme engines.
 *
 * @QaCheck(
 *   id = "system.unused_extensions",
 *   label=@Translation("System: unused non-core extensions"),
 *   details=@Translation("Unused modules and themes present on disk can
 *   represent a useless cost on most dimensions. Packages entirely unused
 *   should usually be removed. This does not necessarily hold in a multi-site
 *   filesystem layout.")
 * )
 */
class UnusedExtensions extends QaCheckBase implements QaCheckInterface {

  /**
   * Extension doesn't have a package clause.
   */
  const NO_PACKAGE = 'no_package';

  /**
   * Extension doesn't have a project clause (normal for themes).
   */
  const NO_PROJECT = 'no_project';

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

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
   * SystemUnusedExtensions constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $id
   *   The plugin ID.
   * @param array $definition
   *   The plugin definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config.factory service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $elm
   *   The extension_list.module service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $elt
   *   The extension_list.theme service.
   */
  public function __construct(
    array $configuration,
    string $id,
    array $definition,
    ConfigFactoryInterface $config,
    ModuleExtensionList $elm,
    ThemeExtensionList $elt
  ) {
    parent::__construct($configuration, $id, $definition);
    $this->config = $config;
    $this->elm = $elm;
    $this->elt = $elt;
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
    $elt = $container->get('extension.list.theme');
    $config = $container->get('config.factory');
    return new static($configuration, $id, $definition, $config, $elm, $elt);
  }

  /**
   * Identify projects entirely consisting of uninstalled modules.
   */
  protected function checkModules(): Result {
    $projects = [];
    foreach ($this->elm->getAllAvailableInfo() as $module => $info) {
      $project = $info['project'] ?? self::NO_PROJECT;
      $info['machine_name'] = $module;
      $projects[$project] = $info;
    }
    foreach ($this->elm->getAllInstalledInfo() as $module => $info) {
      $project = $info['project'] ?? 'no_project';
      unset($projects[$project]);
    }

    return new Result('modules', count($projects) === 0, array_keys($projects));
  }

  /**
   * Clear the base themes chain for a given theme.
   *
   * @param string $name
   *   The theme for which to clear the base chain.
   * @param array $all
   *   The list of all themes.
   * @param array $remaining
   *   The current list of possible themes to clear.
   *
   * @return array
   *   The list of possible themes to clear after these have been cleared.
   */
  protected function clearBaseThemes(string $name, array $all, array $remaining): array {
    $base = $all[$name]['base theme'] ?? '';
    if (empty($base)) {
      return $remaining;
    }
    unset($remaining[$base]);
    return $this->clearBaseThemes($base, $all, $remaining);
  }

  /**
   * Identify uninstalled themes not used as base themes for an enabled theme.
   */
  protected function checkThemes(): Result {
    $allThemes = $this->elt->getAllAvailableInfo();
    $activeThemes = $this->elt->getAllInstalledInfo();

    // Start with just the inactive themes.
    $remaining = array_diff_key($allThemes, $activeThemes);

    // Filter out core themes, which cannot be removed.
    foreach ($remaining as $name => $_) {
      if (($allThemes[$name]['package'] ?? self::NO_PACKAGE) === 'Core') {
        unset($remaining[$name]);
      }
    }

    // Filter out base themes of active themes.
    foreach ($activeThemes as $name => $info) {
      $remaining = $this->clearBaseThemes($name, $allThemes, $remaining);
    }

    $res = new Result('themes', count($remaining) === 0, array_keys($remaining));
    return $res;
  }

  /**
   * {@inheritdoc}
   */
  public function run(): Pass {
    $pass = parent::run();
    $pass->record($this->checkModules());
    $pass->life->modify();
    $pass->record($this->checkThemes());
    $pass->life->end();
    return $pass;
  }

}
