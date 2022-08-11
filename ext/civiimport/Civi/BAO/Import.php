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

namespace Civi\BAO;

use Civi\Api4\UserJob;
use CRM_Civiimport_ExtensionUtil as E;
use CRM_Core_BAO_CustomValueTable;
use CRM_Core_DAO;
use CRM_Core_Exception;
use CRM_Utils_Hook;
use CRM_Utils_Rule;
use CRM_Utils_Type;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class Import extends CRM_Core_DAO {

  /**
   * This is the id field - it has an underscore due to the import table naming convention.
   *
   * @var int
   */
  protected $_id;

  /**
   * Primary key field.
   *
   * @var string[]
   */
  public static $_primaryKey = ['_id'];

  /**
   * @return array
   */
  public static function getImportTables(): array {
    // We need to avoid the api here as it
    $tables = CRM_Core_DAO::executeQuery('
     Select `user_job`.`id` as id, `metadata`, `name`, `job_type`, `user_job`.`created_id`, `created_id`.`display_name`, `user_job`.`created_date`, `user_job`.`expires_date`
     FROM civicrm_user_job user_job
     LEFT JOIN civicrm_contact created_id ON created_id.id = created_id
       -- As of writing expires date is probably not being managed
       -- it is intended to be used to actually purge the record in
       -- a cleanup job so it might not be relevant here & perhaps this will
       -- be removed later
       WHERE (expires_date IS NULL OR expires_date > NOW())
       -- this is a short-cut for looking up if they are imports
       -- it is a new convention, at best, to require anything
       -- specific in the job_type, but it saves any onerous lookups
       -- in a function which needs to avoid loops
       AND job_type LIKE "%import"
         -- also more of a feature than a specification - but we need a table
         -- to do this pseudo-api
       AND metadata LIKE "%table_name%"');
    $importEntities = [];
    while ($tables->fetch()) {
      $tableName = json_decode($tables->metadata, TRUE)['DataSource']['table_name'];
      if (!CRM_Utils_Rule::alphanumeric($tableName) || !CRM_Core_DAO::singleValueQuery('SHOW TABLES LIKE %1', [1 => [$tableName, 'String']])) {
        continue;
      }
      $importEntities[$tables->id] = [
        'user_job_id' => $tables->id,
        'table_name' => $tableName,
        'created_by' => $tables->display_name,
        'created_id' => $tables->created_id ? (int) $tables->created_id : NULL,
        'job_type' => $tables->job_type,
        'created_date' => $tables->created_date,
        'expires_date' => $tables->expires_date,
      ];
    }
    return $importEntities;
  }

  /**
   * @return string[]
   */
  protected function getPrimaryKey(): array {
    return self::$_primaryKey;
  }

  /**
   * Returns fields generic to all imports, indexed by name.
   *
   * This function could arguably go, leaving it to the `ImportSpecProvider`
   * which adds all the other fields. But it does have the nice side effect of
   * putting these three fields first in a natural sort.
   *
   * @param bool $checkPermissions
   *   Filter by field permissions.
   * @return array
   */
  public static function getSupportedFields($checkPermissions = FALSE): array {
    return [
      '_id' => [
        'type' => 'Field',
        'required' => FALSE,
        'nullable' => FALSE,
        'readonly' => TRUE,
        'name' => '_id',
        'title' => E::ts('Import row ID'),
        'data_type' => 'Integer',
        'input_type' => 'Number',
        'column_name' => '_id',
      ],
      '_status' => [
        'type' => 'Field',
        'required' => TRUE,
        // We should add a requeue action or just define an option group but for now..
        'readonly' => TRUE,
        'nullable' => FALSE,
        'name' => '_status',
        'title' => E::ts('Row status'),
        'data_type' => 'String',
        'column_name' => '_status_message',
      ],
      '_status_message' => [
        'type' => 'Field',
        'nullable' => TRUE,
        'readonly' => TRUE,
        'name' => '_status_message',
        'title' => E::ts('Row import message'),
        'description' => '',
        'data_type' => 'String',
        'column_name' => '_status_message',
      ],
    ];
  }

  /**
   * Over-ride the parent to prevent a NULL return.
   *
   * Metadata otherwise handled in `table()`, `writeRecord` and `ImportSpecProvider`
   *
   * @return array
   */
  public static function &fields(): array {
    $result = [];
    return $result;
  }

  /**
   * Override variant of metadata function used in DAO->insert().
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function table(): array {
    $table = [];
    foreach (self::getFieldsForTable($this->tableName()) as $value) {
      $table[$value['name']] = $value['type'] ?? CRM_Utils_Type::T_STRING;
      if (!empty($value['required'])) {
        $table[$value['name']] += self::DB_DAO_NOTNULL;
      }
    }
    return $table;
  }

  /**
   * Create or update a record from supplied params.
   *
   * This overrides the parent in order to tinker with the available fields.
   *
   * If '_id' is supplied, an existing record will be updated
   * Otherwise a new record will be created.
   *
   * @param array $record
   *
   * @return static
   * @throws \CRM_Core_Exception
   */
  public static function writeRecord(array $record): CRM_Core_DAO {
    $op = empty($record['id']) ? 'create' : 'edit';
    $userJobID = $record['user_job_id'];
    $entityName = 'Import_' . $userJobID;
    $userJob = UserJob::get($record['check_permissions'])->addWhere('id', '=', $userJobID)->addSelect('metadata', 'job_type', 'created_id')->execute()->first();

    $tableName = $userJob['metadata']['DataSource']['table_name'];
    CRM_Utils_Hook::pre($op, $entityName, $record['id'] ?? NULL, $record);
    $fields = self::getAllFields($tableName);
    $instance = new self();
    $instance->__table = $tableName;
    // Ensure fields exist before attempting to write to them
    $values = array_intersect_key($record, $fields);
    foreach ($values as $field => $value) {
      $instance->$field = ($value === '') ? 'null' : $value;
    }
    $instance->save();

    if (!empty($record['custom']) && is_array($record['custom'])) {
      CRM_Core_BAO_CustomValueTable::store($record['custom'], $tableName, $instance->_id, $op);
    }

    CRM_Utils_Hook::post($op, $entityName, $instance->_id, $instance);

    return $instance;
  }

  /**
   * Get all the fields available for the import table.
   *
   * This gets the fields based on a `SHOW COLUMNS` result.
   *
   * @param string $tableName
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getFieldsForTable(string $tableName): array {
    if (!CRM_Utils_Rule::alphanumeric($tableName)) {
      // This is purely precautionary so does not need to be a translated string.
      throw new CRM_Core_Exception('Invalid import table');
    }
    $columns = [];
    $result = CRM_Core_DAO::executeQuery("SHOW COLUMNS FROM $tableName");
    while ($result->fetch()) {
      $columns[$result->Field] = ['name' => $result->Field, 'table_name' => $tableName];
    }
    return $columns;
  }

  /**
   * Get all the fields available for the import table.
   *
   * This gets the fields based on a `SHOW COLUMNS` result.
   *
   * @param int $userJobID
   * @param bool $checkPermissions
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function getFieldsForUserJobID(int $userJobID, bool $checkPermissions = TRUE): array {
    $userJob = UserJob::get($checkPermissions)
      ->addWhere('id', '=', $userJobID)
      ->addSelect('created_id.display_name', 'created_id', 'metadata')
      ->execute()->first();
    $tableName = $userJob['metadata']['DataSource']['table_name'];
    return self::getAllFields($tableName);
  }

  /**
   * Checks if this DAO's table ought to exist to prevent import fails.
   *
   * Since these tables are dropped during import & the API will
   * not see them as entities if they don't exist this should be safe.
   *
   * @return bool
   */
  public static function tableHasBeenAdded(): bool {
    return TRUE;
  }

  /**
   * Get all fields for the import instance.
   *
   * @param string $tableName
   *
   * @return array[]
   * @throws \CRM_Core_Exception
   */
  private static function getAllFields(string $tableName): array {
    return array_merge(self::getFieldsForTable($tableName), self::getSupportedFields());
  }

}
