<?php
return new class() extends \Civi\Test\EventCheck implements \Civi\Test\HookInterface {

  private $validOperations = '/^(create|delete|edit|update|view)$/';

  private $validEntity = '/^[A-Z][A-Za-z0-9]*$/';

  private $paramTypes = [
    // One would expect 'CRM_Core_DAO' for all invocations, but keep a list of exceptions.
    '*' => ['NULL', 'CRM_Core_DAO'],
    'EntityTag' => 'array',
    'Profile' => 'array',
    'GroupContact' => ['array'],
  ];

  /**
   * Ensure that the hook data is always well-formed.
   *
   * @see \CRM_Utils_Hook::post
   */
  public function hook_civicrm_post($op, $objectName, $objectId, &$objectRef = NULL) {
    $msg = "Non-conformant hook_civicrm_post($op, $objectName, $objectId,...)";
    $this->assertRegExp($this->validOperations, $op, "$msg: Bad operation");
    $this->assertRegExp($this->validEntity, $objectName, "$msg: Bad object name");
    $this->assertTrue($objectId === NULL || is_numeric($objectId), "$msg: Bad object ID");
    $this->assertType($this->paramTypes[$objectName] ?? $this->paramTypes['*'], $objectRef, "$msg: Bad param type");
  }

  public function on_hook_civicrm_post(\Civi\Core\Event\PostEvent $e) {
    // Ensure that object parameter names are conformant.
    $this->hook_civicrm_post($e->action, $e->entity, $e->id, $e->object);
  }

};
