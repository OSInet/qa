<?php

namespace Drupal\qa\Controller;

use Drupal\content_moderation\ContentModerationState;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\workflows\Entity\Workflow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * WorkflowsReportController validates workflow connectivity.
 *
 * It considers workflows with fonts, sink, or islands as non-optimal:
 * - fonts are states where it is impossible to return once they've been left,
 * - sinks are states from which is is impossible to exit,
 * - islands are unreachable states.
 *
 * The first two are inconvenient, while instances of the latter are useless.
 *
 * Implementation note: some properties and methods are public to allow reuse by
 * an equivalent Drush command formatting the same date for CLI display.
 */
class WorkflowsReportController extends ControllerBase {

  /**
   * The workflow storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  public $storage;

  /**
   * WorkflowsReportController constructor.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage
   *   The workflow storage.
   */
  public function __construct(ConfigEntityStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $etm = $container->get('entity_type.manager');

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
    $storage = $etm->getStorage('workflow');

    return new static($storage);
  }

  /**
   * Count transitions, fonts, sinks, and islands in a workflow.
   *
   * @param \Drupal\workflows\Entity\Workflow $workflow
   *   The workflow to examine.
   *
   * @return array
   *   An information hash.
   */
  public static function getConnectivity(Workflow $workflow) {
    $result = [
      'islandNodes' => 0,
      'fontNodes' => 0,
      'sinkNodes' => 0,
    ];

    /** @var \Drupal\content_moderation\Entity\ContentModerationStateInterface $state */
    foreach ($workflow->getStates() as $state) {
      $id = $state->id();
      $maybeIsland = FALSE;
      if (empty($workflow->getTransitionsForState($id, 'from'))) {
        $result['sinkNodes']++;
        $maybeIsland = TRUE;
      }
      if (empty($workflow->getTransitionsForState($id, 'to'))) {
        $result['fontNodes']++;
        if ($maybeIsland) {
          $result['islandNodes']++;
        }
      }
    }
    return array_filter($result);
  }

  /**
   * Build an information summary for all workflows.
   *
   * @param array $workflows
   *   An array of Workflow entities to check.
   *
   * @return array
   *   A summary information hash, keyed and ordered by id.
   */
  public function getWorkflowSummary(array $workflows) {
    $list = array_map(function (Workflow $workflow) {
      $states = array_map(function (ContentModerationState &$state) {
        return $state->label();
      }, $workflow->getStates());
      $stateIds = array_keys($states);
      sort($stateIds);
      $cell = [
        'label' => $workflow->label(),
        'states' => $stateIds,
        'transitionCount' => count($workflow->getTransitions()),
      ] + self::getConnectivity($workflow);
      return $cell;
    }, $workflows);

    ksort($list);
    return $list;
  }

  /**
   * Build a table cell from a value, using the qa/results library classes.
   *
   * Assumes the cell is to be used within a ".qa-results" CSS element.
   *
   * @param array $workflow
   *   An information array about a workflow.
   * @param string $key
   *   The string to extract from the information array.
   *
   * @return array
   *   A table cell array.
   */
  protected function buildControlCell(array $workflow, $key) {
    $value = $workflow[$key] ?? 0;
    $cell = $value
      ? ['class' => 'qa-results__ko']
      : ['class' => 'qa-results__ok'];
    $cell['data'] = $value;
    return $cell;
  }

  /**
   * Build a table from a workflow summary analysis.
   *
   * @param array $list
   *   A workflow summary analysis, from ::getWorkflowSummary().
   *
   * @return array
   *   A render array for the table.
   */
  protected function build(array $list) {
    $header = [
      $this->t('ID'),
      $this->t('Label'),
      $this->t('States'),
      $this->t('# transitions'),
      $this->t('# fonts'),
      $this->t('# sinks'),
      $this->t('# islands'),
    ];
    $rows = [];
    foreach ($list as $id => $workflow) {
      $row = [];
      $row[] = $id;
      $row[] = $workflow['label'];
      $row[] = implode(', ', $workflow['states']);
      $row[] = $workflow['transitionCount'] ?? 0;
      $row[] = $this->buildControlCell($workflow, 'fontNodes');
      $row[] = $this->buildControlCell($workflow, 'sinkNodes');
      $row[] = $this->buildControlCell($workflow, 'islandNodes');
      $rows[] = $row;
    }

    $build = [
      '#type' => 'table',
      '#attributes' => ['class' => ['qa-results']],
      '#header' => $header,
      '#rows' => $rows,
      '#attached' => ['library' => ['qa/results']],
    ];

    return $build;
  }

  /**
   * Report on existing workflows.
   *
   * @return array
   *   The render array for the controller.
   */
  public function report() {
    $workflows = $this->storage->loadMultiple();
    $list = $this->getWorkflowSummary($workflows);
    $build = $this->build($list);

    return $build;
  }

}
