<?php

declare(strict_types = 1);

namespace Drupal\qa\Commands;

use Drupal\qa\Controller\WorkflowsReportController;
use Drupal\qa\Plugin\QaCheck\References\Integrity;
use Drupal\qa\Plugin\QaCheck\References\TaxonomyIndex;
use Drupal\qa\Plugin\QaCheck\System\UnusedExtensions;
use Drupal\qa\Plugin\QaCheckManager;
use Drupal\qa\Workflows\ContentModerationGraph;
use Drupal\qa\Workflows\ContentModerationGrid;
use Drush\Commands\DrushCommands;
use OSInet\qa\ForceRemoved;
use Symfony\Component\Yaml\Yaml;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class QaCommands extends DrushCommands {

  /**
   * The plugin.manager.qa_check service.
   *
   * @var \Drupal\qa\Plugin\QaCheckManager
   */
  protected $qam;

  /**
   * QaCommands constructor.
   *
   * @param \Drupal\qa\Plugin\QaCheckManager $qam
   *   The plugin.manager.qa_check service.
   */
  public function __construct(QaCheckManager $qam) {
    $this->qam = $qam;
  }

  /**
   * Show the content moderation as a table.
   *
   * @command como:table
   * @aliases cmt,como-table
   */
  public function table() {
    $table = ContentModerationGrid::create(\Drupal::getContainer());
    $table->report();
  }

  /**
   * Show the content moderation as a Graphviz DOT file.
   *
   * @param string $workflow
   *   The machine name of a workflow.
   *
   * @command como:graphviz
   * @aliases cmg,como-graphviz
   */
  public function graphviz(string $workflow = '') {
    $graph = ContentModerationGraph::create(\Drupal::getContainer());
    echo $graph->report();
  }

  /**
   * Show a summary of available workflows.
   *
   * @command qa:workflows-list
   * @aliases qawl,qa-workflows-list
   */
  public function workflowsList() {
    $listBuilder = WorkflowsReportController::create(\Drupal::getContainer());
    $list = $listBuilder->getWorkflowSummary(
      $listBuilder->storage->loadMultiple()
    );
    $this->output->writeln(Yaml::dump($list));
  }

  /**
   * Build a Graphviz DOT file showing the module and theme dependencies.
   *
   * @command qa:dependencies
   * @aliases qadep,qa-dependencies
   */
  public function dependencies() {
    /** @var \Drupal\qa\Dependencies $qaDep */
    $qaDep = \Drupal::service('qa.dependencies');
    $g = $qaDep->build();
    echo $g->build();
  }

  /**
   * Command helper: runs a QaCheck plugin and display its results.
   *
   * @param string $name
   *   The plugin name.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function runPlugin(string $name): void {
    $check = $this->qam->createInstance($name);
    $pass = $check->run();
    $res = [
      'age' => $pass->life->age(),
      'ok' => $pass->ok,
      'result' => [],
    ];
    /** @var \Drupal\qa\Result $result */
    foreach ($pass->result as $key => $result) {
      $res['result'][$key] = [
        'ok' => $result->ok,
        'data' => $result->data,
      ];
    }
    $this->output->writeln(Yaml::dump($res, 5, 2));
  }

  /**
   * Show broken entity_reference fields.
   *
   * @command qa:references:integrity
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function referencesIntegrity() {
    $this->runPlugin(Integrity::NAME);
  }

  /**
   * Show broken taxonomy_index data.
   *
   * @command qa:references:taxonomy_index
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function referencesTaxonomyIndex() {
    $this->runPlugin(TaxonomyIndex::NAME);
  }

  /**
   * Show projects entirely unused and unused themes.
   *
   * @command qa:system:unused
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function systemUnused() {
    $this->runPlugin(UnusedExtensions::NAME);
  }

  /**
   * List extensions removed without a clean uninstall.
   *
   * @command qa:force-removed
   * @aliases qafrm,qa-force-removed
   */
  public function forceRemoved() {
    $finder = ForceRemoved::create();
    echo $finder->find();
  }

}
