<?php

declare(strict_types = 1);

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

  /**
   * The details about the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $details = '';

  /**
   * This plugin uses a batch process.
   *
   * @var bool
   */
  public $usesBatch = FALSE;

  /**
   * The number of steps this plugin goes through during a run.
   *
   * @var int
   */
  public $steps = 1;

}
