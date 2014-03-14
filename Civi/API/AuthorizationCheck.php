<?php
namespace Civi\API;

class AuthorizationCheck {

  /**
   * @var string name of the class being checked
   */
  private $class;

  /**
   * @var array list of concrete instances
   */
  private $entities;

  /**
   * @var string
   * @see \Civi\API\Annotation\Permission::getStandardActions
   */
  private $action;

  /**
   * @var bool
   */
  private $allowed;

  /**
   * @param string $class the name of the class to which access is requested
   * @param string $action the action to performed
   * @param array $entities list of specific instances (if applicable)
   * @see \Civi\API\Annotation\Permission::getStandardActions
   */
  function __construct($class, $action, $entities = array()) {
    $this->class = $class;
    $this->action = $action;
    $this->entities = $entities;
    $this->allowed = FALSE;
  }

  /**
   * Mark as allowed
   */
  public function grant() {
    $this->allowed = TRUE;
  }

  /**
   * @param boolean $allowed
   */
  public function isAllowed() {
    return $this->allowed;
  }

  /**
   * @return string
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * @return string
   */
  public function getClass() {
    return $this->class;
  }

  /**
   * @return array
   */
  public function getEntities() {
    return $this->entities;
  }

}