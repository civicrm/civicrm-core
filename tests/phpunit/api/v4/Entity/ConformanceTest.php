<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace api\v4\Entity;

use Civi\Api4\Entity;
use api\v4\Traits\TableDropperTrait;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class ConformanceTest extends UnitTestCase {

  use TableDropperTrait;
  use \api\v4\Traits\OptionCleanupTrait {
    setUp as setUpOptionCleanup;
  }

  /**
   * @var \api\v4\Service\TestCreationParameterProvider
   */
  protected $creationParamProvider;

  /**
   * Set up baseline for testing
   */
  public function setUp() {
    $tablesToTruncate = [
      'civicrm_custom_group',
      'civicrm_custom_field',
      'civicrm_group',
      'civicrm_event',
      'civicrm_participant',
    ];
    $this->dropByPrefix('civicrm_value_myfavorite');
    $this->cleanup(['tablesToTruncate' => $tablesToTruncate]);
    $this->setUpOptionCleanup();
    $this->loadDataSet('ConformanceTest');
    $this->creationParamProvider = \Civi::container()->get('test.param_provider');
    parent::setUp();
    // calculateTaxAmount() for contribution triggers a deprecation notice
    \PHPUnit\Framework\Error\Deprecated::$enabled = FALSE;
  }

  public function getEntities() {
    return Entity::get()->setCheckPermissions(FALSE)->execute()->column('name');
  }

  /**
   * Fixme: This should use getEntities as a dataProvider but that fails for some reason
   */
  public function testConformance() {
    $entities = $this->getEntities();
    $this->assertNotEmpty($entities);

    foreach ($entities as $data) {
      $entity = $data;
      $entityClass = 'Civi\Api4\\' . $entity;

      $actions = $this->checkActions($entityClass);

      // Go no further if it's not a CRUD entity
      if (array_diff(['get', 'create', 'update', 'delete'], array_keys($actions))) {
        continue;
      }

      $this->checkFields($entityClass, $entity);
      $id = $this->checkCreation($entity, $entityClass);
      $this->checkGet($entityClass, $id, $entity);
      $this->checkGetCount($entityClass, $id, $entity);
      $this->checkUpdateFailsFromCreate($entityClass, $id);
      $this->checkWrongParamType($entityClass);
      $this->checkDeleteWithNoId($entityClass);
      $this->checkDeletion($entityClass, $id);
      $this->checkPostDelete($entityClass, $id, $entity);
    }
  }

  /**
   * @param string $entityClass
   * @param $entity
   */
  protected function checkFields($entityClass, $entity) {
    $fields = $entityClass::getFields()
      ->setCheckPermissions(FALSE)
      ->setIncludeCustom(FALSE)
      ->execute()
      ->indexBy('name');

    $errMsg = sprintf('%s is missing required ID field', $entity);
    $subset = ['data_type' => 'Integer'];

    $this->assertArraySubset($subset, $fields['id'], $errMsg);
  }

  /**
   * @param string $entityClass
   */
  protected function checkActions($entityClass) {
    $actions = $entityClass::getActions()
      ->setCheckPermissions(FALSE)
      ->execute()
      ->indexBy('name');

    $this->assertNotEmpty($actions);
    return (array) $actions;
  }

  /**
   * @param string $entity
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   *
   * @return mixed
   */
  protected function checkCreation($entity, $entityClass) {
    $requiredParams = $this->creationParamProvider->getRequired($entity);
    $createResult = $entityClass::create()
      ->setValues($requiredParams)
      ->setCheckPermissions(FALSE)
      ->execute()
      ->first();

    $this->assertArrayHasKey('id', $createResult, "create missing ID");
    $id = $createResult['id'];

    $this->assertGreaterThanOrEqual(1, $id, "$entity ID not positive");

    return $id;
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   * @param int $id
   */
  protected function checkUpdateFailsFromCreate($entityClass, $id) {
    $exceptionThrown = '';
    try {
      $entityClass::create()
        ->setCheckPermissions(FALSE)
        ->addValue('id', $id)
        ->execute();
    }
    catch (\API_Exception $e) {
      $exceptionThrown = $e->getMessage();
    }
    $this->assertContains('id', $exceptionThrown);
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   * @param int $id
   * @param string $entity
   */
  protected function checkGet($entityClass, $id, $entity) {
    $getResult = $entityClass::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $id)
      ->execute();

    $errMsg = sprintf('Failed to fetch a %s after creation', $entity);
    $this->assertEquals($id, $getResult->first()['id'], $errMsg);
    $this->assertEquals(1, $getResult->count(), $errMsg);
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   * @param int $id
   * @param string $entity
   */
  protected function checkGetCount($entityClass, $id, $entity) {
    $getResult = $entityClass::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $id)
      ->selectRowCount()
      ->execute();
    $errMsg = sprintf('%s getCount failed', $entity);
    $this->assertEquals(1, $getResult->count(), $errMsg);

    $getResult = $entityClass::get()
      ->setCheckPermissions(FALSE)
      ->selectRowCount()
      ->execute();
    $errMsg = sprintf('%s getCount failed', $entity);
    $this->assertGreaterThanOrEqual(1, $getResult->count(), $errMsg);
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   */
  protected function checkDeleteWithNoId($entityClass) {
    $exceptionThrown = '';
    try {
      $entityClass::delete()
        ->execute();
    }
    catch (\API_Exception $e) {
      $exceptionThrown = $e->getMessage();
    }
    $this->assertContains('required', $exceptionThrown);
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   */
  protected function checkWrongParamType($entityClass) {
    $exceptionThrown = '';
    try {
      $entityClass::get()
        ->setCheckPermissions('nada')
        ->execute();
    }
    catch (\API_Exception $e) {
      $exceptionThrown = $e->getMessage();
    }
    $this->assertContains('checkPermissions', $exceptionThrown);
    $this->assertContains('type', $exceptionThrown);
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   * @param int $id
   */
  protected function checkDeletion($entityClass, $id) {
    $deleteResult = $entityClass::delete()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $id)
      ->execute();

    // should get back an array of deleted id
    $this->assertEquals([['id' => $id]], (array) $deleteResult);
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   * @param int $id
   * @param string $entity
   */
  protected function checkPostDelete($entityClass, $id, $entity) {
    $getDeletedResult = $entityClass::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $id)
      ->execute();

    $errMsg = sprintf('Entity "%s" was not deleted', $entity);
    $this->assertEquals(0, count($getDeletedResult), $errMsg);
  }

}
