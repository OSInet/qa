<?php

namespace Drupal\qa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\qa\Data;
use Drupal\qa\Plugin\QaCheckManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ControlsListController.
 *
 * @package Drupal\qa\Controller
 */
class CheckListController extends ControllerBase {

  /**
   * The plugin.manager.qa_check service.
   *
   * @var \Drupal\qa\Plugin\QaCheckManager
   */
  protected $qam;

  /**
   * CheckListController constructor.
   *
   * @param \Drupal\qa\Plugin\QaCheckManager $qam
   *   The plugin.manager.qa_check service.
   */
  public function __construct(QaCheckManager $qam) {
    $this->qam = $qam;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $qam = $container->get(Data::MANAGER);
    return new static($qam);
  }

  /**
   * Controller listing the available plugins.
   *
   * @return array
   *   A render array.
   */
  public function report() {
    $checks = [
      '#theme' => 'item_list',
      '#items' => [],
    ];
    $pp = $this->qam->getPluginsByPackage();
    foreach ($pp as $package => $plugins) {
      $group = [
        '#theme' => 'item_list',
        '#items' => [],
        '#title' => ucfirst($package),
      ];
      foreach ($plugins as $def) {
        $label = $def['label'];
        $props = [];
        if ($def['usesBatch'] ?? FALSE) {
          $props[] = $this->t('batch');
        }
        if (($def['steps'] ?? 1) > 1) {
          $props[] = $this->formatPlural($def['steps'], "1 step",
            "@count steps", ['@count' => $def['steps']],
          );
        }
        if (!empty($props)) {
          $label .= ' (' . implode(', ', $props) . ')';
        }
        $details = $def['details'];
        $group['#items'][] = [
          '#markup' => "${label}<br /><small>${details}</small>",
        ];
      }
      $checks['#items'][] = $group;
    }
    return $checks;
  }

  /**
   * Placeholder controller for "view".
   *
   * @param string $qaVariable
   *   The variable to view.
   *
   * @return array
   *   A render array.
   */
  public function view($qaVariable) {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: view'),
    ];
  }

}
