<?php


namespace drupal\Qa\Plugin\QaCheck;

use Drupal\Core\Plugin\PluginBase;
use Drupal\qa\Annotation\QaCheck;
use Drupal\qa\Plugin\QaCheckInterface;

/**
 * Class SystemUnusedExtensions
 *
 * @QaCheck(
 *   id = "system.unused_extensions",
 *   label=@Translation("System: unused extensions")
 * )
 */
class SystemUnusedExtensions extends PluginBase implements QaCheckInterface {
}
