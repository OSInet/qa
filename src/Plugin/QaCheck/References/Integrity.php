<?php

declare(strict_types = 1);

namespace Drupal\qa\Plugin\QaCheck\References;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\qa\Pass;
use Drupal\qa\Plugin\QaCheckBase;
use Drupal\qa\Plugin\QaCheckInterface;
use Drupal\qa\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Integrity checks for broken entity references.
 *
 * It covers core entity_reference only.
 *
 * @QaCheck(
 *   id = "references.integrity",
 *   label = @Translation("Referential integrity"),
 *   details = @Translation("This check finds broken entity references. Missing nodes or references mean broken links and a bad user experience. These should usually be edited."),
 *   usesBatch = false,
 *   steps = 3,
 * )
 */
class Integrity extends QaCheckBase implements QaCheckInterface {

  const NAME = 'references.integrity';

  const STEP_ER = 'entity_reference';

  const STEP_FILE = 'file';

  const STEP_IMAGE = 'image';

  const STEP_ERR = 'entity_reference_revisions';

  const STEP_DER = 'dynamic_entity_reference';

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $etm;

  /**
   * A map of storage handler by entity_type ID.
   *
   * @var array
   */
  protected $storages;

  /**
   * SystemUnusedExtensions constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $id
   *   The plugin ID.
   * @param array $definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $etm
   *   The entity_type.manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config.factory service.
   */
  public function __construct(
    array $configuration,
    string $id,
    array $definition,
    EntityTypeManagerInterface $etm,
    ConfigFactoryInterface $config
  ) {
    parent::__construct($configuration, $id, $definition);
    $this->config = $config;
    $this->etm = $etm;

    $this->cacheStorages();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $id,
    $definition
  ) {
    $etm = $container->get('entity_type.manager');
    $config = $container->get('config.factory');
    return new static($configuration, $id, $definition, $etm, $config);
  }

  /**
   * Fetch and cache the storage handlers per entity type for repeated use.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function cacheStorages(): void {
    $ets = array_keys($this->etm->getDefinitions());
    $handlers = [];
    foreach ($ets as $et) {
      $handlers[$et] = $this->etm->getStorage($et);
    }
    $this->storages = $handlers;
  }

  /**
   * Check entity references in the passed reference map.
   *
   * @param array $fieldMap
   *   A map of fields by entity type.
   *
   * @return array
   *   A map of broken references.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function checkForward(array $fieldMap): array {
    $checks = [];
    foreach ($fieldMap as $et => $fields) {
      $checks[$et] = [
        // Eventual result of a broken reference:
        // <id> => [ <field_name> => <target_id> ].
      ];
      $entities = $this->storages[$et]->loadMultiple();
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      foreach ($entities as $entity) {
        $checks[$et][$entity->id()] = [];
        foreach ($fields as $name => $targetET) {
          if (!$entity->hasField($name)) {
            continue;
          }
          $target = $entity->get($name);
          if ($target->isEmpty()) {
            continue;
          }
          $checks[$et][$entity->id()][$name] = [];
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $value */
          foreach ($target as $delta => $value) {
            // Happens with DER.
            if (is_array($targetET)) {
              $targetType = $value->toArray()['target_type'];
              // A fail here would be a severe case where content was not
              // migrated after a schema change.
              $deltaTargetET = in_array($targetType,
                $targetET) ? $targetType : '';
            }
            else {
              $deltaTargetET = $targetET;
            }

            $targetID = $value->toArray()[EntityReferenceItem::mainPropertyName()];
            if (!empty($deltaTargetET)) {
              foreach ($entity->referencedEntities() as $targetEntity) {
                $x = $targetEntity->getEntityTypeId();
                if ($x != $deltaTargetET) {
                  continue;
                }
                // Target found, next delta.
                $x = $targetEntity->id();
                if ($x === $targetID) {
                  continue 2;
                }
              }
            }
            // Target not found: broken reference.
            $checks[$et][$entity->id()][$name][$delta] = $targetID;
          }
          if (empty($checks[$et][$entity->id()][$name])) {
            unset($checks[$et][$entity->id()][$name]);
          }
        }
        if (empty($checks[$et][$entity->id()])) {
          unset($checks[$et][$entity->id()]);
        }
      }
      if (empty($checks[$et])) {
        unset($checks[$et]);
      }
    }
    return $checks;
  }

  /**
   * Perform a reference integrity check of the specified kind.
   *
   * @param string $kind
   *   The reference kind.
   *
   * @return \Drupal\qa\Result
   *   The check result.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function checkReferenceType(string $kind): Result {
    $fieldMap = $this->getFields($kind);
    $checks = $this->checkForward($fieldMap);
    return new Result($kind, empty($checks), $checks);
  }

  /**
   * Get reference fields of the selected type.
   *
   * @param string $refType
   *   The field type.
   *
   * @return array
   *   A field by entity type map.
   */
  protected function getFields(string $refType): array {
    $fscStorage = $this->storages['field_storage_config'];
    $defs = $fscStorage->loadMultiple();
    $fields = [];
    /** @var \Drupal\field\FieldStorageConfigInterface $fsc */
    foreach ($defs as $fsc) {
      if ($fsc->getType() !== $refType) {
        continue;
      }
      $et = $fsc->getTargetEntityTypeId();
      $name = $fsc->getName();
      $target = $fsc->getSetting('target_type');
      if (empty($target)) {
        // Dynamic Entity Reference allows multiple target entity types.
        $target = array_values($fsc->getSetting('entity_type_ids'));
      }
      // XXX hard-coded knowledge. Maybe refactor once multiple types are used.
      // $prop = $fsc->getMainPropertyName();
      if (!isset($fields[$et])) {
        $fields[$et] = [];
      }
      $fields[$et][$name] = $target;
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function run(): Pass {
    $pass = parent::run();

    $steps = [
      self::STEP_ER,
      self::STEP_ERR,
      self::STEP_DER,
      self::STEP_FILE,
      self::STEP_IMAGE,
    ];
    foreach ($steps as $step) {
      $pass->record($this->checkReferenceType($step));
      $pass->life->modify();
    }
    $pass->life->end();
    return $pass;
  }

}
