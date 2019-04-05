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

/**
 * This supplements the permissions of the CMS system, allowing us
 * to temporarily acknowledge permission grants for API keys.
 *
 * In normal usage, the class isn't even instantiated - it's only
 * used when processing certain API backends.
 */
class CRM_Core_Permission_Temp {
  static $id = 0;

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
