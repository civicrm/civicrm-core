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


namespace api\v4;

use Civi\Api4\UFMatch;
use Civi\Api4\Utils\CoreUtil;
use Civi\Test\HeadlessInterface;

require_once 'api/Exception.php';

/**
 * @group headless
 */
class Api4TestBase extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  /**
   * Records created which will be deleted during tearDown
   *
   * @var array
   */
  public $testRecords = [];

  /**
   * @see CiviUnitTestCase
   *
   * @param string $name
   * @param array $data
   * @param string $dataName
   */
  public function __construct($name = NULL, array $data = [], $dataName = '') {
    parent::__construct($name, $data, $dataName);
    error_reporting(E_ALL);
  }

  public function setUpHeadless() {
    return \Civi\Test::headless()->apply();
  }

  /**
   */
  public function tearDown(): void {
    $impliments = class_implements($this);
    // If not created in a transaction, test records must be deleted
    if (!in_array('Civi\Test\TransactionalInterface', $impliments, TRUE)) {
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
  }

  /**
   * Quick clean by emptying tables created for the test.
   *
   * @param array $params
   */
  public function cleanup($params) {
    $params += [
      'tablesToTruncate' => [],
    ];
    \CRM_Core_DAO::executeQuery("SET FOREIGN_KEY_CHECKS = 0;");
    foreach ($params['tablesToTruncate'] as $table) {
      \Civi::log()->info('truncating: ' . $table);
      $sql = "TRUNCATE TABLE $table";
      \CRM_Core_DAO::executeQuery($sql);
    }
    \CRM_Core_DAO::executeQuery("SET FOREIGN_KEY_CHECKS = 1;");
  }

  /**
   * Emulate a logged in user since certain functions use that.
   * value to store a record in the DB (like activity)
   * @see https://issues.civicrm.org/jira/browse/CRM-8180
   *
   * @return int
   *   Contact ID of the created user.
   */
  public function createLoggedInUser() {
    $contactID = $this->createTestRecord('Contact')['id'];
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

  /**
   * Inserts a test record, supplying all required values if not provided.
   *
   * Test records will be automatically deleted during tearDown.
   *
   * @param string $entityName
   * @param array $values
   * @return array|null
   * @throws \API_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public function createTestRecord(string $entityName, array $values = []) {
    return $this->saveTestRecords($entityName, ['records' => [$values]])->single();
  }

  /**
   * Saves one or more test records, supplying default values.
   *
   * Test records will be automatically deleted during tearDown.
   *
   * @param string $entityName
   * @param array $saveParams
   * @return \Civi\Api4\Generic\Result
   * @throws \API_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public function saveTestRecords(string $entityName, array $saveParams) {
    $saveParams += [
      'checkPermissions' => FALSE,
      'defaults' => [],
    ];
    $idField = CoreUtil::getIdFieldName($entityName);
    foreach ($saveParams['records'] as &$record) {
      $record += $saveParams['defaults'];
      if (empty($record[$idField])) {
        $this->getRequiredValuesToCreate($entityName, $record);
      }
    }
    $saved = civicrm_api4($entityName, 'save', $saveParams);
    foreach ($saved as $item) {
      $this->testRecords[] = [$entityName, [[$idField, '=', $item[$idField]]]];
    }
    return $saved;
  }

  /**
   * Get the required fields for the api entity + action.
   *
   * @param string $entity
   * @param array $values
   *
   * @return array
   * @throws \API_Exception
   */
  public function getRequiredValuesToCreate(string $entity, &$values = []) {
    $requiredFields = civicrm_api4($entity, 'getfields', [
      'action' => 'create',
      'loadOptions' => TRUE,
      'where' => [
        ['type', 'IN', ['Field', 'Extra']],
        ['OR',
          [
            ['required', '=', TRUE],
            // Include contitionally-required fields only if they don't create a circular FK reference
            ['AND', [['required_if', 'IS NOT EMPTY'], ['fk_entity', '!=', $entity]]],
          ],
        ],
        ['default_value', 'IS EMPTY'],
        ['readonly', 'IS EMPTY'],
      ],
    ], 'name');

    $extraValues = [];
    foreach ($requiredFields as $fieldName => $requiredField) {
      if (!isset($values[$fieldName])) {
        $extraValues[$fieldName] = $this->getRequiredValue($requiredField);
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
          "activityTypes" => [
            [
              "name" => "Open Case",
              "max_instances" => "1",
            ],
            [
              "name" => "Follow up",
            ],
          ],
          "activitySets" => [
            [
              "name" => "standard_timeline",
              "label" => "Standard Timeline",
              "timeline" => 1,
              "activityTypes" => [
                [
                  "name" => "Open Case",
                  "status" => "Completed",
                ],
                [
                  "name" => "Follow up",
                  "reference_activity" => "Open Case",
                  "reference_offset" => "3",
                  "reference_select" => "newest",
                ],
              ],
            ],
          ],
          "timelineActivityTypes" => [
            [
              "name" => "Open Case",
              "status" => "Completed",
            ],
            [
              "name" => "Follow up",
              "reference_activity" => "Open Case",
              "reference_offset" => "3",
              "reference_select" => "newest",
            ],
          ],
          "caseRoles" => [
            [
              "name" => "Parent of",
              "creator" => "1",
              "manager" => "1",
            ],
          ],
        ];
        break;
    }

    $values += $extraValues;
    return $values;
  }

  /**
   * Attempt to get a value using field option, defaults, FKEntity, or a random
   * value based on the data type.
   *
   * @param array $field
   *
   * @return mixed
   * @throws \Exception
   */
  private function getRequiredValue(array $field) {
    if (!empty($field['options'])) {
      return key($field['options']);
    }
    if (!empty($field['fk_entity'])) {
      return $this->getFkID($field['fk_entity']);
    }
    if (isset($field['default_value'])) {
      return $field['default_value'];
    }
    if ($field['name'] === 'contact_id') {
      return $this->getFkID('Contact');
    }
    if ($field['name'] === 'entity_id') {
      // What could possibly go wrong with this?
      switch ($field['table_name'] ?? NULL) {
        case 'civicrm_financial_item':
          return $this->getFkID(\Civi\Api4\Service\Spec\Provider\FinancialItemCreationSpecProvider::DEFAULT_ENTITY);

        default:
          return $this->getFkID('Contact');
      }
    }

    $randomValue = $this->getRandomValue($field['data_type']);

    if ($randomValue) {
      return $randomValue;
    }

    throw new \API_Exception('Could not provide default value');
  }

  /**
   * Get an ID for the appropriate entity.
   *
   * @param string $fkEntity
   *
   * @return int
   *
   * @throws \API_Exception
   */
  private function getFkID(string $fkEntity) {
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
   */
  private function getRandomValue($dataType) {
    switch ($dataType) {
      case 'Boolean':
        return TRUE;

      case 'Integer':
        return random_int(1, 2000);

      case 'String':
        return \CRM_Utils_String::createRandom(10, implode('', range('a', 'z')));

      case 'Text':
        return \CRM_Utils_String::createRandom(100, implode('', range('a', 'z')));

      case 'Money':
        return sprintf('%d.%2d', rand(0, 2000), rand(10, 99));

      case 'Date':
        return '20100102';

      case 'Timestamp':
        return 'now';
    }

    return NULL;
  }

}
