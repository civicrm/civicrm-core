<?php
return new class() extends \Civi\Test\EventCheck implements \Civi\Test\HookInterface {

  private $validOperations = '/^(create|delete|edit|update|view)$/';

  /**
   * Ensure that the hook data is always well-formed.
   *
   * @see \CRM_Utils_Hook::custom
   */
  public function hook_civicrm_custom($op, $groupID, $entityID, &$params) {
    $msg = "Non-conformant hook_civicrm_custom($op, $groupID, $entityID,...)";
    $this->assertRegExp($this->validOperations, $op, "$msg: Bad operation");
    $this->assertTrue(is_numeric($groupID), "$msg: Bad group ID");
    $this->assertTrue(is_numeric($entityID), "$msg: Bad entity ID");
    $this->assertTrue(is_array($params), "$msg: Bad params");
  }

  public function on_hook_civicrm_custom(\Civi\Core\Event\GenericHookEvent $e) {
    // Ensure that object parameter names are conformant.
    $this->hook_civicrm_custom($e->op, $e->groupID, $e->entityID, $e->params);
  }

};
