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
use Civi\Core\Exception\DBQueryException;
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
   * Get the array of import tables in the database.
   *
   * Caching is a challenge here as the tables are loaded by the entityTypes hook
   * before the cache & full class loading is necessarily available. We did have
   * caching in this function but removed it recently in favour of a static cache in
   * the other function as that function was 'doing it's work' from the entityTypes
   * hook anyway.
   *
   * In general, call this function from any code that runs late enough in the boot
   * order that caches/ class loading is available in case it diverges once again
   * from the lower level function.
   *
   * @return array
   */
  public static function getImportTables(): array {
    return _civiimport_civicrm_get_import_tables();
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
      $table[$value['name']] = ($value['data_type'] === 'Integer') ? CRM_Utils_Type::T_INT : CRM_Utils_Type::T_STRING;
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
    $op = empty($record['_id']) ? 'create' : 'edit';
    $userJobID = $record['_user_job_id'];
    $entityName = 'Import_' . $userJobID;
    $userJob = UserJob::get($record['check_permissions'])->addWhere('id', '=', $userJobID)->addSelect('metadata', 'job_type', 'created_id')->execute()->first();

    $tableName = $userJob['metadata']['DataSource']['table_name'];
    CRM_Utils_Hook::pre($op, $entityName, $record['_id'] ?? NULL, $record);
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
    $cacheKey = 'civiimport_table_fields' . $tableName;
    if (\Civi::cache('metadata')->has($cacheKey)) {
      return \Civi::cache('metadata')->get($cacheKey);
    }
    if (!CRM_Utils_Rule::alphanumeric($tableName)) {
      // This is purely precautionary so does not need to be a translated string.
      throw new CRM_Core_Exception('Invalid import table');
    }
    $columns = [];
    $userJob = UserJob::get(FALSE)
      ->addWhere('metadata', 'LIKE', '%' . $tableName . '%')
      ->addSelect('metadata', 'job_type')->execute()->first();
    $headers = $userJob['metadata']['DataSource']['column_headers'] ?? [];
    $entity = \CRM_Core_BAO_UserJob::getType($userJob['job_type'])['entity'];

    try {
      $result = CRM_Core_DAO::executeQuery("SHOW COLUMNS FROM $tableName");
    }
    catch (DBQueryException $e) {
      if ($e->getSQLErrorCode() === 1146) {
        throw new CRM_Core_Exception('Import table no longer exists');
      }
      throw $e;
    }

    $userFieldIndex = 0;
    while ($result->fetch()) {
      $columns[$result->Field] = ['name' => $result->Field, 'table_name' => $tableName];
      if (strpos($result->Field, '_') !== 0) {
        $columns[$result->Field]['label'] = ts('Import field') . ':' . ($headers[$userFieldIndex] ?? $result->Field);
        $columns[$result->Field]['data_type'] = 'String';
        $userFieldIndex++;
      }
      else {
        $columns[$result->Field]['label'] = ($result->Field === '_entity_id') ? E::ts('Row Imported to %1 ID', [1 => $entity]) : $result->Field;
        $columns[$result->Field]['fk_entity'] = ($result->Field === '_entity_id') ? $entity : NULL;
        $columns[$result->Field]['data_type'] = strpos($result->Type, 'int') === 0 ? 'Integer' : 'String';
      }
    }
    \Civi::cache('metadata')->set($cacheKey, $columns);
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

  /**
   * Defines the default key as 'id'.
   *
   * @return array
   */
  public function keys() {
    return ['_id'];
  }

  /**
   * Tells DB_DataObject which keys use autoincrement.
   * 'id' is autoincrementing by default.
   *
   *
   * @return array
   */
  public function sequenceKey() {
    return ['_id', TRUE];
  }

}
