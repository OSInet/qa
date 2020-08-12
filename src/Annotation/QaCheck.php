<?php

namespace Drupal\qa\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a QA Check item annotation object.
 *
 * @see \Drupal\qa\Plugin\QaCheckManager
 * @see plugin_api
 *
 * @Annotation
 */
class QaCheck extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
