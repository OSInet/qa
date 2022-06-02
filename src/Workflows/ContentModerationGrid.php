<?php

namespace Drupal\qa\Workflows;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentModerationGrid implements ContainerInjectionInterface {

  use StringTranslationTrait;

  protected $stateStorage;

  protected $transStorage;

  public function __construct(EntityStorageInterface $stateStorage, EntityStorageInterface $transStorage) {
    $this->stateStorage = $stateStorage;
    $this->transStorage = $transStorage;
  }

  protected function buildGrid() {
    $grid = [];
    $stateIds = array_keys($this->getStates());
    $stateCells = array_map(function ($stateId) {
      return [];
    }, array_flip($stateIds));

    foreach ($stateIds as $stateId) {
      $grid[$stateId] = $stateCells;
    }

    foreach ($this->getTrans() as $transId => $trans) {
      $grid[$trans['from']][$trans['to']][] = $transId;
    }

    return $grid;
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

  public function getDuplicateTransLabels() {
    $labels = [];
    foreach ($this->getTrans() as $trans) {
      if (isset($labels[$trans['label']])) {
        $labels[$trans['label']]++;
      }
      else {
        $labels[$trans['label']] = 1;
      }
    }
    $repeatedLabels = array_filter($labels, function ($count) {
      return $count > 1;
    });

    return $repeatedLabels;
  }

  public static function create(ContainerInterface $container) {
    /** @var EntityTypeManagerInterface $etm */
    $etm = $container->get('entity_type.manager');

    $stateStorage = $etm->getStorage('moderation_state');
    $transStorage = $etm->getStorage('moderation_state_transition');

    return new static($stateStorage, $transStorage);
  }

  protected function getStates() {
    $fullStates = $this->stateStorage->loadMultiple();
    $simpleStates = array_map(function ($entity) {
      return $entity->label();
    }, $fullStates);
    asort($simpleStates);
    return $simpleStates;
  }

  protected function getTrans() {
    $fullTrans = $this->transStorage->loadMultiple();
    $simpleTrans = array_map(function ($trans) {
      return [
        'label' => $trans->label(),
        'from' => $trans->getFromState(),
        'to' => $trans->getToState(),
      ];
    }, $fullTrans);

    return $simpleTrans;
  }

  public function report() {
    $grid = $this->buildGrid();

    $rows = [];

    // Build header rows.
    $headerStates = array_merge(["From \\\n" => "    \\ To"], $this->getStates());
    $rows[] = array_map(function ($id, $label) {
      return "${label}\n${id}";
    }, array_keys($headerStates), $headerStates);

    // Build data rows.
    foreach ($grid as $id => $transList) {
      $rows[] = $this->buildRow($id, $transList);
    }

    // Render.
    drush_print_table($rows, TRUE);
    drush_print("");

    $duplicates = $this->getDuplicateTransLabels();
    if (!empty($duplicates)) {
      $count = count($duplicates);
      $message = $this->formatPlural($count, "One repeated transition label: @info", "@count repeated transition labels: @info", [
        "@info" => implode(", ", array_keys($duplicates))
      ]);
      drush_print($message, 'warning');
    }
  }

}
