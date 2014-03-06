<?php
namespace Civi\API\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 *
 * Define coarse-grained permissions for standard actions (get/create/delete) on a given entity
 *
 * Usage: CiviAPI\Permission(get="view all contacts", create="edit all contacts", delete="delete contacts")
 */
class Permission {
  /**
   * Names of the standard actions
   */
  const GET = 'get', CREATE = 'create', DELETE = 'delete';

  /**
   * The name of the permission to require if none has been explicitly set
   */
  const DEFAULT_PERMISSION = 'administer CiviCRM';

  /**
   * @var array (string $action => string $permission)
   */
  private $permissions;

  /**
   * @return array (string $action)
   */
  public static function getStandardActions() {
    return array(self::GET, self::CREATE, self::DELETE);
  }

  /**
   * @param array $values (string $action => string $permission)
   */
  public function __construct(array $values) {
    $this->permissions = $values;
  }

  public function getPermission($action) {
    return isset($this->permissions[$action]) ? $this->permissions[$action] : self::DEFAULT_PERMISSION;
  }
}