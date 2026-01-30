<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Import\DataSource\DataSourceInterface;
use Civi\Import\DataSource\DataSourceTrait;

/**
 * This class defines the DataSource interface but must be subclassed to be
 * useful.
 */
abstract class CRM_Import_DataSource implements DataSourceInterface {
  use DataSourceTrait;
  /**
   * @var \CRM_Core_DAO
   */
  private $queryResultObject;

  /**
   * @var int
   */
  private $limit;

  public function getLimit(): int {
    return $this->limit;
  }

  public function getOffset(): int {
    return $this->offset;
  }

  public function getStatuses(): array {
    return $this->statuses;
  }

  /**
   * @param int $limit
   *
   * @return CRM_Import_DataSource
   */
  public function setLimit(int $limit): DataSourceInterface {
    $this->limit = $limit;
    $this->flushQueryResults();
    return $this;
  }

  /**
   * @param int $offset
   *
   * @return CRM_Import_DataSource
   */
  public function setOffset(int $offset): CRM_Import_DataSource {
    $this->offset = $offset;
    $this->flushQueryResults();
    return $this;
  }

  /**
   * @var int
   */
  private $offset;

  /**
   * Statuses of rows to fetch.
   *
   * @var array
   */
  private $statuses = [];

  /**
   * Fields to select.
   *
   * @var array
   */
  private $selectFields;

  /**
   * Fields to select as aggregates.
   *
   * @var array
   */
  private $aggregateFields;

  /**
   * The name of the import table.
   *
   * @var string
   */
  private $tableName;

  /**
   * @return array|null
   */
  public function getSelectFields(): ?array {
    return $this->selectFields;
  }

  /**
   * @param array $selectFields
   *
   * @return CRM_Import_DataSource
   */
  public function setSelectFields(array $selectFields): CRM_Import_DataSource {
    $this->selectFields = $selectFields;
    return $this;
  }

  /**
   * @param array $fields
   *
   * @return CRM_Import_DataSource
   */
  public function setAggregateFields(array $fields): CRM_Import_DataSource {
    $this->aggregateFields = $fields;
    return $this;
  }

  /**
   * @return array|null
   */
  public function getAggregateFields(): ?array {
    return $this->aggregateFields;
  }

  /**
   * Current row.
   *
   * @var array
   */
  private $row;

  /**
   * @param array $statuses
   *
   * @return self
   */
  public function setStatuses(array $statuses): DataSourceInterface {
    $this->statuses = $statuses;
    $this->flushQueryResults();
    return $this;
  }

  /**
   * Form fields declared for this datasource.
   *
   * @var string[]
   */
  protected $submittableFields = [];

  /**
   * Get rows as an array.
   *
   * The array has all values.
   *
   * @param bool $nonAssociative
   *   Return as a non-associative array?
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getRows(bool $nonAssociative = TRUE): array {
    $rows = [];
    while ($this->getRow()) {
      // Historically we expect a non-associative array...
      $rows[] = $nonAssociative ? array_values($this->row) : $this->row;
    }
    $this->queryResultObject = NULL;
    return $rows;
  }

  /**
   * Get the next row.
   *
   * @return array|null
   * @throws \CRM_Core_Exception
   */
  public function getRow(): ?array {
    if (!$this->queryResultObject) {
      $this->instantiateQueryObject();
    }
    if (!$this->queryResultObject->fetch()) {
      return NULL;
    }
    $values = $this->queryResultObject->toArray();
    $this->row = $values;
    return $values;
  }

  /**
   * Flush the existing query to retrieve rows.
   *
   * The query will be run again, potentially retrieving newly-available rows.
   * Note the 'newly available' could mean an external process has intervened.
   * For example the import_extensions lazy-loads into the import table.
   *
   * @return void
   */
  private function flushQueryResults() {
    $this->queryResultObject = NULL;
  }

  /**
   * Get row count.
   *
   * The array has all values.
   *
   * @param array $statuses
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  public function getRowCount(array $statuses = []): int {
    if (!$this->getTableName()) {
      return 0;
    }
    $this->statuses = $statuses;
    $query = 'SELECT count(*) FROM ' . $this->getTableName() . ' ' . $this->getStatusClause();
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Get the field names of the fields holding data in the import tracking table.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getDataFieldNames(): array {
    $result = CRM_Core_DAO::executeQuery(
      'SHOW FIELDS FROM ' . $this->getTableName() . "
      WHERE Field NOT LIKE '\_%'");
    $fields = [];
    while ($result->fetch()) {
      $fields[] = $result->Field;
    }
    return $fields;
  }

  /**
   * Get an array of column headers, if any.
   *
   * Null is returned when there are none - ie because a csv file does not
   * have an initial header row.
   *
   * This is presented to the user in the MapField screen so
   * that can see what fields they are mapping.
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  public function getNumberOfColumns(): int {
    return $this->getUserJob()['metadata']['DataSource']['number_of_columns'];
  }

  /**
   * Generated metadata relating to the the datasource.
   *
   * This is values that are computed within the DataSource class and
   * which are stored in the userJob metadata in the DataSource key - eg.
   *
   * ['table_name' => $]
   *
   * Will be in the user_job.metadata field encoded into the json like
   *
   * `{'DataSource' : ['table_name' => $], 'submitted_values' : .....}`
   *
   * @var array
   */
  protected $dataSourceMetadata = [];

  /**
   * Get metadata about the datasource.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getDataSourceMetadata(): array {
    if (!$this->dataSourceMetadata && $this->getUserJobID()) {
      $this->dataSourceMetadata = $this->getUserJob()['metadata']['DataSource'] ?? [];
    }

    return $this->dataSourceMetadata;
  }

  /**
   * Get the table name for the import job.
   *
   * @return string|null
   *
   * @throws \CRM_Core_Exception
   */
  protected function getTableName(): ?string {
    // The old name is still stored...
    $tableName = $this->getDataSourceMetadata()['table_name'] ?? NULL;
    if (!$tableName) {
      return NULL;
    }
    if (!$this->tableName) {
      $this->tableName = $tableName;
    }
    return $this->tableName;
  }

  /**
   * Get the fields declared for this datasource.
   *
   * @return string[]
   */
  public function getSubmittableFields(): array {
    return $this->submittableFields;
  }

  /**
   * Add a status columns to the import table.
   *
   * We add
   *  _id - primary key
   *  _status
   *  _statusMsg
   *
   * Note that
   * 1) the use of the preceding underscore has 2 purposes - it avoids clashing
   *   with an id field (code comments from 14 years ago suggest perhaps there
   *   could be cases where it still clashes but time didn't tell in this case)
   * 2) the show fields query used to get the column names excluded the
   *   administrative fields, relying on this convention.
   *
   * @param string $tableName
   *
   * @throws \CRM_Core_Exception
   */
  protected function addTrackingFieldsToTable(string $tableName): void {
    $trackingFields = self::getStandardTrackingFields();
    // Insert additional fields after `_entity_id`
    // (kept this order to keep refactor minimal, but does the column order really matter?)
    array_splice($trackingFields, 1, 0, $this->getAdditionalTrackingFields());

    $sql = 'ALTER TABLE ' . $tableName . ' ADD COLUMN ' . implode(', ADD COLUMN ', $trackingFields);
    $sql .= ', ADD INDEX(' . implode('), ADD INDEX(', self::getStandardIndices()) . ')';
    CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
  }

  public static function getStandardTrackingFields(): array {
    return [
      '_entity_id INT',
      '_status VARCHAR(32) DEFAULT "NEW" NOT NULL',
      '_status_message LONGTEXT',
      '_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT',
    ];
  }

  public static function getStandardIndices(): array {
    return [
      '_id',
      '_status',
    ];
  }

  /**
   * Get any additional import specific tracking fields.
   *
   * @throws \CRM_Core_Exception
   */
  private function getAdditionalTrackingFields(): array {
    $sql = [];
    $fields = $this->getParser()->getTrackingFields();
    foreach ($fields as $fieldName => $spec) {
      $sql[] = '_' . $fieldName . ' ' . $spec['type'];
    }
    return $sql;
  }

  /**
   * Get the import parser.
   *
   * @return CRM_Import_Parser
   *
   * @throws \CRM_Core_Exception
   */
  private function getParser() {
    $parserClass = '';
    foreach (CRM_Core_BAO_UserJob::getTypes() as $type) {
      if ($this->getUserJob()['job_type'] === $type['id']) {
        $parserClass = $type['class'];
        break;
      }
    }
    /** @var \CRM_Import_Parser $parser */
    $parser = new $parserClass();
    $parser->setUserJobID($this->getUserJobID());
    return $parser;
  }

  /**
   * Has the import job completed.
   *
   * @return bool
   *   True if no rows remain to be imported.
   *
   * @throws \CRM_Core_Exception
   */
  public function isCompleted(): bool {
    return (bool) $this->getRowCount(['new']);
  }

  /**
   * Update the status of the import row to reflect the processing outcome.
   *
   * @param int $id
   * @param string $status
   * @param string $message
   * @param int|null $entityID
   *   Optional created entity ID
   * @param array $additionalFields
   *   Optional array e.g ['related_contact' => 4]
   *
   * @throws \CRM_Core_Exception
   */
  public function updateStatus(int $id, string $status, string $message, ? int $entityID = NULL, array $additionalFields = []): void {
    $sql = 'UPDATE ' . $this->getTableName() . ' SET _status = %1, _status_message = %2 ';
    $params = [1 => [$status, 'String'], 2 => [$message, 'String']];
    if ($entityID) {
      $sql .= ', _entity_id = %3';
      $params[3] = [$entityID, 'Integer'];
    }
    $nextParam = 4;
    foreach ($additionalFields as $fieldName => $value) {
      $sql .= ', _' . $fieldName . ' = %' . $nextParam;
      $params[$nextParam] = is_numeric($value) ? [$value, 'Int'] : [json_encode($value), 'String'];
      $nextParam++;
    }
    CRM_Core_DAO::executeQuery($sql . ' WHERE _id = ' . $id, $params);
  }

  /**
   *
   * @throws \CRM_Core_Exception
   */
  private function instantiateQueryObject(): void {
    $query = 'SELECT ' . $this->getSelectClause() . ' FROM ' . $this->getTableName() . ' ' . $this->getStatusClause();
    if ($this->limit) {
      $query .= ' LIMIT ' . $this->limit . ($this->offset ? (' OFFSET ' . $this->offset) : NULL);
    }
    $this->queryResultObject = CRM_Core_DAO::executeQuery($query);
  }

  /**
   * @return string
   */
  private function getSelectClause(): string {
    if ($this->getAggregateFields()) {
      $fields = [];
      foreach ($this->getAggregateFields() as $field) {
        $fields[] = $field['operation'] . '(_' . $field['name'] . ') as ' . $field['name'];
      }
      return implode(',', $fields);
    }
    return $this->getSelectFields() ? '`' . implode('`, `', $this->getSelectFields()) . '`' : '*';
  }

  /**
   * Get the mapping of constants to database status codes.
   *
   * @return array[]
   */
  protected function getStatusMapping(): array {
    return [
      CRM_Import_Parser::VALID => ['imported', 'new', 'valid', 'soft_credit_imported', 'pledge_payment_imported'],
      CRM_Import_Parser::ERROR => ['error', 'invalid', 'soft_credit_error', 'pledge_payment_error'],
      CRM_Import_Parser::DUPLICATE => ['duplicate'],
      CRM_Import_Parser::NO_MATCH => ['invalid_no_match'],
      CRM_Import_Parser::UNPARSED_ADDRESS_WARNING => ['warning_unparsed_address'],
      CRM_Import_Parser::SOFT_CREDIT_ERROR => ['soft_credit_error'],
      CRM_Import_Parser::SOFT_CREDIT => ['soft_credit_imported'],
      CRM_Import_Parser::PLEDGE_PAYMENT => ['pledge_payment_imported'],
      CRM_Import_Parser::PLEDGE_PAYMENT_ERROR => ['pledge_payment_error'],
      'new' => ['new', 'valid'],
      'valid' => ['valid'],
      'imported' => ['imported', 'soft_credit_imported', 'pledge_payment_imported', 'warning_unparsed_address'],
      'unimported' => ['new', 'valid', 'error', 'invalid', 'soft_credit_error', 'pledge_payment_error', 'invalid_no_match'],
    ];
  }

  /**
   * Get the status filter clause.
   *
   * @return string
   */
  private function getStatusClause(): string {
    if (!empty($this->statuses)) {
      $statuses = [];
      foreach ($this->statuses as $status) {
        foreach ($this->getStatusMapping()[$status] as $statusName) {
          $statuses[] = '"' . $statusName . '"';
        }
      }
      return ' WHERE _status IN (' . implode(',', $statuses) . ')';
    }
    return '';
  }

}
