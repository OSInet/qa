<?php

namespace OSInet\DrupalQA;


class ForceRemoved {

  public static function create() {
    return new static();
  }

  protected function getExtensions() {
    $result = db_select('system', 's' )->fields('s', [
      'filename',
      'name',
      'type',
      'status',
      'bootstrap',
      'schema_version',
      'weight',
      'info',
    ])->execute();

    $extensions = iterator_to_array($result);
    return $extensions;
  }

  protected function filterMissing($extension) {
    $path = $extension->filename;
    return !file_exists($path);
  }

  protected function stripInfo(\stdClass $extension) {
    switch ($extension->schema_version) {
      case 0:
        $state = 'disabled';
        break;
      case -1:
        $state = 'uninstalled';
        break;
      default:
        $state = "enabled:{$extension->schema_version}";
        break;
    }
    $info = unserialize($extension->info);

    $item = [
      'name' => $extension->name,
      'type' => $extension->type,
      'state' => $state,
      'dependencies' => isset($info['dependencies']) ? $info['dependencies'] : [],
    ];

    return $item;
  }

  public function find() {
    $missing = array_filter($this->getExtensions(), [$this, 'filterMissing']);
    $items = array_map([$this, 'stripInfo'], $missing);
    return json_encode($items, JSON_PRETTY_PRINT);
  }
}