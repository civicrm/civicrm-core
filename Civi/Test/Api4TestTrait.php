<?php

namespace Civi\Test;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\UFMatch;
use Civi\Api4\Utils\CoreUtil;

/**
 * Class Api4TestTrait
 *
 * @package Civi\Test
 *
 * This trait defines apiv4 helper functions to create, track and cleanup test data.
 *
 * Specifically:
 * - `createTestRecord()` will create a new entity record with example data.
 * - `saveTestRecords()` is the same as `createTestRecord` for multiple creation of the same entity type.
 * - `deleteTestRecords()` will batch-delete all the test-data created by `createTestRecord()`
 */
trait Api4TestTrait {

  /**
   * Records created which will be deleted during tearDown
   *
   * @var array
   */
  protected $testRecords = [];

  /**
   * Inserts a test record, supplying all required values if not provided.
   *
   * Test records will be automatically deleted if `deleteTestRecords` is called.
   *
   * This is a convenience helper for `saveTestRecords` when working with a
   * single entity.
   *
   * @param string $entityName
   * @param array $values
   *
   * @return array|null
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function createTestRecord(string $entityName, array $values = []): ?array {
    return $this->saveTestRecords($entityName, ['records' => [$values]])->single();
  }

  /**
   * @param string $entityName
   * @param array|string|int $idOrFilters
   *   Either the entity id or filters like ['name' => 'foo']
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getTestRecord(string $entityName, $idOrFilters): array {
    if (!is_array($idOrFilters)) {
      $idField = CoreUtil::getIdFieldName($entityName);
      $idOrFilters = [$idField => $idOrFilters];
    }
    $where = [];
    foreach ($idOrFilters as $key => $value) {
      $where[] = [$key, '=', $value];
    }
    return civicrm_api4($entityName, 'get', ['where' => $where])->single();
  }

  /**
   * Saves one or more test records, supplying default values.
   *
   * Test records will be deleted when the `deleteTestRecords` function is
   * called, usually in `tearDown`.
   *
   * If the transactional method is in use (and nothing is down to cause
   * the transaction to commit, such as creating custom fields) then the
   * `deleteTestRecords` function does not need to be called.
   *
   * @param string $entityName
   * @param array $saveParams
   *
   * @return \Civi\Api4\Generic\Result
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function saveTestRecords(string $entityName, array $saveParams): Result {
    // Shortcut for creating a bunch of records
    if (is_int($saveParams['records'])) {
      $saveParams['records'] = array_fill(0, $saveParams['records'], []);
    }
    $saveParams += [
      'checkPermissions' => FALSE,
      'defaults' => [],
    ];
    $idField = CoreUtil::getIdFieldName($entityName);
    foreach ($saveParams['records'] as $index => $record) {
      $saveParams['records'][$index] += $saveParams['defaults'];
      if (empty($record[$idField])) {
        $saveParams['records'][$index] = $this->getRequiredValuesToCreate($entityName, $saveParams['records'][$index]);
      }
    }
    $saved = civicrm_api4($entityName, 'save', $saveParams);
    foreach ($saved as $item) {
      $this->registerTestRecord($entityName, [[$idField, '=', $item[$idField]]]);
    }
    return $saved;
  }

  /**
   * Register a record to be automatically cleaned up during tearDown
   * @param string $entityName
   * @param string|int|array $where
   *   Where clause (or for short, just the ID)
   */
  public function registerTestRecord(string $entityName, $where): void {
    if (!is_array($where)) {
      $idField = CoreUtil::getIdFieldName($entityName);
      $where = [[$idField, '=', $where]];
    }
    $this->testRecords[] = [$entityName, $where];
  }

  /**
   * Generate some random lowercase letters.
   *
   * @param int $len
   * @return string
   */
  private function randomLetters(int $len = 10): string {
    return \CRM_Utils_String::createRandom($len, implode('', range('a', 'z')));
  }

  /**
   * Get the required fields for the api entity + action.
   *
   * @param string $entity
   * @param array $values
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getRequiredValuesToCreate(string $entity, array $values = []): array {
    $requiredFields = civicrm_api4($entity, 'getfields', [
      'action' => 'create',
      'loadOptions' => TRUE,
      'where' => [
        ['type', 'IN', ['Field', 'Extra']],
        ['OR',
          [
            ['required', '=', TRUE],
            // Include conditionally-required fields only if they don't create a circular FK reference
            ['AND', [['required_if', 'IS NOT EMPTY'], ['fk_entity', '!=', $entity]]],
          ],
        ],
        ['default_value', 'IS EMPTY'],
        ['readonly', 'IS EMPTY'],
      ],
      'orderBy' => ['required' => 'DESC'],
      'checkPermissions' => FALSE,
    ], 'name');

    $extraValues = [];
    foreach ($requiredFields as $fieldName => $field) {
      if (
        self::isMissingValue($values, $fieldName) &&
        ($field['required'] || AbstractAction::evaluateCondition($field['required_if'], ['values' => $values + $extraValues]))
      ) {
        $extraValues[$fieldName] = $this->getRequiredValue($field);
      }
    }

    // Hack in some extra per-entity values that couldn't be determined by metadata.
    // Try to keep this to a minimum and improve metadata as a first-resort.

    switch ($entity) {
      case 'UFField':
        $extraValues['field_name'] = 'activity_campaign_id';
        break;

      case 'Translation':
        $extraValues['entity_table'] = 'civicrm_msg_template';
        $extraValues['entity_field'] = 'msg_subject';
        $extraValues['entity_id'] = $this->getFkID('MessageTemplate');
        break;

      case 'Case':
        $extraValues['creator_id'] = $this->getFkID('Contact');
        break;

      case 'CaseContact':
        // Prevent "already exists" error from using an existing contact id
        $extraValues['contact_id'] = $this->createTestRecord('Contact')['id'];
        break;

      case 'CaseType':
        $extraValues['definition'] = [
          'activityTypes' => [
            [
              'name' => 'Open Case',
              'max_instances' => 1,
            ],
            [
              'name' => 'Follow up',
            ],
          ],
          'activitySets' => [
            [
              'name' => 'standard_timeline',
              'label' => 'Standard Timeline',
              'timeline' => 1,
              'activityTypes' => [
                [
                  'name' => 'Open Case',
                  'status' => 'Completed',
                ],
                [
                  'name' => 'Follow up',
                  'reference_activity' => 'Open Case',
                  'reference_offset' => 3,
                  'reference_select' => 'newest',
                ],
              ],
            ],
          ],
          'timelineActivityTypes' => [
            [
              'name' => 'Open Case',
              'status' => 'Completed',
            ],
            [
              'name' => 'Follow up',
              'reference_activity' => 'Open Case',
              'reference_offset' => 3,
              'reference_select' => 'newest',
            ],
          ],
          'caseRoles' => [
            [
              'name' => 'Parent of',
              'creator' => 1,
              'manager' => 1,
            ],
          ],
        ];
        break;
    }

    $values += $extraValues;
    return $values;
  }

  private static function isMissingValue(array $values, string $fieldName): bool {
    // Check if field value is set
    if (isset($values[$fieldName])) {
      return FALSE;
    }
    // Also check unique-field joins like :name or .name
    if (isset($values["$fieldName:name"]) || isset($values["$fieldName.name"])) {
      return FALSE;
    }
    // A few other unique-field joins are possible, but not important for unit tests
    return TRUE;
  }

  /**
   * Attempt to get a value using field option, defaults, FKEntity, or a random
   * value based on the data type.
   *
   * @param array $field
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  private function getRequiredValue(array $field) {
    // Hack that shouldn't exist except that these entities were added with bad fk metadata
    // and existing tests at the time didn't catch it.
    // TODO: Fix these entities and delete this list
    // NOT TODO: Add to this list!
    $fixmeDfkMissing = [
      'EntityBatch' => 'Contact',
      'FinancialTrxn' => 'Contact',
      'Log' => 'Contact',
      'Managed' => 'Contact',
      'PCPBlock' => 'Contact',
      'PriceSetEntity' => 'Contact',
      'RecentItem' => 'Contact',
    ];
    if (!empty($field['options'])) {
      return key($field['options']);
    }
    if (!empty($field['fk_entity'])) {
      return $this->getFkID($field['fk_entity']);
    }
    if (!empty($field['dfk_entities'])) {
      return $this->getFkID(reset($field['dfk_entities']));
    }
    if (isset($field['default_value'])) {
      return $field['default_value'];
    }
    // Obviously an FK field, but if we get here it's missing FK metadata :(
    if ($field['name'] === 'contact_id' || $field['name'] === 'entity_id') {
      if (isset($fixmeDfkMissing[$field['entity']])) {
        return $this->getFkID($fixmeDfkMissing[$field['entity']]);
      }
      throw new \CRM_Core_Exception($field['name'] . ' should have foreign key information defined.');
    }
    // If there are no options but the field is supposed to have them, we may need to
    // create a new option
    if (!empty($field['suffixes']) && !empty($field['table_name'])) {
      $optionValue = $this->createOptionValue($field['table_name'], $field['name']);
      if ($optionValue) {
        return $optionValue;
      }
    }

    $randomValue = $this->getRandomValue($field['data_type']);

    if ($randomValue) {
      return $randomValue;
    }

    throw new \CRM_Core_Exception('Could not provide default value');
  }

  /**
   * Creates a dummy option value when one is required but the option list is empty
   *
   * @param string $tableName
   * @param string $fieldName
   * @return mixed|null
   */
  private function createOptionValue(string $tableName, string $fieldName) {
    $daoName = \CRM_Core_DAO_AllCoreTables::getClassForTable($tableName);
    $pseudoconstant = $daoName::getSupportedFields()[$fieldName]['pseudoconstant'] ?? NULL;
    if (!empty($pseudoconstant['optionGroupName'])) {
      $newOption = $this->createTestRecord('OptionValue', [
        'option_group_id:name' => $pseudoconstant['optionGroupName'],
      ]);
      return $newOption['value'];
    }
    // Other types of
    return NULL;
  }

  /**
   * Delete records previously created by the `saveTestRecords` function.
   *
   * This should be called during the `tearDown` function if the test
   * class does not use the transactional interface.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function deleteTestRecords(): void {
    // Delete all test records in reverse order to prevent fk constraints
    foreach (array_reverse($this->testRecords) as $record) {
      $params = ['checkPermissions' => FALSE, 'where' => $record[1]];

      // Set useTrash param if it exists
      $entityClass = CoreUtil::getApiClass($record[0]);
      $deleteAction = $entityClass::delete();
      if (property_exists($deleteAction, 'useTrash')) {
        $params['useTrash'] = FALSE;
      }

      civicrm_api4($record[0], 'delete', $params);
    }
  }

  protected function conditionallyDeleteTestRecords(): void {
    $implements = class_implements($this);
    // If not created in a transaction, test records must be deleted
    $needsCleanup = !in_array('Civi\Test\TransactionalInterface', $implements, TRUE) ||
      // Creating custom groups or custom fields breaks transactions & requires cleanup
      array_intersect(['CustomField', 'CustomGroup'], array_column($this->testRecords, 0));
    if ($needsCleanup) {
      $this->deleteTestRecords();
    }
  }

  /**
   * Get an ID for the appropriate entity.
   *
   * @param string $fkEntity
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  private function getFkID(string $fkEntity): int {
    $params = ['checkPermissions' => FALSE];
    // Be predictable about what type of contact we select
    if ($fkEntity === 'Contact') {
      $params['where'] = [['contact_type', '=', 'Individual']];
    }
    $entityList = civicrm_api4($fkEntity, 'get', $params);
    // If no existing entities, create one
    if ($entityList->count() < 1) {
      return $this->createTestRecord($fkEntity)['id'];
    }

    return $entityList->last()['id'];
  }

  /**
   * @param $dataType
   *
   * @return int|null|string
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  private function getRandomValue($dataType) {
    switch ($dataType) {
      case 'Boolean':
        return TRUE;

      case 'Integer':
        return random_int(1, 2000);

      case 'String':
        return $this->randomLetters();

      case 'Text':
        return $this->randomLetters(100);

      case 'Money':
      case 'Float':
        return sprintf('%d.%2d', random_int(0, 2000), random_int(10, 99));

      case 'Date':
        return '20100102';

      case 'Timestamp':
        return 'now';
    }

    return NULL;
  }

  /**
   * Emulate a logged in user since certain functions use that.
   * value to store a record in the DB (like activity)
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-8180
   *
   * @return int
   *   Contact ID of the created user.
   * @throws \CRM_Core_Exception
   */
  public function createLoggedInUser(): int {
    $contactID = $this->createTestRecord('Individual', [
      'first_name' => 'Logged In',
      'last_name' => 'User ' . mt_rand(),
    ])['id'];
    UFMatch::delete(FALSE)->addWhere('uf_id', '=', 6)->execute();
    $this->createTestRecord('UFMatch', [
      'contact_id' => $contactID,
      'uf_name' => 'superman',
      'uf_id' => 6,
    ]);

    $session = \CRM_Core_Session::singleton();
    $session->set('userID', $contactID);
    return $contactID;
  }

  public function userLogout() {
    \CRM_Core_Session::singleton()->reset();
    UFMatch::delete(FALSE)
      ->addWhere('uf_name', '=', 'superman')
      ->execute();
  }

}
