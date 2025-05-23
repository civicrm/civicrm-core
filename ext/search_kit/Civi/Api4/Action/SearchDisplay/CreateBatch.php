<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\UserJob;

/**
 * Creates an UserJob instance for a batch data entry display
 *
 * @method $this setSavedSearch(string $savedSearchName)
 * @method $this setDisplay(string $displayName)
 * @method string getDisplay()
 *
 * @since 6.3
 */
class CreateBatch extends AbstractAction {
  use \Civi\Api4\Generic\Traits\SavedSearchInspectorTrait;

  /**
   * Saved search name
   *
   * @var string
   * @required
   */
  protected $savedSearch;

  /**
   * Search display name
   *
   * @var string
   * @required
   */
  protected $display;

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $this->loadSavedSearch();
    $this->loadSearchDisplay();

    $table = \CRM_Utils_SQL_TempTable::build()
      ->setCategory('searchbatch')
      ->setDurable();
    $tableName = $table->getName();
    \CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `$tableName`");

    $userJob = [
      'job_type' => 'search_batch_import',
      'status_id:name' => 'draft',
      'is_template' => FALSE,
      'expires_date' => $this->savedSearch['expires_date'],
      'search_display_id' => $this->display['id'],
      'metadata' => [
        'DataSource' => [
          'table_name' => $tableName,
          'column_headers' => [],
          'column_specs' => [],
        ],
      ],
    ];

    $tableColumns = [];

    foreach ($this->display['settings']['columns'] as $column) {
      if (empty($column['spec'])) {
        continue;
      }
      $fieldSpec = $column['spec'];
      $tableColumns[$fieldSpec['name']] = $this->getSqlType($fieldSpec);
      $fieldSpec['label'] = $column['label'];
      $userJob['metadata']['DataSource']['column_headers'][] = $column['label'];
      $userJob['metadata']['DataSource']['column_specs'][$fieldSpec['name']] = $fieldSpec;
    }

    $columnSql = implode(",\n", \CRM_Import_DataSource::getStandardTrackingFields());
    foreach ($tableColumns as $name => $type) {
      $columnSql .= ",\n`$name` $type";
    }

    $table->createWithColumns($columnSql);

    // Add indices
    $alterSql = "ALTER TABLE `$tableName` ADD INDEX(" . implode('), ADD INDEX(', \CRM_Import_DataSource::getStandardIndices()) . ')';
    \CRM_Core_DAO::executeQuery($alterSql, [], TRUE, NULL, FALSE, FALSE);

    $result[] = UserJob::create(FALSE)
      ->setValues($userJob)
      ->execute()->single();

    // Add an empty row to get the user started
    $sql = "INSERT INTO `$tableName` () VALUES ()";
    \CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
  }

  private function getSqlType(array $fieldSpec) {
    if (empty($fieldSpec['data_type']) || !empty($fieldSpec['serialize']) || !empty($fieldSpec['options'])) {
      return 'text';
    }
    $map = [
      'Boolean' => 'boolean',
      'Date' => 'date',
      'Float' => 'double',
      'Timestamp' => 'datetime',
      'Money' => 'decimal(20,2)',
    ];
    return $map[$fieldSpec['data_type']] ?? 'text';
  }

}
