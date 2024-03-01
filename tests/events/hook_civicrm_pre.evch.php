<?php

use Civi\Test\EventCheck;
use Civi\Test\HookInterface;

return new class() extends EventCheck implements HookInterface {

  protected $operations = ['view', 'create', 'edit', 'delete', 'update'];

  protected $objectNames;

  public function __construct() {
    $this->objectNames = array_merge(
      array_keys(CRM_Core_DAO_AllCoreTables::getEntities()),
      ['Organization', 'Individual', 'Household']
    );
  }

  /**
   * Ensure that the hook data is always well-formed.
   *
   * @see \CRM_Utils_Hook::config()
   */
  public function hook_civicrm_pre($op, $objectName, $id, &$params): void {
    $msg = 'Non-conforming hook_civicrm_pre(...)';

    static::assertContains($op, $this->operations, "$msg: op=$op");
    static::assertContains($objectName, $this->objectNames, "$msg: objectName=$objectName");
    $this->assertType('int|NULL', $id, $msg);
    $this->assertType('array', $params, $msg);
  }

};
