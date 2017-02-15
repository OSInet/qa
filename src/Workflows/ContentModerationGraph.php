<?php

namespace Drupal\qa\Workflows;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Grafizzi\Graph\Attribute;
use Grafizzi\Graph\Edge;
use Grafizzi\Graph\Graph;
use Grafizzi\Graph\Node;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Pimple\Tests\Fixtures\PimpleServiceProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentModerationGraph extends ContentModerationReportBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The Pimple instance used by Grafizzi.
   *
   * @var \Pimple\Container
   */
  protected $pimple;

  public function __construct(EntityStorageInterface $stateStorage, EntityStorageInterface $transStorage, Container $pimple) {
    $this->pimple = $pimple;
    $this->stateStorage = $stateStorage;
    $this->transStorage = $transStorage;
  }

  /**
   * @return \Grafizzi\Graph\Graph
   */
  protected function buildGraph() {
    $dic = $this->pimple;
    $g = new Graph($dic);
    $states = $this->getStates();
    $transList = $this->getTrans();

    $nodes = [];
    foreach ($states as $name => $label) {
      $nodes[$name] = new Node($dic, $name, [
        new Attribute($dic, 'label', "${label}\n${name}"),
      ]);
      $g->addChild($nodes[$name]);
    }

    foreach ($transList as $name => $trans) {
      $g->addChild(new Edge($dic,
        $nodes[$trans['from']],
        $nodes[$trans['to']],
        [new Attribute($dic, 'label', "${trans['label']}\n$name")]
      ));
    }

    return $g;
  }

  protected function buildRow(string $stateId, array $transList) {
    $states = $this->getStates();
    $trans = $this->getTrans();
    $row = ["${states[$stateId]}\n${stateId}"];
    foreach ($transList as $from => $transIds) {
      $cellArray = [];
      foreach ($transIds as $transId) {
        assert($trans[$transId]['from'] = $stateId);
        $transLabel = $trans[$transId]['label'];
      }
      $cellArray[] = $transLabel;
      $cellArray[] = $transId;
      $cellArray[] = "";
      $cell = implode("\n", $cellArray);
      $row[$trans[$transId]['to']] = $cell;
    }

    return $row;
  }

  public static function create(ContainerInterface $container) {
    /** @var EntityTypeManagerInterface $etm */
    $etm = $container->get('entity_type.manager');

    $stateStorage = $etm->getStorage('moderation_state');
    $transStorage = $etm->getStorage('moderation_state_transition');

    $logger = new Logger(basename(__FILE__, '.php'));
    // Change the minimum logging level using the Logger:: constants.
    $logger->pushHandler(new StreamHandler('php://stderr', Logger::INFO));

    $pimple = new Container([
      'logger' => $logger,
      'directed' => TRUE,
    ]);

    return new static($stateStorage, $transStorage, $pimple);
  }

  public function report() {
    $grid = $this->buildGrid();
    return $this->buildGraph($grid)
      ->build();
  }

}
