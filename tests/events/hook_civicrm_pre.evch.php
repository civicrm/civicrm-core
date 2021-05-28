<?php
return new class() extends \Civi\Test\EventCheck implements \Civi\Test\HookInterface {

  private $validOperations = '/^(create|delete|edit|update|view)$/';

  private $validEntity = '/^[A-Z][A-Za-z0-9]*$/';

  private $paramTypes = [
    // One would expect 'array' for all invocations, but keep a list of exceptions.
    '*' => ['NULL', 'array'],
    'Tag' => ['CRM_Core_DAO', 'array'],
  ];

  /**
   * Ensure that the hook data is always well-formed.
   *
   * @see \CRM_Utils_Hook::pre
   */
  public function hook_civicrm_pre($op, $objectName, $id, &$params = []) {
    $msg = "Non-conformant hook_civicrm_pre($op, $objectName, $id,...)";
    $this->assertRegExp($this->validOperations, $op, "$msg: Bad operation");
    $this->assertRegExp($this->validEntity, $objectName, "$msg: Bad object name");
    $this->assertTrue($id === NULL || is_numeric($id), "$msg: Bad object ID");
    $this->assertType($this->paramTypes[$objectName] ?? $this->paramTypes['*'], $params, "$msg: Bad param type");
  }

  public function on_hook_civicrm_pre(\Civi\Core\Event\PreEvent $e) {
    // Ensure that object parameter names are conformant.
    $this->hook_civicrm_pre($e->action, $e->entity, $e->id, $e->params);
  }

};
