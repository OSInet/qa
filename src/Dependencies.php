<?php

namespace Drupal\qa;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Grafizzi\Graph\Attribute;
use Grafizzi\Graph\Cluster;
use Grafizzi\Graph\Edge;
use Grafizzi\Graph\Graph;
use Grafizzi\Graph\Node;
use Pimple\Container;
use Psr\Log\LoggerInterface;

class Dependencies {
  const SHAPE_THEME = 'octagon';
  const SHAPE_ENGINE = 'doubleoctagon';

  /**
   * @var \Grafizzi\Graph\Attribute
   */
  protected $font;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * @var \Pimple\Container
   */
  protected $pimple;

  /**
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  public function __construct(
    ModuleExtensionList $moduleExtensionList,
    ThemeExtensionList $themeExtensionList,
    LoggerInterface $logger
  ) {
    $this->logger = $logger;
    $this->moduleExtensionList = $moduleExtensionList;
    $this->themeExtensionList = $themeExtensionList;
    $this->pimple = new Container(['logger' => $logger]);

    $this->font = $this->attr("fontsize", 10);
  }


  /**
   * Clone of function _graphviz_create_filepath() from graphviz_filter.module.
   *
   * @param string $path
   * @param string $filename
   *
   * @return string
   */
  public function graphvizCreateFilepath($path, $filename) {
    if (!empty($path)) {
      return rtrim($path, '/') .'/'. $filename;
    }
    return $filename;
  }

  /**
   * Facade for Grafizzi Attribute constructor.
   *
   * @param $name
   * @param $value
   *
   * @return \Grafizzi\Graph\Attribute
   */
  public function attr(string $name, string $value) : Attribute {
    return new Attribute($this->pimple, $name, $value);
  }

  public function edge(Node $from, Node $to, array $attrs) : Edge {
    return new Edge($this->pimple, $from, $to, $attrs);
  }

  /**
   * Facade for Grafizzi Node constructor.
   *
   * Strips the optional "namespace" (aka project or package) part of the name.
   *
   * @param string $name
   * @param \Grafizzi\Graph\Attribute[] $attrs
   *
   * @return \Grafizzi\Graph\Node
   */
  public function node(string $name, array $attrs = []) : Node {
    // Strip the "namespace" part.
    $arName = explode(':', $name);
    $localName = array_pop($arName);

    $arLocal = explode(' ', $localName);
    $simpleName = current($arLocal);
    return new Node($this->pimple, $simpleName, $attrs);
  }

  /**
   * Facade for Grafizzi Cluster constructor.
   *
   * @param string $name
   *
   * @return \Grafizzi\Graph\Cluster
   */
  public function cluster(string $name, array $attrs = []): Cluster {
    return new Cluster($this->pimple, urlencode($name), [
      $this->attr('label', $name),
    ] + $attrs);
  }

  protected function initGraph() : Graph {
    $g = new Graph($this->pimple, "deps", [
      $this->attr("rankdir", "RL"),
    ]);
    $g->setDirected(TRUE);
    return $g;
  }

  public function buildModules(Graph $g) : Graph {
    $modules = $this->moduleExtensionList->reset()->getList();
    krsort($modules);

    $packages = [];

    foreach ($modules as $module => $detail) {
      if (!$detail->status) {
        continue;
      }
      $package = $detail->info['package'] ?? '';
      if (!empty($package)) {
        if (!isset($packages[$package])) {
          $packageCluster = $this->cluster($package);
          $packages[$package] = $packageCluster;
          $g->addChild($packageCluster);
        }
        else {
          /** @var \Grafizzi\Graph\Cluster $packageCluster */
          $packageCluster = $packages[$package];
        }

        $packageCluster->addChild($from = $this->node("${package}:${module}", [$this->font]));
      }
      else {
        $g->addChild($from = $this->node($module, [$this->font]));
      }

      $dependencies = $detail->info['dependencies'] ?? [];
      foreach ($dependencies as $depend) {
        $to = $this->node($depend, [$this->font]);
        $g->addChild(
          $this->edge($from, $to, [
            $this->font,
            $this->attr('color', 'lightgray'),
          ]));
      }
    }
    return $g;
  }

  protected function buildTheming(Graph $g) : Graph {
    $engineShape = $this->attr('shape', static::SHAPE_ENGINE);
    $themeShape = $this->attr('shape', static::SHAPE_THEME);
    $engineLine = $this->attr('style', 'dotted');
    $baseLine = $this->attr('style', 'dashed');

    $themeList = $this->themeExtensionList->getAllAvailableInfo();
    krsort($themeList);

    $engines = [];
    $themes = [];

    foreach ($themeList as $theme => $detail) {
      // Build theme engine links.
      $themes[$theme] = $from = $this->node($theme, [$this->font, $themeShape]);
      $g->addChild($from);
      if (!empty($detail['engine'])) {
        // D8 still theoretically supports multiple engines (e.g. nyan_cat).
        $engine = basename($detail['engine']); // with extension
        $engineBase = basename($engine, '.engine');
        if (!isset($engines[$engineBase])) {
          $engines[$engineBase] = $engineNode = $this->node($engineBase, [
            $engineShape,
          ]);
          $g->addChild($engineCluster = $this->cluster($engineBase, [$this->font]));
          $engineCluster->addChild($engineNode);
        }
        $to = $engines[$engineBase];
        $g->addChild($this->edge($from, $to, [$this->font, $engineLine]));
      }
      else {
        $g->addChild($from);
      }

      // Build base theme links.
      $toName = $detail['base theme'] ?? '';
      if (!empty($toName)) {
        $to = $themes[$toName];
        if (empty($to)) {
          $to = $this->node($toName, [$this->font]);
          $g->addChild($to);
        }
        $g->addChild($this->edge($from, $to, [$this->font, $baseLine]));
      }
    }

    return $g;
  }

  public function build() : Graph {
    // @see https://wiki.php.net/rfc/pipe-operator
    $g = $this->initGraph();
    $g = $this->buildModules($g);
    $g = $this->buildTheming($g);
    return $g;
  }

}
