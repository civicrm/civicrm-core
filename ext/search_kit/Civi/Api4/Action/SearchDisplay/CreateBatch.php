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
    \CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS $tableName");

    $userJob = [
      'job_type' => 'search_batch_import',
      'status_id:name' => 'draft',
      'is_template' => FALSE,
      'expires_date' => $this->savedSearch['expires_date'],
      'metadata' => [
        'DataSource' => [
          'table_name' => $tableName,
          'column_headers' => [],
        ],
      ],
    ];

    $pseudoFields = array_column(AbstractRunAction::getPseudoFields(), 'name');

    $tableColumns = [];

    foreach ($this->display['settings']['columns'] as $i => $column) {
      if (empty($column['key']) || in_array($column['key'], $pseudoFields)) {
        continue;
      }
      [$key] = explode(':', $column['key']);
      $field = $this->getField($key);
      $userJob['metadata']['DataSource']['column_headers'][] = $field['label'];
      $columnName = \CRM_Utils_String::munge($key, '_', 61);
      if (in_array($columnName, $tableColumns)) {
        $columnName .= $i;
      }
      $tableColumns[] = $columnName;
    }

    $columnSql = implode(",\n", \CRM_Import_DataSource::getStandardTrackingFields());
    $columnSql .= ",\n`" . implode("` text,\n`", $tableColumns) . "` text";

    $table->createWithColumns($columnSql);

    // Add indices
    $alterSql = "ALTER TABLE $tableName ADD INDEX(" . implode('), ADD INDEX(', \CRM_Import_DataSource::getStandardIndices()) . ')';
    \CRM_Core_DAO::executeQuery($alterSql, [], TRUE, NULL, FALSE, FALSE);

    $result[] = UserJob::create(FALSE)
      ->setValues($userJob)
      ->execute()->single();
  }

}
