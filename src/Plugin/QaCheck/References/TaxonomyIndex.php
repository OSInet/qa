<?php

declare(strict_types=1);

namespace Drupal\qa\Plugin\QaCheck\References;

use Drupal\Core\Database\Connection;
use Drupal\qa\Pass;
use Drupal\qa\Plugin\QaCheckBase;
use Drupal\qa\Plugin\QaCheckInterface;
use Drupal\qa\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TaxonomyIndex checks for broken taxonomy_index references.
 *
 * It is not a generic reference integrity check, just a consistency chec for
 * the taxonomy_index table.
 *
 * @QaCheck(
 *   id = "references.taxonomy_index",
 *   label = @Translation("Taxonomy index"),
 *   details = @Translation("This check finds references in the taxonomy_index. These have to be repaired, as they can cause incorrect content displays."),
 *   usesBatch = false,
 *   steps = 1,
 * )
 */
class TaxonomyIndex extends QaCheckBase implements QaCheckInterface {

  const NAME = "references." . self::TABLE;

  const TABLE = 'taxonomy_index';

  const KEY_NODES = 'missingNodes';
  const KEY_TERMS = 'missingTerms';

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * ContentOrphans constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $id
   *   The plugin ID.
   * @param array $definition
   *   The plugin definition.
   * @param \Drupal\qa\Plugin\QaCheck\References\Connection $db
   *   The database service.
   */
  public function __construct(
    array $configuration,
    string $id,
    array $definition,
    Connection $db
  ) {
    parent::__construct($configuration, $id, $definition);
    $this->db = $db;
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
    $db = $container->get('database');
    return new static($configuration, $id, $definition, $db);
  }

  /**
   * Locate {taxonomy_index} entries linking to a missing term or node.
   *
   * @return \Drupal\qa\Result
   *   The check result.
   */
  public function checkIndex(): Result {
    $sql = <<<SQL
SELECT ti.tid AS indexTid,ti.nid AS indexNid,
    nfd.nid,
    tfd.tid
FROM {taxonomy_index} ti
  LEFT JOIN {taxonomy_term_field_data} tfd ON ti.tid = tfd.tid
  LEFT JOIN {node_field_data} nfd ON ti.nid = nfd.nid
WHERE tfd.tid IS NULL
  OR nfd.nid IS NULL
SQL;
    // No node access: we are scanning the whole database with full privileges.
    $q = $this->db->query($sql);
    $missing = [
      self::KEY_TERMS => [],
      self::KEY_NODES => [],
    ];
    foreach ($q->fetchAll() as $o) {
      if (is_null($o->tid)) {
        $missing[self::KEY_TERMS][] = [
          'nid' => (int) $o->indexNid,
          'missingTid' => (int) $o->indexTid,
        ];
      }
      if (is_null($o->nid)) {
        $missing[self::KEY_NODES][] = [
          'tid' => (int) $o->indexTid,
          'missingNid' => (int) $o->indexNid,
        ];
      }
    }
    sort($missing[self::KEY_TERMS]);
    sort($missing[self::KEY_NODES]);
    if (empty($missing[self::KEY_TERMS])) {
      unset($missing[self::KEY_TERMS]);
    }
    if (empty($missing[self::KEY_NODES])) {
      unset($missing[self::KEY_NODES]);
    }
    return new Result(self::TABLE, empty($missing), $missing);
  }

  /**
   * {@inheritdoc}
   */
  public function run(): Pass {
    $pass = parent::run();
    $pass->record($this->checkIndex());
    $pass->life->end();
    return $pass;
  }

}
