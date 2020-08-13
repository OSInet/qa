<?php
declare(strict_types = 1);

namespace Drupal\qa\Commands;

use Drupal\qa\Controller\WorkflowsReportController;
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
   * Show the content moderation as a table.
   *
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
   * @param $workflow
   *   The machine name of a workflow
   *
   * @command como:graphviz
   * @aliases cmg,como-graphviz
   */
  public function graphviz($workflow = NULL) {
    $graph = ContentModerationGraph::create(\Drupal::getContainer());
    echo $graph->report();
  }


  /**
   * Show a summary of available workflows
   *
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
   *
   * @command qa:dependencies
   * @aliases qadep,qa-dependencies
   */
  public function dependencies() {
    /** @var \Drupal\qa\Dependencies $qaDep */
    $qaDep = \Drupal::service('qa.dependencies');
    $G = $qaDep->build();
    echo $G->build();
  }

  /**
   * Show projects entirely unused and unused themes.
   *
   * @command qa:system:unused
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function system_unused() {
    /** @var \Drupal\qa\Plugin\QaCheckManager $qam */
    $qam = \Drupal::service('plugin.manager.qa_check');
    $unused = $qam->createInstance('system.unused_extensions');
    $pass = $unused->run();
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
    $this->output->writeln(Yaml::dump($res, 4, 2));
  }

  /**
   * List extensions removed without a clean uninstall.
   *
   *
   * @command qa:force-removed
   * @aliases qafrm,qa-force-removed
   */
  public function forceRemoved() {
    $finder = ForceRemoved::create();
    echo $finder->find();
  }

}
