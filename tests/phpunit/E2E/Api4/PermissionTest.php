<?php

namespace E2E\Api4;

/**
 * Class PermissionTest
 * @package E2E\Api4
 * @group e2e
 */
class PermissionTest extends \CiviEndToEndTestCase {

  /**
   * The "Permission.get" API provides a combined list of permissions, including
   * Civi and CMS permissions. The underlying implementations are split among
   * different CMS integrations.
   *
   * This is a general sanity-check/well-formed-ness
   */
  public function testGet() {
    // If the CMS integration is working,t hen you should expect one of these "smoke-test" permissions to be present.
    $smokeTest = [
      'Backdrop' => 'Drupal:post comments',
      'Drupal' => 'Drupal:post comments',
      'Drupal8' => 'Drupal:post comments',
      'WordPress' => 'WordPress:list_users',
    ];

    $perms = \civicrm_api4('Permission', 'get')->indexBy('name');
    $this->assertTrue(isset($perms['administer CiviCRM']));

    $isOptionalString = function($arr, $key) {
      return !array_key_exists($key, $arr)
        || is_string($arr[$key])
        || $arr[$key] === NULL;
    };

    foreach ($perms as $permName => $perm) {
      $ser = json_encode($perm, JSON_UNESCAPED_SLASHES);
      $this->assertTrue(is_string($permName), 'Permission name should be a string');
      $this->assertTrue($isOptionalString($perm, 'title'), "Permission \"$permName\" should have string \"title\" ($ser)");
      $this->assertTrue($isOptionalString($perm, 'description'), "Permission \"$permName\" should have string \"description\" ($ser)");
      $this->assertTrue($isOptionalString($perm, 'group'), "Permission \"$permName\" should have string \"group\" ($ser)");
      $this->assertTrue(is_bool($perm['is_active']), "Permission \"$permName\" should have boolean \"is_active\" ($ser)");
      $this->assertTrue(is_bool($perm['is_synthetic']), "Permission \"$permName\" should have boolean \"is_synthetic\" ($ser)");
    }

    $groups = array_unique(\CRM_Utils_Array::collect('group', $perms->getArrayCopy()));
    $this->assertTrue(in_array('civicrm', $groups), 'There should be at least one permission in the "civicrm" group.');
    $this->assertTrue(in_array('cms', $groups), 'There should be at least one permission in the "cms" group.');
    $this->assertTrue(in_array('const', $groups), 'There should be at least one permission in the "const" group.');

    if (isset($smokeTest[CIVICRM_UF])) {
      $smokeTestPerm = $smokeTest[CIVICRM_UF];
      $this->assertTrue(isset($perms[$smokeTestPerm]));
      $this->assertTrue(is_bool($perm['is_active']), "Smoke-test permission \"$smokeTestPerm\" should have boolean \"is_active\"");
    }
  }

}
