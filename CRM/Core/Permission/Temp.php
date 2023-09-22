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

/**
 * This supplements the permissions of the CMS system, allowing us
 * to temporarily acknowledge permission grants for API keys.
 *
 * In normal usage, the class isn't even instantiated - it's only
 * used when processing certain API backends.
 */
class CRM_Core_Permission_Temp {
  public static $id = 0;

  /**
   * Array(int $grantId => array($perm))
   *
   * @var array
   */
  private $grants;

  /**
   * Array ($perm => 1);
   * @var array
   */
  private $idx;

  /**
   * Grant permissions temporarily.
   *
   * @param string|array $perms
   *   List of permissions to apply.
   * @return string|int
   *   A handle for the grant. Useful for revoking later on.
   */
  public function grant($perms) {
    $perms = (array) $perms;
    $id = self::$id++;
    $this->grants[$id] = $perms;
    $this->idx = $this->index($this->grants);
    return $id;
  }

  /**
   * Revoke a previously granted permission.
   *
   * @param string|int $id
   *   The handle previously returned by grant().
   */
  public function revoke($id) {
    unset($this->grants[$id]);
    $this->idx = $this->index($this->grants);
  }

  /**
   * Determine if a permission has been granted.
   *
   * @param string $perm
   *   The permission name (e.g. "view all contacts").
   * @return bool
   */
  public function check($perm) {
    return (isset($this->idx['administer CiviCRM']) || isset($this->idx[$perm]));
  }

  /**
   * Generate an optimized index of granted permissions.
   *
   * @param array $grants
   *   Array(string $permName).
   * @return array
   *   Array(string $permName => bool $granted).
   */
  protected function index($grants) {
    $idx = [];
    foreach ($grants as $grant) {
      foreach ($grant as $perm) {
        $idx[$perm] = 1;
      }
    }
    return $idx;
  }

}
