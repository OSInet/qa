<?php

declare(strict_types = 1);

namespace Drupal\qa\Plugin\QaCheck\Cache;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\qa\Pass;
use Drupal\qa\Plugin\QaCheckBase;
use Drupal\qa\Plugin\QaCheckInterface;
use Drupal\qa\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Size checks the size of data in cache, flagging extra-wide data.
 *
 * This is useful especially for memcached which is by default limited to 1MB
 * per item, causing the Memcache driver to go through slower remediating
 * mechanisms when data is too large.
 *
 * It also flags extra-large cache bins.
 *
 * @QaCheck(
 *   id = "cache.sizes",
 *   label = @Translation("Cache sizes"),
 *   details = @Translation("Find cache entries ≥0.5MB and bins which are empty, ≥1GB, or ≥128k items."),
 *   usesBatch = false,
 *   steps = 1,
 * )
 */
class Sizes extends QaCheckBase implements QaCheckInterface {
  use StringTranslationTrait;

  const ELLIPSIS = '…';

  const NAME = 'cache.sizes';

  /**
   * Memcache default entry limit: 1024*1024 * 0.5 for safety.
   */
  const MAX_ITEM_SIZE = 1 << 19;

  /**
   * Maximum number of items per bin (128k).
   */
  const MAX_BIN_ITEMS = 1 << 17;

  /**
   * Maximum data size per bin (1 GB).
   */
  const MAX_BIN_SIZE = 1 << 30;

  /**
   * Size of data summary in reports.
   */
  const SUMMARY_LENGTH = 1 << 9;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Undeclared constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $id
   *   The plugin ID.
   * @param array $definition
   *   The plugin definition.
   * @param \Drupal\Core\Database\Connection $db
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
    assert($db instanceof Connection);
    return new static($configuration, $id, $definition, $db);
  }

  /**
   * Get the list of cache bins, correlating the DB and container.
   *
   * @return array
   *   The names of all bins.
   */
  public function getAllBins(): array {
    $dbBins = $this->db
      ->schema()
      ->findTables('cache_%');
    $dbBins = array_filter($dbBins, function ($bin) {
      return $this->isSchemaCache($bin);
    });
    sort($dbBins);

    // TODO add service-based bin detection.
    return $dbBins;
  }

  /**
   * Does the schema of the table the expected cache schema structure ?
   *
   * @param string $table
   *   The name of the table to check.
   *
   * @return bool
   *   Is it ?
   */
  public function isSchemaCache(string $table): bool {
    // Core findTable messes the "_" conversion to regex. Double-check here.
    if (strpos($table, 'cache_') !== 0) {
      return FALSE;
    }
    $referenceSchemaKeys = [
      'checksum',
      'cid',
      'created',
      'data',
      'expire',
      'serialized',
      'tags',
    ];
    // XXX MySQL-compatible only.
    $names = array_keys($this->db
      ->query("DESCRIBE $table")
      ->fetchAllAssoc('Field')
    );
    sort($names);
    return $names == $referenceSchemaKeys;
  }

  /**
   * Check a single cache bin.
   *
   * TODO support table prefixes.
   *
   * @param string $bin
   *   The name of the bin to check in DB, where it matches the table name.
   *
   * @return \Drupal\qa\Result
   *   The check result for the bin.
   */
  public function checkBin($bin): Result {
    $res = new Result($bin, FALSE);
    $arg = ['@name' => $bin];

    if (!$this->db->schema()->tableExists($bin)) {
      $res->data = $this->t('Bin @name is missing in the database.', $arg);
      return $res;
    }

    $sql = <<<SQL
SELECT cid, data, expire, created, serialized
FROM {$bin}
ORDER BY cid;
SQL;
    $q = $this->db->query($sql);
    if (!$q instanceof StatementInterface) {
      $res->data = $this->t('Failed fetching database data for bin @name.', $arg);
      return $res;
    }

    [$res->ok, $res->data['count'], $res->data['size'], $res->data['items']] =
      $this->checkBinContents($q);
    if ($res->ok) {
      unset($res->data['items']);
    }
    return $res;
  }

  /**
   * Check the contents of an existing and accessible bin.
   *
   * @param \Drupal\Core\Database\StatementInterface $q
   *   The query object for the bin contents, already queried.
   *
   * @return array
   *   - 0 : status bool
   *   - 1 : item count
   *   - 2 : total size
   *   - 3 : result array
   */
  protected function checkBinContents(StatementInterface $q) {
    $count = 0;
    $size = 0;
    $status = TRUE;
    $items = [];
    foreach ($q->fetchAll() as $row) {
      // Cache drivers will need to serialize anyway.
      $data = $row->serialized ? $row->data : serialize($row->data);
      $len = strlen($data);
      if ($len == 0 || $len >= static::MAX_ITEM_SIZE) {
        $status = FALSE;
        $items[$row->cid] = [
          'len' => number_format($len, 0, ',', ''),
          // Auto-escaped in Twig when rendered in the Web UI.
          'data' => mb_substr($data, 0, static::SUMMARY_LENGTH) . self::ELLIPSIS,
        ];
      }
      $size += $len;
      $count++;
    }

    // Empty bins are suspicious. So are bins with empty entries.
    if ($count === 0 || $size === 0) {
      $status = FALSE;
    }
    return [$status, $count, $size, $items];
  }

  /**
   * Render a result for the Web UI.
   *
   * @param \Drupal\qa\Pass $pass
   *   A check pass to render.
   *
   * @return array
   *   A render array.
   *
   * @FIXME inconsistent logic, fix in issue #8.
   */
  protected function build(Pass $pass): array {
    $build = [];
    $bins = $pass->result['bins'];
    if ($pass->ok) {
      $info = $this->formatPlural(count($bins),
        '1 bin checked, not containing suspicious values',
        '@count bins checked, none containing suspicious values', []
      );
    }
    else {
      $info = $this->formatPlural(count($bins),
        '1 view checked and containing suspicious values',
        '@count bins checked, @bins containing suspicious values', [
          '@bins' => count($pass->result),
        ]);
    }

    foreach ($bins as $bin) {
      // Prepare for theming.
      $result = [];
      // @XXX May be inconsistent with non-BMP strings ?
      uksort($pass->result, 'strcasecmp');
      foreach ($pass->result as $bin_name => $bin_report) {
        foreach ($bin_report as $entry) {
          array_unshift($entry, $bin_name);
          $result[] = $entry;
        }
      }
      $header = [
        $this->t('Bin'),
        $this->t('CID'),
        $this->t('Length'),
        $this->t('Beginning of data'),
      ];

      $build[$bin] = [
        'info' => [
          '#markup' => $info,
        ],
        'list' => [
          '#markup' => '<p>' . $this->t('Checked: @checked', [
            '@checked' => implode(', ', $bins),
          ]) . "</p>\n",
        ],
        'table' => [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $result,
        ],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function run(): Pass {
    $pass = parent::run();
    $bins = $this->getAllBins();
    foreach ($bins as $bin_name) {
      $pass->record($this->checkBin($bin_name));
    }
    $pass->life->end();
    return $pass;
  }

}
