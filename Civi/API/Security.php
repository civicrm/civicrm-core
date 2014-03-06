<?php
namespace Civi\API;

/**
 * 1. The permission model uses distinctive strings to identify the required permissions for any given action
 * 2. The REST API provides a generic, one-size-fits-all pipeline for processing actions.
 * 3. This class bridges the gap. The REST API calls this class to determine if a generic action (like
 * "create instance of \Civi\Core\Contact") is permitted, and this class determines which permission ("edit all contacts")
 * is required.
 */
class Security {

  /**
   * @var \Doctrine\Common\Annotations\Reader
   */
  private $annotationReader;

  /**
   * @var array (string $className => array(string $action => string $permission))
   */
  private $cache;

  /**
   * @var callable
   */
  private $permChecker;

  /**
   * @param \Doctrine\Common\Annotations\Reader $annotationReader
   * @param callable $permChecker a function which returns TRUE if the current user has a given permission
   */
  public function __construct($annotationReader, $permChecker = array('CRM_Core_Permission', 'check')) {
    $this->annotationReader = $annotationReader;
    $this->permChecker = $permChecker;
    $this->cache = array();
  }

  /**
   * Determine if user is authorized to perform an action on an API entity
   *
   * @code
   * if (!$security->check(new AuthorizationCheck('My\EntityName', 'create')) {
   *   fatal("Access denied");
   * }
   * @endcode
   *
   * @param AuthorizationCheck $check
   * @return boolean TRUE if allowed
   */
  public function check($check) {
    $perm = $this
      ->getAnnotation($check->getClass())
      ->getPermission($check->getAction());
    if (call_user_func($this->permChecker, $perm)) {
      $check->grant();
    }
    else {
      // TODO: use event/hook/callback for advanced permission checks
    }

    return $check->isAllowed();
  }

  /**
   * @param string $class
   * @return \Civi\API\Annotation\Permission|NULL
   */
  public function getAnnotation($class) {
    if (!array_key_exists($class, $this->cache)) {
      $this->cache[$class] = $this->annotationReader->getClassAnnotation(new \ReflectionClass($class), 'Civi\API\Annotation\Permission');
    }
    else {
      $this->cache[$class] = new \Civi\API\Annotation\Permission(array());
    }
    return $this->cache[$class];
  }
}