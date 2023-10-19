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

namespace api\v4\Entity;

use api\v4\Traits\CheckAccessTrait;
use api\v4\Traits\TableDropperTrait;
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\Entity;
use api\v4\Api4TestBase;
use Civi\Api4\Event\ValidateValuesEvent;
use Civi\Api4\Provider\ActionObjectProvider;
use Civi\Api4\Service\Spec\CustomFieldSpec;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\PostEvent;
use Civi\Core\Event\PreEvent;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HookInterface;

/**
 * @group headless
 */
class ConformanceTest extends Api4TestBase implements HookInterface {

  use CheckAccessTrait;
  use TableDropperTrait;

  /**
   * Set up baseline for testing
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    // Enable all components
    \CRM_Core_BAO_ConfigSetting::enableAllComponents();
    parent::setUp();
    $this->resetCheckAccess();
  }

  public function setUpHeadless(): CiviEnvBuilder {
    // Install all core extensions that provide APIs
    return Test::headless()->install([
      'org.civicrm.search_kit',
      'civigrant',
    ])->apply();
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function tearDown(): void {
    CustomField::delete()->addWhere('id', '>', 0)->execute();
    CustomGroup::delete()->addWhere('id', '>', 0)->execute();
    $tablesToTruncate = [
      'civicrm_case_type',
      'civicrm_group',
      'civicrm_event',
      'civicrm_participant',
      'civicrm_batch',
      'civicrm_product',
      'civicrm_translation',
    ];
    $this->cleanup(['tablesToTruncate' => $tablesToTruncate]);
    parent::tearDown();
  }

  /**
   * Get entities to test.
   *
   * This is the hi-tech list as generated via Civi's runtime services. It
   * is canonical, but relies on services that may not be available during
   * early parts of PHPUnit lifecycle.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getEntitiesHitech(): array {
    return $this->toDataProviderArray(Entity::get(FALSE)->execute()->column('name'));
  }

  /**
   * Get entities to test.
   *
   * This method uses file-scanning only and doesn't include dynamic entities (e.g. from multi-record custom fields)
   * But it may be summoned at any time during PHPUnit lifecycle.
   *
   * @return array
   */
  public function getEntitiesLotech(): array {
    $provider = new ActionObjectProvider();
    $entityNames = [];
    foreach ($provider->getAllApiClasses() as $className) {
      $entityNames[] = $className::getEntityName();
    }
    return $this->toDataProviderArray($entityNames);
  }

  /**
   * Ensure that "getEntitiesLotech()" (which is the 'dataProvider') is up to date
   * with "getEntitiesHitech()" (which is a live feed available entities).
   */
  public function testEntitiesProvider(): void {
    $this->assertEquals($this->getEntitiesHitech(), $this->getEntitiesLotech(), "The lo-tech list of entities does not match the hi-tech list. You probably need to update getEntitiesLotech().");
  }

  /**
   * @param string $entityName
   *   Ex: 'Contact'
   *
   * @dataProvider getEntitiesLotech
   *
   * @throws \CRM_Core_Exception
   */
  public function testConformance(string $entityName): void {
    $entityClass = CoreUtil::getApiClass($entityName);

    $this->checkEntityInfo($entityClass);
    $actions = $this->checkActions($entityClass);

    // Go no further if it's not a CRUD entity
    if (array_diff(['get', 'create', 'update', 'delete'], array_keys($actions))) {
      $this->markTestSkipped("The API \"$entityName\" does not implement CRUD actions");
    }

    $this->checkFields($entityName);
    $this->checkCreationDenied($entityName, $entityClass);
    $entityKeys = $this->checkCreation($entityName);
    $getResult = $this->checkGet($entityName, $entityKeys);
    // civi.api4.authorizeRecord does not work on `get` actions
    // $this->checkGetAllowed($entityClass, $id, $entityName);
    $this->checkGetCount($entityClass, $entityKeys, $entityName);
    $this->checkUpdateFailsFromCreate($entityClass, $entityKeys);
    $this->checkUpdate($entityName, $entityKeys, $getResult);
    $this->checkWrongParamType($entityClass);
    $this->checkDeleteWithNoId($entityClass);
    $this->checkDeletionDenied($entityClass, $entityKeys, $entityName);
    $this->checkDeletionAllowed($entityClass, $entityKeys, $entityName);
    $this->checkPostDelete($entityClass, $entityKeys, $entityName);
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   */
  protected function checkEntityInfo($entityClass): void {
    $info = $entityClass::getInfo();
    $this->assertNotEmpty($info['name']);
    $this->assertNotEmpty($info['title']);
    $this->assertNotEmpty($info['title_plural']);
    $this->assertNotEmpty($info['type']);
    $this->assertNotEmpty($info['description']);
    $this->assertIsArray($info['primary_key']);
    $this->assertMatchesRegularExpression(';^\d\.\d+$;', $info['since']);
    $this->assertContains($info['searchable'], ['primary', 'secondary', 'bridge', 'none']);
  }

  /**
   * @param string $entityName
   *
   * @throws \CRM_Core_Exception
   */
  protected function checkFields($entityName) {
    $fields = civicrm_api4($entityName, 'getFields', [
      'checkPermissions' => FALSE,
      'action' => 'create',
      'where' => [['type', '=', 'Field']],
    ])->indexBy('name');

    $idField = CoreUtil::getIdFieldName($entityName);

    $errMsg = sprintf('%s getfields is missing primary key field', $entityName);

    $this->assertArrayHasKey($idField, $fields, $errMsg);
    // Hmm, not true of every primary key... what about Afform.name?
    $this->assertEquals('Integer', $fields[$idField]['data_type']);

    // The underlying schema is not 100% consistent, but this is the standard in APIv4
    if (isset($fields['is_active'])) {
      $this->assertTrue($fields['is_active']['default_value']);
      $this->assertFalse($fields['is_active']['required']);
    }

    // Ensure that the getFields (FieldSpec) format is generally consistent.
    foreach ($fields as $field) {
      $isNotNull = function($v) {
        return $v !== NULL;
      };
      $class = empty($field['custom_field_id']) ? FieldSpec::class : CustomFieldSpec::class;
      $spec = (new $class($field['name'], $field['entity']))->loadArray($field, TRUE);
      $this->assertEquals(
        array_filter($field, $isNotNull),
        array_filter($spec->toArray(), $isNotNull)
      );
    }
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function checkActions($entityClass): array {
    $actions = $entityClass::getActions(FALSE)
      ->execute()
      ->indexBy('name');

    $this->assertNotEmpty($actions);
    return (array) $actions;
  }

  /**
   * @param string $entityName
   *
   * @return array
   */
  protected function checkCreation(string $entityName): array {
    $isReadOnly = $this->isReadOnly($entityName);

    $hookLog = [];
    $onValidate = function(ValidateValuesEvent $e) use (&$hookLog) {
      $hookLog[$e->getEntityName()][$e->getActionName()] = 1 + ($hookLog[$e->getEntityName()][$e->getActionName()] ?? 0);
    };
    \Civi::dispatcher()->addListener('civi.api4.validate', $onValidate);
    \Civi::dispatcher()->addListener('civi.api4.validate::' . $entityName, $onValidate);

    $this->setCheckAccessGrants(["{$entityName}::create" => TRUE]);
    $this->assertEquals(0, $this->checkAccessCounts["{$entityName}::create"]);

    $requiredParams = $this->getRequiredValuesToCreate($entityName);
    $createResult = civicrm_api4($entityName, 'create', [
      'values' => $requiredParams,
      'checkPermissions' => !$isReadOnly,
    ])->single();

    $primaryKeys = CoreUtil::getInfoItem($entityName, 'primary_key');

    foreach ($primaryKeys as $idField) {
      $this->assertArrayHasKey($idField, $createResult, "create missing $idField");
    }
    $id = $createResult[$primaryKeys[0]];
    $this->assertGreaterThanOrEqual(1, $id, "$entityName ID not positive");
    if (!$isReadOnly) {
      $this->assertEquals(1, $this->checkAccessCounts["{$entityName}::create"]);
    }
    $this->resetCheckAccess();

    $this->assertEquals(2, $hookLog[$entityName]['create']);
    \Civi::dispatcher()->removeListener('civi.api4.validate', $onValidate);
    \Civi::dispatcher()->removeListener('civi.api4.validate::' . $entityName, $onValidate);

    return array_intersect_key($createResult, array_flip($primaryKeys));
  }

  /**
   * @param string $entityName
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   */
  protected function checkCreationDenied(string $entityName, $entityClass): void {
    $this->setCheckAccessGrants(["{$entityName}::create" => FALSE]);
    $this->assertEquals(0, $this->checkAccessCounts["{$entityName}::create"]);

    $requiredParams = $this->getRequiredValuesToCreate($entityName);

    try {
      $entityClass::create()
        ->setValues($requiredParams)
        ->execute();
      $this->fail("{$entityClass}::create() should throw an authorization failure.");
    }
    catch (UnauthorizedException $e) {
      // OK, expected exception
    }
    if (!$this->isReadOnly($entityName)) {
      $this->assertEquals(1, $this->checkAccessCounts["{$entityName}::create"]);
    }
    $this->resetCheckAccess();
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   * @param array $entityKeys
   */
  protected function checkUpdateFailsFromCreate($entityClass, array $entityKeys): void {
    $exceptionThrown = '';
    try {
      $entityClass::create(FALSE)
        ->addValue('id', reset($entityKeys))
        ->execute();
    }
    catch (\CRM_Core_Exception $e) {
      $exceptionThrown = $e->getMessage();
    }
    $this->assertStringContainsString('id', $exceptionThrown);
  }

  /**
   * @param string $entityName
   * @param array $entityKeys
   */
  protected function checkGet(string $entityName, array $entityKeys): array {
    $getResult = civicrm_api4($entityName, 'get', [
      'checkPermissions' => FALSE,
      'where' => self::valsToClause($entityKeys),
    ])->single();
    foreach ($entityKeys as $key => $val) {
      $this->assertEquals($val, $getResult[$key]);
    }
    return $getResult;
  }

  /**
   * Ensure updating an entity does not alter it
   *
   * @param string $entityName
   * @param array $entityKeys
   * @param array $getResult
   * @throws \CRM_Core_Exception
   */
  protected function checkUpdate(string $entityName, array $entityKeys, array $getResult): void {
    civicrm_api4($entityName, 'update', [
      'checkPermissions' => FALSE,
      'where' => self::valsToClause($entityKeys),
      'values' => $entityKeys,
    ]);
    $getResult2 = civicrm_api4($entityName, 'get', [
      'checkPermissions' => FALSE,
      'where' => self::valsToClause($entityKeys),
    ]);
    $this->assertEquals($getResult, $getResult2->single());
  }

  /**
   * FIXME: Not working. `civi.api4.authorizeRecord` does not work on `get` actions.
   */
  protected function checkGetAllowed($entityClass, $id, $entityName) {
    $this->setCheckAccessGrants(["{$entityName}::get" => TRUE]);
    $getResult = $entityClass::get()
      ->addWhere('id', '=', $id)
      ->execute();

    $errMsg = sprintf('Failed to fetch a %s after creation', $entityName);
    $idField = CoreUtil::getIdFieldName($entityName);
    $this->assertEquals($id, $getResult->first()[$idField], $errMsg);
    $this->assertEquals(1, $getResult->count(), $errMsg);
    $this->resetCheckAccess();
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   * @param array $entityKeys
   * @param string $entityName
   */
  protected function checkGetCount(string $entityClass, array $entityKeys, string $entityName): void {
    $getResult = $entityClass::get(FALSE)
      ->setWhere(self::valsToClause($entityKeys))
      ->selectRowCount()
      ->execute();
    $errMsg = sprintf('%s getCount failed', $entityName);
    $this->assertEquals(1, $getResult->count(), $errMsg);

    $getResult = $entityClass::get(FALSE)
      ->selectRowCount()
      ->execute();
    $this->assertGreaterThanOrEqual(1, $getResult->count(), $errMsg);
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   */
  protected function checkDeleteWithNoId($entityClass) {
    try {
      $entityClass::delete()
        ->execute();
      $this->fail("$entityClass should require ID to delete.");
    }
    catch (\CRM_Core_Exception $e) {
      // OK
    }
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   */
  protected function checkWrongParamType($entityClass) {
    $exceptionThrown = '';
    try {
      $entityClass::get()
        ->setDebug('not a bool')
        ->execute();
    }
    catch (\CRM_Core_Exception $e) {
      $exceptionThrown = $e->getMessage();
    }
    $this->assertStringContainsString('debug', $exceptionThrown);
    $this->assertStringContainsString('type', $exceptionThrown);
  }

  /**
   * Delete an entity - while having a targeted grant (hook_civirm_checkAccess).
   *
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   * @param array $entityKeys
   * @param string $entityName
   */
  protected function checkDeletionAllowed($entityClass, $entityKeys, $entityName) {
    $this->setCheckAccessGrants(["{$entityName}::delete" => TRUE]);
    $this->assertEquals(0, $this->checkAccessCounts["{$entityName}::delete"]);
    $isReadOnly = $this->isReadOnly($entityName);

    $deleteAction = $entityClass::delete()
      ->setCheckPermissions(!$isReadOnly)
      ->setWhere(self::valsToClause($entityKeys));

    if (property_exists($deleteAction, 'useTrash')) {
      $deleteAction->setUseTrash(FALSE);
    }

    $log = $this->withPrePostLogging(function() use (&$deleteAction, &$deleteResult) {
      $deleteResult = $deleteAction->execute();
    });

    if (CoreUtil::isType($entityName, 'DAOEntity')) {
      // We should have emitted an event.
      $hookEntity = ($entityName === 'Contact') ? 'Individual' : $entityName;/* ooph */
      $this->assertContains("pre.{$hookEntity}.delete", $log, "$entityName should emit hook_civicrm_pre() for deletions");
      $this->assertContains("post.{$hookEntity}.delete", $log, "$entityName should emit hook_civicrm_post() for deletions");

      // should get back an array of deleted id
      $this->assertEquals([$entityKeys], (array) $deleteResult);
      if (!$isReadOnly) {
        $this->assertEquals(1, $this->checkAccessCounts["{$entityName}::delete"]);
      }
    }
    $this->resetCheckAccess();
  }

  /**
   * Attempt to delete an entity while having explicitly denied permission (hook_civicrm_checkAccess).
   *
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   * @param array $entityKeys
   * @param string $entityName
   */
  protected function checkDeletionDenied($entityClass, array $entityKeys, $entityName) {
    $this->setCheckAccessGrants(["{$entityName}::delete" => FALSE]);
    $this->assertEquals(0, $this->checkAccessCounts["{$entityName}::delete"]);

    try {
      $entityClass::delete()
        ->setWhere(self::valsToClause($entityKeys))
        ->execute();
      $this->fail("{$entityName}::delete should throw an authorization failure.");
    }
    catch (UnauthorizedException $e) {
      // OK
    }

    if (!$this->isReadOnly($entityName)) {
      $this->assertEquals(1, $this->checkAccessCounts["{$entityName}::delete"]);
    }
    $this->resetCheckAccess();
  }

  /**
   * @param \Civi\Api4\Generic\AbstractEntity|string $entityClass
   * @param array $entityKeys
   * @param string $entityName
   */
  protected function checkPostDelete($entityClass, array $entityKeys, $entityName) {
    $getDeletedResult = $entityClass::get(FALSE)
      ->setWhere(self::valsToClause($entityKeys))
      ->execute();

    $errMsg = sprintf('Entity "%s" was not deleted', $entityName);
    $this->assertEquals(0, count($getDeletedResult), $errMsg);
  }

  /**
   * @param array $names
   *   List of entity names.
   *   Ex: ['Foo', 'Bar']
   * @return array
   *   List of data-provider arguments, one for each entity-name.
   *   Ex: ['Foo' => ['Foo'], 'Bar' => ['Bar']]
   */
  protected function toDataProviderArray($names) {
    sort($names);

    $result = [];
    foreach ($names as $name) {
      $result[$name] = [$name];
    }
    return $result;
  }

  /**
   * @param string $entityName
   * @return bool
   */
  protected function isReadOnly($entityName) {
    return CoreUtil::isType($entityName, 'ReadOnlyEntity');
  }

  /**
   * Temporarily enable logging for `hook_civicrm_pre` and `hook_civicrm_post`.
   *
   * @param callable $callable
   *   Run this function. Create a log while running this function.
   * @return array
   *   Log; list of times the hooks were called.
   *   Ex: ['pre.Event.delete', 'post.Event.delete']
   */
  protected function withPrePostLogging($callable): array {
    $log = [];

    $listen = function ($e) use (&$log) {
      if ($e instanceof PreEvent) {
        $log[] = "pre.{$e->entity}.{$e->action}";
      }
      elseif ($e instanceof PostEvent) {
        $log[] = "post.{$e->entity}.{$e->action}";
      }
    };

    try {
      \Civi::dispatcher()->addListener('hook_civicrm_pre', $listen);
      \Civi::dispatcher()->addListener('hook_civicrm_post', $listen);
      $callable();
    }
    finally {
      \Civi::dispatcher()->removeListener('hook_civicrm_pre', $listen);
      \Civi::dispatcher()->removeListener('hook_civicrm_post', $listen);
    }

    return $log;
  }

  private static function valsToClause(array $vals) {
    $clause = [];
    foreach ($vals as $key => $val) {
      $clause[] = [$key, '=', $val];
    }
    return $clause;
  }

}
