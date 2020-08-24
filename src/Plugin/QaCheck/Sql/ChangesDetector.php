<?php

declare(strict_types=1);

namespace Drupal\qa\Plugin\QaCheck\Sql;

use Drupal\Core\Database\Connection;
use Drupal\qa\Pass;
use Drupal\qa\Plugin\QaCheckBase;
use Drupal\qa\Result;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ChangeDetector finds changes between two databases.
 *
 * @QaCheck(
 *   id = "sql.changes",
 *   label = @Translation("SQL: changes"),
 *   details = @Translation("Detects changes between two SQL databases using
 *   the same engine."), usesBatch = false, steps = 1,
 * )
 */
class ChangesDetector extends QaCheckBase {

  const NAME = 'sql.changes';

  const SERVICE = 'database.qa.';

  const LEFT = self::SERVICE . 'left';

  const RIGHT = self::SERVICE . 'right';

  /**
   * The tables found with the same schema in both databases.
   *
   * @var array
   */
  protected $both;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $left;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $right;

  /**
   * ChangeDetector constructor.
   *
   * @param array $configuration
   * @param string $id
   * @param string $definition
   * @param \Drupal\Core\Database\Connection $left
   * @param \Drupal\Core\Database\Connection $right
   */
  public function __construct(
    array $configuration,
    string $id,
    array $definition,
    Connection $left,
    Connection $right,
    LoggerInterface $logger
  ) {
    parent::__construct($configuration, $id, $definition);
    $this->left = $left;
    $this->right = $right;
    $this->logger = $logger;
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
    $left = $container->get(self::LEFT);
    $right = $container->get(self::RIGHT);
    $logger = $container->get('logger.channel.qa');
    return new static($configuration, $id, $definition, $left, $right, $logger);
  }

  protected static function dbTables(Connection $db): array {
    $names = $db->query('SHOW TABLES')->fetchCol();
    // Probably already sorted, but let's not take any risk.
    sort($names);
    return $names;
  }

  /**
   * Get the creation string for a table.
   *
   * @param \Drupal\Core\Database\Connection $db
   * @param string $name
   *
   * @unsafe possible SQL injection: only use on safe $name values.
   *
   * @return array
   */
  protected static function dbCreateTable(
    Connection $db,
    string $name
  ): string {
    $sql = "SHOW CREATE TABLE `${name}`;";
    $res = current($db->query($sql)->fetchCol(1));
    return $res;
  }

  public function compareSchema(): Result {
    $ret = [];
    $l = self::dbTables($this->left);
    $r = self::dbTables($this->right);
    $onlyLeft = array_diff($l, $r);
    $onlyRight = array_diff($r, $l);
    if (!empty($onlyLeft)) {
      $ret['onlyLeft'] = $onlyLeft;
    }
    if (!empty($onlyRight)) {
      $ret['onlyRight'] = $onlyRight;
    }

    $changed = [];
    $this->both = array_intersect($l, $r);
    foreach ($this->both as $name) {
      $lCreate = self::dbCreateTable($this->left, $name);
      $rCreate = self::dbCreateTable($this->right, $name);
      if ($lCreate !== $rCreate) {
        $changed[] = $name;
      }
    }
    if (!empty($changed)) {
      $ret['changed'] = $changed;
    }
    return new Result('schema', empty($ret), $ret);
  }

  /**
   * Specific to MySQL-family.
   *
   * @param \Drupal\Core\Database\Connection $db
   * @param string $name
   *
   * @unsafe possible SQL injection: only use on safe $name values.
   *
   * @return array
   */
  protected static function getPK(Connection $db, string $name): array {
    $sql = "SHOW KEYS FROM `${name}` WHERE Key_name = 'PRIMARY'";
    $pk = $db->query($sql)->fetchAll();
    return array_map(function (stdClass $desc): string {
      return $desc->Column_name;
    }, $pk);
  }

  /**
   * @param string $name
   * @param array $pk
   * @param array $key
   *
   * @return bool
   *
   * @unsafe possible SQL injection: only use on safe $name, $pk, and $key values.
   */
  protected function compareRow(string $name, array $pk, array $key): bool {
    $sql = "SELECT * FROM `$name` WHERE ";
    $crit = [];
    foreach (array_combine($pk, $key) as $k => $v) {
      $crit[] = "$k = '$v'";
    };
    $sql .= join(' AND ', $crit);
    $left = serialize($this->left->query($sql)->fetch());
    $right = serialize($this->right->query($sql)->fetch());
    return $left !== $right;
  }

  public function compareContent() {
    // A hash of changed rows by table name, for exclusive or modified rows.
    $ret = [];
    foreach ($this->both as $name) {
      $this->logger->warning("Checking @name", ['@name' => $name]);
      if (!in_array($name, [
        'menu_links',
        'menu_router',
        'node',
      ])) {
        continue;
      }
      // Both tables have the same schema, only get it once.
      $pk = self::getPK($this->left, $name);
      if (empty($pk)) {
        $ret['no_pk'][] = $name;
        continue;
      }

      $lKeys = [];
      $q = $this->left
        ->select($name, 'qa')
        ->fields('qa', $pk);
      foreach ($pk as $pkCol) {
        $q = $q->orderBy($pkCol);
      }
      $lKeysCursor = $q->orderBy(...$pk);
      foreach ($lKeysCursor as $row) {
        $key = [];
        foreach ($pk as $index => $col) {
          $key[] = $row->$col;
        }
        $lKeys[] = serialize($key);
      }

      $rKeys = [];
      $q = $this->right
        ->select($name, 'qa')
        ->fields('qa', $pk);
      foreach ($pk as $pkCol) {
        $q = $q->orderBy($pkCol);
      }
      $rKeysCursor = $q->orderBy(...$pk)
        ->execute();
      foreach ($rKeysCursor as $row) {
        $key = [];
        foreach ($pk as $index => $col) {
          $key[] = $row->$col;
        }
        $rKeys[] = serialize($key);
      }
      $onlyLeft = array_values(array_diff($lKeys, $rKeys));
      if (!empty($onlyLeft)) {
        $ret['onlyLeft'][$name] = array_map('unserialize', $onlyLeft);
      }
      $onlyRight = array_values(array_diff($rKeys, $lKeys));
      if (!empty($onlyRight)) {
        $ret['onlyRight'][$name] = array_map('unserialize', $onlyRight);
      }
      $both = array_values(array_intersect($lKeys, $rKeys));

      foreach ($both as $index => $key) {
        $arKey = unserialize($key);
        if ($this->compareRow($name, $pk, $arKey)) {
          $ret['changed'][$name][$index] = $arKey;
        }
        if ($index % 5000 === 0) {
          $this->logger->warning("@index rows compared", ['@index' => $index]);
        }
      }
    }

    return new Result('content', FALSE, $ret);
  }

  /**
   * {@inheritdoc}
   */
  public function run(): Pass {
    $pass = parent::run();
    $pass->record($this->compareSchema());
    $pass->life->modify();
    $pass->record($this->compareContent());
    $pass->life->end();
    return $pass;
  }

}
